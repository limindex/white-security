<!-- generated from white-security issues; do not edit -->
# WS-CFG-002 CORS Misconfiguration (CORS 설정 오류)

심각도: 위험
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP Top 10:2025 A01(접근 통제 실패) · OWASP API 2023 API8(보안 설정 오류) · ASVS 5.0 V3.4.2 · CWE-942 · CWE-346

## 점검 항목

- 쿠키·인증 헤더를 쓰는 API는 허용 출처를 정확한 주소 목록으로 정해야 한다.
- 요청 `Origin` 값을 검사 없이 허용 출처 헤더에 되돌려 주지 않아야 한다.
- 출처 정규식은 앞뒤를 고정하고 점(`.`)을 이스케이프해야 한다.
- `null` 출처, 운영의 `http://`·`localhost` 출처는 허용하지 않아야 한다.
- 출처마다 응답이 달라지면 `Vary: Origin`을 붙여야 한다.
- CORS와 별개로 API는 서버에서 인증·권한을 확인해야 한다.

## 탐지 규칙

- **찾는 곳**: `Access-Control-Allow-Origin`, `CORSMiddleware`, `allow_credentials=True`, `supports_credentials`, 그 밖에 config/cors.php의 allowed_origins·allowed_origins_patterns, FastAPI allow_origin_regex, Express cors() 옵션, next.config의 headers(), proxy·middleware에서 붙이는 응답 헤더.
- **검사 1 — 출처를 가리지 않는 허용**: 지적할 때: credentials를 허용하면서 허용 출처가 `*`인 경우(Laravel `config/cors.php`와 Starlette·FastAPI는 이 조합에서 요청 Origin을 그대로 돌려줌). 요청 `Origin` 헤더를 목록 비교 없이 `Access-Control-Allow-Origin`에 넣는 경우(Express `origin: true` 포함). / 지적하지 않을 때: credentials 없이 `*`로 여는 공개 API(로그인 없이 누구나 보는 데이터)인 경우.
- **검사 2 — 느슨한 출처 비교**: 지적할 때: `endsWith("example.com")`·`includes(`·이스케이프하지 않은 점·앞뒤를 고정하지 않은 `preg_match`·정규식으로 출처를 비교하는 경우, 운영 목록에 `null`·`http://`·`localhost` 출처가 있는 경우. / 지적하지 않을 때: 정확히 일치하는 목록 비교나 `#^https://app\.example\.com$#`처럼 고정된 정규식인 경우.
- **등급**: 위험: credentials 허용과 아무 출처 허용(`*` 반사·Origin 되돌려 주기)이 함께 있는 경우. / 주의: 느슨한 출처 비교, 운영 목록의 `null`·`http://`·`localhost`. / 참고: `Vary: Origin` 없음, 쓰지 않는 메서드·헤더까지 `*`로 허용.
- **보고 예**: `[위험] WS-CFG-002 main.py:9 — CORSMiddleware 에 allow_origins=["*"]·allow_credentials=True 라 아무 사이트가 로그인 사용자 응답을 읽음 → allow_origins 를 프런트엔드 주소 목록으로`
- **검토 주의**: FastAPI(Starlette)의 `allow_origin_regex`는 전체 일치로 비교하므로 앞뒤 고정이 없다고 지적하지 않아야 한다. 화면과 API가 같은 출처(같은 도메인·포트)이면 CORS 헤더 자체가 필요 없으므로 지우라고 안내해야 한다.
- **고칠 방법**: 허용 출처를 환경 변수의 정확한 주소 목록으로 바꾸고, credentials가 필요 없는 공개 API만 `*`를 써야 한다.

Code examples (read only the file for the stack you are writing): `WS-CFG-002.code.laravel.md` (Laravel) · `WS-CFG-002.code.nextjs.md` (Next.js) · `WS-CFG-002.code.fastapi.md` (FastAPI)
