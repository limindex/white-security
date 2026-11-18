---
id: WS-FLOW-001
title: Business Flow Abuse (가입·쿠폰·예약 자동화 남용)
slug: business-flow-abuse
group: auth
severity: medium
site_only: false
depth: brief
languages: [laravel, nextjs, django]
maps:
  owasp2025: [A06]
  api2023: [API6]
  llm2026: []
  agentic2026: []
  asvs5: [V2.3.1, V2.3.2, V2.4.1, V2.4.2]
  cwe: [CWE-799, CWE-837, CWE-841]
  kr_guide: []
  kr_ciip: [AU, PV]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js, Django, 가입 혜택·추천 보상·쿠폰·선착순 예약·한정 판매가 있는 서비스]
severity_reason: "영향: 혜택·포인트 대량 부정 수령, 좌석·재고 선점으로 실제 고객 피해 · 악용: 계정 대량 생성과 자동화 스크립트로 가능(요청 하나하나는 정상이라 개별 피해는 작게 보임)"
sources:
  - title: "OWASP API Security Top 10:2023 - API6 Unrestricted Access to Sensitive Business Flows"
    url: https://owasp.org/API-Security/editions/2023/en/0xa6-unrestricted-access-to-sensitive-business-flows/
    year: 2023
  - title: OWASP Automated Threats to Web Applications (OAT)
    url: https://owasp.org/www-project-automated-threats-to-web-applications/
    year: 2026
  - title: KISA 2026 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드 (자동화 공격·프로세스 검증 누락)
    url: https://www.kisa.or.kr/2060204/form?postSeq=22
    year: 2025
  - title: "Cloudflare Turnstile - Server-side validation"
    url: https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
    year: 2026
  - title: django-ratelimit documentation
    url: https://django-ratelimit.readthedocs.io/en/stable/
    year: 2026
---

## 요약

가입 혜택·추천 보상·쿠폰·선착순 예약·한정 판매처럼 돈이나 재고가 걸린 흐름을 자동화 도구와 대량 계정으로 반복 실행해 혜택을 쓸어 가거나 좌석·재고를 선점하는 문제. 요청 하나하나는 정상이라 입력 검증으로는 막히지 않아, 사람 단위 한도와 단계 순서 확인이 필요(OWASP API Security 2023 API6 Unrestricted Access to Sensitive Business Flows).

## 대응 방법

```text
가입 혜택·추천 보상·쿠폰 발급·선착순 예약·한정 판매처럼 돈이나 재고가 걸린 기능을 찾아 줘.
기능마다 사람(인증된 휴대폰)·계정·IP 단위 한도, 단계 순서 확인, 캡차가 있는지 표로 보여 주고 빠진 곳에 넣어 줘.
혜택은 DB 유니크 제약으로 사람당 한 번만 지급되게 하고, 가입 즉시 주지 말고 본인 확인 뒤에 주게 해 줘.
```

- 같은 휴대폰 번호로 계정을 여러 개 만들어도 가입 혜택은 한 번만 지급돼야 한다.
- 예약 확정 API를 앞 단계(좌석 선택·결제) 없이 바로 부르면 실패해야 한다.

## 점검 항목

- 혜택 지급은 사람 단위 키(인증된 휴대폰·결제 수단)에 DB 유니크 제약을 걸어 한 번만 되게 해야 한다.
- 가입·추천 보상은 본인 확인이나 첫 구매 같은 실제 행동 뒤에 줘야 한다.
- 쿠폰 발급·예약·한정 판매는 계정·IP 단위 한도와 서버에서 검증하는 캡차를 둬야 한다.
- 여러 단계 흐름은 서버가 앞 단계 완료를 확인해 순서를 건너뛰지 못하게 해야 한다.
- 한도에 걸린 요청과 짧은 시간의 대량 가입은 기록하고 알림을 받아야 한다.

## 탐지 규칙

- **찾는 곳**: `/coupons`, `/redeem`, `/referral`, `/signup`, `siteverify`, `@ratelimit(`, 그 밖에 가입 혜택·포인트 지급, 선착순 예약·한정 판매 확정 처리.
- **검사 1 — 사람 단위 한도 없음**: 지적할 때: 가입 혜택·추천 보상·쿠폰을 계정 단위로만 확인해, 계정을 새로 만들면 다시 받을 수 있는 경우. 지급 기록에 유니크 제약이 없는 경우. 자기 자신을 추천인으로 넣을 수 있는 경우. / 지적하지 않을 때: 인증된 휴대폰·DI·결제 수단 같은 사람 단위 키에 유니크 제약이 있는 경우.
- **검사 2 — 자동화 방어 없음**: 지적할 때: 가입·쿠폰 발급·예약 API에 횟수 제한·캡차가 없는 경우. 캡차 토큰을 서버에서 검증(`siteverify`)하지 않는 경우. / 지적하지 않을 때: 서버가 캡차 토큰을 검증하고 계정·IP 한도가 있는 경우.
- **검사 3 — 단계 건너뛰기**: 지적할 때: 확정·지급 API가 앞 단계 완료(좌석 홀드·결제·본인 확인)를 서버에서 확인하지 않는 경우. / 지적하지 않을 때: 서버 상태 값으로 순서와 유효시간을 확인하는 경우.
- **등급**: 위험: 계정만 바꾸면 혜택을 반복해서 받을 수 있는 경우, 확정 API로 앞 단계를 건너뛸 수 있는 경우. / 주의: 가입·쿠폰·예약에 횟수 제한·캡차가 없는 경우. / 참고: 대량 가입·한도 초과 기록과 알림이 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-FLOW-001 app/Http/Controllers/RegisterController.php:41 — 가입 즉시 5,000포인트 지급, 사람 단위 중복 확인 없음 → 휴대폰 인증 뒤 지급, 인증 번호 해시에 unique 제약`
- **검토 주의**: 같은 요청이 동시에 들어와 두 번 처리되는 문제는 WS-RACE-001, 로그인 시도 제한은 WS-AUTH-003에서 다루므로 여기서는 반복·자동화와 순서만 봐야 한다.
- **고칠 방법**: 혜택은 사람 단위 유니크 키로 지급 기록을 먼저 남기고, 확정 단계는 서버 상태로 앞 단계를 확인해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
public function store(Request $request)
{
    $user = User::create($request->validate(['email' => 'required|email|unique:users', 'name' => 'required']));
    $user->increment('points', 5000);                    // 가입만 하면 지급 → 계정을 계속 만든다
    if ($ref = User::where('referral_code', $request->input('ref'))->first()) {
        $ref->increment('points', 3000);                 // 자기 계정을 추천인으로 넣어 무한 적립
    }
    return redirect('/');
}
```

✓ 안전 코드

```php
// 마이그레이션: rewards 테이블의 reward_key 에 unique 인덱스
// 휴대폰 본인 인증이 끝난 뒤에만 호출(가입 즉시 지급하지 않음)
public function grantSignupReward(User $user): void
{
    $key = 'signup:'.hash_hmac('sha256', $user->verified_phone, config('app.key'));  // 사람 단위 키
    try {
        DB::transaction(function () use ($user, $key) {
            Reward::create(['user_id' => $user->id, 'reward_key' => $key, 'points' => 5000]);
            $user->increment('points', 5000);
        });
    } catch (UniqueConstraintViolationException) {
        // 같은 번호로 이미 받은 혜택 → 지급하지 않음
    }
}
// 추천 보상도 같은 방식: 추천인·가입자 번호가 다르고, 가입자가 첫 구매를 마친 뒤 지급
// 가입 라우트: ->middleware('throttle:signup') + 캡차 토큰 서버 검증
```

### nextjs

✗ 취약 코드

```ts
// app/api/coupons/claim/route.ts — 선착순 쿠폰
export async function POST(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return new Response(null, { status: 401 });
  // 캡차·한도 없음, 계정만 새로 만들면 또 받는다
  await db.couponClaim.create({ data: { userId: session.user.id, couponId: "WELCOME" } });
  return Response.json({ ok: true });
}
```

✓ 안전 코드

```ts
import { Prisma } from "@prisma/client";

export async function POST(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return new Response(null, { status: 401 });
  const { captchaToken } = await req.json();
  const v = await fetch("https://challenges.cloudflare.com/turnstile/v0/siteverify", {
    method: "POST",
    body: new URLSearchParams({ secret: process.env.TURNSTILE_SECRET!, response: String(captchaToken) }),
  }).then((r) => r.json());
  if (!v.success) return new Response("captcha", { status: 400 });          // 캡차는 서버에서 검증
  const user = await db.user.findUnique({ where: { id: session.user.id } });
  if (!user?.phoneHash) return new Response("본인 확인 필요", { status: 403 });  // 사람 단위 키
  try {
    await db.couponClaim.create({ data: { userId: user.id, phoneHash: user.phoneHash, couponId: "WELCOME" } });
  } catch (e) {                                                                // @@unique([couponId, phoneHash])
    if (e instanceof Prisma.PrismaClientKnownRequestError && e.code === "P2002") {
      return new Response("이미 받은 쿠폰", { status: 409 });
    }
    throw e;
  }
  return Response.json({ ok: true });
}
```

### django

✗ 취약 코드

```python
@login_required
@require_POST
def confirm_booking(request, slot_id):
    # 좌석 홀드·결제 단계를 건너뛰고 바로 불러도 확정된다
    Booking.objects.create(user=request.user, slot_id=slot_id, status="confirmed")
    return redirect("my-bookings")
```

✓ 안전 코드

```python
from django_ratelimit.decorators import ratelimit

@login_required
@require_POST
@ratelimit(key="user", rate="10/m", block=True)       # 계정당 분당 10회
def confirm_booking(request, hold_id):
    with transaction.atomic():
        hold = (Hold.objects.select_for_update()
                .filter(id=hold_id, user=request.user, status="paid", expires_at__gt=timezone.now())
                .first())
        if hold is None:                                # 앞 단계(홀드·결제)를 마친 것만
            return HttpResponseBadRequest("앞 단계를 먼저 완료해 주세요")
        Booking.objects.create(user=request.user, slot_id=hold.slot_id, status="confirmed")  # slot_id unique
        hold.status = "used"
        hold.save(update_fields=["status"])
    return redirect("my-bookings")
```
