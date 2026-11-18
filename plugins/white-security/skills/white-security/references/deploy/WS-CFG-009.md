<!-- generated from white-security issues; do not edit -->
# WS-CFG-009 Shadow · Zombie API (잊힌 API·옛 버전 엔드포인트)

심각도: 주의
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP API 2023 API9(자산 목록 관리 미흡) · ASVS 5.0 V13.4.5 · ASVS 5.0 V4.3.2 · ASVS 5.0 V4.1.4 · ASVS 5.0 V15.2.3 · CWE-912 · CWE-284(접근 통제 미흡) · 기반시설 웹 21 WM(불필요한 Method 악용)

## 점검 항목

- API 목록(경로·메서드·버전·인증·담당)을 두고 배포 때마다 갱신해야 한다.
- 옛 버전은 종료일을 정하고, 남은 동안 새 버전과 같은 검사를 써야 한다.
- 테스트·임시·디버그 경로는 운영에 등록되지 않아야 한다.
- 공개 API가 아니면 운영의 API 문서·스키마·introspection을 꺼야 한다.
- 스테이징·개발 서버는 인터넷에 열지 않고 운영 데이터를 쓰지 않아야 한다.
- 쓰지 않는 HTTP 메서드(TRACE 등)는 막아야 한다.

## 탐지 규칙

- **찾는 곳**: `openapi_url`, `docs_url`, `introspection`, `/api/v1`, `route:list`, 그 밖에 v1·legacy·old·test·tmp 이름의 라우터 파일과 경로, Swagger UI 라우트, 스테이징 도메인 설정.
- **검사 1 — 검사가 빠진 옛 경로·남은 테스트 경로**: 지적할 때: 같은 기능의 `/v1`·`/v2` 라우트가 함께 있는데 옛 쪽에만 인증·권한 미들웨어나 입력 검증이 빠진 경우, `test`·`debug`·`old`·`legacy`·`tmp` 이름의 라우트가 환경 확인 없이 운영 라우트 파일에 등록된 경우. / 지적하지 않을 때: 옛 버전이 같은 미들웨어 그룹·의존성·핸들러를 쓰는 경우, 로컬 환경에서만 등록되는 경우.
- **검사 2 — 운영에 열린 API 문서**: 지적할 때: FastAPI 기본값(`/docs`·`/redoc`·`/openapi.json`)이 환경 구분 없이 켜진 경우, Swagger UI 라우트·GraphQL introspection이 운영에서도 켜진 경우. / 지적하지 않을 때: 외부 개발자용으로 공개하는 API 문서, 환경 변수로 운영에서 끄는 경우.
- **등급**: 위험: 옛 경로가 인증·권한 검사 없이 같은 데이터를 돌려주는 경우, 데이터를 지우거나 바꾸는 테스트 경로가 운영에 열린 경우. / 주의: 운영에 남은 테스트·관리 경로, 내부 API 문서 공개. / 참고: API 목록 없음, 종료 일정 없는 옛 버전.
- **보고 예**: `[위험] WS-CFG-009 routes/api.php:8 — v1 주문 조회가 auth 미들웨어 밖이라 v2 의 권한 검사를 우회해 남의 주문 조회 → v1 도 같은 auth·can:view 그룹으로 옮기고 종료일 공지`
- **검토 주의**: 경로별 권한 검사 자체의 결함은 WS-AUTH-001·WS-AUTH-002에서 다루므로 여기서는 버전·목록·문서 노출만 봐야 한다. 경로를 지우는 일은 앱·외부 연동이 아직 쓰는지 사용자에게 확인한 뒤 해야 한다.
- **고칠 방법**: 경로 목록을 뽑아 쓰지 않는 경로를 지우고, 남는 옛 버전은 새 버전과 같은 검사로 묶은 뒤 종료일을 알려야 한다.

Code examples (read only the file for the stack you are writing): `WS-CFG-009.code.fastapi.md` (FastAPI) · `WS-CFG-009.code.laravel.md` (Laravel)
