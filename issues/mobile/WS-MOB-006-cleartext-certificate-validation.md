---
id: WS-MOB-006
title: Cleartext Traffic · Certificate Validation Bypass (평문 통신·인증서 검증 끔)
slug: cleartext-certificate-validation
group: mobile
severity: high
grade_hint: "인증서 검증 끔, 로그인·토큰이 http로"
always_on: '`android:usesCleartextTraffic="true"` `NSAllowsArbitraryLoads` → 통신은 모두 https여야 하고, 인증서·호스트 이름 검증을 끄지 않아야 한다.'
site_only: false
depth: brief
languages: [expo, flutter, kotlin]
maps:
  owasp2025: [A04, A02]
  api2023: []
  llm2026: []
  agentic2026: []
  owasp_mobile2024: [M5, M8]
  asvs5: [V12.2.1, V12.3.2, V12.1.1]
  cwe: [CWE-319, CWE-295, CWE-297]
  kr_guide: ["보안기능-11", "보안기능-5"]
  kr_ciip: [SN]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [React Native·Expo, Flutter, Android(Kotlin·OkHttp·WebView), iOS(ATS), 공용 와이파이에서 쓰이는 앱]
severity_reason: "영향: 로그인 정보·토큰·개인정보를 중간에서 읽거나 응답을 바꿔치기 · 악용: 같은 와이파이·가짜 접속 지점에서 가로채기, 검증을 끈 앱은 아무 인증서나 받아들임"
sources:
  - title: "OWASP Mobile Top 10 2024 - M5: Insecure Communication"
    url: https://owasp.org/www-project-mobile-top-10/2023-risks/m5-insecure-communication
    year: 2024
  - title: "OWASP MASVS v2.1 - MASVS-NETWORK"
    url: https://mas.owasp.org/MASVS/08-MASVS-NETWORK/
    year: 2026
  - title: "Android Developers - Network security configuration (cleartext 기본값, debug-overrides, pin-set)"
    url: https://developer.android.com/privacy-and-security/security-config
    year: 2026
  - title: "Android Developers - <application> android:usesCleartextTraffic (기본값·지원 중단 예정·소켓 API 미적용)"
    url: https://developer.android.com/guide/topics/manifest/application-element
    year: 2026
  - title: "Android Developers - Unsafe X509TrustManager"
    url: https://developer.android.com/privacy-and-security/risks/unsafe-trustmanager
    year: 2026
  - title: "Apple Developer - NSAllowsArbitraryLoads (App Store 심사 사유 필요)"
    url: https://developer.apple.com/documentation/bundleresources/information-property-list/nsapptransportsecurity/nsallowsarbitraryloads
    year: 2026
  - title: "Apple Developer - Preventing Insecure Network Connections (ATS)"
    url: https://developer.apple.com/documentation/security/preventing-insecure-network-connections
    year: 2026
  - title: "Flutter Docs - Breaking changes: Insecure HTTP connections are disabled by default on iOS and Android (2.0 도입, 2.2 되돌림 제안, Dart가 연 소켓에는 플랫폼 정책 미적용)"
    url: https://docs.flutter.dev/release/breaking-changes/network-policy-ios-android
    year: 2026
  - title: "Expo Docs - BuildProperties (android.usesCleartextTraffic)"
    url: https://docs.expo.dev/versions/latest/sdk/build-properties/
    year: 2026
---

## 요약

개발 서버 연결이나 오래된 http API 때문에 평문(http) 통신을 허용하거나(`usesCleartextTraffic`·`NSAllowsArbitraryLoads`), 개발 프록시 인증서 때문에 인증서 검증을 꺼 둔 채 배포해 같은 와이파이에서 토큰·개인정보를 가로챌 수 있는 문제(진단가이드 이름: 부적절한 인증서 유효성 검증).

## 위험 배경

- Android(targetSdk 28 이상)와 iOS(ATS, App Transport Security, https 강제 기능)는 기본이 https라, 예외 설정과 검증 코드 덮어쓰기가 주된 원인.

## 대응 방법

```text
AndroidManifest.xml, network_security_config.xml, Info.plist, app.json, 네트워크 코드에서 http 허용 설정을 찾아 줘.
인증서 검증을 끄는 코드(TrustManager, hostnameVerifier, badCertificateCallback, onReceivedSslError)도 찾아 함께 표로 보여 줘.
모든 API·이미지·WebView 주소를 https 로 바꾸고, cleartext 허용과 ATS 예외, 검증을 끄는 코드를 지워 줘.
개발 프록시 인증서가 필요하면 Android 는 debug-overrides, 그 밖에는 디버그 빌드에서만 쓰는 설정으로 옮겨 줘.
```

- 릴리스 빌드에서 http 주소 요청이 실패하고, 직접 만든 인증서를 쓰는 프록시를 거치면 연결이 거절되어야 한다.
- 운영 빌드 설정에 `NSAllowsArbitraryLoads`·`cleartextTrafficPermitted="true"`가 없어야 한다.

## 점검 항목

- API·로그인·이미지·WebView 주소는 모두 https여야 한다.
- 운영 빌드에 cleartext 허용·ATS 전체 해제가 없어야 하고, 꼭 필요한 예외는 도메인 하나로 좁혀 이유를 기록해야 한다.
- 인증서·호스트 이름 검증을 끄는 코드(모든 인증서 믿기·`onReceivedSslError`에서 계속 진행)가 없어야 한다.
- 개발용 인증서 신뢰는 디버그 빌드에서만 켜져야 한다.
- Flutter `dart:io` 요청처럼 플랫폼 설정을 거치지 않는 통신은 코드에서 https만 허용해야 한다.
- 인증서 고정(pinning)은 기본 권장이 아니며, 꼭 필요할 때만 예비 키를 함께 넣고 교체 계획을 세워야 한다.

## 탐지 규칙

- **찾는 곳**: `android:usesCleartextTraffic="true"`, `cleartextTrafficPermitted="true"`, `NSAllowsArbitraryLoads`, `NSExceptionAllowsInsecureHTTPLoads`, `badCertificateCallback`, `onReceivedSslError`, `X509TrustManager`, `hostnameVerifier`, 그 밖에 expo-build-properties의 usesCleartextTraffic, iOS `urlSession(_:didReceive:completionHandler:)`, 코드 속 http 주소, `<pin-set`.
- **검사 1 — 평문 허용**: 지적할 때: 운영 빌드에 `usesCleartextTraffic="true"`·`base-config cleartextTrafficPermitted="true"`·`NSAllowsArbitraryLoads`가 있는 경우, API·로그인·WebView 주소가 http인 경우, targetSdk 27 이하인데 cleartext를 끄지 않은 경우. / 지적하지 않을 때: debug 매니페스트·`debug-overrides`에만 있는 개발 설정, `NSAllowsLocalNetworking`만 켠 경우.
- **검사 2 — 인증서 검증 끔**: 지적할 때: 빈 `checkServerTrusted`, `hostnameVerifier { _, _ -> true }`, `badCertificateCallback = … => true`, `onReceivedSslError`에서 `handler.proceed()`, iOS에서 서버 신뢰 평가 없이 `.useCredential`로 넘기는 경우, `rejectUnauthorized: false`. / 지적하지 않을 때: 기본 검증을 쓰거나 검증 뒤에 더 좁히는(고정) 경우.
- **검사 3 — 고정과 예외 관리**: 지적할 때: 예비 키 없는 pin-set, 사용자 CA(`src="user"`)를 운영 빌드 base-config에서 신뢰, 이유 없는 도메인 예외. / 지적하지 않을 때: 예비 키가 있는 고정, 기록된 단일 도메인 예외.
- **등급**: 위험: 인증서·호스트 이름 검증을 끄는 코드, 로그인·토큰이 오가는 http 통신. / 주의: 운영 빌드의 cleartext 허용·ATS 전체 해제, 운영 빌드의 사용자 CA 신뢰. / 참고: 예비 키 없는 고정, 이유가 기록되지 않은 도메인 예외.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-MOB-006 android/app/src/main/java/com/example/Api.kt:21 — 모든 인증서를 믿는 X509TrustManager 와 hostnameVerifier { _, _ -> true } → 기본 OkHttpClient 로 되돌리고 개발 프록시는 debug-overrides 로`
- **검토 주의**: Android 9(API 28) 이상을 대상으로 하면 cleartext가 기본 꺼짐, 27 이하는 기본 허용이다. `usesCleartextTraffic`은 지원 중단 예정이라 API 24 이상은 network security config로 정하고, 이 설정은 플랫폼 HTTP 스택에 적용될 뿐 소켓 API는 따르지 않는다(Android 문서). ATS는 iOS 9 SDK 이상에서 기본으로 켜지고 URLSession 같은 Apple 네트워크 API에 적용되며, `NSAllowsArbitraryLoads`를 켜면 App Store 심사에서 사유를 내야 한다(Apple 문서). Flutter `dart:io`·`package:http` 요청은 자체 소켓을 써서 ATS·cleartext 설정은 물론 network_security_config의 `pin-set`·`debug-overrides`도 적용된다고 기대할 수 없으므로(Flutter 2.0에서 넣은 http 차단 정책은 문서에 2.2에서 되돌림(제안)으로 적혀 있어 지금 동작을 기대할 근거가 없음) 플랫폼 설정만 보고 안전하다고 판단하지 않아야 한다. 인증서 고정은 키 교체 때 앱이 끊기는 부담이 있어 기본 권장이 아니고, `pin-set`의 `expiration`은 업데이트 안 된 앱의 연결을 살리지만 그 날짜 뒤에는 고정이 풀린다(Android 문서). 서버 쪽 TLS 설정·`verify=False`는 WS-CRY-001에서 다룬다.
- **고칠 방법**: 주소를 https로 바꾸고 cleartext·ATS 예외와 검증을 끄는 코드를 지우며, 개발용 인증서 신뢰는 디버그 빌드 전용 설정으로 옮겨야 한다.

## 코드 예시

### expo

✗ 취약 코드

```json
{
  "expo": {
    "ios": {
      "infoPlist": { "NSAppTransportSecurity": { "NSAllowsArbitraryLoads": true } }
    },
    "plugins": [
      ["expo-build-properties", { "android": { "usesCleartextTraffic": true } }]
    ],
    "extra": { "apiUrl": "http://api.example.com" }
  }
}
```

✓ 안전 코드

```json
{
  "expo": {
    "plugins": [
      ["expo-build-properties", { "android": { "usesCleartextTraffic": false } }]
    ],
    "extra": { "apiUrl": "https://api.example.com" }
  }
}
```

### flutter

✗ 취약 코드

```dart
import 'dart:io';
import 'package:http/io_client.dart';

// 개발 프록시 때문에 넣은 설정이 운영 빌드까지 감
final client = IOClient(HttpClient()..badCertificateCallback = (cert, host, port) => true);

Future<void> login(String id, String pw) =>
    client.post(Uri.parse('http://api.example.com/login'), body: {'id': id, 'pw': pw}); // 평문
```

✓ 안전 코드

```dart
import 'package:http/http.dart' as http;

const apiBase = 'https://api.example.com';

Uri api(String path) {
  final uri = Uri.parse('$apiBase$path');
  // dart:io 요청에는 ATS·Android cleartext 설정이 적용된다고 기대할 수 없어 코드에서 https 만 허용
  if (uri.scheme != 'https') throw ArgumentError('https only: $uri');
  return uri;
}

// 기본 클라이언트: 시스템 신뢰 저장소로 인증서·호스트 이름 확인, badCertificateCallback 은 쓰지 않음
Future<http.Response> login(String id, String pw) => http.post(api('/login'), body: {'id': id, 'pw': pw});
```

### kotlin

✗ 취약 코드

```kotlin
// AndroidManifest.xml: <application android:usesCleartextTraffic="true" …>
val trustAll = object : X509TrustManager {
    override fun checkClientTrusted(chain: Array<X509Certificate>, authType: String) {}
    override fun checkServerTrusted(chain: Array<X509Certificate>, authType: String) {} // 아무 인증서나 통과
    override fun getAcceptedIssuers(): Array<X509Certificate> = arrayOf()
}
val ssl = SSLContext.getInstance("TLS").apply { init(null, arrayOf(trustAll), SecureRandom()) }
val client = OkHttpClient.Builder()
    .sslSocketFactory(ssl.socketFactory, trustAll)
    .hostnameVerifier { _, _ -> true }                                                   // 호스트 이름도 안 봄
    .build()

webView.webViewClient = object : WebViewClient() {
    override fun onReceivedSslError(view: WebView, handler: SslErrorHandler, error: SslError) {
        handler.proceed()                                                                // 인증서 오류 무시
    }
}
```

✓ 안전 코드

```kotlin
/* res/xml/network_security_config.xml
<network-security-config>
    <base-config cleartextTrafficPermitted="false" />
    <debug-overrides>  <!-- debuggable 빌드에서만: 개발 프록시 인증서 -->
        <trust-anchors><certificates src="user" /></trust-anchors>
    </debug-overrides>
</network-security-config>
AndroidManifest.xml: <application android:networkSecurityConfig="@xml/network_security_config" …> */

val client = OkHttpClient()   // 기본값: 시스템 CA 로 인증서·호스트 이름 확인
// 인증서 고정은 기본 권장이 아님. 꼭 필요하면 <pin-set> 에 예비 키를 함께 넣음(Android 문서)
// expiration 을 걸면 업데이트 안 된 앱도 연결은 유지되지만 그 날짜 뒤로는 고정이 풀림

webView.webViewClient = object : WebViewClient() {
    override fun onReceivedSslError(view: WebView, handler: SslErrorHandler, error: SslError) {
        handler.cancel()      // 인증서 오류면 페이지를 열지 않음
    }
}
```
