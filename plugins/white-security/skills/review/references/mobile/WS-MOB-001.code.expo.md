<!-- generated from white-security issues; do not edit -->
# WS-MOB-001 코드 예시 — Expo

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
