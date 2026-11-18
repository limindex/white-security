<!-- generated from white-security issues; do not edit -->
# data · 개인정보 보호

- `service_role` `sb_secret_` `createClient(` `using (true)` `disable row level security` `security definer` `user_metadata` `storage.objects` → WS-BAAS-001: 비밀 키가 클라이언트에, RLS 없음·using (true)
- `firestore.rules` `storage.rules` `database.rules.json` `if true` `request.time <` `now <` `firebase-admin` `serviceAccountKey` → WS-BAAS-002: 쓰기를 여는 if true·기한부 규칙, 서비스 계정 키 노출
- `card_number` `account_number` `passport_no` `license_no` `biometric` `=> 'encrypted'` `AESGCM(` `createCipheriv(` → WS-PRV-001: 고유식별정보·카드·계좌를 평문 저장, 키가 소스에
- `sentry_sdk.init(` `Sentry.init(` `send_default_pii` `sendDefaultPii` `maskAllText` `before_send` `beforeSend` `redact:` → WS-PRV-002: 주민번호·카드번호·토큰이 로그·외부 도구로 감
- `access_log` `audit_log` `AccessLog` `activity()->` `auditlog.register(` `LogEntry` `/admin` → WS-PRV-003: 개인정보 대량 조회에 접속기록 없음
- `jumin` `resident_no` `residentNumber` `rrn_` `foreigner_no` `주민등록번호` `\b\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])-?[1-8]\d{6}\b` → WS-PRV-004: 근거 없는 주민번호 수집, 평문 저장·표시·로그
- `.select('*')` `select("*")` `include: { author: true }` `include: { user: true }` `->with('user')` `return User::` `->toArray()` `"__all__"` → WS-PRV-005: 다른 회원 연락처·주소가 응답에 원문으로
- `pg_dump` `mysqldump` `dumpdata` `loaddata` `db:seed` `Excel::download(` `fputcsv(` `*.sql` → WS-PRV-006: 공개된 곳의 실데이터 덤프, 운영 데이터 부은 개발 DB
