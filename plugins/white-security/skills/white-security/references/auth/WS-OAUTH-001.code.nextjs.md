<!-- generated from white-security issues; do not edit -->
# WS-OAUTH-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// auth.ts (Auth.js)
export const { handlers, auth } = NextAuth({
  providers: [
    Google({ allowDangerousEmailAccountLinking: true, checks: ["none"] }),  // 이메일로 자동 연결, state·PKCE 끔
  ],
  callbacks: {
    redirect: ({ url }) => url,             // 로그인 뒤 아무 주소로나 이동
  },
});
```

✓ 안전 코드 (safe example)

```ts
// auth.ts (Auth.js)
export const { handlers, auth } = NextAuth({
  adapter: PrismaAdapter(prisma),            // 계정은 (provider, providerAccountId)로 연결
  providers: [Google],                       // 기본 checks(PKCE) 그대로, 끄지 않기
  // redirect 콜백은 덮어쓰지 않는다(기본값이 같은 사이트 안 주소만 허용)
});
// 제공자 콘솔의 Redirect URI 는 https://example.com/api/auth/callback/google 하나만 정확히 등록
```
