---
id: WS-CFG-003
title: Missing Security Headers (CSP·HSTS 등 보안 헤더 누락)
slug: missing-security-headers
group: deploy
severity: medium
site_only: false
depth: brief
languages: [nextjs, django, nginx]
maps:
  owasp2025: [A02]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  asvs5: [V3.4.1, V3.4.3, V3.4.4, V3.4.5, V3.4.6, V12.2.1]
  cwe: [CWE-1021, CWE-693, CWE-319]
  kr_guide: []
  kr_ciip: [SN]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Next.js, Django, Nginx, Laravel·FastAPI 등 HTML을 내보내는 모든 웹 앱]
severity_reason: "영향: XSS 피해 확대, 첫 HTTP 접속 가로채기, 투명 iframe 클릭 유도 · 악용: 다른 약점과 함께 쓰일 때 피해가 커짐"
sources:
  - title: "OWASP ASVS 5.0 - V3.4 Browser Security Mechanism Headers"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: OWASP HTTP Headers Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html
    year: 2026
  - title: "Next.js - Content Security Policy (Without Nonces)"
    url: https://nextjs.org/docs/app/guides/content-security-policy
    year: 2026
  - title: "Django 6.0 - Content Security Policy·Settings (SECURE_HSTS_SECONDS 등 기본값)"
    url: https://docs.djangoproject.com/en/6.0/ref/csp/
    year: 2026
  - title: "nginx - ngx_http_headers_module (add_header 상속·always)"
    url: https://nginx.org/en/docs/http/ngx_http_headers_module.html
    year: 2026
  - title: "RFC 6797 - HTTP Strict Transport Security (HSTS)"
    url: https://www.rfc-editor.org/rfc/rfc6797
    year: 2012
---

## 요약

응답에 CSP(Content-Security-Policy, 불러올 수 있는 스크립트 출처 제한)·HSTS(Strict-Transport-Security, 브라우저가 HTTPS로만 접속하게 함)·`X-Content-Type-Options: nosniff`·`frame-ancestors`(남의 사이트가 내 화면을 iframe으로 싸는 것 금지) 같은 보안 헤더가 없어, XSS 피해가 커지고 HTTP 가로채기·클릭재킹(보이지 않게 덮은 내 화면을 누르게 하는 공격)에 무방비인 문제. 헤더는 코드 버그를 고치지 않는 보조 방어.

## 대응 방법

```text
이 앱의 응답에 CSP·HSTS·X-Content-Type-Options·frame-ancestors(또는 X-Frame-Options)·Referrer-Policy 가 붙는지 설정 파일 기준으로 확인해 줘.
빠진 헤더를 앱 설정(next.config headers(), Django SecurityMiddleware·SECURE_CSP)이나 웹 서버 설정 한곳에 모아 넣어 줘.
CSP 는 먼저 Report-Only 로 넣어 깨지는 곳을 확인한 뒤 차단 모드로 바꿔 줘.
```

- 브라우저 개발자 도구 Network 탭의 문서 응답 헤더에 위 헤더가 모두 보여야 하고, 404·500 응답에도 붙어야 한다.
- `http://` 주소로 접속하면 `https://`로 넘어가야 한다.

## 점검 항목

- HTML 응답에는 CSP를 넣고 `object-src 'none'`·`base-uri`·`frame-ancestors`를 정해야 한다.
- HTTPS 사이트는 HSTS `max-age`를 1년(31536000) 이상으로 해야 한다.
- 모든 응답에 `X-Content-Type-Options: nosniff`를 붙여야 한다.
- `Referrer-Policy`로 주소 속 정보가 외부로 새지 않게 해야 한다.
- HTTP 요청은 HTTPS로 넘기고, 개인정보는 HTTPS로만 주고받아야 한다.
- nginx는 `add_header`에 `always`를 붙이고, 하위 블록에서도 다시 넣어야 한다.

## 탐지 규칙

- **찾는 곳**: `Content-Security-Policy`, `Strict-Transport-Security`, `SECURE_HSTS_SECONDS`, `async headers()`, `add_header`, 그 밖에 Django SECURE_CSP, helmet 설정, 응답 헤더 미들웨어, vercel.json·netlify.toml 의 headers, X-Frame-Options.
- **검사 1 — 빠진 헤더**: 지적할 때: HTML을 내보내는 앱의 설정(next.config `headers()`, Django `MIDDLEWARE`·`SECURE_*`, nginx `add_header`, Laravel·Express 미들웨어) 어디에도 CSP·HSTS·nosniff·`frame-ancestors`(또는 `X-Frame-Options`)가 없는 경우. nginx에서 `add_header`가 있는 하위 `location`이 위쪽 보안 헤더를 다시 넣지 않는 경우, `always`가 없어 오류 응답에서 빠지는 경우. Django에서 `SecurityMiddleware`를 뺀 경우. / 지적하지 않을 때: 앞단(CDN·호스팅 설정 파일)에서 같은 헤더를 붙이는 것이 보이는 경우. JSON만 내보내는 API에 CSP가 없는 경우.
- **검사 2 — 효과 없는 값**: 지적할 때: CSP에 `default-src *`·`script-src *`·`data:` 스크립트, 운영에도 켜진 `'unsafe-eval'`, HSTS `max-age`가 1년 미만이거나 0인 경우. / 지적하지 않을 때: Next.js 공식 예시처럼 nonce 없이 `'unsafe-inline'`만 있는 경우(참고로만 표시하고 nonce 방식을 안내).
- **등급**: 주의: HTML 앱에 CSP·HSTS가 모두 없음, 로그인·결제 화면에 `frame-ancestors`·`X-Frame-Options` 없음, HTTP 접속을 HTTPS로 넘기지 않음. / 참고: nosniff·Referrer-Policy 없음, `'unsafe-inline'`, nginx `always` 누락, HSTS 1년 미만.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[주의] WS-CFG-003 next.config.js:1 — 응답 헤더 설정이 없어 CSP·HSTS·frame-ancestors 없이 배포됨 → headers() 에 보안 헤더 묶음 추가`
- **검토 주의**: HSTS의 `includeSubDomains`·`preload`는 HTTP만 쓰는 하위 도메인을 막고 되돌리기 어려우므로, 모든 하위 도메인이 HTTPS인지 사용자에게 확인을 받고 권해야 한다.
- **고칠 방법**: 보안 헤더를 앱 설정이나 웹 서버 한곳에 묶어 모든 응답(오류 응답 포함)에 붙여야 하고, CSP는 Report-Only로 시험한 뒤 차단 모드로 바꿔야 한다.

## 코드 예시

### nextjs

✗ 취약 코드

```js
// next.config.js — 응답 헤더 설정 없음
module.exports = {
  reactStrictMode: true,
};
```

✓ 안전 코드

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

### django

✗ 취약 코드

```python
# settings.py — SecurityMiddleware 를 빼고, HSTS·CSP 설정 없음
MIDDLEWARE = [
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
]
```

✓ 안전 코드

```python
# settings.py (Django 6.0 이상)
from django.utils.csp import CSP

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",          # HSTS·nosniff·Referrer-Policy·COOP
    "django.middleware.csp.ContentSecurityPolicyMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware", # X-Frame-Options: DENY(기본값)
]
SECURE_HSTS_SECONDS = 31536000        # 기본값 0(꺼짐). HTTPS 가 되는 것을 확인한 뒤 켠다
SECURE_HSTS_INCLUDE_SUBDOMAINS = True
SECURE_SSL_REDIRECT = True            # 프록시 뒤라면 SECURE_PROXY_SSL_HEADER 도 맞춘다
SECURE_CSP = {
    "default-src": [CSP.SELF],
    "object-src": [CSP.NONE],
    "base-uri": [CSP.SELF],
    "frame-ancestors": [CSP.NONE],
}
# nosniff(True)·Referrer-Policy(same-origin)는 기본값이 이미 켜져 있다
```

### nginx

✗ 취약 코드

```nginx
server {
    listen 443 ssl;
    server_name example.com;
    add_header X-Content-Type-Options "nosniff";      # always 없음 → 404·500 응답에는 빠짐

    location /static/ {
        add_header Cache-Control "public, max-age=31536000";
        # 이 블록에 add_header 가 있으므로 위 server 의 add_header 는 상속되지 않음
    }
}
```

✓ 안전 코드

```nginx
# /etc/nginx/snippets/security-headers.conf
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Content-Security-Policy "default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# /etc/nginx/conf.d/example.conf
server {
    listen 443 ssl;
    server_name example.com;
    include snippets/security-headers.conf;

    location /static/ {
        include snippets/security-headers.conf;      # add_header 가 있는 블록마다 다시 포함
        add_header Cache-Control "public, max-age=31536000" always;
    }
}
server {
    listen 80;
    server_name example.com;
    return 301 https://$host$request_uri;            # HTTP 는 HTTPS 로만
}
```
