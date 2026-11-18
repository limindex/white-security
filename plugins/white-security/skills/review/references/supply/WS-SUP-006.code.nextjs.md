<!-- generated from white-security issues; do not edit -->
# WS-SUP-006 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// 같은 관리자 확인을 라우트마다 복사: 한 곳만 고쳐지고 나머지는 옛 조건 그대로
// app/api/admin/users/route.ts — 나중에 고친 조건
export async function GET() {
  const s = await auth();
  if (s?.user?.role !== "admin" || !s.user.emailVerified) return new Response(null, { status: 403 });
  // ...
}

// app/api/admin/export/route.ts — 복사해 둔 옛 조건: 옛 필드라 권한을 뺀 계정도 통과
export async function GET() {
  const s = await auth();
  if (!s?.user?.isAdmin) return new Response(null, { status: 403 });
  // ...
}
```

✓ 안전 코드 (safe example)

```ts
// lib/require-admin.ts — 보안 판단은 한 곳에
import "server-only";
import { auth } from "@/auth";

export async function requireAdmin() {
  const user = (await auth())?.user;
  return user && user.role === "admin" && user.emailVerified && !user.suspended ? user : null;
}

// app/api/admin/export/route.ts — 모든 관리자 라우트가 같은 함수를 부름
export async function GET() {
  const admin = await requireAdmin();
  if (!admin) return new Response(null, { status: 403 });
  // ...
}
```
