<!-- generated from white-security issues; do not edit -->
# WS-MOB-006 코드 예시 — Kotlin

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
// 인증서 고정이 필요하면 network_security_config 의 <pin-set> 에 예비 키를 함께 넣음(Android 문서)

webView.webViewClient = object : WebViewClient() {
    override fun onReceivedSslError(view: WebView, handler: SslErrorHandler, error: SslError) {
        handler.cancel()      // 인증서 오류면 페이지를 열지 않음
    }
}
```
