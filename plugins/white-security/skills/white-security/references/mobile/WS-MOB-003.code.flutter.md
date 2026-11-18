<!-- generated from white-security issues; do not edit -->
# WS-MOB-003 코드 예시 — Flutter

✗ 취약 코드 (vulnerable example — do not use)

```dart
import 'package:app_links/app_links.dart';

// myapp://login?token=...  /  myapp://open?url=https://...
void listenLinks() {
  AppLinks().uriLinkStream.listen((uri) async {
    if (uri.host == 'login') await storage.write(key: 'access_token', value: uri.queryParameters['token']);
    if (uri.host == 'open') navigator.pushNamed('/web', arguments: uri.queryParameters['url']); // 검증 없음
    if (uri.host == 'delete-account') await api.deleteAccount();                              // 링크만으로 탈퇴
  });
}
```

✓ 안전 코드 (safe example)

```dart
import 'package:app_links/app_links.dart';
// AndroidManifest.xml: <intent-filter android:autoVerify="true"> … <data android:scheme="https" android:host="app.example.com" android:pathPrefix="/magic-login"/>
// iOS: Associated Domains 에 applinks:app.example.com, 서버에 /.well-known/apple-app-site-association

final _code = RegExp(r'^[A-Za-z0-9_-]{43}$');

void listenLinks() {
  AppLinks().uriLinkStream.listen((uri) async {
    final ours = uri.scheme == 'https' && uri.host == 'app.example.com';
    if (ours && uri.path == '/magic-login') {
      final code = uri.queryParameters['code'] ?? '';
      final verifier = await storage.read(key: 'magic_verifier');     // 로그인 요청 때 이 기기에 저장한 값
      if (!_code.hasMatch(code) || verifier == null) return;
      final token = await api.exchangeMagicCode(code: code, codeVerifier: verifier);
      await storage.write(key: 'access_token', value: token);
      await storage.delete(key: 'magic_verifier');
    } else if (uri.scheme == 'myapp' && uri.host == 'product') {
      final id = uri.queryParameters['id'] ?? '';
      if (RegExp(r'^\d{1,10}$').hasMatch(id)) navigator.pushNamed('/product', arguments: id); // 화면 열기만
    }
  });
}
```
