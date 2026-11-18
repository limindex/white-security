<!-- generated from white-security issues; do not edit -->
# WS-CSRF-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/account/email/route.ts — 쿠키 세션으로 사용자 확인, 출처 검사 없음
export async function POST(req: Request) {
  const user = await getSessionUser();   // 쿠키는 다른 사이트가 보낸 요청에도 실린다
  if (!user) return new Response(null, { status: 401 });
  const { email } = await req.json();    // text/plain 폼으로 보낸 본문도 읽힌다
  await db.user.update({ where: { id: user.id }, data: { email } });
  return Response.json({ ok: true });
}
```

✓ 안전 코드 (safe example)

```ts
// app/api/account/email/route.ts
const APP_ORIGIN = process.env.APP_ORIGIN; // 예: https://app.example.com

export async function POST(req: Request) {
  const site = req.headers.get("sec-fetch-site");
  const origin = req.headers.get("origin");
  const sameSite = site ? site === "same-origin" : !!APP_ORIGIN && origin === APP_ORIGIN;
  if (!sameSite) return new Response(null, { status: 403 }); // 다른 사이트에서 온 요청 거절
  const user = await getSessionUser();
  if (!user) return new Response(null, { status: 401 });
  const { email } = await req.json();
  await db.user.update({ where: { id: user.id }, data: { email } });
  return Response.json({ ok: true });
}
```
