<!-- generated from white-security issues; do not edit -->
# WS-SMS-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/password-reset/sms/route.ts
export async function POST(req: Request) {
  const { userId, phone } = await req.json();               // 화면에 보여 준 번호를 요청에 다시 실어 보낸다
  const code = String(Math.floor(Math.random() * 1e6)).padStart(6, "0");  // 예측 가능한 난수
  await redis.set(`reset:${userId}`, code);                 // 만료 없음
  await sendSms(phone, `인증번호 ${code}`);                  // 번호를 바꾸면 공격자 휴대폰으로 간다
  return Response.json({ ok: true });
}
```

✓ 안전 코드 (safe example)

```ts
import { randomInt } from "node:crypto";

export async function POST(req: Request) {
  const { email } = await req.json();
  const user = await db.user.findUnique({ where: { email: String(email).toLowerCase() } });
  if (user?.phone) {
    const sent = await redis.incr(`reset-sent:${user.id}`);            // 계정별 하루 발송 한도(IP 한도는 미들웨어에서)
    if (sent === 1) await redis.expire(`reset-sent:${user.id}`, 86400);
    if (sent <= 5) {
      const code = randomInt(0, 1_000_000).toString().padStart(6, "0"); // 암호학적 난수
      await redis.set(`reset:${user.id}`, code, { ex: 300 });          // 5분(Upstash Redis 문법)
      await redis.set(`reset-tries:${user.id}`, 0, { ex: 300 });
      await sendSms(user.phone, `[비밀번호 재설정] 인증번호 ${code}`); // DB 에 등록된 번호로만
    }
  }
  return Response.json({ ok: true });                                  // 계정 유무와 관계없이 같은 응답
}
```
