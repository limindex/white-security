<!-- generated from white-security issues; do not edit -->
# WS-SEC-001 Hardcoded Secrets (비밀키 노출)

심각도: 위험
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP Top 10:2025 A07(인증 실패) · ASVS 5.0 V13.3 · CWE-798 · CWE-540 · CWE-312 · 국내 보안약점 49 보안기능-6(하드코드된 중요정보) · 국내 보안약점 49 보안기능-5(암호화되지 않은 중요정보) · 기반시설 웹 21 IL(정보 누출)

## 점검 항목

- 소스·설정 파일·테스트 데이터·주석에 비밀값 리터럴(키 접두어가 붙은 문자열, `password=`, `secret`, `token`, 비밀번호가 든 접속 문자열, `-----BEGIN ... PRIVATE KEY-----`)이 없어야 한다. 비밀값은 실행 환경(환경 변수, 시크릿 매니저, KMS)에서 읽고, 값이 없으면 기본값으로 대신하지 않고 시작을 멈춰야 한다.
- `.env`, `*.pem`, `*.key`, 서비스 계정 JSON은 `.gitignore`에 넣고 repository에는 값을 비운 `.env.example`만 둬야 하며, 비밀값 검사(pre-commit 훅, 푸시 보호, CI 스캔)를 둬야 한다.
- 브라우저로 나가는 코드에는 공개용 키만 둬야 한다. `VITE_`, `NEXT_PUBLIC_`, `REACT_APP_`, `EXPO_PUBLIC_` 접두어 변수에 비밀키를 넣지 않고, 비밀키가 필요한 호출은 서버가 대신해야 한다.
- 로그, 오류 응답, 디버그 출력에 비밀값과 `Authorization` 헤더를 찍지 않아야 한다.
- 이미 커밋되거나 밖에 보인 비밀값은 교체가 먼저이고 기록 지우기는 그다음이어야 한다. 키는 필요한 권한만 주고 만료·교체 주기를 정해야 한다.
- 개인정보처리시스템이 저장하는 인증정보(비밀번호, 액세스·리프레시 토큰, 접근 키)는 암호화해 저장하고 비밀번호는 일방향 암호화해야 하며, 암호 키는 소스·설정 파일에 평문으로 두지 않아야 한다(고시 제7조 제1항·제6항, 안내서).

## 탐지 규칙

- **찾는 곳**: 이번에 쓰거나 고친 파일 전체, 특히 설정 파일(`.env*`, `config.*`, `settings.py`, `application.yml`, `docker-compose.yml`, CI 워크플로, `.mcp.json`), 브라우저 코드의 `import.meta.env.VITE_*`·`process.env.NEXT_PUBLIC_*`, 로그 출력부.
- **검사 1 — 리터럴 비밀값**: 지적할 때: 이름에 `key`, `secret`, `token`, `password`, `passwd`, `credential`, `private`가 든 변수에 16자 이상의 무작위로 보이는 문자열이 있는 경우. 알려진 키 형식(`AKIA`로 시작하는 20자, `ghp_`, `sk_live_`, `xoxb-`, `-----BEGIN ... PRIVATE KEY-----`)이나 비밀번호가 든 접속 문자열이 있는 경우. / 지적하지 않을 때: 빈 값, 자리표시자(`changeme`, `your-key-here`, `xxx`, `<...>`, `example`), `.env.example`·문서의 예시, 실제 서비스에 닿지 않는 테스트 폴더 속 가짜 값, 서비스 문서가 공개해도 된다고 한 공개용 키(publishable key 등).
- **검사 2 — 브라우저로 나가는 비밀키**: 지적할 때: 브라우저 번들 접두어 변수의 이름이나 쓰임새가 서버 전용 비밀키인 경우(이름에 `SECRET`이 있음, `Authorization: Bearer`에 그대로 씀). / 지적하지 않을 때: 공개용 키이고 권한 규칙이 서버 쪽에 따로 있는 경우.
- **검사 3 — 설정·출력으로 새는 값**: 지적할 때: 환경 변수가 없으면 고정값으로 도는 코드(`os.getenv("SECRET_KEY", "dev-secret")`, `process.env.JWT_SECRET || "secret"`), git에 추적되거나 `.gitignore`에 없는 `.env`·키 파일, 로그·오류 응답에 그대로 넣는 비밀값·접속 문자열·인증 헤더. / 지적하지 않을 때: 기본값이 개발 전용 분기에만 있고 운영 설정에서는 값이 없으면 멈추는 경우.
- **등급**: 위험: 실제 값으로 보이는 비밀값이 repository나 브라우저 번들에 있는 경우. / 주의: 없으면 고정값으로 도는 코드, `.gitignore` 누락, 로그 출력. / 참고: 비밀값 검사 도구 없음, 키 교체 주기 미정.
- **보고 예**: `[위험] WS-SEC-001 src/config.php:12 — DB 비밀번호가 코드에 평문으로 있음(값 가림) → 환경 변수로 옮기고, 이미 커밋됐다면 비밀번호부터 바꿈`
- **검토 주의**: 비밀값 자체는 옮겨 적지 않고 앞 4자만 남겨 `…[가림]`으로 표기해야 한다.
- **고칠 방법**: 코드에서 값을 지우고 환경 변수나 시크릿 매니저에서 읽게 바꿔야 하고, 사용자에게 "이 키는 이미 노출됐으니 서비스에서 새로 발급하고 옛 키를 폐기해야 한다"고 반드시 알려야 한다. git 기록을 다시 쓰는 작업(`git filter-repo` 등)은 사용자 확인 없이 하지 않아야 한다.

Code examples (read only the file for the language you are writing): `WS-SEC-001.code.python.md` · `WS-SEC-001.code.js.md` · `WS-SEC-001.code.php.md`
