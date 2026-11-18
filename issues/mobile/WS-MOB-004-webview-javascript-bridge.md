---
id: WS-MOB-004
title: Insecure WebView · JavaScript Bridge (WebView 설정·JS 브리지)
slug: webview-javascript-bridge
group: mobile
severity: high
site_only: false
depth: brief
languages: [react-native, flutter, kotlin]
maps:
  owasp2025: [A05, A02]
  api2023: []
  llm2026: []
  agentic2026: []
  owasp_mobile2024: [M4, M8]
  asvs5: [V3.5.5, V14.2.1]
  cwe: [CWE-749, CWE-940, CWE-79, CWE-200]
  kr_guide: ["입력데이터 검증 및 표현-4", "입력데이터 검증 및 표현-7"]
  kr_ciip: [XS]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [React Native(react-native-webview), Expo, Flutter(webview_flutter), Android WebView(Kotlin), 웹 화면을 앱 안에 띄우는 하이브리드 앱]
severity_reason: "영향: WebView에 열린 남의 페이지가 앱 기능(토큰 읽기·결제·파일)을 호출하거나 기기 파일을 읽음 · 악용: 앱이 여는 주소를 바꾸거나 열린 페이지에 스크립트·iframe을 넣기"
sources:
  - title: "OWASP Mobile Top 10 2024 - M4: Insufficient Input/Output Validation"
    url: https://owasp.org/www-project-mobile-top-10/2023-risks/m4-insufficient-input-output-validation
    year: 2024
  - title: "OWASP MASVS v2.1 - MASVS-PLATFORM"
    url: https://mas.owasp.org/MASVS/09-MASVS-PLATFORM/
    year: 2026
  - title: "Android Developers - Insecure WebView native bridges"
    url: https://developer.android.com/privacy-and-security/risks/insecure-webview-native-bridges
    year: 2026
  - title: "Android Developers - WebSettings (setAllowFileAccess 기본값)"
    url: https://developer.android.com/reference/android/webkit/WebSettings
    year: 2026
  - title: "Android Developers - androidx.webkit WebViewCompat (addWebMessageListener, allowedOriginRules)"
    url: https://developer.android.com/reference/androidx/webkit/WebViewCompat
    year: 2026
  - title: "react-native-webview 14 - Reference"
    url: https://github.com/react-native-webview/react-native-webview/blob/master/docs/Reference.md
    year: 2026
  - title: "webview_flutter 4.14 (pub.dev)"
    url: https://pub.dev/packages/webview_flutter
    year: 2026
---

## 요약

WebView에 JS 브리지(웹 페이지가 앱 기능을 부르는 통로)를 열어 두고 아무 주소나 띄우거나, `file://` 접근을 켜 두어 남의 페이지가 토큰 읽기·결제 같은 앱 기능을 호출하거나 기기 파일을 읽는 문제. Android `addJavascriptInterface`는 iframe을 포함한 모든 프레임에 객체를 넣고 부른 쪽 출처를 알려 주지 않으므로(Android 문서), 브리지는 우리 도메인 페이지에서 온 메시지만 받아야 함.

## 대응 방법

```text
WebView 를 쓰는 곳을 찾아 여는 주소의 출처, JS 브리지(onMessage, addJavascriptInterface, JavaScriptChannel)로 할 수 있는 일, file 접근 설정을 표로 보여 줘.
WebView 는 우리 도메인 https 페이지만 열게 하고, 다른 주소는 외부 브라우저로 넘기게 고쳐 줘.
브리지는 보낸 페이지의 출처를 확인하고 정해 둔 메시지만 처리하게, 토큰·결제는 브리지로 넘기지 않게 바꿔 줘.
```

- 다른 도메인 페이지에서 `window.ReactNativeWebView.postMessage`·브리지 함수를 불러도 앱이 아무 동작도 하지 않아야 한다.
- `file://` 주소를 WebView에 넣어도 기기 파일이 열리지 않아야 한다.

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
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-MOB-004 src/screens/Web.tsx:22 — route.params.url 을 그대로 열고 onMessage 가 출처 확인 없이 pay 를 실행 → 우리 도메인만 열고 nativeEvent.url 로 출처 확인, 결제는 앱 화면에서`
- **검토 주의**: Android WebView는 JavaScript가 기본 꺼져 있지만 `react-native-webview`는 기본 켜져 있고 `originWhitelist` 기본값이 http·https 전체다(라이브러리 문서). `setAllowFileAccess`는 targetSdk 29 이하에서 기본 true, 30 이상에서 기본 false다(Android 문서). Flutter `JavaScriptMessage`에는 보낸 쪽 출처가 없으므로 이동 제한과 `currentUrl()` 확인을 함께 써야 한다. 페이지 자체의 XSS는 WS-XSS-001, 인증서 오류 무시(`onReceivedSslError`)는 WS-MOB-006에서 다룬다.
- **고칠 방법**: WebView를 우리 https 도메인으로 묶고, 브리지는 출처 확인과 허용 메시지만 처리하게 하며, file 접근을 꺼야 한다.

## 코드 예시

### react-native

✗ 취약 코드

```tsx
import { Linking } from "react-native";
import { WebView } from "react-native-webview";

export function WebScreen({ route }: { route: { params: { url: string } } }) {
  return (
    <WebView
      source={{ uri: route.params.url }}                 // 딥링크로 받은 아무 주소
      originWhitelist={["*"]}
      allowFileAccess
      allowUniversalAccessFromFileURLs
      injectedJavaScriptBeforeContentLoaded={`window.__TOKEN__ = "${accessToken}";`} // 페이지에 토큰 주입
      onMessage={(e) => {
        const msg = JSON.parse(e.nativeEvent.data);       // 누가 보냈는지 확인 안 함
        if (msg.type === "pay") payNow(msg.amount);
        if (msg.type === "open") Linking.openURL(msg.url);
      }}
    />
  );
}
```

✓ 안전 코드

```tsx
import { Linking } from "react-native";
import { WebView } from "react-native-webview";

const ORIGIN = "https://help.example.com";
const isOurs = (u: string) => u === ORIGIN || u.startsWith(ORIGIN + "/"); // .example.com.evil 같은 주소는 불일치

export function HelpScreen({ onClose }: { onClose: () => void }) {
  return (
    <WebView
      source={{ uri: `${ORIGIN}/faq` }}                   // 고정 주소만
      originWhitelist={[ORIGIN]}
      onShouldStartLoadWithRequest={(req) => {
        if (isOurs(req.url)) return true;
        if (req.url.startsWith("https://")) Linking.openURL(req.url); // 밖 링크는 외부 브라우저로
        return false;
      }}
      onMessage={(e) => {
        if (!isOurs(e.nativeEvent.url)) return;           // 우리 페이지가 보낸 것만
        if (e.nativeEvent.data === "close") onClose();    // 정해 둔 메시지만, 토큰·결제는 브리지로 처리하지 않음
      }}
    />
  );
}
```

### flutter

✗ 취약 코드

```dart
final controller = WebViewController()
  ..setJavaScriptMode(JavaScriptMode.unrestricted)
  ..addJavaScriptChannel('AppBridge', onMessageReceived: (m) async {
    final msg = jsonDecode(m.message) as Map<String, dynamic>;   // 어느 페이지가 보냈는지 모름
    if (msg['type'] == 'getToken') {
      final t = await storage.read(key: 'access_token');
      controller.runJavaScript("onToken('$t')");                 // 열린 아무 페이지에 토큰 전달
    }
  })
  ..loadRequest(Uri.parse(linkFromPush));                        // 푸시로 받은 주소
```

✓ 안전 코드

```dart
const origin = 'https://help.example.com';
bool isOurs(String? u) => u != null && (u == origin || u.startsWith('$origin/'));

late final WebViewController controller;

void setup(VoidCallback onClose) {
  controller = WebViewController()
    ..setJavaScriptMode(JavaScriptMode.unrestricted)
    ..setNavigationDelegate(NavigationDelegate(
      onNavigationRequest: (req) {
        if (isOurs(req.url)) return NavigationDecision.navigate;
        if (req.url.startsWith('https://')) launchUrl(Uri.parse(req.url)); // url_launcher: 외부 브라우저
        return NavigationDecision.prevent;
      },
    ))
    ..addJavaScriptChannel('AppBridge', onMessageReceived: (m) async {
      if (!isOurs(await controller.currentUrl())) return;          // 메시지에 출처가 없어 현재 주소로 확인
      if (m.message == 'close') onClose();                          // 정해 둔 메시지만
    })
    ..loadRequest(Uri.parse('$origin/faq'));
}
```

### kotlin

✗ 취약 코드

```kotlin
class WebActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val webView = WebView(this).also { setContentView(it) }
        webView.settings.javaScriptEnabled = true
        webView.settings.allowFileAccess = true                         // targetSdk 29 이하는 기본 true
        webView.settings.allowUniversalAccessFromFileURLs = true        // file:// 페이지가 아무 출처나 읽음
        webView.addJavascriptInterface(object {
            @JavascriptInterface fun getToken(): String = TokenStore.read(this@WebActivity) // 모든 프레임에서 호출 가능
        }, "Android")
        webView.loadUrl(intent.getStringExtra("url")!!)                // 다른 앱이 넘긴 주소
    }
}
```

✓ 안전 코드

```kotlin
private const val ORIGIN = "https://help.example.com"

class HelpActivity : AppCompatActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val webView = WebView(this).also { setContentView(it) }
        webView.settings.apply {
            javaScriptEnabled = true
            allowFileAccess = false              // 앱 안 파일은 WebViewAssetLoader(https 가상 주소)로
            allowContentAccess = false
        }
        webView.webViewClient = object : WebViewClientCompat() {
            override fun shouldOverrideUrlLoading(view: WebView, request: WebResourceRequest): Boolean {
                val u = request.url
                if (u.scheme == "https" && u.host == "help.example.com") return false
                if (u.scheme == "https") startActivity(Intent(Intent.ACTION_VIEW, u)) // 외부 브라우저
                return true
            }
        }
        if (WebViewFeature.isFeatureSupported(WebViewFeature.WEB_MESSAGE_LISTENER)) {
            // 허용 출처의 프레임에만 window.appBridge 가 생김
            WebViewCompat.addWebMessageListener(webView, "appBridge", setOf(ORIGIN)) { _, message, _, isMainFrame, _ ->
                if (isMainFrame && message.data == "close") finish()          // 정해 둔 메시지만
            }
        }
        webView.loadUrl("$ORIGIN/faq")
    }
}
```
