<!-- generated from white-security issues; do not edit -->
# WS-MOB-006 코드 예시 — Flutter

✗ 취약 코드 (vulnerable example — do not use)

```dart
import 'dart:io';
import 'package:http/io_client.dart';

// 개발 프록시 때문에 넣은 설정이 운영 빌드까지 감
final client = IOClient(HttpClient()..badCertificateCallback = (cert, host, port) => true);

Future<void> login(String id, String pw) =>
    client.post(Uri.parse('http://api.example.com/login'), body: {'id': id, 'pw': pw}); // 평문
```

✓ 안전 코드 (safe example)

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
