---
id: WS-PAY-001
title: Payment Tampering (결제 금액 변조·웹훅 위조)
slug: payment-tampering
group: auth
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs, fastapi]
maps:
  owasp2025: [A06, A08]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V2.2.1, V2.2.2, V2.3.1, V2.3.3]
  cwe: [CWE-602, CWE-472, CWE-345, CWE-347]
  kr_guide: ["입력데이터 검증 및 표현-15", "보안기능-10"]
  kr_ciip: [PV]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js·Stripe, FastAPI, 결제대행(PG) 결제창·웹훅을 붙인 모든 쇼핑·예약 서비스]
severity_reason: "영향: 정가보다 싼 값으로 구매, 결제 없이 주문 확정·포인트 지급 · 악용: 요청의 금액 값 수정이나 가짜 결제 알림 전송만으로 가능"
sources:
  - title: KISA 2026 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드 (프로세스 검증 누락 — 가격 변조)
    url: https://www.kisa.or.kr/2060204/form?postSeq=22
    year: 2025
  - title: "OWASP Top 10:2025 - A08 Software or Data Integrity Failures"
    url: https://top10.owasp.org/2025/A08_2025-Software_or_Data_Integrity_Failures
    year: 2025
  - title: "Stripe Docs - Receive Stripe events in your webhook endpoint (서명 확인·중복 이벤트)"
    url: https://docs.stripe.com/webhooks
    year: 2026
  - title: "CWE-472 External Control of Assumed-Immutable Web Parameter"
    url: https://cwe.mitre.org/data/definitions/472.html
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

결제 금액을 브라우저가 보낸 값으로 정하거나, 결제창이 돌려준 "성공" 값과 서명 없는 결제 알림(웹훅)만 믿고 주문을 완료 처리해, 100원에 사거나 결제 없이 주문이 확정되는 문제(진단가이드 이름: 보안기능 결정에 사용되는 부적절한 입력값·부적절한 전자서명 확인). 결제대행(PG) 연동은 서버가 PG 승인·조회 API로 주문번호·금액·상태를 다시 맞춰 본 뒤에 완료 처리해야 함.

## 대응 방법

```text
결제 요청·결제 완료 콜백·웹훅·취소 API를 찾아 금액과 결제 상태를 어디서 정하는지 알려 줘.
금액은 서버의 주문 기록으로만 정하고, 완료 처리 전에 결제대행(PG) 승인·조회 API로 주문번호·금액·상태를 대조하게 바꿔 줘.
웹훅은 PG 문서대로 원문 본문으로 서명을 확인하고, 같은 결제 알림이 여러 번 와도 한 번만 처리되게 해 줘.
```

- 결제 요청의 금액을 개발자 도구로 100원으로 바꾸면 결제가 거절되거나 주문이 완료되지 않아야 한다.
- 서명 없는 가짜 웹훅을 보내면 400이 나오고 주문 상태가 바뀌지 않아야 한다.

## 점검 항목

- 결제 금액·상품·수량·할인은 서버의 주문·상품 데이터로 계산해야 한다.
- 결제 완료는 PG 승인·조회 결과의 주문번호·금액·통화·상태를 주문과 대조한 뒤 처리해야 한다.
- 웹훅은 원문 본문으로 서명을 확인해야 하고, 서명이 없거나 PG 문서가 권하면 조회 API로 다시 확인해야 한다.
- 같은 결제 알림은 한 번만 처리해야 한다(결제 ID 유니크 제약·상태 전이 검사).
- 취소·환불은 본인 주문인지, 누적 환불액이 결제액을 넘지 않는지 확인해야 한다.
- PG 비밀 키·웹훅 서명 키는 서버에만 두고, 테스트 키와 운영 키를 나눠야 한다.

## 탐지 규칙

- **찾는 곳**: `constructEvent(`, `construct_event(`, `stripe-signature`, `/payments/confirm`, `/webhooks/`, 그 밖에 결제창 호출 금액, 결제 완료 리다이렉트 처리, 주문 상태를 paid로 바꾸는 코드, 환불 API.
- **검사 1 — 클라이언트가 정한 금액**: 지적할 때: 결제 요청·주문 생성에서 금액·단가·할인·합계를 요청 값으로 쓰는 경우. / 지적하지 않을 때: 서버가 상품·쿠폰 데이터로 계산하고, 받은 값은 비교만 하는 경우.
- **검사 2 — 결제 결과 확인 없음**: 지적할 때: 결제창 리다이렉트의 `status=success`·금액 파라미터만 보고 주문을 완료하는 경우. PG 승인·조회 응답의 금액·주문번호를 주문과 대조하지 않는 경우. / 지적하지 않을 때: 서버가 PG 승인·조회 API를 직접 불러 금액·상태·주문번호를 대조하는 경우.
- **검사 3 — 웹훅 위조·중복 처리**: 지적할 때: 웹훅이 서명 확인 없이 상태를 바꾸는 경우. 파싱한 JSON으로 서명을 다시 계산하는 경우(원문 본문 필요). 서명을 일반 비교(`==`)로 맞춰 보는 경우. 같은 이벤트를 두 번 처리할 수 있는 경우. / 지적하지 않을 때: PG 라이브러리의 서명 검증 함수나 `hmac.compare_digest`·`hash_equals`로 원문을 확인하고, 결제 ID 유니크 제약이나 상태 조건부 갱신이 있는 경우.
- **등급**: 위험: 금액을 요청 값으로 정함, 결제 결과·웹훅을 확인 없이 믿음. / 주의: 중복 처리 방지 없음, 환불 금액 확인 없음, 상태 전이 검사 없음. / 참고: 테스트·운영 키 혼용, 제공되는 웹훅 발신 IP 제한 미사용.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PAY-001 app/api/webhooks/payment/route.ts:3 — 웹훅 본문을 서명 확인 없이 믿고 주문을 PAID 로 변경 → req.text() 원문으로 constructEvent 검증 후 금액·통화 대조, PENDING 상태일 때만 갱신`
- **검토 주의**: 결제대행사마다 서명 방식과 권장 확인 절차(서명 헤더, 조회 API 재확인)가 다르므로 그 PG의 공식 문서와 대조해야 하고, 문서를 찾지 못하면 "확인 필요"로 표시해야 한다.
- **고칠 방법**: 금액은 서버가 정하고, 완료는 서버가 PG에 직접 물어본 결과로만 처리해야 한다. 상태 변경은 "결제 대기일 때만" 조건을 붙여 한 번만 일어나게 해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// 결제창이 돌아오면 쿼리 값을 그대로 믿는다: /pay/return?order_id=..&status=success&amount=100
public function return(Request $request)
{
    $order = Order::findOrFail($request->order_id);
    if ($request->status === 'success') {
        $order->update(['status' => 'paid', 'paid_amount' => $request->amount]);
    }
    return redirect('/orders/'.$order->id);
}
```

✓ 안전 코드

```php
public function return(Request $request)
{
    $order = Order::where('id', $request->input('order_id'))->where('status', 'pending')->firstOrFail();
    // 결제대행(PG) 승인 API 를 서버가 직접 부른다(주소·인증·필드 이름은 PG 문서대로)
    $res = Http::withBasicAuth(config('services.pg.secret_key'), '')
        ->post(config('services.pg.confirm_url'), [
            'payment_id' => (string) $request->input('payment_id'),
            'order_id' => (string) $order->id,
            'amount' => $order->total_amount,              // 금액은 서버의 주문 기록
        ])->throw()->json();
    abort_unless(($res['order_id'] ?? null) === (string) $order->id
        && (int) ($res['amount'] ?? -1) === (int) $order->total_amount    // DB 값이 문자열로 올 수 있어 정수로 맞춘다
        && ($res['currency'] ?? '') === $order->currency && ($res['status'] ?? '') === 'paid', 409);
    $done = Order::where('id', $order->id)->where('status', 'pending')       // 대기 상태일 때만 → 한 번만
        ->update(['status' => 'paid', 'payment_id' => $res['payment_id']]);  // payment_id 는 unique 컬럼
    abort_if($done === 0, 409);
    return redirect('/orders/'.$order->id);
}
```

### nextjs

✗ 취약 코드

```ts
// app/api/webhooks/payment/route.ts
export async function POST(req: Request) {
  const event = await req.json();                     // 누가 보냈는지 확인하지 않는다
  if (event.type === "checkout.session.completed") {
    await db.order.update({
      where: { id: event.data.object.metadata.orderId },
      data: { status: "PAID" },
    });
  }
  return Response.json({ received: true });
}
```

✓ 안전 코드

```ts
import Stripe from "stripe";
const stripe = new Stripe(process.env.STRIPE_SECRET_KEY!);

export async function POST(req: Request) {
  const raw = await req.text();                       // 서명은 파싱 전 원문으로 확인
  let event: Stripe.Event;
  try {
    event = stripe.webhooks.constructEvent(raw, req.headers.get("stripe-signature") ?? "", process.env.STRIPE_WEBHOOK_SECRET!);
  } catch {
    return new Response("bad signature", { status: 400 });
  }
  if (event.type === "checkout.session.completed") {
    const s = event.data.object;
    const order = await db.order.findUnique({ where: { id: s.metadata?.orderId ?? "" } });
    if (order && s.payment_status === "paid" && s.amount_total === order.totalAmount && s.currency === order.currency) {
      await db.order.updateMany({                     // 대기 상태일 때만 → 같은 알림이 다시 와도 한 번만
        where: { id: order.id, status: "PENDING" },
        data: { status: "PAID", paymentRef: s.id },
      });
    }
  }
  return Response.json({ received: true });
}
```

### fastapi

✗ 취약 코드

```python
@app.post("/webhooks/pg")
async def pg_webhook(payload: dict):                   # 서명 확인 없음
    if payload["status"] == "paid":
        mark_paid(payload["order_id"], payload["amount"])  # 보낸 금액·상태를 그대로 믿는다
    return {"ok": True}
```

✓ 안전 코드

```python
import hashlib, hmac, json, time

@app.post("/webhooks/pg")
async def pg_webhook(request: Request):
    raw = await request.body()                         # 파싱 전 원문으로 서명 확인
    ts = request.headers.get("x-pg-timestamp", "")     # 헤더 이름·서명 방식은 PG 문서대로
    sig = request.headers.get("x-pg-signature", "")
    expected = hmac.new(PG_WEBHOOK_SECRET, ts.encode() + b"." + raw, hashlib.sha256).hexdigest()  # 키는 bytes(환경 변수 .encode())
    if not ts.isdigit() or abs(time.time() - int(ts)) > 300 or not hmac.compare_digest(expected, sig):
        raise HTTPException(400)                       # 위조·오래된 알림 거절
    event = json.loads(raw)
    payment = pg_client.get_payment(event["payment_id"])  # 알림 내용 대신 조회 API 로 다시 확인
    order = get_order(payment["order_id"])
    if order is None:
        return {"ok": True}                            # 모르는 주문은 처리하지 않음(기록만)
    if (payment["status"] == "paid" and payment["amount"] == order.total_amount
            and payment["currency"] == order.currency):
        mark_paid_once(order.id, payment["payment_id"])   # 대기 상태일 때만 갱신 + payment_id 유니크
    return {"ok": True}
```
