<!-- generated from white-security issues; do not edit -->
# WS-AUTH-005 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/signup/route.ts — 전화 인증은 브라우저에서 끝내고 결과만 받는다
export async function POST(req: Request) {
  const { phone, phoneVerified, name } = await req.json();
  if (!phoneVerified) return new Response("인증 필요", { status: 400 });  // 보낸 true 를 그대로 믿음
  await db.user.create({ data: { phone, name, phoneVerified: true } });
  return Response.json({ ok: true });
}
```

✓ 안전 코드 (safe example)

```ts
import { getAuth } from "firebase-admin/auth";

export async function POST(req: Request) {
  const { idToken, name } = await req.json();
  // 서명·만료·폐기 여부를 서버에서 확인(브라우저의 confirmationResult.confirm 뒤 받은 ID 토큰)
  const t = await getAuth().verifyIdToken(String(idToken), true).catch(() => null);
  if (!t?.phone_number) return new Response("인증 필요", { status: 401 });
  if (Date.now() / 1000 - t.auth_time > 300) return new Response("다시 인증해 주세요", { status: 401 });
  await db.user.create({
    data: { phone: t.phone_number, firebaseUid: t.uid, name: String(name).slice(0, 30) },  // 번호는 토큰에서
  });                                                    // firebaseUid 는 unique 제약으로 재사용 차단
  return Response.json({ ok: true });
}
```
