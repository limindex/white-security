<!-- generated from white-security issues; do not edit -->
# WS-RED-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
"use server";
import { redirect } from "next/navigation";

export async function login(formData: FormData) {
  await signIn(formData);
  redirect(String(formData.get("next") ?? "/")); // 다른 사이트 주소도 그대로 이동
}
```

✓ 안전 코드 (safe example)

```ts
"use server";
import { redirect } from "next/navigation";

const ORIGIN = "https://app.example.com";

function safeNext(value: unknown): string {
  if (typeof value !== "string" || !value.startsWith("/")) return "/";
  const u = new URL(value, ORIGIN);                          // 브라우저와 같은 방식으로 해석
  const path = u.pathname + u.search + u.hash;
  return u.origin === ORIGIN && !path.startsWith("//") ? path : "/";
}

export async function login(formData: FormData) {
  await signIn(formData);
  redirect(safeNext(formData.get("next")));                  // 내 사이트 안 경로만
}
```
