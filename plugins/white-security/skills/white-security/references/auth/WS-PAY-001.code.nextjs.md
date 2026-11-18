<!-- generated from white-security issues; do not edit -->
# WS-PAY-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
