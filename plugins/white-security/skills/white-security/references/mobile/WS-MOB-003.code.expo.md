<!-- generated from white-security issues; do not edit -->
# WS-MOB-003 코드 예시 — Expo

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
