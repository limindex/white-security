---
id: WS-MOB-003
title: Deep Link Hijacking · Unvalidated Deep Links (딥링크 가로채기·검증 없는 딥링크)
slug: deep-link-hijacking
group: mobile
severity: high
grade_hint: "링크로 토큰 전달, 링크만으로 송금·삭제"
always_on: "`Linking.getInitialURL(` `onOpenURL` → 딥링크로 토큰을 주고받지 않고, 링크 값은 검증한 뒤 화면 이동에만 써야 한다."
site_only: false
depth: brief
languages: [expo, flutter, express]
maps:
  owasp2025: [A01, A07]
  api2023: [API2]
  llm2026: []
  agentic2026: []
  owasp_mobile2024: [M4, M8, M3]
  asvs5: [V14.2.1, V6.5.1, V6.5.5, V10.4.6, V2.2.1]
  cwe: [CWE-939, CWE-20, CWE-601, CWE-598]
  kr_guide: ["입력데이터 검증 및 표현-7", "입력데이터 검증 및 표현-15"]
  kr_ciip: [IA, PV]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [React Native·Expo, Flutter, 메일·문자 로그인 링크, 비밀번호 재설정 링크, 간편 로그인(OAuth) 콜백, 푸시·광고 딥링크로 화면을 여는 앱]
severity_reason: "영향: 링크 속 로그인 토큰 탈취로 계정 장악, 확인 없는 송금·삭제 실행, 피싱 페이지를 앱 화면처럼 표시 · 악용: 같은 스킴을 등록한 앱 설치 또는 조작한 링크 한 번 누르기"
sources:
  - title: "OWASP Mobile Top 10 2024 - M4: Insufficient Input/Output Validation"
    url: https://owasp.org/www-project-mobile-top-10/2023-risks/m4-insufficient-input-output-validation
    year: 2024
  - title: "Android Developers - Unsafe use of deep links"
    url: https://developer.android.com/privacy-and-security/risks/unsafe-use-of-deeplinks
    year: 2026
  - title: "Android Developers - Verify Android App Links"
    url: https://developer.android.com/training/app-links/verify-applinks
    year: 2026
  - title: "Apple Developer - Defining a custom URL scheme for your app"
    url: https://developer.apple.com/documentation/xcode/defining-a-custom-url-scheme-for-your-app
    year: 2026
  - title: "Apple Developer - Supporting associated domains"
    url: https://developer.apple.com/documentation/xcode/supporting-associated-domains
    year: 2026
  - title: "React Native Docs - Security (Deep linking, OAuth2 and PKCE)"
    url: https://reactnative.dev/docs/security
    year: 2026
  - title: "RFC 8252 - OAuth 2.0 for Native Apps"
    url: https://www.rfc-editor.org/rfc/rfc8252
    year: 2017
---

## 요약

`myapp://` 같은 사용자 지정 스킴 링크에 로그인 토큰을 실어 보내거나, 딥링크로 받은 주소·값을 확인 없이 화면 이동·송금·WebView 로드에 쓰는 문제(진단가이드 이름: 신뢰되지 않는 URL 주소로 자동접속 연결). 같은 스킴은 다른 앱도 등록할 수 있어(Apple·Android 문서) 링크 가로채기가 가능한 구조.

## 대응 방법

```text
앱이 받는 딥링크(사용자 지정 스킴·유니버설 링크·App Links·intent-filter)와 그 처리 코드를 찾아, 링크로 들어오는 값과 그 값으로 하는 일을 표로 보여 줘.
링크로 토큰을 보내는 곳은 한 번만 쓰는 짧은 코드로 바꾸고, 앱이 서버에서 교환하게(PKCE처럼 요청한 기기만 교환 가능) 고쳐 줘.
로그인·결제 링크는 https 유니버설 링크·App Links(autoVerify, apple-app-site-association·assetlinks.json)로 옮기고, 처리 코드는 정해진 경로·값 형식만 받게 해 줘.
```

- `adb shell pm get-app-links <패키지>` 결과에서 우리 도메인이 `verified`여야 한다.
- 링크로 연 화면에서 송금·삭제·권한 변경은 사용자의 확인 없이 실행되지 않아야 한다.

## 점검 항목

- 딥링크에 로그인 토큰·세션·비밀번호 재설정 토큰을 그대로 담지 않아야 한다.
- 링크 코드는 한 번만 쓰이고 몇 분 안에 만료되며, 요청한 기기에서만 교환되어야 한다.
- 로그인·결제·재설정 링크는 도메인 확인을 거친 유니버설 링크·App Links로 보내야 한다.
- 딥링크 처리 코드는 호스트·경로·값 형식을 허용 목록으로 확인해야 한다.
- 딥링크만으로 송금·삭제·설정 변경을 실행하지 않고, 앱 안 확인 화면과 서버 권한 확인을 거쳐야 한다.
- 딥링크로 받은 주소를 WebView에 바로 열지 않아야 한다(WS-MOB-004).

## 탐지 규칙

- **찾는 곳**: `Linking.getInitialURL(`, `Linking.addEventListener(`, `uriLinkStream`, `onOpenURL`, `<intent-filter`, `android:autoVerify`, `apple-app-site-association`, `assetlinks.json`, 그 밖에 app.json의 scheme·intentFilters·associatedDomains, 로그인 메일·재설정 메일을 만드는 서버 코드, OAuth redirect_uri.
- **검사 1 — 링크 속 비밀값**: 지적할 때: 서버가 만드는 링크(`myapp://…`·`https://…`)에 액세스·리프레시 토큰·세션 ID·오래 쓰는 재설정 토큰을 담는 경우, 앱이 링크의 토큰을 그대로 저장해 로그인하는 경우, PKCE 없는 OAuth 콜백을 사용자 지정 스킴으로 받는 경우. / 지적하지 않을 때: 한 번만 쓰고 몇 분 안에 만료되는 코드를 서버에서 교환하는 경우, PKCE를 쓰는 OAuth 콜백.
- **검사 2 — 검증 없는 처리**: 지적할 때: 링크의 호스트·경로를 확인하지 않고 화면 이름·주소를 그대로 쓰는 경우, 링크 값으로 송금·삭제·설정 변경을 바로 실행하는 경우, 링크의 URL을 WebView·브라우저에 그대로 여는 경우. / 지적하지 않을 때: 허용한 경로·정규식에 맞는 값만 받고, 중요한 동작은 확인 화면을 거치는 경우.
- **검사 3 — 링크 소유 확인**: 지적할 때: 로그인·결제 링크를 사용자 지정 스킴으로만 받는 경우, https intent-filter에 `android:autoVerify="true"`가 없거나 `assetlinks.json`·`apple-app-site-association`이 없는 경우. / 지적하지 않을 때: 상품 화면 열기처럼 가로채여도 해가 없는 링크만 스킴으로 받는 경우.
- **등급**: 위험: 링크로 토큰 전달, 링크만으로 송금·삭제 실행. / 주의: 호스트·경로 검증 없음, 로그인 링크가 App Links·유니버설 링크 확인 없이 동작. / 참고: 링크 코드 만료 시간이 길거나 재사용 가능.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-MOB-003 server/auth.js:31 — 로그인 메일 링크 myapp://login?token=<30일 JWT> 로 토큰 전달 → 10분·1회용 code 로 바꾸고 앱이 code_verifier 와 함께 교환`
- **검토 주의**: Android는 같은 링크를 여러 앱이 등록할 수 있고, 12 이상에서는 확인되지 않은 웹 링크를 앱으로 바로 열지 않는다(Android 문서). Apple도 같은 스킴을 다른 앱이 등록하면 어느 앱이 열릴지 정해져 있지 않다고 밝힌다. OAuth 앱 로그인은 PKCE가 기본이며 서버 쪽 redirect_uri 검증은 WS-OAUTH-001에서 다룬다. 서버의 권한 확인 자체는 WS-AUTH-001·WS-MOB-005에서 다룬다.
- **고칠 방법**: 링크에는 1회용 짧은 코드만 담아 요청한 기기에서 서버로 교환하게 하고, 중요한 링크는 App Links·유니버설 링크로 옮기며, 처리 코드는 허용 경로·형식만 받아야 한다.

## 코드 예시

### expo

✗ 취약 코드

```ts
import * as Linking from "expo-linking";
import * as SecureStore from "expo-secure-store";

// 메일 속 "앱에서 로그인" 링크: myapp://login?token=<30일짜리 JWT>
Linking.addEventListener("url", async ({ url }) => {
  const { hostname, queryParams } = Linking.parse(url);
  if (hostname === "login") await SecureStore.setItemAsync("access_token", String(queryParams?.token)); // 가로채면 끝
  if (hostname === "web") router.push({ pathname: "/web", params: { url: String(queryParams?.url) } });  // 아무 주소나 WebView 로
  if (hostname === "transfer") await api.transfer(String(queryParams?.to), Number(queryParams?.amount)); // 확인 없이 실행
});
```

✓ 안전 코드

```ts
import * as Crypto from "expo-crypto";
import * as Linking from "expo-linking";
import * as SecureStore from "expo-secure-store";
// app.json: ios.associatedDomains ["applinks:app.example.com"],
//           android.intentFilters [{ action: "VIEW", autoVerify: true, data: [{ scheme: "https", host: "app.example.com", pathPrefix: "/magic-login" }], category: ["BROWSABLE", "DEFAULT"] }]

export async function requestMagicLink(email: string) {
  const verifier = Crypto.randomUUID() + Crypto.randomUUID();                // 이 기기에만 남는 값
  await SecureStore.setItemAsync("magic_verifier", verifier);
  const challenge = (await Crypto.digestStringAsync(Crypto.CryptoDigestAlgorithm.SHA256, verifier,
    { encoding: Crypto.CryptoEncoding.BASE64 })).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
  await api.post("/auth/magic/start", { email, codeChallenge: challenge });
}

Linking.addEventListener("url", async ({ url }) => {
  const { scheme, hostname, path, queryParams } = Linking.parse(url);
  if (scheme === "https" && hostname === "app.example.com" && path === "magic-login") {
    const code = String(queryParams?.code ?? "");
    const verifier = await SecureStore.getItemAsync("magic_verifier");
    if (!/^[A-Za-z0-9_-]{43}$/.test(code) || !verifier) return;               // 형식이 다르거나 이 기기가 요청한 게 아니면 무시
    const { accessToken } = await api.post("/auth/magic/exchange", { code, codeVerifier: verifier });
    await SecureStore.setItemAsync("access_token", accessToken);
    await SecureStore.deleteItemAsync("magic_verifier");
  } else if (scheme === "myapp" && hostname === "product" && /^\d{1,10}$/.test(String(queryParams?.id))) {
    router.push(`/product/${queryParams?.id}`);                              // 가로채여도 해가 없는 화면 열기만
  }
});
```

### flutter

✗ 취약 코드

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

✓ 안전 코드

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

### express

✗ 취약 코드

```js
app.post("/auth/magic/start", async (req, res) => {
  const user = await db.users.findByEmail(req.body.email);
  if (user) {
    const token = jwt.sign({ sub: user.id }, process.env.JWT_SECRET, { expiresIn: "30d" });
    await sendMail(user.email, `myapp://login?token=${token}`);   // 같은 스킴을 등록한 앱이 받아 가면 계정 탈취
  }
  res.sendStatus(204);
});
```

✓ 안전 코드

```js
import crypto from "node:crypto";
const sha256 = (s) => crypto.createHash("sha256").update(s).digest("base64url");

app.post("/auth/magic/start", magicLimiter, async (req, res) => {        // 발송 한도(WS-SMS-001)
  const { email, codeChallenge } = req.body;
  const user = await db.users.findByEmail(email);
  if (user && /^[A-Za-z0-9_-]{43}$/.test(codeChallenge ?? "")) {
    const code = crypto.randomBytes(32).toString("base64url");             // 43자, 1회용
    await db.magicCodes.insert({ codeHash: sha256(code), userId: user.id, codeChallenge,
                                 expiresAt: new Date(Date.now() + 10 * 60_000) });
    await sendMail(user.email, `https://app.example.com/magic-login?code=${code}`); // App Links·유니버설 링크
  }
  res.sendStatus(204);                                                     // 계정 유무와 관계없이 같은 응답
});

app.post("/auth/magic/exchange", async (req, res) => {
  const { code, codeVerifier } = req.body;
  if (typeof code !== "string" || typeof codeVerifier !== "string") return res.sendStatus(400);
  // used_at IS NULL AND expires_at > now() 인 행만 used_at 을 채우고 돌려줌(한 번만 성공)
  const row = await db.magicCodes.consume(sha256(code));
  if (!row) return res.sendStatus(400);
  const a = Buffer.from(sha256(codeVerifier)), b = Buffer.from(row.codeChallenge);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return res.sendStatus(400); // 링크만 가로챈 기기는 실패
  res.json({ accessToken: issueAccessToken(row.userId) });                // 짧은 수명 토큰(WS-SESS-001)
});
```
