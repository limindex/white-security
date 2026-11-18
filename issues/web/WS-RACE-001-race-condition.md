---
id: WS-RACE-001
title: Race Condition (경쟁 조건: 검사 시점과 사용 시점)
slug: race-condition
group: web
severity: high
site_only: false
depth: brief
languages: [laravel, django, nextjs]
maps:
  owasp2025: [A06]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V15.4.2, V2.3.3, V2.3.4]
  cwe: [CWE-362, CWE-367]
  kr_guide: ["시간 및 상태-1"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Django, Next.js·Prisma, 쿠폰·포인트·잔액·재고·1회 보상 기능]
severity_reason: "영향: 쿠폰·포인트 중복 사용, 재고보다 많은 판매, 한 번 결제로 여러 번 지급 · 악용: 같은 요청을 동시에 여러 번 보내는 것만으로 가능"
sources:
  - title: "PortSwigger Research, Smashing the state machine: the true potential of web race conditions"
    url: https://portswigger.net/research/smashing-the-state-machine
    year: 2023
  - title: "CWE-367: Time-of-check Time-of-use (TOCTOU) Race Condition"
    url: https://cwe.mitre.org/data/definitions/367.html
    year: 2025
  - title: "Django - QuerySet select_for_update()"
    url: https://docs.djangoproject.com/en/5.2/ref/models/querysets/#select-for-update
    year: 2026
  - title: "Laravel 12.x Database - Pessimistic Locking"
    url: https://laravel.com/docs/12.x/queries#pessimistic-locking
    year: 2026
---

## 요약

쿠폰 사용 여부·잔액·재고를 "먼저 확인하고 나중에 바꾸는" 코드에 같은 요청이 동시에 들어와, 모두 확인을 통과한 뒤 여러 번 차감·지급되는 문제(진단가이드 이름: 경쟁조건: 검사 시점과 사용 시점). TOCTOU(Time-of-check to time-of-use)라고도 부름.

## 대응 방법

```text
쿠폰·포인트·잔액·재고·가입 보상처럼 한 번만 되거나 개수가 정해진 처리를 찾아, 확인과 변경이 따로 실행되는 곳을 알려 줘.
확인과 변경을 조건부 UPDATE 한 문장이나 트랜잭션 + 행 잠금(SELECT ... FOR UPDATE)으로 묶고, 바뀐 행 수로 성공을 판단하게 바꿔 줘.
결제·지급 처리에는 멱등 키(같은 요청을 한 번만 처리하는 키)와 DB 고유 제약을 넣어 줘.
```

- 같은 쿠폰 사용 요청을 동시에 10번 보내도 한 번만 성공해야 한다.

## 점검 항목

- 확인과 변경은 조건부 `UPDATE ... WHERE` 한 문장이나 행 잠금 트랜잭션으로 묶어야 한다.
- 성공 여부는 바뀐 행 수로 판단해야 한다.
- 한 번만 생겨야 하는 기록에는 DB 고유 제약(unique)을 걸어야 한다.
- 결제·지급 요청은 멱등 키로 두 번째 처리를 막아야 한다.
- 잠금은 메모리 변수가 아니라 DB·Redis처럼 여러 서버가 함께 보는 곳에 둬야 한다.

## 탐지 규칙

- **찾는 곳**: `lockForUpdate(`, `select_for_update(`, `$transaction(`, 그 밖에 쿠폰·포인트·잔액·재고 값을 읽고 따로 저장하는 코드, increment, 결제 완료 콜백·웹훅 처리.
- **검사 1 — 확인 뒤 따로 변경**: 지적할 때: 쿠폰 사용 여부·잔액·재고를 읽어(`find`, `get`, `findUnique`) 조건을 확인한 뒤 별도 문장으로 저장(`save`, `update`)하고, 잠금·조건부 UPDATE가 없는 경우. / 지적하지 않을 때: `UPDATE ... WHERE used_at IS NULL`처럼 조건을 UPDATE 안에 넣고 바뀐 행 수를 확인하는 경우. 트랜잭션 안에서 `lockForUpdate`·`select_for_update`로 읽는 경우.
- **검사 2 — 중복 처리 방지 없음**: 지적할 때: 결제 완료 콜백·웹훅·보상 지급이 같은 주문으로 여러 번 처리될 수 있는 경우(고유 제약·멱등 키 없음). / 지적하지 않을 때: 주문 id 고유 제약이나 처리 기록 테이블로 두 번째 처리를 막는 경우.
- **등급**: 위험: 돈·포인트·쿠폰·재고에 확인 뒤 변경 패턴. / 주의: 가입 보상·투표·좋아요처럼 1회 제한 기능의 같은 패턴. / 참고: 메모리 변수 잠금(서버가 여러 대면 무효).
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-RACE-001 app/Http/Controllers/CouponController.php:22 — 쿠폰 used_at 을 읽고 따로 update, 동시 요청에 중복 사용 → whereNull('used_at')->update() 후 바뀐 행 수 확인`
- **고칠 방법**: 고친 뒤 같은 요청을 동시에 여러 번 보내는 테스트(스레드·`Promise.all`)로 한 번만 성공하는지 확인해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
public function redeem(Request $request, Coupon $coupon)
{
    if ($coupon->used_at !== null) abort(409);            // 확인
    $request->user()->increment('points', $coupon->points);
    $coupon->update(['used_at' => now()]);                 // 따로 변경: 동시 요청은 둘 다 통과
    return back();
}
```

✓ 안전 코드

```php
public function redeem(Request $request, Coupon $coupon)
{
    DB::transaction(function () use ($request, $coupon) {
        $used = Coupon::whereKey($coupon->id)->whereNull('used_at')
            ->update(['used_at' => now(), 'used_by' => $request->user()->id]); // 확인과 변경을 한 문장으로
        abort_if($used !== 1, 409, '이미 사용한 쿠폰입니다');                   // 바뀐 행 수로 판단
        $request->user()->increment('points', $coupon->points);              // 같은 트랜잭션
    });
    return back();
}
```

### django

✗ 취약 코드

```python
@require_POST
@login_required
def withdraw(request):
    amount = int(request.POST["amount"])
    acct = Account.objects.get(user=request.user)
    if acct.balance < amount:                # 확인
        return HttpResponse(status=400)
    acct.balance -= amount                   # 파이썬에서 계산해 저장: 동시 요청이 같은 잔액을 읽는다
    acct.save()
    return HttpResponse("ok")
```

✓ 안전 코드

```python
from django.db import transaction

@require_POST
@login_required
def withdraw(request):
    amount = int(request.POST["amount"])
    if not 1 <= amount <= 1_000_000:
        return HttpResponse(status=400)
    with transaction.atomic():
        acct = Account.objects.select_for_update().get(user=request.user)  # 행 잠금: 다른 요청은 기다린다
        if acct.balance < amount:
            return HttpResponse(status=400)
        acct.balance -= amount
        acct.save(update_fields=["balance"])
    return HttpResponse("ok")
```

### nextjs

✗ 취약 코드

```ts
"use server";
export async function claimSignupBonus() {
  const user = await requireUser();
  const u = await prisma.user.findUnique({ where: { id: user.id } });
  if (u?.bonusClaimed) throw new Error("이미 받은 보상입니다");   // 확인
  await prisma.user.update({                                      // 따로 변경
    where: { id: user.id },
    data: { bonusClaimed: true, points: { increment: 1000 } },
  });
}
```

✓ 안전 코드

```ts
"use server";
export async function claimSignupBonus() {
  const user = await requireUser();
  const { count } = await prisma.user.updateMany({
    where: { id: user.id, bonusClaimed: false },                  // 조건을 UPDATE 안에
    data: { bonusClaimed: true, points: { increment: 1000 } },
  });
  if (count !== 1) throw new Error("이미 받은 보상입니다");        // 바뀐 행 수로 판단
}
```
