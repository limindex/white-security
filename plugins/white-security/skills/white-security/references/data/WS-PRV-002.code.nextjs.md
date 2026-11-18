<!-- generated from white-security issues; do not edit -->
# WS-PRV-002 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/orders/route.ts — /api/orders?phone=010...&orderNo=... : 번호가 접근 로그·Referer 에 남음
export async function GET(req: Request) {
  const q = new URL(req.url).searchParams;
  console.log("order lookup", { phone: q.get("phone"), headers: Object.fromEntries(req.headers) }); // 쿠키까지
  return Response.json(await findGuestOrder(q.get("orderNo"), q.get("phone")));
}

// instrumentation-client.ts
Sentry.init({ dsn: process.env.NEXT_PUBLIC_SENTRY_DSN, sendDefaultPii: true,
  integrations: [Sentry.replayIntegration({ maskAllText: false })] });   // 화면 입력값까지 녹화
```

✓ 안전 코드 (safe example)

```ts
// lib/log.ts
import pino from "pino";
export const log = pino({
  redact: { paths: ["phone", "email", "*.phone", "*.email", "req.headers.cookie", "req.headers.authorization"], censor: "[가림]" },
});

// app/api/orders/lookup/route.ts — 개인정보는 POST 본문으로 받고 로그엔 식별자만
export async function POST(req: Request) {
  const { orderNo, phone } = await req.json();
  const order = await findGuestOrder(orderNo, phone);
  log.info({ orderId: order?.id ?? null }, "guest order lookup");
  return order ? Response.json({ id: order.id, status: order.status }) : new Response(null, { status: 404 });
}

// instrumentation-client.ts — sendDefaultPii 는 켜지 않고(기본 꺼짐), 리플레이 가림은 기본값 유지
Sentry.init({ dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  integrations: [Sentry.replayIntegration({ maskAllText: true, blockAllMedia: true })] });
```
