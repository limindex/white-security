<!-- generated from white-security issues; do not edit -->
# WS-ENC-001 Leftover Debug Code·Data Exposure (남은 디버그 코드·잘못된 데이터 노출)

심각도: 주의
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP Top 10:2025 A01(접근 통제 실패) · OWASP API 2023 API3(객체 속성 수준 권한 검증 실패) · ASVS 5.0 V13.4.2 · ASVS 5.0 V15.2.3 · ASVS 5.0 V15.3.1 · CWE-489 · CWE-488 · CWE-495 · CWE-496 · CWE-615 · CWE-200(권한 없는 자에게 민감 정보 노출) · 국내 보안약점 49 캡슐화-1(잘못된 세션에 의한 데이터 정보 노출) · 국내 보안약점 49 캡슐화-2(제거되지 않고 남은 디버그 코드) · 국내 보안약점 49 캡슐화-3(Public 메소드로부터 반환된 Private 배열) · 국내 보안약점 49 캡슐화-4(Private 배열에 Public 데이터 할당) · 국내 보안약점 49 보안기능-13(주석문 안에 포함된 시스템 주요정보) · 기반시설 웹 21 IL(정보 누출)

## 점검 항목

- 디버그 라우트·`phpinfo()`·`dd()`·테스트 계정 코드는 배포 전에 지워야 한다.
- 화면 HTML·JS 주석에 내부 주소·계정·키를 남기지 않아야 한다.
- 요청별 사용자 정보는 전역 변수가 아니라 요청 객체·세션에 둬야 한다.
- 응답은 허용 필드만 골라 보내야 한다(비밀번호 해시·토큰·내부 메모 제외).
- 전역 설정·캐시 객체는 그대로 돌려주거나 고치지 말고 복사본을 써야 한다.

## 탐지 규칙

- **찾는 곳**: `phpinfo(`, `dd(`, `response_model`, `$hidden`, 그 밖에 /debug·/test 라우트, var_dump·console.log 로 찍는 토큰, 모듈 전역 currentUser.
- **검사 1 — 남은 디버그 코드**: 지적할 때: 운영 라우트에 `/debug`·`/test`·`phpinfo()`·`dd()`·`dump()`가 있거나, 토큰·비밀번호·개인정보를 `console.log`·`print`로 찍는 경우. / 지적하지 않을 때: 개발 환경에서만 켜지는 코드(`app()->isLocal()`, `NODE_ENV !== "production"`)이고 운영 설정에서 꺼진 것이 확인되는 경우.
- **검사 2 — 요청 사이 공유 상태**: 지적할 때: 모듈 전역·클래스 정적 변수·싱글턴에 현재 사용자·장바구니·토큰을 넣어 다른 요청이 읽을 수 있는 경우(Node·FastAPI·Laravel Octane처럼 프로세스가 오래 사는 서버). / 지적하지 않을 때: `req`·`request.state`·세션·`AsyncLocalStorage`·`contextvars`에 두는 경우.
- **검사 3 — 모델 통째 응답**: 지적할 때: ORM 모델·DB 행을 그대로 JSON으로 돌려줘 비밀번호 해시·토큰·내부 필드가 나가는 경우. / 지적하지 않을 때: `response_model`·API Resource·`$hidden`·필드 선택으로 보낼 값을 고른 경우.
- **등급**: 위험: 다른 사용자 정보가 섞이는 공유 상태, 비밀번호 해시·토큰이 응답에 나가는 경우. / 주의: 운영에서 열리는 디버그 라우트, 개인정보를 찍는 로그. / 참고: 주석 속 내부 정보, 전역 객체를 그대로 돌려주는 코드.
- **보고 예**: `[위험] WS-ENC-001 src/auth.js:5 — 로그인 사용자를 모듈 전역 currentUser 에 저장, 동시 요청에서 다른 사람 정보가 보임 → req.user 에 저장`
- **고칠 방법**: 디버그 코드는 지우는 것을 기본으로 하고, 남겨야 하면 환경 설정으로 운영에서 꺼야 한다.

Code examples (read only the file for the stack you are writing): `WS-ENC-001.code.express.md` (Express) · `WS-ENC-001.code.fastapi.md` (FastAPI) · `WS-ENC-001.code.laravel.md` (Laravel)
