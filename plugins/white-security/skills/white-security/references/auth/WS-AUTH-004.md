<!-- generated from white-security issues; do not edit -->
# WS-AUTH-004 Exposed Admin Interface (관리자 페이지 노출)

## 탐지 규칙

- **찾는 곳**: `admin.site.urls`, `viewTelescope`, `viewHorizon`, `app/admin/`, `adminer.php`, `phpmyadmin`, 그 밖에 클라이언트 컴포넌트의 useSession 역할 분기, /manage·/backoffice 경로와 관리자 로그인 화면.
- **검사 1 — 서버 확인 없는 관리 화면**: 지적할 때: 관리 화면·API가 클라이언트 컴포넌트의 역할 확인(`useSession(` 뒤 조건 렌더링), 메뉴 숨김, 추측하기 어려운 주소, Next.js 레이아웃 한 곳에만 기대는 경우. `viewTelescope`·`viewHorizon` 게이트가 항상 참이거나 운영 환경에서 켜진 경우. / 지적하지 않을 때: 서버의 정책·데이터 접근 함수(예: `requireAdmin()`)가 DB 역할을 확인하고, 페이지·관리용 API·Server Action에서도 같은 확인을 부르는 경우.
- **검사 2 — 외부 접속 보호**: 지적할 때: 인터넷에 열린 관리자 로그인이 아이디·비밀번호만 받는 경우. Adminer·phpMyAdmin 같은 DB 관리 도구가 웹 루트에 있는 경우. / 지적하지 않을 때: OTP·패스키 같은 2단계 인증이나 VPN·IP 허용 목록이 설정에 보이는 경우(코드만으로 판단이 안 되면 "확인 필요"로 표시).
- **검사 3 — 오래 유지되는 관리자 세션**: 지적할 때: 관리자 세션이 며칠씩 유지되거나 "로그인 유지"가 관리자에게도 적용되는 경우. / 지적하지 않을 때: 비활동 시간 제한이 있는 경우.
- **등급**: 위험: 관리 기능·운영 도구가 로그인이나 역할 확인 없이 열리는 경우, DB 관리 도구가 웹에 노출된 경우. / 주의: 관리자 로그인에 2단계 인증·IP 제한이 없는 경우. / 참고: 관리자 세션 시간 제한 없음, 기본 주소(`/admin/`) 그대로.
- **보고 예**: `[위험] WS-AUTH-004 app/Providers/TelescopeServiceProvider.php:22 — viewTelescope 게이트가 항상 true 라 운영에서 누구나 요청·쿼리 기록 열람 → Telescope 는 local 환경에서만 등록`
- **고칠 방법**: 역할 확인을 서버의 화면·API 양쪽에 넣어야 하고, 관리자 접속은 2단계 인증과 네트워크 제한을 함께 걸어야 한다.

## 점검 항목

- 관리자 화면과 API는 서버에서 DB의 역할을 확인해야 한다(메뉴 숨김·주소 바꾸기는 보조).
- 관리자 로그인은 2단계 인증을 쓰고, 접속은 사무실·VPN IP로 제한해야 한다.
- Telescope·Horizon·Adminer·phpMyAdmin 같은 운영 도구는 운영 서버에서 끄거나 잠가야 한다.
- 관리자 세션은 짧은 비활동 시간이 지나면 끊어야 한다.
- 관리자 로그인 실패와 관리 작업은 접속기록(누가·언제·무엇을 했는지 남기는 기록)으로 남겨야 한다.

Code examples (read only the file for the stack you are writing): `WS-AUTH-004.code.laravel.md` (Laravel) · `WS-AUTH-004.code.nextjs.md` (Next.js) · `WS-AUTH-004.code.django.md` (Django)

기본 심각도: 위험
기준: OWASP Top 10:2025 A01(접근 통제 실패) · OWASP Top 10:2025 A02(보안 설정 오류) · OWASP API 2023 API5(기능 수준 권한 검증 실패) · ASVS 5.0 V8.2.1 · ASVS 5.0 V8.3.1 · ASVS 5.0 V8.4.2 · ASVS 5.0 V7.3.1 · CWE-306(중요 기능 인증 누락) · CWE-862(권한 검사 누락) · CWE-284(접근 통제 미흡) · 국내 보안약점 49 보안기능-1(적절한 인증 없는 중요 기능 허용) · 국내 보안약점 49 보안기능-2(부적절한 인가) · 기반시설 웹 21 AE(관리자 페이지 노출)
