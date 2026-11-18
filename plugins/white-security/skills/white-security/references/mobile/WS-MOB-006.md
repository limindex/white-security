<!-- generated from white-security issues; do not edit -->
# WS-MOB-006 Cleartext Traffic · Certificate Validation Bypass (평문 통신·인증서 검증 끔)

## 탐지 규칙

- **찾는 곳**: `android:usesCleartextTraffic="true"`, `cleartextTrafficPermitted="true"`, `NSAllowsArbitraryLoads`, `NSExceptionAllowsInsecureHTTPLoads`, `badCertificateCallback`, `onReceivedSslError`, `X509TrustManager`, `hostnameVerifier`, 그 밖에 expo-build-properties의 usesCleartextTraffic, iOS `urlSession(_:didReceive:completionHandler:)`, 코드 속 http 주소, `<pin-set`.
- **검사 1 — 평문 허용**: 지적할 때: 운영 빌드에 `usesCleartextTraffic="true"`·`base-config cleartextTrafficPermitted="true"`·`NSAllowsArbitraryLoads`가 있는 경우, API·로그인·WebView 주소가 http인 경우, targetSdk 27 이하인데 cleartext를 끄지 않은 경우. / 지적하지 않을 때: debug 매니페스트·`debug-overrides`에만 있는 개발 설정, `NSAllowsLocalNetworking`만 켠 경우.
- **검사 2 — 인증서 검증 끔**: 지적할 때: 빈 `checkServerTrusted`, `hostnameVerifier { _, _ -> true }`, `badCertificateCallback = … => true`, `onReceivedSslError`에서 `handler.proceed()`, iOS에서 서버 신뢰 평가 없이 `.useCredential`로 넘기는 경우, `rejectUnauthorized: false`. / 지적하지 않을 때: 기본 검증을 쓰거나 검증 뒤에 더 좁히는(고정) 경우.
- **검사 3 — 고정과 예외 관리**: 지적할 때: 예비 키 없는 pin-set, 사용자 CA(`src="user"`)를 운영 빌드 base-config에서 신뢰, 이유 없는 도메인 예외. / 지적하지 않을 때: 예비 키가 있는 고정, 기록된 단일 도메인 예외.
- **등급**: 위험: 인증서·호스트 이름 검증을 끄는 코드, 로그인·토큰이 오가는 http 통신. / 주의: 운영 빌드의 cleartext 허용·ATS 전체 해제, 운영 빌드의 사용자 CA 신뢰. / 참고: 예비 키 없는 고정, 이유가 기록되지 않은 도메인 예외.
- **보고 예**: `[위험] WS-MOB-006 android/app/src/main/java/com/example/Api.kt:21 — 모든 인증서를 믿는 X509TrustManager 와 hostnameVerifier { _, _ -> true } → 기본 OkHttpClient 로 되돌리고 개발 프록시는 debug-overrides 로`
- **고칠 방법**: 주소를 https로 바꾸고 cleartext·ATS 예외와 검증을 끄는 코드를 지우며, 개발용 인증서 신뢰는 디버그 빌드 전용 설정으로 옮겨야 한다.

## 점검 항목

- API·로그인·이미지·WebView 주소는 모두 https여야 한다.
- 운영 빌드에 cleartext 허용·ATS 전체 해제가 없어야 하고, 꼭 필요한 예외는 도메인 하나로 좁혀 이유를 기록해야 한다.
- 인증서·호스트 이름 검증을 끄는 코드(모든 인증서 믿기·`onReceivedSslError`에서 계속 진행)가 없어야 한다.
- 개발용 인증서 신뢰는 디버그 빌드에서만 켜져야 한다.
- Flutter `dart:io` 요청처럼 플랫폼 설정을 거치지 않는 통신은 코드에서 https만 허용해야 한다.
- 인증서 고정(pinning)은 기본 권장이 아니며, 꼭 필요할 때만 예비 키를 함께 넣고 교체 계획을 세워야 한다.

## 검토 주의

Android 9(API 28) 이상을 대상으로 하면 cleartext가 기본 꺼짐, 27 이하는 기본 허용이다. `usesCleartextTraffic`은 지원 중단 예정이라 API 24 이상은 network security config로 정하고, 이 설정은 플랫폼 HTTP 스택에 적용될 뿐 소켓 API는 따르지 않는다(Android 문서). ATS는 iOS 9 SDK 이상에서 기본으로 켜지고 URLSession 같은 Apple 네트워크 API에 적용되며, `NSAllowsArbitraryLoads`를 켜면 App Store 심사에서 사유를 내야 한다(Apple 문서). Flutter `dart:io`·`package:http` 요청은 자체 소켓을 써서 ATS·cleartext 설정은 물론 network_security_config의 `pin-set`·`debug-overrides`도 적용된다고 기대할 수 없으므로(Flutter 2.0에서 넣은 http 차단 정책은 문서에 2.2에서 되돌림(제안)으로 적혀 있어 지금 동작을 기대할 근거가 없음) 플랫폼 설정만 보고 안전하다고 판단하지 않아야 한다. 인증서 고정은 키 교체 때 앱이 끊기는 부담이 있어 기본 권장이 아니고, `pin-set`의 `expiration`은 업데이트 안 된 앱의 연결을 살리지만 그 날짜 뒤에는 고정이 풀린다(Android 문서). 서버 쪽 TLS 설정·`verify=False`는 WS-CRY-001에서 다룬다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-MOB-006.code.expo.md` (Expo) · `WS-MOB-006.code.flutter.md` (Flutter) · `WS-MOB-006.code.kotlin.md` (Kotlin)

기본 심각도: 위험 · CWE-319 · CWE-295 · CWE-297
