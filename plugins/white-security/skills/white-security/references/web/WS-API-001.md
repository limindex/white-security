<!-- generated from white-security issues; do not edit -->
# WS-API-001 Dangerous API Use (위험한 API 사용·DNS 기반 보안 결정)

## 탐지 규칙

- **찾는 곳**: `gethostbyaddr(`, `dns.reverse(`, `extract($_`, `tempfile.mktemp(`, 그 밖에 new Buffer·mysql_* 함수, 토큰·서명을 == 나 === 로 비교하는 코드.
- **검사 1 — DNS 이름으로 허용**: 지적할 때: 요청 IP의 역방향 DNS 이름이나 `Host` 헤더로 관리자·내부 기능을 허용하는 경우. / 지적하지 않을 때: 인증·mTLS·서명 검증으로 허용하는 경우. 검색 크롤러 확인처럼 정방향 조회로 다시 맞춰 보고(이름→IP 일치) 접근 권한과 무관한 곳에만 쓰는 경우.
- **검사 2 — 위험한 함수**: 지적할 때: 요청 배열에 `extract()`, `tempfile.mktemp`, Node `new Buffer(숫자)`, 제거된 `mysql_*` 함수를 쓰는 경우. / 지적하지 않을 때: 코드가 만든 배열에 `extract()`를 `EXTR_SKIP`으로 쓰는 경우.
- **검사 3 — 비밀값 비교**: 지적할 때: 토큰·서명·해시를 `==`·`===`·`!=`로 비교하는 경우, PHP 느슨한 비교(`==`)로 해시·숫자 문자열을 비교하는 경우. / 지적하지 않을 때: `hash_equals`·`password_verify`·`crypto.timingSafeEqual`·`hmac.compare_digest`를 쓰는 경우.
- **등급**: 위험: 역방향 DNS 이름으로 관리 기능 허용, 요청 배열에 `extract()`. / 주의: 비밀값 일반 비교, PHP `==` 해시 비교, `mktemp`. / 참고: `new Buffer()` 등 폐기 API.
- **보고 예**: `[위험] WS-API-001 admin/index.php:4 — gethostbyaddr 결과가 .corp.example.com 으로 끝나면 관리자 허용 → 로그인·권한 확인으로 교체`
- **고칠 방법**: 위험 함수를 바꾼 뒤 같은 기능의 기존 테스트로 동작이 같은지 확인해야 한다.

## 점검 항목

- 접근 허용은 역방향 DNS 이름이 아니라 인증·서명·IP 허용 목록으로 판단해야 한다.
- 요청 값을 `extract()`로 변수에 풀지 말고 필요한 값만 꺼내야 한다.
- 임시 파일은 `mktemp` 대신 `NamedTemporaryFile`·`mkstemp`로 만들어야 한다.
- 토큰·서명 비교는 `hash_equals`·`timingSafeEqual`·`compare_digest`로 해야 한다.
- PHP 비교는 `===`로 하고, 해시 문자열을 `==`로 비교하지 않아야 한다.
- 언어 문서가 폐기·위험으로 표시한 함수(`mysql_*`, `new Buffer()`)는 쓰지 않아야 한다.

Code examples (read only the file for the stack you are writing): `WS-API-001.code.express.md` (Express) · `WS-API-001.code.python.md` (Python) · `WS-API-001.code.php.md` (PHP)

기본 심각도: 주의
기준: OWASP Top 10:2025 A06(안전하지 않은 설계) · ASVS 5.0 V15.3.5 · CWE-350 · CWE-676 · CWE-242 · CWE-377 · CWE-697 · 국내 보안약점 49 API 오용-1(DNS lookup에 의존한 보안결정) · 국내 보안약점 49 API 오용-2(취약한 API 사용)
