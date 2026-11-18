<!-- generated from white-security issues; do not edit -->
# WS-INJ-004 NoSQL·LDAP·XPath Injection (NoSQL·LDAP·XPath 삽입)

## 탐지 규칙

- **찾는 곳**: `findOne(req.body`, `find(req.query`, `$where`, `ldap_search(`, `xpath(`, 그 밖에 req.body·req.query 를 받는 findOne·updateOne·aggregate, ldap3 search 필터, 문자열로 만든 LDAP 필터.
- **검사 1 — 요청 객체가 조건**: 지적할 때: `req.body`·`req.query`·JSON 값이 형 확인 없이 `find`·`findOne`·`updateOne` 조건에 들어가는 경우(Express 4의 기본 쿼리 파서는 대괄호 이름도 객체로 만듦). / 지적하지 않을 때: `String()`·`Number()` 변환이나 zod·Pydantic 스키마로 형을 확인한 경우, Mongoose `sanitizeFilter`가 켜진 경우.
- **검사 2 — 서버 JS 실행 연산자**: 지적할 때: `$where`·`$function`·`$accumulator`·`mapReduce`에 외부 값이 들어가는 경우. / 지적하지 않을 때: 코드에 고정된 식만 쓰는 경우.
- **검사 3 — LDAP·XPath 문자열**: 지적할 때: LDAP 필터(`(uid=...)`)나 XPath 식에 입력을 이어 붙이는 경우. / 지적하지 않을 때: `escape_filter_chars`·`ldap_escape()` 같은 이스케이프나 XPath 변수 바인딩을 거친 경우.
- **등급**: 위험: 로그인·권한 조회 조건에 형 확인 없는 요청 값, `$where`에 입력. / 주의: 일반 조회 조건의 형 확인 없음, LDAP·XPath 이어 붙이기. / 참고: `$` 키 검사를 직접 만든 경우(빠진 경로 확인 필요).
- **보고 예**: `[위험] WS-INJ-004 routes/auth.js:9 — req.body.password 를 형 확인 없이 findOne 조건에 넣음 → 문자열 확인 후 해시 비교, mongoose sanitizeFilter 켜기`
- **고칠 방법**: 조회 직전에 형 확인을 넣고, 같은 모델을 쓰는 다른 라우트에도 같은 검사가 있는지 확인해야 한다.

## 점검 항목

- NoSQL 조회 값은 스키마 검증이나 `String()`으로 기대한 형인지 확인해야 한다.
- 요청 본문을 조회 조건 객체로 통째로 넘기지 않아야 한다.
- `$where`·`$function`·`mapReduce`처럼 서버에서 JS를 돌리는 기능은 쓰지 않아야 한다.
- Mongoose는 `sanitizeFilter` 옵션을 켜 둬야 한다.
- LDAP 필터 값은 `escape_filter_chars`로, DN 값은 DN용 이스케이프로 처리해야 한다.
- XPath 식에 입력을 이어 붙이지 말고 변수 바인딩이나 허용 목록 검사를 거쳐야 한다.

Code examples (read only the file for the stack you are writing): `WS-INJ-004.code.express.md` (Express) · `WS-INJ-004.code.fastapi.md` (FastAPI) · `WS-INJ-004.code.python.md` (Python)

기본 심각도: 위험
기준: OWASP Top 10:2025 A05(삽입) · ASVS 5.0 V1.2.4 · ASVS 5.0 V1.2.6 · ASVS 5.0 V1.2.7 · ASVS 5.0 V15.3.5 · CWE-943 · CWE-90 · CWE-643 · CWE-20(입력값 검증 미흡) · 국내 보안약점 49 입력데이터 검증 및 표현-9(XML 삽입) · 국내 보안약점 49 입력데이터 검증 및 표현-10(LDAP 삽입) · 기반시설 웹 21 CI(코드 인젝션)
