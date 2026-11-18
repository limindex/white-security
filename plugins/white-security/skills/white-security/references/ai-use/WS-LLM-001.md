<!-- generated from white-security issues; do not edit -->
# WS-LLM-001 Improper Output Handling (AI 출력을 그대로 실행·표시)

## 탐지 규칙

- **찾는 곳**: LLM 응답이 흘러가는 곳 — 코드 실행(`eval(`, `exec(`), 셸(`subprocess.`, `shell_exec(`), SQL 문자열(`cursor.execute(`), HTML(`innerHTML =`, `dangerouslySetInnerHTML`, `v-html`), 그 밖에 `new Function(`, `child_process.exec(`, `db.query(`, 이스케이프 없는 PHP echo. 응답 변수 예: reply, completion, output, content 등(`response.text`, `.message.content`, `output_text`).
- **검사 1 — AI 출력 실행**: 지적할 때: LLM 응답 문자열이 코드 실행·셸·SQL 문자열로 그대로 들어가는 경우. / 지적하지 않을 때: 응답을 JSON으로 파싱해 허용 목록의 값만 쓰고, 실행은 고정된 함수·매개변수 바인딩으로 하는 경우. 격리된 샌드박스(네트워크·파일 접근 없는 별도 컨테이너)에서만 실행하는 경우.
- **검사 2 — AI 출력의 HTML 렌더링**: 지적할 때: LLM 응답을 새니타이저 없이 HTML로 넣는 경우. / 지적하지 않을 때: 텍스트로 그리거나 새니타이저를 거치는 경우.
- **등급**: 위험: AI 출력이 `eval`·셸·SQL로 들어가는 경우. 남이 쓴 글이 섞인 AI 출력이 HTML로 그려지는 경우. / 주의: 사용자 본인 입력만 들어가는 AI 출력이 HTML로 그려지는 경우. 출력 형식 검사가 없는 경우. / 참고: 샌드박스에서만 실행하지만 시간·메모리 제한이 없는 경우.
- **보고 예**: `[위험] WS-LLM-001 api/report.py:57 — 모델이 쓴 SQL 을 cursor.execute 로 그대로 실행 → 조건만 JSON 으로 받아 허용 목록 검사 후 매개변수 바인딩`

## 점검 항목

- AI 출력은 사용자 입력과 똑같이 믿지 않고, 받는 쪽에서 형식(JSON 스키마·길이·허용 값)을 검사해야 한다.
- AI 출력을 `eval`·`exec`·`new Function`·셸 문자열로 실행하지 않고, 할 일은 서버에 미리 정한 목록에서 고르게 해야 한다.
- AI에게 SQL을 쓰게 하지 않고, 검색 조건만 JSON으로 받아 허용 목록으로 검사한 뒤 매개변수 바인딩으로 조회해야 한다. 꼭 필요하면 읽기 전용 계정과 행 수 제한을 두어야 한다.
- 화면에는 `textContent`나 자동 이스케이프로 그려야 하고, HTML이 필요하면 새니타이저를 거쳐야 한다(이미지 유출 통로는 WS-PI-002).
- AI가 고른 파일 경로·URL도 허용 목록으로 검사해야 한다.

Code examples (read only the file for the stack you are writing): `WS-LLM-001.code.python.md` (Python) · `WS-LLM-001.code.js.md` (JavaScript) · `WS-LLM-001.code.php.md` (PHP)

기본 심각도: 위험
기준: OWASP Top 10:2025 A05(삽입) · OWASP LLM 2026 LLM10(출력 처리 미흡) · OWASP 에이전트 2026 ASI05(예상치 못한 코드 실행) · ASVS 5.0 V1.3.2 · ASVS 5.0 V1.2.4 · ASVS 5.0 V1.2.5 · ASVS 5.0 V3.2.2 · CWE-1426 · CWE-94(코드 삽입) · CWE-79(크로스사이트 스크립팅) · CWE-89(SQL 삽입) · CWE-78(운영체제 명령어 삽입) · 국내 보안약점 49 입력데이터 검증 및 표현-1(SQL 삽입) · 국내 보안약점 49 입력데이터 검증 및 표현-2(코드 삽입) · 국내 보안약점 49 입력데이터 검증 및 표현-4(크로스사이트 스크립트) · 국내 보안약점 49 입력데이터 검증 및 표현-5(운영체제 명령어 삽입) · 기반시설 웹 21 CI(코드 인젝션) · 기반시설 웹 21 SI(SQL 인젝션) · 기반시설 웹 21 XS(크로스사이트 스크립트)
