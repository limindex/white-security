<!-- generated from white-security issues; do not edit -->
# WS-FLOW-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
