<!-- generated from white-security issues; do not edit -->
# WS-CRY-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// lib/crypto.ts
import crypto from "node:crypto";

const KEY = Buffer.from(process.env.DATA_KEY_HEX!, "hex");
const IV = Buffer.alloc(16, 0);                                       // 고정 IV

export function encrypt(text: string) {
  const c = crypto.createCipheriv("aes-256-cbc", KEY, IV);            // 변조 검사 없는 CBC
  return c.update(text, "utf8", "base64") + c.final("base64");
}
export const inviteCode = () => Math.random().toString(36).slice(2);  // 예측 가능

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";   // 사설 인증서 오류를 없애려고 전체 TLS 검증을 끔
```

✓ 안전 코드 (safe example)

```ts
// lib/crypto.ts
import "server-only";
import crypto from "node:crypto";

const KEY = Buffer.from(process.env.DATA_KEY_HEX ?? "", "hex");
if (KEY.length !== 32) throw new Error("DATA_KEY_HEX 는 32바이트(16진수 64자리)여야 합니다");

export function encrypt(text: string): string {
  const iv = crypto.randomBytes(12);                                  // 매번 새 nonce
  const c = crypto.createCipheriv("aes-256-gcm", KEY, iv);
  const body = Buffer.concat([c.update(text, "utf8"), c.final()]);
  return Buffer.concat([iv, c.getAuthTag(), body]).toString("base64");
}
export function decrypt(b64: string): string {
  const buf = Buffer.from(b64, "base64");
  if (buf.length < 28) throw new Error("암호문이 너무 짧습니다");
  const d = crypto.createDecipheriv("aes-256-gcm", KEY, buf.subarray(0, 12), { authTagLength: 16 });
  d.setAuthTag(buf.subarray(12, 28));                                 // 변조되면 final() 에서 오류
  return Buffer.concat([d.update(buf.subarray(28)), d.final()]).toString("utf8");
}
export const inviteCode = () => crypto.randomBytes(16).toString("base64url");   // 128비트
// 사설 CA 는 NODE_EXTRA_CA_CERTS=/path/ca.pem 으로 그 CA 만 더하고 검증은 켜 둔다
```
