<!-- generated from white-security issues; do not edit -->
# web · 웹 애플리케이션

- `gethostbyaddr(` `dns.reverse(` `extract($_` `tempfile.mktemp(` → WS-API-001: 역DNS로 관리 허용, extract($_)
- `csrf_exempt` `validateCsrfTokens(` `VerifyCsrfToken` `allowedOrigins` → WS-CSRF-001: 계정·결제 변경에 CSRF 보호 없음
- `new RegExp(` `re.compile(` `per_page` `paginate($request` → WS-DOS-001: 공개 경로 ReDoS, 입력으로 만든 정규식
- `phpinfo(` `var_dump(` `response_model` `$hidden` → WS-ENC-001: 응답에 해시·토큰, 사용자 간 공유 상태
- `except Exception` `getMessage()` `err.stack` `getTraceAsString(` → WS-ERR-001: 인증·권한·결제 검사 fail-open
- `pool.connect(` `client.release(` `getConnection(` `(int) $request->input(` → WS-ERR-002: 금액 음수·범위 검사 없음
- `Content-Disposition` `smtplib` `setHeader(` `From: $` → WS-HDR-001: 메일 헤더에 요청 값
- `cursor.execute(` `->query(` `pool.query(` `whereRaw(` → WS-INJ-001: 외부 값이 SQL 문자열에
- `shell=True` `os.system(` `child_process` `shell_exec(` → WS-INJ-002: 외부 값이 셸 문자열에
- `render_template_string(` `Blade::render(` `new Function(` `ejs.render(` → WS-INJ-003: 외부 값이 템플릿 원문·eval에
- `findOne(req.body` `find(req.query` `$where` `ldap_search(` → WS-INJ-004: 로그인 조회에 객체 값, $where
- `res.sendFile(` `FileResponse(` `send_file(` `->download(` → WS-PATH-001: 요청 값 경로, 범위 확인 없음
- `lockForUpdate(` `select_for_update(` `$transaction(` → WS-RACE-001: 돈·쿠폰·재고 확인 뒤 변경
- `->input('next'` `request.GET.get("next"` `searchParams.get("next"` `formData.get("next"` → WS-RED-001: 로그인·OAuth 뒤 검사 없는 이동
- `move_uploaded_file(` `getClientOriginalName(` `public_path(` `location ~ \.php$` → WS-SHELL-001: 웹 루트에 요청 확장자로 저장
- `multer(` `UploadFile` `->store(` `request.FILES` → WS-UPL-001: 형식 검사 없이 웹 공개 경로에 저장
- `{!! $` `dangerouslySetInnerHTML` `mark_safe(` `|safe` → WS-XSS-001: 남의 글이 이스케이프 없이 HTML로
