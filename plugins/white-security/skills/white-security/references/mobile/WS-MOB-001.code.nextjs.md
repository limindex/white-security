<!-- generated from white-security issues; do not edit -->
# WS-MOB-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/ask/route.ts — 키는 서버로 옮겼지만 누구나 아무 요청이나 대신 보낼 수 있음
export async function POST(req: Request) {
  const body = await req.text();                       // 모델·길이·내용을 앱이 정함
  const r = await fetch("https://api.openai.com/v1/responses", {
    method: "POST",
    headers: { Authorization: `Bearer ${process.env.OPENAI_API_KEY}`, "Content-Type": "application/json" },
    body,
  });
  return new Response(r.body, { status: r.status });   // 로그인·한도 없음
}
```

✓ 안전 코드 (safe example)

```ts
// app/api/ask/route.ts
import OpenAI from "openai";
import { verifyAccessToken } from "@/lib/auth";        // 우리 로그인 토큰 확인(WS-AUTH-002)
import { rateLimit } from "@/lib/rate-limit";          // 사용자별 한도(WS-LLM-004)

const openai = new OpenAI({ apiKey: process.env.OPENAI_API_KEY }); // 서버 환경 변수, NEXT_PUBLIC_ 금지

export async function POST(req: Request) {
  const user = await verifyAccessToken(req.headers.get("authorization"));
  if (!user) return new Response(null, { status: 401 });
  if (!(await rateLimit(`ask:${user.id}`, { max: 20, windowSec: 3600 }))) return new Response(null, { status: 429 });

  const { question } = await req.json().catch(() => ({}));
  if (typeof question !== "string" || question.length > 2000) return new Response(null, { status: 400 });

  const r = await openai.responses.create({
    model: "gpt-4.1-mini",          // 모델·출력 길이는 서버가 고정
    input: question,
    max_output_tokens: 800,
  });
  return Response.json({ answer: r.output_text });
}
```
