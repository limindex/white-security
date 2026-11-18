<!-- generated from white-security issues; do not edit -->
# WS-CFG-003 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```js
// next.config.js — 응답 헤더 설정 없음
module.exports = {
  reactStrictMode: true,
};
```

✓ 안전 코드 (safe example)

```ts
// proxy.ts (15 이하는 middleware.ts) — 공식 가이드의 nonce 방식: 요청마다 새 nonce
import { NextRequest, NextResponse } from "next/server";

export function proxy(request: NextRequest) {
  const nonce = Buffer.from(crypto.randomUUID()).toString("base64");
  const isDev = process.env.NODE_ENV === "development";
  const csp = [
    "default-src 'self'",
    `script-src 'self' 'nonce-${nonce}' 'strict-dynamic'${isDev ? " 'unsafe-eval'" : ""}`,
    `style-src 'self' 'nonce-${nonce}'`,
    "img-src 'self' blob: data:",
    "object-src 'none'",
    "base-uri 'none'",                      // 공식 예시는 'self', ASVS 3.4.3 은 'none'
    "form-action 'self'",
    "frame-ancestors 'none'",
    "upgrade-insecure-requests",
  ].join("; ");
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set("x-nonce", nonce);
  requestHeaders.set("Content-Security-Policy", csp);  // Next.js 가 여기서 nonce 를 읽어 스크립트에 붙인다
  const response = NextResponse.next({ request: { headers: requestHeaders } });
  response.headers.set("Content-Security-Policy", csp);
  return response;
}
export const config = {
  matcher: [{ source: "/((?!api|_next/static|_next/image|favicon.ico).*)",
    missing: [{ type: "header", key: "next-router-prefetch" }, { type: "header", key: "purpose", value: "prefetch" }] }],
};
// nonce 는 동적 렌더링에서만 붙는다(정적 생성·ISR 페이지는 await connection() 등으로 동적 렌더링).
// 동적 렌더링을 못 하는 정적 사이트는 nonce 없는 'unsafe-inline' 정책(인라인 XSS 는 못 막음)이나 실험 기능 SRI 를 쓴다.

// next.config.ts — 나머지 헤더는 모든 응답에
const nextConfig = {
  async headers() {
    return [{ source: "/(.*)", headers: [
      // includeSubDomains 는 모든 하위 도메인이 HTTPS 일 때만
      { key: "Strict-Transport-Security", value: "max-age=31536000; includeSubDomains" },
      { key: "X-Content-Type-Options", value: "nosniff" },
      { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
    ] }];
  },
};
export default nextConfig;
```
