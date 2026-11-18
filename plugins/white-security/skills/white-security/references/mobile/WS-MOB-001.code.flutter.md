<!-- generated from white-security issues; do not edit -->
# WS-MOB-001 코드 예시 — Flutter

✗ 취약 코드 (vulnerable example — do not use)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

// const 문자열도, --dart-define 으로 넣은 값도 앱 바이너리에 그대로 남음
const openAiKey = String.fromEnvironment('OPENAI_API_KEY');

Future<String> ask(String q) async {
  final res = await http.post(
    Uri.parse('https://api.openai.com/v1/responses'),
    headers: {'Authorization': 'Bearer $openAiKey', 'Content-Type': 'application/json'},
    body: jsonEncode({'model': 'gpt-4.1-mini', 'input': q}),
  );
  return res.body;
}
```

✓ 안전 코드 (safe example)

```dart
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

const apiBase = 'https://api.example.com';   // 공개해도 되는 주소만
const storage = FlutterSecureStorage();

Future<String> ask(String q) async {
  final token = await storage.read(key: 'access_token');
  final res = await http.post(
    Uri.parse('$apiBase/api/ask'),
    headers: {'Authorization': 'Bearer $token', 'Content-Type': 'application/json'},
    body: jsonEncode({'question': q}),       // 키·모델은 서버가 정함
  );
  if (res.statusCode != 200) throw Exception('ask failed: ${res.statusCode}');
  return (jsonDecode(res.body) as Map<String, dynamic>)['answer'] as String;
}
```
