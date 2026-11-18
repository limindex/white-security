<!-- generated from white-security issues; do not edit -->
# WS-MOB-004 코드 예시 — Kotlin

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
        } // 지원하지 않는 오래된 WebView 에서는 브리지를 열지 않음(addJavascriptInterface 로 대체하지 않음)
        webView.loadUrl("$ORIGIN/faq")
    }
}
```
