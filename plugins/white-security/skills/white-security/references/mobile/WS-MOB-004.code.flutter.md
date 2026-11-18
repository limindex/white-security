<!-- generated from white-security issues; do not edit -->
# WS-MOB-004 코드 예시 — Flutter

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
      // 메시지에 출처가 없어 현재 주소로 확인 — currentUrl()은 메인 프레임 주소라 iframe 이 보낸 것과 구별 못 함
      // → 브리지 페이지는 CSP frame-src 'none' 등으로 외부 iframe 을 넣지 않음
      if (!isOurs(await controller.currentUrl())) return;
      if (m.message == 'close') onClose();                          // 정해 둔 메시지만
    })
    ..loadRequest(Uri.parse('$origin/faq'));
}
```
