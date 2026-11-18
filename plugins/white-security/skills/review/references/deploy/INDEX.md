<!-- generated from white-security issues; do not edit -->
# deploy · 설정·배포

When: 설정 파일·키·서버·컨테이너·배포 환경
Signals are candidates, not findings: open the matching `<ID>.md` here (max 2 per task) and decide with its checks (constant or request value?).
Stacks: Python · FastAPI · Django · Flask · JavaScript · Next.js · PHP · Laravel · Supabase · Shell · YAML · Dockerfile · Nginx

- `APP_DEBUG=true` `DEBUG = True` `debug=True` `FLASK_DEBUG` `manage.py runserver` `flask run` → WS-CFG-001 Debug Mode in Production
- `Access-Control-Allow-Origin` `CORSMiddleware` `allow_credentials=True` `supports_credentials` → WS-CFG-002 CORS Misconfiguration
- `Content-Security-Policy` `Strict-Transport-Security` `SECURE_HSTS_SECONDS` `async headers()` `add_header` → WS-CFG-003 Missing Security Headers
- `getPublicUrl(` `public: true` `"Principal": "*"` `allUsers` `allAuthenticatedUsers` `ports:` → WS-CFG-004 Public Bucket · Exposed Database
- `autoindex on` `Options Indexes` `productionBrowserSourceMaps` `sourcemap: true` `.htaccess` → WS-CFG-005 Exposed .git · .env · Backup Files
- `MYSQL_ROOT_PASSWORD` `POSTGRES_PASSWORD` `POSTGRES_HOST_AUTH_METHOD` `createsuperuser` `db:seed --force` → WS-CFG-006 Default Credentials
- `privileged: true` `docker.sock` `COPY . .` `USER root` `runAsNonRoot` `allowPrivilegeEscalation` → WS-CFG-007 Insecure Container
- `RateLimiter::for(` `throttleApi(` `throttle:` `slowapi` `limit_req_zone` `client_max_body_size` → WS-CFG-008 Missing Rate Limiting
- `openapi_url` `docs_url` `introspection` `/api/v1` `route:list` → WS-CFG-009 Shadow · Zombie API
- `verify=False` `rejectUnauthorized: false` `NODE_TLS_REJECT_UNAUTHORIZED` `withoutVerifying(` `MODE_ECB` → WS-CRY-001 Weak Cryptography
- `user_login_failed` `Events\Failed` `Log::channel(` `LOGGING = {` → WS-LOG-001 Security Logging & Alerting Failures
- `sk-` `AKIA` `ghp_` `xoxb-` `-----BEGIN` `.env*` `NEXT_PUBLIC_` `VITE_` → WS-SEC-001 Hardcoded Secrets
