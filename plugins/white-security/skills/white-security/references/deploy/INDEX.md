<!-- generated from white-security issues; do not edit -->
# deploy · 설정·배포

When: 설정 파일·키·서버·컨테이너·배포 환경
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.
Stacks: Python · FastAPI · Django · Flask · JavaScript · Next.js · PHP · Laravel · Supabase · Shell · YAML · Nginx

- `APP_DEBUG=true` `DEBUG = True` `debug=True` `FLASK_DEBUG` `manage.py runserver` `flask run` → WS-CFG-001 Debug Mode in Production
- `Access-Control-Allow-Origin` `CORSMiddleware` `allow_credentials=True` `supports_credentials` → WS-CFG-002 CORS Misconfiguration
- `Content-Security-Policy` `Strict-Transport-Security` `SECURE_HSTS_SECONDS` `async headers()` `add_header` → WS-CFG-003 Missing Security Headers
- `getPublicUrl(` `public: true` `"Principal": "*"` `allUsers` `BlockPublicAcls` `ports:` → WS-CFG-004 Public Bucket · Exposed Database
- `.env*` `config.*` `settings.py` `application.yml` `docker-compose.yml` `.mcp.json` `import.meta.env.VITE_*` `process.env.NEXT_PUBLIC_*` → WS-SEC-001 Hardcoded Secrets
