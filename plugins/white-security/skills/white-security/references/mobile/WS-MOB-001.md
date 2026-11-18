<!-- generated from white-security issues; do not edit -->
# WS-MOB-001 Hardcoded Secrets in App Bundle (앱 번들 속 비밀키)

## 탐지 규칙

- **찾는 곳**: `EXPO_PUBLIC_`, `sk-proj-`, `api.openai.com`, `api.anthropic.com`, `String.fromEnvironment(`, `--dart-define`, `react-native-config`, `buildConfigField`, 그 밖에 앱 쪽 `.env`·`app.json`·`eas.json`·`Info.plist`·`strings.xml` 속 키 이름(SECRET·PRIVATE·service_role), 앱이 직접 부르는 유료 API 주소.
- **검사 1 — 앱에 든 비밀키**: 지적할 때: 앱 코드나 `EXPO_PUBLIC_`·`--dart-define`·`BuildConfig` 값에 AI·결제·메일·DB 관리용 비밀키가 있는 경우, Supabase `service_role`·Firebase Admin 키, 앱 코드 속 서명·암호화 키. / 지적하지 않을 때: Supabase anon(publishable) 키·Firebase 웹 설정·결제 공개 키처럼 공개를 전제로 만든 키, API 주소.
- **검사 2 — 열린 대리 서버**: 지적할 때: 키를 서버로 옮겼지만 로그인 확인·호출 한도 없이 앱이 보낸 본문(모델·길이·주소)을 그대로 외부 API로 넘기는 경우. / 지적하지 않을 때: 서버가 로그인 사용자를 확인하고 한도를 두며 모델·길이를 고정하는 경우.
- **등급**: 위험: 유료·관리용 비밀키가 앱에 들어 있음, 인증 없는 대리 서버. / 주의: 공개용 키에 앱 서명·번들 ID 제한이 없음, 대리 서버에 한도 없음. / 참고: 난독화를 비밀키 보호 수단으로 설명함.
- **보고 예**: `[위험] WS-MOB-001 app/ask.tsx:12 — EXPO_PUBLIC_OPENAI_API_KEY 가 앱 번들에 그대로 들어감 → 서버 /api/ask 로 옮기고 키 재발급`
- **고칠 방법**: 비밀키 호출을 로그인·한도가 있는 서버 API로 옮기고, 배포됐던 키는 폐기·재발급해야 한다.

## 점검 항목

- 앱 코드·번들·`EXPO_PUBLIC_`·`--dart-define`·`BuildConfig`에 비밀키가 없어야 한다.
- 비밀키가 필요한 호출은 내 서버가 대신하고, 서버는 로그인과 사용자별 호출 한도를 확인해야 한다.
- 서버 대리 호출은 모델·길이·대상 주소를 서버가 정하고, 앱이 보낸 요청을 그대로 넘기지 않아야 한다.
- 앱에 꼭 들어가는 공개용 키는 앱 서명·번들 ID·API 범위로 제한해야 한다.
- 한 번이라도 배포된 비밀키는 폐기·재발급해야 한다.

## 검토 주의

`EXPO_PUBLIC_`·`NEXT_PUBLIC_`·`VITE_` 값과 `String.fromEnvironment` 상수는 빌드 때 앱에 글자 그대로 들어가므로 "환경 변수라 안전"이라고 판단하지 않아야 한다. 공개를 전제로 한 키(Supabase anon·Firebase 설정)는 키보다 RLS·보안 규칙(WS-BAAS-001·002)이 핵심이다. 서버 쪽 비밀값 관리는 WS-SEC-001, AI 호출 비용 한도는 WS-LLM-004에서 다룬다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-MOB-001.code.expo.md` (Expo) · `WS-MOB-001.code.flutter.md` (Flutter) · `WS-MOB-001.code.nextjs.md` (Next.js)

기본 심각도: 위험 · CWE-798 · CWE-321 · CWE-200
