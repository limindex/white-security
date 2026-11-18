<!-- generated from white-security issues; do not edit -->
# WS-PRV-002 Sensitive Data in Logs · Error Trackers (로그·오류 추적 도구에 남는 개인정보)

## 탐지 규칙

- **찾는 곳**: `sentry_sdk.init(`, `Sentry.init(`, `send_default_pii`, `sendDefaultPii`, `maskAllText`, `before_send`, `beforeSend`, `redact:`, 그 밖에 요청 본문·헤더·사용자 객체를 통째로 넘기는 로그 호출, 개인정보를 쿼리 문자열로 받는 GET 라우트, 분석 이벤트 속성, AI 호출 추적 도구 설정.
- **검사 1 — 도구가 자동으로 모으는 값**: 지적할 때: `send_default_pii=True`·`sendDefaultPii: true`인 경우, 세션 리플레이의 입력값 가림을 끈 경우(`maskAllText: false`), 회원가입·결제처럼 개인정보를 받는 앱에서 오류 추적 도구를 쓰면서 보내기 전 가림 함수가 없는 경우. / 지적하지 않을 때: `before_send`·`beforeSend`에서 요청 본문·쿠키·지역 변수를 빼거나 가리는 경우, 리플레이 기본 가림을 그대로 둔 경우.
- **검사 2 — 직접 남기는 로그**: 지적할 때: 요청 본문 전체·헤더 전체·사용자 객체를 로그·`console.log`·분석 이벤트에 넣는 경우, 전화번호·이메일·주민등록번호를 URL 쿼리로 받아 접근 로그와 Referer에 남는 경우. / 지적하지 않을 때: 사용자 id·요청 id 같은 식별자만 남기거나 로거 가림 설정(`redact`)으로 가린 경우.
- **등급**: 위험: 주민등록번호·카드번호·비밀번호·토큰이 로그나 외부 도구로 가는 경우. / 주의: `send_default_pii` 켬, 요청 본문 전체 로그, URL 쿼리 속 전화번호·이메일, 리플레이 가림 해제. / 참고: 외부 도구 보관 기간·열람 권한을 저장소에서 확인할 수 없는 경우("확인 필요").
- **보고 예**: `[주의] WS-PRV-002 app/main.py:9 — sentry_sdk.init(send_default_pii=True) 로 회원가입 요청 본문·IP가 외부로 전송 → 옵션을 빼고 before_send 로 요청 본문·지역 변수 제거`
- **고칠 방법**: 로그에는 식별자만 남기고, 로거 가림 설정과 오류 추적 도구의 보내기 전 가림 함수를 한곳에 둬야 하며, 이미 쌓인 로그 속 개인정보는 보관 기간 설정과 삭제로 정리해야 한다.

## 점검 항목

- 요청 본문·사용자 객체 전체를 로그에 남기지 않아야 한다.
- 로거에는 개인정보·토큰 필드를 가리는 설정을 둬야 한다.
- 오류 추적 도구는 보내기 전 요청 본문·쿠키·지역 변수를 빼야 한다.
- 개인정보 자동 수집 옵션(`send_default_pii`)을 켜지 않아야 한다.
- 전화번호·이메일 같은 개인정보는 URL 쿼리에 넣지 않아야 한다.
- 외부 로그·오류 도구의 보관 기간과 열람 권한을 최소로 정해야 한다.

## 검토 주의

어떤 보안 사건을 남길지는 WS-LOG-001, 접속기록 법정 항목은 WS-PRV-003, AI API로 보내는 개인정보는 WS-LLM-003에서 다룬다. 개인정보를 외부(특히 국외) 오류 추적 서비스로 보내면 처리 위탁·국외 이전에 해당할 수 있으므로, 가림이 어려운 값을 보내야 한다면 처리방침 기재 여부를 사용자에게 확인하도록 안내해야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-PRV-002.code.fastapi.md` (FastAPI) · `WS-PRV-002.code.nextjs.md` (Next.js)

기본 심각도: 주의 · CWE-532 · CWE-359 · CWE-200
