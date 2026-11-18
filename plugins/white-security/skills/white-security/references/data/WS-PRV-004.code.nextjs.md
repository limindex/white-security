<!-- generated from white-security issues; do not edit -->
# WS-PRV-004 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/signup/route.ts — 폼의 <input name="jumin"> 을 그대로 저장
export async function POST(req: Request) {
  const { name, email, jumin } = await req.json();
  const user = await db.user.create({ data: { name, email, jumin } });   // 평문
  return Response.redirect(new URL(`/welcome?jumin=${jumin}`, req.url)); // URL 에도 남음
}
```

✓ 안전 코드 (safe example)

```ts
// app/api/signup/route.ts — 주민등록번호는 받지 않음
import { z } from "zod";

const Signup = z.object({
  name: z.string().min(1).max(50),
  email: z.string().email(),
  birthDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
}).strict();                                 // 정의하지 않은 필드(jumin 등)가 오면 거절

export async function POST(req: Request) {
  const parsed = Signup.safeParse(await req.json());
  if (!parsed.success) return new Response(null, { status: 400 });
  const user = await db.user.create({ data: parsed.data });
  return Response.json({ id: user.id });
}
// 성인·본인 확인이 꼭 필요하면 본인확인 서비스 결과를 서버에서 검증해(WS-AUTH-005) 생년월일만 저장
```
