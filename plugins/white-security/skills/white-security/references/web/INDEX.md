<!-- generated from white-security issues; do not edit -->
# web · 웹 애플리케이션

When: 입력을 받아 DB·화면·파일·명령으로 넘기는 웹 기능
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.
Stacks: Python · FastAPI · Django · Flask · JavaScript · Next.js · Express · PHP · Laravel · Nginx

- `csrf_exempt` `validateCsrfTokens(` `VerifyCsrfToken` `allowedOrigins` → WS-CSRF-001 CSRF (Cross-Site Request Forgery)
- `cursor.execute(` `->query(` `pool.query(` `whereRaw(` `DB::raw(` `knex.raw(` → WS-INJ-001 SQL 인젝션 (SQLi)
- `shell=True` `os.system(` `child_process` `shell_exec(` `Process::run` → WS-INJ-002 OS Command Injection
- `render_template_string(` `Blade::render(` `new Function(` `ejs.render(` → WS-INJ-003 SSTI·Code Injection
- `findOne(` `$where` `sanitizeFilter` `ldap_search(` `xpath(` → WS-INJ-004 NoSQL·LDAP·XPath Injection
- `res.sendFile(` `FileResponse(` `send_file(` `->download(` `path.join(` → WS-PATH-001 Path Traversal
- `move_uploaded_file(` `getClientOriginalName(` `public_path(` `location ~ \.php$` `.htaccess` → WS-SHELL-001 Web Shell
- `{!! $` `dangerouslySetInnerHTML` `mark_safe(` `|safe` `.innerHTML =` → WS-XSS-001 XSS (Cross-Site Scripting)
