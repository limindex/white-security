<!-- generated from white-security issues; do not edit -->
# WS-MOB-002 코드 예시 — Flutter

✗ 취약 코드 (vulnerable example — do not use)

```dart
import 'package:shared_preferences/shared_preferences.dart';

Future<void> onLogin(String accessToken, String refreshToken) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('access_token', accessToken);   // Android XML·iOS plist 에 평문
  await prefs.setString('refresh_token', refreshToken);
  print('refresh=$refreshToken');                        // 로그에 토큰
}
```

✓ 안전 코드 (safe example)

```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

const storage = FlutterSecureStorage(
  iOptions: IOSOptions(accessibility: KeychainAccessibility.unlocked_this_device), // Keychain
  // Android 기본값: Keystore 로 감싼 키 + AES-GCM (11.x)
);

Future<void> onLogin(String accessToken, String refreshToken) async {
  await storage.write(key: 'access_token', value: accessToken);
  await storage.write(key: 'refresh_token', value: refreshToken);
  final prefs = await SharedPreferences.getInstance();
  await prefs.remove('access_token');                    // 예전 평문 값 정리
  await prefs.remove('refresh_token');
}

Future<void> onLogout() => storage.deleteAll();
// AndroidManifest.xml: <application android:allowBackup="false"> — 백업 복원 시 키 오류도 막음(패키지 문서)
```
