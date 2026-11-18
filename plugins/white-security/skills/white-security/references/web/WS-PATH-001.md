<!-- generated from white-security issues; do not edit -->
# WS-PATH-001 Path Traversal (경로 조작 및 자원 삽입)

심각도: 위험
기준: OWASP Top 10:2025 A01(접근 통제 실패) · ASVS 5.0 V5.3.2 · ASVS 5.0 V5.4.1 · ASVS 5.0 V1.1.1 · CWE-22(경로 조작) · CWE-23 · CWE-73 · CWE-99 · 국내 보안약점 49 입력데이터 검증 및 표현-3(경로 조작 및 자원 삽입) · 기반시설 웹 21 FD(파일 다운로드)

## 점검 항목

- 파일은 이름 대신 DB id로 찾고, 실제 경로는 서버가 저장한 값을 써야 한다.
- 이름을 받아야 하면 허용 문자(`^[A-Za-z0-9._-]+$`)만 받고 `.`으로 시작하는 이름을 막아야 한다.
- 경로는 정규화(`realpath`·`resolve`)한 뒤 기준 폴더 안인지 다시 확인해야 한다.
- 검사는 URL 디코딩·유니코드 정규화를 모두 끝낸 값에 해야 한다.
- ANSI(CP949) 변환을 거치면 `₩`가 `\`로 바뀌므로 차단 목록이 아닌 허용 문자로 검사해야 한다.

## 탐지 규칙

- **찾는 곳**: `res.sendFile(`, `FileResponse(`, `send_file(`, `->download(`, 그 밖에 요청 값이 들어가는 path.join·open·readFile·file_get_contents·fopen·include.
- **검사 1 — 입력으로 경로 만들기**: 지적할 때: 요청·DB·압축 속 이름 값이 경로에 이어지고 기준 폴더 안인지 확인이 없는 경우. / 지적하지 않을 때: DB id로 찾은 서버 경로를 쓰는 경우. `realpath`·`resolve` 뒤 기준 폴더(구분자 포함)로 시작하는지 확인하는 경우. 허용 문자 정규식을 통과한 이름만 쓰는 경우.
- **검사 2 — 허술한 확인**: 지적할 때: `../`를 한 번만 지우는 치환, 정규화 전에 하는 검사, 구분자 없이 `startsWith(base)`로 비교하는 경우(`/data/files2`도 통과). / 지적하지 않을 때: 정규화한 경로가 `base + 구분자`로 시작하는지 비교하는 경우.
- **검사 3 — include·템플릿 경로**: 지적할 때: 요청 값으로 `include`·`require`·템플릿·언어 파일 경로를 고르는 경우. / 지적하지 않을 때: 허용 목록 매핑으로만 고르는 경우.
- **등급**: 위험: 요청 값으로 만든 경로로 파일을 읽거나 쓰면서 범위 확인이 없는 경우, `include`에 요청 값. / 주의: 한 번 치환·정규화 전 검사·구분자 없는 비교. / 참고: Windows 서버인데 `₩`를 거르지 않는 차단 목록 검사.
- **보고 예**: `[위험] WS-PATH-001 routes/files.js:7 — ?name= 값을 path.join 으로 붙여 sendFile → DB id 로 찾거나 resolve 후 기준 폴더 안인지 확인`
- **고칠 방법**: 파일 찾는 방식을 id로 바꿀 때 기존 링크가 깨지지 않게 이름→id 매핑을 두고, 범위 확인에 실패하면 404로 처리해야 한다.

Code examples (read only the file for the stack you are writing): `WS-PATH-001.code.express.md` (Express) · `WS-PATH-001.code.fastapi.md` (FastAPI) · `WS-PATH-001.code.laravel.md` (Laravel)
