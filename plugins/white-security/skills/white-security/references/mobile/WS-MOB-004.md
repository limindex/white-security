<!-- generated from white-security issues; do not edit -->
# WS-MOB-004 Insecure WebView · JavaScript Bridge (WebView 설정·JS 브리지)

심각도: 위험
기준: OWASP Top 10:2025 A05(삽입) · OWASP Top 10:2025 A02(보안 설정 오류) · OWASP Mobile 2024 M4(입출력 검증 부족) · OWASP Mobile 2024 M8(보안 설정 오류) · ASVS 5.0 V3.5.5 · ASVS 5.0 V14.2.1 · CWE-749 · CWE-940 · CWE-79(크로스사이트 스크립팅) · CWE-200(권한 없는 자에게 민감 정보 노출) · 국내 보안약점 49 입력데이터 검증 및 표현-4(크로스사이트 스크립트) · 국내 보안약점 49 입력데이터 검증 및 표현-7(신뢰되지 않는 URL 주소로 자동접속 연결) · 기반시설 웹 21 XS(크로스사이트 스크립트)

## 점검 항목

- WebView는 우리 도메인의 https 페이지만 열고, 다른 주소는 외부 브라우저로 넘겨야 한다.
- 딥링크·푸시·서버 응답으로 받은 주소를 WebView에 바로 열지 않아야 한다(WS-MOB-003).
- JS 브리지는 보낸 쪽 출처를 확인하고 허용한 메시지 형식만 처리해야 한다.
- 토큰·개인정보를 페이지에 주입하거나 브리지로 돌려주지 않아야 한다.
- `file://` 접근과 file URL의 교차 출처 접근은 꺼야 하고, 앱 안 파일은 `WebViewAssetLoader`로 열어야 한다.
- 브리지를 쓰는 페이지에는 남의 스크립트·광고 iframe을 넣지 않고 CSP를 걸어야 한다.

## 탐지 규칙

- **찾는 곳**: `addJavascriptInterface(`, `setAllowFileAccess(true)`, `allowUniversalAccessFromFileURLs`, `window.ReactNativeWebView`, `onMessage=`, `originWhitelist`, `injectedJavaScript`, `addJavaScriptChannel(`, 그 밖에 `allowFileAccess = true`·`setJavaScriptEnabled(true)`, WebView에 넘기는 주소 변수, `onShouldStartLoadWithRequest`·`shouldOverrideUrlLoading`·`onNavigationRequest`.
- **검사 1 — 아무 주소나 여는 WebView**: 지적할 때: 딥링크·푸시·API 응답·사용자 입력의 주소를 WebView에 그대로 여는 경우, `originWhitelist={['*']}`, 이동 제한(`onShouldStartLoadWithRequest`·`shouldOverrideUrlLoading`·`onNavigationRequest`)이 없는 경우, http 주소. / 지적하지 않을 때: 고정된 우리 https 도메인만 열고 다른 주소는 외부 브라우저로 넘기는 경우.
- **검사 2 — 출처 확인 없는 브리지**: 지적할 때: `onMessage`·`addJavascriptInterface`·`addJavaScriptChannel`이 보낸 쪽 출처를 보지 않고 결제·토큰·파일·`Linking.openURL` 같은 동작을 하는 경우, 브리지 메서드가 토큰·개인정보를 돌려주는 경우, `injectedJavaScript`로 토큰을 페이지에 넣는 경우. / 지적하지 않을 때: `nativeEvent.url`·`WebViewCompat.addWebMessageListener` 허용 출처로 우리 페이지만 받고 정해 둔 메시지만 처리하는 경우.
- **검사 3 — 파일 접근**: 지적할 때: `setAllowFileAccess(true)`·`allowFileAccess`·`allowFileAccessFromFileURLs`·`allowUniversalAccessFromFileURLs`를 켠 경우, targetSdk 29 이하에서 file 접근을 명시적으로 끄지 않은 경우. / 지적하지 않을 때: file 접근을 끄고 `WebViewAssetLoader`나 번들 자산을 https 가상 주소로 여는 경우.
- **등급**: 위험: 임의 주소를 여는 WebView에 토큰·결제를 다루는 브리지, file URL 교차 출처 접근. / 주의: 출처 확인 없는 브리지, `originWhitelist={['*']}`, targetSdk 29 이하에서 file 접근 기본값 방치. / 참고: 브리지 페이지에 CSP 없음.
- **보고 예**: `[위험] WS-MOB-004 src/screens/Web.tsx:22 — route.params.url 을 그대로 열고 onMessage 가 출처 확인 없이 pay 를 실행 → 우리 도메인만 열고 nativeEvent.url 로 출처 확인, 결제는 앱 화면에서`
- **검토 주의**: Android WebView는 JavaScript가 기본 꺼져 있지만 `react-native-webview`는 기본 켜져 있고 `originWhitelist` 기본값이 http·https 전체다(라이브러리 문서). `setAllowFileAccess`는 targetSdk 29 이하에서 기본 true, 30 이상에서 기본 false다(Android 문서). Flutter `JavaScriptMessage`에는 보낸 쪽 출처가 없고 `currentUrl()`은 메인 프레임 주소라 iframe이 보낸 메시지와 구별하지 못하므로, 이동 제한과 함께 브리지 페이지에 외부 iframe을 넣지 않고 CSP `frame-src`로 막아야 한다. `react-native-webview`도 Android에서 `WEB_MESSAGE_LISTENER`를 지원하지 않는 오래된 WebView에서는 `addJavascriptInterface`로 대체하고 `nativeEvent.url`이 최상위 페이지 주소가 된다(라이브러리 소스). 페이지 자체의 XSS는 WS-XSS-001, 인증서 오류 무시(`onReceivedSslError`)는 WS-MOB-006에서 다룬다.
- **고칠 방법**: WebView를 우리 https 도메인으로 묶고, 브리지는 출처 확인과 허용 메시지만 처리하게 하며, file 접근을 꺼야 한다.

Code examples (read only the file for the stack you are writing): `WS-MOB-004.code.react-native.md` (React Native) · `WS-MOB-004.code.flutter.md` (Flutter) · `WS-MOB-004.code.kotlin.md` (Kotlin)
