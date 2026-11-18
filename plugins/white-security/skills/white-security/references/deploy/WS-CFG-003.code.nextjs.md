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

```js
// next.config.js — 공식 가이드의 nonce 없는 CSP + 나머지 보안 헤더
const isDev = process.env.NODE_ENV === "development";
const csp = [
  "default-src 'self'",
  `script-src 'self' 'unsafe-inline'${isDev ? " 'unsafe-eval'" : ""}`,
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' blob: data:",
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self'",
  "frame-ancestors 'none'",
  "upgrade-insecure-requests",
].join("; ");

module.exports = {
  async headers() {
    return [{
      source: "/(.*)",
      headers: [
        { key: "Content-Security-Policy", value: csp },
        { key: "Strict-Transport-Security", value: "max-age=31536000; includeSubDomains" },
        { key: "X-Content-Type-Options", value: "nosniff" },
        { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
      ],
    }];
  },
};
// 'unsafe-inline' 없이 막으려면 공식 가이드의 nonce 방식(proxy.ts, 15 이하 middleware.ts)을 쓴다
```
