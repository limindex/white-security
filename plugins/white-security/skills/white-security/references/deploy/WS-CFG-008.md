<!-- generated from white-security issues; do not edit -->
# WS-CFG-008 Missing Rate Limiting (요청 횟수·크기 제한 없음)

## 탐지 규칙

- **찾는 곳**: `RateLimiter::for(`, `throttleApi(`, `throttle:`, `slowapi`, `limit_req_zone`, `client_max_body_size`, 그 밖에 per_page·limit 인자를 그대로 쓰는 paginate·쿼리, Next.js bodySizeLimit, 문자·메일·내보내기 핸들러, 외부 호출의 timeout.
- **검사 1 — 횟수 제한 없음**: 지적할 때: 로그인 없이 열리는 API나 무거운 작업(보고서·내보내기·파일 처리·문자·메일 발송)에 앱(`throttle:`, slowapi, 미들웨어)이나 앞단(nginx `limit_req`, 호스팅 방화벽) 어디에도 횟수 제한이 보이지 않는 경우. Laravel 11 이상에서 `throttleApi()`나 `throttle:` 없이 api 경로를 여는 경우. / 지적하지 않을 때: 앞단 설정에 같은 경로의 제한이 보이는 경우(설정이 저장소 밖이면 "확인 필요"로 표시).
- **검사 2 — 크기·개수·시간 상한 없음**: 지적할 때: 요청에서 받은 페이지 크기 인자를 상한 없이 목록 조회 쿼리에 넣는 경우, 공개 서버 설정의 `client_max_body_size 0`(크기 검사 끔), 요청 본문으로 받는 배열·문자열에 길이 제한이 없어 반복·DB 저장·외부 호출로 이어지는 경우, 요청 처리 중 부르는 외부 HTTP 호출에 timeout이 없는 경우. / 지적하지 않을 때: `min(…, 100)`·`le=100` 같은 상한, 검증 규칙의 `max:`가 있는 경우, 관리자 전용·배치 작업처럼 외부 요청이 닿지 않는 코드.
- **등급**: 위험: 요금이 드는 호출(문자·메일·결제·외부 API)이나 무거운 작업이 제한 없이 열린 경우. / 주의: API 전체 기본 한도 없음, 페이지 크기 상한 없음. / 참고: 초과 응답이 429가 아님, 여러 서버인데 메모리 카운터 사용.
- **보고 예**: `[주의] WS-CFG-008 routes/api.php:14 — per_page 를 그대로 paginate 에 넣어 한 번에 수십만 행 조회 가능 → min(per_page, 100) 상한과 throttleApi() 추가`
- **고칠 방법**: 앱이나 앞단 한곳에 기본 횟수 제한을 넣고, 무거운 경로·페이지 크기·본문 크기·처리 시간에 각각 상한을 둬야 한다.

## 점검 항목

- API 전체에 사용자·IP별 기본 횟수 제한을 걸어야 한다.
- 문자·메일·외부 API를 부르는 무거운 경로에는 더 낮은 한도를 둬야 한다.
- 페이지 크기 인자에는 최댓값(예: 100)을 정해야 한다.
- 요청 본문 크기와 외부 호출·쿼리 시간에 상한을 둬야 한다.
- 여러 서버·워커면 카운터를 공용 저장소에 두고, 초과 시 429를 돌려줘야 한다.
- 프록시·CDN 뒤에서는 신뢰하는 프록시가 넣은 주소만 써야 한다.

## 검토 주의

로그인 시도 제한은 WS-AUTH-003, AI 호출 비용은 WS-LLM-004, 크롤러 대량 수집은 WS-BOT-001에서 자세히 다룬다. 프록시·CDN 뒤에서 IP로 셀 때는 신뢰하는 프록시가 넣은 주소만 쓰게 설정돼 있는지(Laravel TrustProxies, uvicorn `--forwarded-allow-ips`, nginx real_ip) 함께 봐야 한다.

Code examples (read only the file for the stack you are writing): `WS-CFG-008.code.laravel.md` (Laravel) · `WS-CFG-008.code.fastapi.md` (FastAPI) · `WS-CFG-008.code.nginx.md` (Nginx)

기본 심각도: 주의
기준: OWASP API 2023 API4(자원 소비 무제한) · ASVS 5.0 V2.4.1 · ASVS 5.0 V15.2.2 · CWE-770(한도 없는 자원 할당) · CWE-400 · CWE-799 · 기반시설 웹 21 AU(자동화 공격)
