---
id: WS-MOB-001
title: Hardcoded Secrets in App Bundle (앱 번들 속 비밀키)
slug: hardcoded-secrets-app-bundle
group: mobile
severity: high
site_only: false
depth: brief
languages: [expo, flutter, nextjs]
maps:
  owasp2025: [A02, A07]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  owasp_mobile2024: [M1, M7]
  asvs5: [V13.3.1, V13.3.2]
  cwe: [CWE-798, CWE-321, CWE-200]
  kr_guide: ["보안기능-6"]
  kr_ciip: [IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [React Native·Expo, Flutter, 앱에서 AI·지도·결제·메일 같은 유료 API를 부르는 기능, 앱 전용 백엔드(Next.js·Supabase Edge Functions 등)]
severity_reason: "영향: 키 주인 명의로 유료 API 무제한 호출·데이터 접근, 키를 바꿀 때까지 계속 · 악용: 스토어에서 받은 앱 파일을 풀어 문자열 검색만 하면 됨"
sources:
  - title: "OWASP Mobile Top 10 2024 - M1: Improper Credential Usage"
    url: https://owasp.org/www-project-mobile-top-10/2023-risks/m1-improper-credential-usage
    year: 2024
  - title: "OWASP MASWE-0004: Sensitive Data Hardcoded in the App Package (MASVS v2.1)"
    url: https://mas.owasp.org/MASWE/MASVS-STORAGE/MASWE-0004/
    year: 2026
  - title: "React Native Docs - Security (Storing Sensitive Info)"
    url: https://reactnative.dev/docs/security
    year: 2026
  - title: "Expo Docs - Environment variables in Expo (EXPO_PUBLIC_)"
    url: https://docs.expo.dev/guides/environment-variables/
    year: 2026
  - title: "Flutter Docs - Obfuscate Dart code (Limitations and warnings)"
    url: https://docs.flutter.dev/deployment/obfuscate
    year: 2026
  - title: "Android Developers - Hardcoded cryptographic secrets"
    url: https://developer.android.com/privacy-and-security/risks/hardcoded-cryptographic-secrets
    year: 2026
---

## 요약

AI·지도·결제·메일 API의 비밀키를 앱 코드나 `EXPO_PUBLIC_`·`--dart-define` 값으로 넣어, 스토어에서 받은 앱 파일을 풀기만 하면 키가 그대로 보이는 문제(진단가이드 이름: 하드코드된 중요정보). 난독화(코드 이름을 알아보기 어렵게 바꾸는 처리)는 문자열을 숨기지 못하므로, 비밀키를 쓰는 호출은 로그인 확인과 사용량 한도를 둔 내 서버를 거쳐야 함.

## 대응 방법

```text
앱 코드·.env·app.json·eas.json·--dart-define·BuildConfig 에서 API 키·토큰·비밀번호를 찾아, 공개해도 되는 키(Supabase anon·Firebase 설정·결제 공개 키)와 비밀키로 나눠 표로 보여 줘.
비밀키를 쓰는 호출은 서버 API(로그인 확인·사용자별 한도·모델과 길이 고정)로 옮기고, 앱은 그 서버만 부르게 고쳐 줘.
앱에 남는 공개용 키는 콘솔에서 앱 패키지 이름·서명·번들 ID로 사용처를 제한해 줘.
```

- 빌드한 앱 파일(APK·IPA·JS 번들)에서 `sk-`·`service_role` 같은 문자열을 검색해도 나오지 않아야 한다.
- 이미 배포된 앱에 들어 있던 비밀키는 폐기하고 새로 발급해야 한다.

## 점검 항목

- 앱 코드·번들·`EXPO_PUBLIC_`·`--dart-define`·`BuildConfig`에 비밀키가 없어야 한다.
- 비밀키가 필요한 호출은 내 서버가 대신하고, 서버는 로그인과 사용자별 호출 한도를 확인해야 한다.
- 서버 대리 호출은 모델·길이·대상 주소를 서버가 정하고, 앱이 보낸 요청을 그대로 넘기지 않아야 한다.
- 앱에 꼭 들어가는 공개용 키는 앱 서명·번들 ID·API 범위로 제한해야 한다.
- 한 번이라도 배포된 비밀키는 폐기·재발급해야 한다.

## 탐지 규칙

- **찾는 곳**: `EXPO_PUBLIC_`, `sk-proj-`, `api.openai.com`, `api.anthropic.com`, `String.fromEnvironment(`, `--dart-define`, `react-native-config`, `buildConfigField`, 그 밖에 앱 쪽 `.env`·`app.json`·`eas.json`·`Info.plist`·`strings.xml` 속 키 이름(SECRET·PRIVATE·service_role), 앱이 직접 부르는 유료 API 주소.
- **검사 1 — 앱에 든 비밀키**: 지적할 때: 앱 코드나 `EXPO_PUBLIC_`·`--dart-define`·`BuildConfig` 값에 AI·결제·메일·DB 관리용 비밀키가 있는 경우, Supabase `service_role`·Firebase Admin 키, 앱 코드 속 서명·암호화 키. / 지적하지 않을 때: Supabase anon(publishable) 키·Firebase 웹 설정·결제 공개 키처럼 공개를 전제로 만든 키, API 주소.
- **검사 2 — 열린 대리 서버**: 지적할 때: 키를 서버로 옮겼지만 로그인 확인·호출 한도 없이 앱이 보낸 본문(모델·길이·주소)을 그대로 외부 API로 넘기는 경우. / 지적하지 않을 때: 서버가 로그인 사용자를 확인하고 한도를 두며 모델·길이를 고정하는 경우.
- **등급**: 위험: 유료·관리용 비밀키가 앱에 들어 있음, 인증 없는 대리 서버. / 주의: 공개용 키에 앱 서명·번들 ID 제한이 없음, 대리 서버에 한도 없음. / 참고: 난독화를 비밀키 보호 수단으로 설명함.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-MOB-001 app/ask.tsx:12 — EXPO_PUBLIC_OPENAI_API_KEY 가 앱 번들에 그대로 들어감 → 서버 /api/ask 로 옮기고 키 재발급`
- **검토 주의**: `EXPO_PUBLIC_`·`NEXT_PUBLIC_`·`VITE_` 값과 `String.fromEnvironment` 상수는 빌드 때 앱에 글자 그대로 들어가므로 "환경 변수라 안전"이라고 판단하지 않아야 한다. 공개를 전제로 한 키(Supabase anon·Firebase 설정)는 키보다 RLS·보안 규칙(WS-BAAS-001·002)이 핵심이다. 서버 쪽 비밀값 관리는 WS-SEC-001, AI 호출 비용 한도는 WS-LLM-004에서 다룬다.
- **고칠 방법**: 비밀키 호출을 로그인·한도가 있는 서버 API로 옮기고, 배포됐던 키는 폐기·재발급해야 한다.

## 코드 예시

### expo

✗ 취약 코드

```ts
// .env: EXPO_PUBLIC_OPENAI_API_KEY=sk-proj-...  ← 빌드 때 앱 번들에 글자 그대로 들어감
export async function ask(question: string) {
  const res = await fetch("https://api.openai.com/v1/responses", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${process.env.EXPO_PUBLIC_OPENAI_API_KEY}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ model: "gpt-4.1-mini", input: question }),
  });
  return res.json();
}
```

✓ 안전 코드

```ts
import * as SecureStore from "expo-secure-store";

// EXPO_PUBLIC_ 에는 공개해도 되는 값(내 API 주소·anon 키)만 둔다
const API = process.env.EXPO_PUBLIC_API_URL;

export async function ask(question: string) {
  const token = await SecureStore.getItemAsync("access_token"); // 우리 서비스 로그인 토큰
  const res = await fetch(`${API}/api/ask`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
    body: JSON.stringify({ question }),                         // 키·모델은 서버가 정함
  });
  if (!res.ok) throw new Error(`ask failed: ${res.status}`);
  return (await res.json()) as { answer: string };
}
```

### flutter

✗ 취약 코드

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

✓ 안전 코드

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

### nextjs

✗ 취약 코드

```ts
// app/api/ask/route.ts — 키는 서버로 옮겼지만 누구나 아무 요청이나 대신 보낼 수 있음
export async function POST(req: Request) {
  const body = await req.text();                       // 모델·길이·내용을 앱이 정함
  const r = await fetch("https://api.openai.com/v1/responses", {
    method: "POST",
    headers: { Authorization: `Bearer ${process.env.OPENAI_API_KEY}`, "Content-Type": "application/json" },
    body,
  });
  return new Response(r.body, { status: r.status });   // 로그인·한도 없음
}
```

✓ 안전 코드

```ts
// app/api/ask/route.ts
import OpenAI from "openai";
import { verifyAccessToken } from "@/lib/auth";        // 우리 로그인 토큰 확인(WS-AUTH-002)
import { rateLimit } from "@/lib/rate-limit";          // 사용자별 한도(WS-LLM-004)

const openai = new OpenAI({ apiKey: process.env.OPENAI_API_KEY }); // 서버 환경 변수, NEXT_PUBLIC_ 금지

export async function POST(req: Request) {
  const user = await verifyAccessToken(req.headers.get("authorization"));
  if (!user) return new Response(null, { status: 401 });
  if (!(await rateLimit(`ask:${user.id}`, { max: 20, windowSec: 3600 }))) return new Response(null, { status: 429 });

  const { question } = await req.json().catch(() => ({}));
  if (typeof question !== "string" || question.length > 2000) return new Response(null, { status: 400 });

  const r = await openai.responses.create({
    model: "gpt-4.1-mini",          // 모델·출력 길이는 서버가 고정
    input: question,
    max_output_tokens: 800,
  });
  return Response.json({ answer: r.output_text });
}
```
