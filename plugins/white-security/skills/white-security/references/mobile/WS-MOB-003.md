<!-- generated from white-security issues; do not edit -->
# WS-MOB-003 Deep Link Hijacking · Unvalidated Deep Links (딥링크 가로채기·검증 없는 딥링크)

## 탐지 규칙

- **찾는 곳**: `Linking.getInitialURL(`, `Linking.addEventListener(`, `uriLinkStream`, `onOpenURL`, `<intent-filter`, `android:autoVerify`, `apple-app-site-association`, `assetlinks.json`, 그 밖에 app.json의 scheme·intentFilters·associatedDomains, 로그인 메일·재설정 메일을 만드는 서버 코드, OAuth redirect_uri.
- **검사 1 — 링크 속 비밀값**: 지적할 때: 서버가 만드는 링크(`myapp://…`·`https://…`)에 액세스·리프레시 토큰·세션 ID·오래 쓰는 재설정 토큰을 담는 경우, 앱이 링크의 토큰을 그대로 저장해 로그인하는 경우, PKCE 없는 OAuth 콜백을 사용자 지정 스킴으로 받는 경우. / 지적하지 않을 때: 한 번만 쓰고 몇 분 안에 만료되는 코드를 서버에서 교환하는 경우, PKCE를 쓰는 OAuth 콜백.
- **검사 2 — 검증 없는 처리**: 지적할 때: 링크의 호스트·경로를 확인하지 않고 화면 이름·주소를 그대로 쓰는 경우, 링크 값으로 송금·삭제·설정 변경을 바로 실행하는 경우, 링크의 URL을 WebView·브라우저에 그대로 여는 경우. / 지적하지 않을 때: 허용한 경로·정규식에 맞는 값만 받고, 중요한 동작은 확인 화면을 거치는 경우.
- **검사 3 — 링크 소유 확인**: 지적할 때: 로그인·결제 링크를 사용자 지정 스킴으로만 받는 경우, https intent-filter에 `android:autoVerify="true"`가 없거나 `assetlinks.json`·`apple-app-site-association`이 없는 경우. / 지적하지 않을 때: 상품 화면 열기처럼 가로채여도 해가 없는 링크만 스킴으로 받는 경우.
- **등급**: 위험: 링크로 토큰 전달, 링크만으로 송금·삭제 실행. / 주의: 호스트·경로 검증 없음, 로그인 링크가 App Links·유니버설 링크 확인 없이 동작. / 참고: 링크 코드 만료 시간이 길거나 재사용 가능.
- **보고 예**: `[위험] WS-MOB-003 server/auth.js:31 — 로그인 메일 링크 myapp://login?token=<30일 JWT> 로 토큰 전달 → 10분·1회용 code 로 바꾸고 앱이 code_verifier 와 함께 교환`
- **고칠 방법**: 링크에는 1회용 짧은 코드만 담아 요청한 기기에서 서버로 교환하게 하고, 중요한 링크는 App Links·유니버설 링크로 옮기며, 처리 코드는 허용 경로·형식만 받아야 한다.

## 점검 항목

- 딥링크에 로그인 토큰·세션·비밀번호 재설정 토큰을 그대로 담지 않아야 한다.
- 링크 코드는 한 번만 쓰이고 몇 분 안에 만료되며, 요청한 기기에서만 교환되어야 한다.
- 로그인·결제·재설정 링크는 도메인 확인을 거친 유니버설 링크·App Links로 보내야 한다.
- 딥링크 처리 코드는 호스트·경로·값 형식을 허용 목록으로 확인해야 한다.
- 딥링크만으로 송금·삭제·설정 변경을 실행하지 않고, 앱 안 확인 화면과 서버 권한 확인을 거쳐야 한다.
- 딥링크로 받은 주소를 WebView에 바로 열지 않아야 한다(WS-MOB-004).

## 검토 주의

Android는 같은 링크를 여러 앱이 등록할 수 있고, 12 이상에서는 확인되지 않은 웹 링크를 앱으로 바로 열지 않는다(Android 문서). Apple도 같은 스킴을 다른 앱이 등록하면 어느 앱이 열릴지 정해져 있지 않다고 밝힌다. OAuth 앱 로그인은 PKCE가 기본이며 서버 쪽 redirect_uri 검증은 WS-OAUTH-001에서 다룬다. 서버의 권한 확인 자체는 WS-AUTH-001·WS-MOB-005에서 다룬다.

Code examples (read only the file for the stack you are writing): `WS-MOB-003.code.expo.md` (Expo) · `WS-MOB-003.code.flutter.md` (Flutter) · `WS-MOB-003.code.express.md` (Express)

기본 심각도: 위험
기준: OWASP Top 10:2025 A01(접근 통제 실패) · OWASP Top 10:2025 A07(인증 실패) · OWASP API 2023 API2(인증 실패) · OWASP Mobile 2024 M4(입출력 검증 부족) · OWASP Mobile 2024 M8(보안 설정 오류) · OWASP Mobile 2024 M3(취약한 인증·인가) · ASVS 5.0 V14.2.1 · ASVS 5.0 V6.5.1 · ASVS 5.0 V6.5.5 · ASVS 5.0 V10.4.6 · ASVS 5.0 V2.2.1 · CWE-939 · CWE-20(입력값 검증 미흡) · CWE-601 · CWE-598 · 국내 보안약점 49 입력데이터 검증 및 표현-7(신뢰되지 않는 URL 주소로 자동접속 연결) · 국내 보안약점 49 입력데이터 검증 및 표현-15(보안기능 결정에 사용되는 부적절한 입력값) · 기반시설 웹 21 IA(불충분한 인증) · 기반시설 웹 21 PV(프로세스 검증 누락)
