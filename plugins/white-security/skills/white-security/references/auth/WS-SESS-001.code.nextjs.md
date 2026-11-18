<!-- generated from white-security issues; do not edit -->
# WS-SESS-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// lib/session.ts
import { decodeJwt } from "jose";
import { cookies } from "next/headers";

export async function getUser() {
  const token = (await cookies()).get("token")?.value;
  return token ? decodeJwt(token) : null;   // 서명 확인 없이 내용만 읽는다 → 직접 만든 토큰도 통과
}
// 로그인 화면: localStorage.setItem("token", token)  ← 스크립트 한 줄이면 토큰이 샌다
```

✓ 안전 코드 (safe example)

```ts
import { jwtVerify, SignJWT } from "jose";
import { cookies } from "next/headers";

const key = new TextEncoder().encode(process.env.SESSION_SECRET);  // 32바이트 이상 무작위 값
const OPT = { issuer: "example-web", audience: "example-web" };

export async function createSession(userId: string) {
  const token = await new SignJWT({ sub: userId }).setProtectedHeader({ alg: "HS256" })
    .setIssuer(OPT.issuer).setAudience(OPT.audience).setIssuedAt().setExpirationTime("2h").sign(key);
  (await cookies()).set("session", token, { httpOnly: true, secure: true, sameSite: "lax", path: "/", maxAge: 7200 });
}

export async function getUser() {
  const token = (await cookies()).get("session")?.value;
  if (!token) return null;
  try {
    const { payload } = await jwtVerify(token, key, { ...OPT, algorithms: ["HS256"], requiredClaims: ["exp", "sub"] });
    return payload;
  } catch {
    return null;                              // 서명·알고리즘·만료·발급자·대상 중 하나라도 틀리면 거절
  }
}
```
