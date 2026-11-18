<!-- generated from white-security issues; do not edit -->
# WS-PRV-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/billing/route.ts
export async function POST(req: Request) {
  const { cardNumber, expiry } = await req.json();
  await db.card.create({ data: { userId: session.userId, cardNumber, expiry } }); // 카드번호 평문 저장
  return Response.json({ ok: true });
}
```

✓ 안전 코드 (safe example)

```ts
// lib/field-crypto.ts — 서버 전용. FIELD_KEY 는 32바이트 Base64(NEXT_PUBLIC_ 금지)
import "server-only";
import { createCipheriv, createDecipheriv, randomBytes } from "node:crypto";

const key = Buffer.from(process.env.FIELD_KEY!, "base64");

export function encryptField(plain: string, aad: string): string {
  const iv = randomBytes(12);
  const c = createCipheriv("aes-256-gcm", key, iv, { authTagLength: 16 }).setAAD(Buffer.from(aad));
  const body = Buffer.concat([c.update(plain, "utf8"), c.final()]);
  return Buffer.concat([iv, c.getAuthTag(), body]).toString("base64");
}

export function decryptField(token: string, aad: string): string {
  const raw = Buffer.from(token, "base64");
  const d = createDecipheriv("aes-256-gcm", key, raw.subarray(0, 12), { authTagLength: 16 }).setAAD(Buffer.from(aad));
  d.setAuthTag(raw.subarray(12, 28));
  return Buffer.concat([d.update(raw.subarray(28)), d.final()]).toString("utf8"); // 변조되면 예외
}

// 카드번호는 받지 않는다: 결제대행사 결제창에서 받은 빌링키(토큰)만 저장
// await db.card.create({ data: { userId, billingKey: encryptField(billingKey, "card.billingKey"), last4 } });
```
