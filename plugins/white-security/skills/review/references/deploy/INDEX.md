<!-- generated from white-security issues; do not edit -->
# deploy · 설정·배포

- `APP_DEBUG=true` `DEBUG = True` `debug=True` `FLASK_DEBUG` `manage.py runserver` `flask run` → WS-CFG-001: 운영에서 디버그 켜짐
- `Access-Control-Allow-Origin` `CORSMiddleware` `allow_credentials=True` `supports_credentials` → WS-CFG-002: credentials 허용과 아무 출처 허용이 함께
- `Content-Security-Policy` `Strict-Transport-Security` `SECURE_HSTS_SECONDS` `async headers()` `add_header` → WS-CFG-003: 주의: HTML 앱에 CSP·HSTS 둘 다 없음
- `getPublicUrl(` `public: true` `"Principal": "*"` `allUsers` `allAuthenticatedUsers` `ports:` → WS-CFG-004: 개인정보 버킷 공개, 인증 없는 DB·Redis 노출
- `autoindex on` `Options Indexes` `productionBrowserSourceMaps` `sourcemap: true` `.htaccess` → WS-CFG-005: .git·.env·DB 덤프가 웹에서 열림
- `MYSQL_ROOT_PASSWORD` `POSTGRES_PASSWORD` `POSTGRES_HOST_AUTH_METHOD` `createsuperuser` `db:seed --force` → WS-CFG-006: 외부에 닿는 서비스의 기본·고정 비밀번호
- `privileged: true` `docker.sock` `COPY . .` `USER root` `runAsNonRoot` `allowPrivilegeEscalation` → WS-CFG-007: 이미지 속 비밀값, docker.sock·privileged
- `RateLimiter::for(` `throttleApi(` `throttle:` `slowapi` `limit_req_zone` `client_max_body_size` → WS-CFG-008: 요금 드는 호출·무거운 작업에 제한 없음
- `openapi_url` `docs_url` `introspection` `/api/v1` `route:list` → WS-CFG-009: 검사 없는 옛 경로, 운영에 열린 쓰기 테스트 경로
- `verify=False` `rejectUnauthorized: false` `NODE_TLS_REJECT_UNAUTHORIZED` `withoutVerifying(` `MODE_ECB` → WS-CRY-001: 인증서 검증 끔, 예측 가능한 토큰 난수, ECB·고정 IV
- `user_login_failed` `Events\Failed` `Log::channel(` `LOGGING = {` → WS-LOG-001: 로그에 비밀번호·토큰·주민번호
- `sk-` `AKIA` `ghp_` `xoxb-` `-----BEGIN` `.env*` `NEXT_PUBLIC_` `VITE_` → WS-SEC-001: 실제 비밀값이 저장소·브라우저 번들에 있음
