<!-- generated from white-security issues; do not edit -->
# WS-DOS-001 ReDoS · Resource Exhaustion (정규식 서비스 거부·자원 고갈)

## 탐지 규칙

- **찾는 곳**: `new RegExp(`, `re.compile(`, `per_page`, `paginate($request`, 그 밖에 preg_match, limit·page_size·depth 요청 값, 외부 JSON을 도는 재귀 함수, while(true).
- **검사 1 — 위험한 정규식**: 지적할 때: 외부 입력에 쓰는 정규식에 중첩 반복(`(x+)+`, `(x*)*`, `(\w+\s?)*`)이나 겹치는 선택이 있는 경우. 사용자 입력으로 `new RegExp()`를 만드는 경우. / 지적하지 않을 때: 반복이 한 겹이거나 반복 사이에 구분 글자가 꼭 있고 입력 길이 상한이 앞에 있는 경우. RE2·`re2` 패키지를 쓰는 경우.
- **검사 2 — 상한 없는 크기·반복**: 지적할 때: `limit`·`per_page`·`page_size`·`depth`를 요청에서 받아 상한 없이 쓰는 경우. 외부 데이터 구조를 깊이·개수 제한 없이 재귀로 도는 경우. 외부 응답에 따라서만 끝나는 `while (true)`. / 지적하지 않을 때: 검증 규칙(`max:100`, `le=100`)이나 `min(값, 상한)`으로 자르는 경우.
- **등급**: 위험: 로그인·검색처럼 인증 없이 열린 곳의 위험한 정규식, 사용자 입력으로 만든 정규식. / 주의: 상한 없는 페이지 크기·재귀 깊이. / 참고: 입력 길이 상한만 없는 단순 정규식.
- **보고 예**: `[주의] WS-DOS-001 src/validate.js:3 — 이름 검사 정규식에 중첩 반복 ([a-z0-9]+\s?)* → 길이 50 상한 후 [a-z0-9]+( [a-z0-9]+)* 로 교체`
- **고칠 방법**: 정규식을 바꾼 뒤 기존에 통과하던 값이 그대로 통과하는지 테스트하고, 긴 실패 입력의 처리 시간 테스트를 하나 추가해야 한다.

## 점검 항목

- 사용자 입력에 쓰는 정규식에 중첩 반복·겹치는 선택이 없어야 한다.
- 정규식·파싱 전에 입력 길이 상한을 둬야 한다.
- 페이지 크기·내보내기 행 수에 서버 상한을 둬야 한다.
- 재귀는 깊이 상한을, 반복문은 횟수·전체 개수 상한을 가져야 한다.
- 사용자가 정규식을 직접 입력하는 기능은 RE2 같은 선형 엔진을 써야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-DOS-001.code.express.md` (Express) · `WS-DOS-001.code.fastapi.md` (FastAPI) · `WS-DOS-001.code.laravel.md` (Laravel)

기본 심각도: 주의 · CWE-1333 · CWE-400 · CWE-770 · CWE-674 · CWE-835
