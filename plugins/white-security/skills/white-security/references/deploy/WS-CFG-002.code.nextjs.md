<!-- generated from white-security issues; do not edit -->
# WS-CFG-002 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/me/route.ts — 요청 Origin 을 그대로 허용
export async function GET(req: Request) {
  const origin = req.headers.get("origin") ?? "*";
  const user = await getCurrentUser();
  return Response.json(user, {
    headers: {
      "Access-Control-Allow-Origin": origin,
      "Access-Control-Allow-Credentials": "true",
    },
  });
}
```

✓ 안전 코드 (safe example)

```ts
// lib/cors.ts
const ALLOWED = new Set((process.env.CORS_ALLOWED_ORIGINS ?? "").split(",").filter(Boolean));

export function corsHeaders(req: Request): Record<string, string> {
  const origin = req.headers.get("origin");
  const h: Record<string, string> = { Vary: "Origin" };
  if (origin && ALLOWED.has(origin)) {               // 정확히 같은 주소만
    h["Access-Control-Allow-Origin"] = origin;
    h["Access-Control-Allow-Credentials"] = "true";
  }
  return h;
}

// app/api/me/route.ts — CORS 와 별개로 서버에서 로그인 확인
export async function GET(req: Request) {
  const user = await requireUser();                  // 앱의 로그인 확인 함수
  return Response.json({ id: user.id, name: user.name }, { headers: corsHeaders(req) });
}
export function OPTIONS(req: Request) {              // 사전 요청(preflight)
  return new Response(null, { status: 204, headers: { ...corsHeaders(req),
    "Access-Control-Allow-Methods": "GET", "Access-Control-Allow-Headers": "Content-Type" } });
}
```
