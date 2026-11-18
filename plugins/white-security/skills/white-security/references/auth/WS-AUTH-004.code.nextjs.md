<!-- generated from white-security issues; do not edit -->
# WS-AUTH-004 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```tsx
// app/admin/page.tsx — 클라이언트에서만 역할을 본다
"use client";
import { useSession } from "next-auth/react";

export default function AdminPage() {
  const { data: session } = useSession();
  if (session?.user?.role !== "admin") return <p>권한 없음</p>;
  return <UserTable />;  // 아래 API 는 누구나 부를 수 있다
}

// app/api/admin/users/route.ts
export async function GET() {
  return Response.json(await db.user.findMany());
}
```

✓ 안전 코드 (safe example)

```tsx
// app/admin/layout.tsx — 서버에서 확인(role 은 세션 콜백에서 DB 값으로 넣은 것)
import { auth } from "@/auth";
import { notFound } from "next/navigation";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const session = await auth();
  if (session?.user?.role !== "admin") notFound();       // 남에게는 없는 페이지처럼
  return <>{children}</>;
}

// app/api/admin/users/route.ts — 레이아웃은 API 를 지켜 주지 않으므로 따로 확인
export async function GET() {
  const session = await auth();
  if (session?.user?.role !== "admin") return new Response(null, { status: 404 });
  return Response.json(await db.user.findMany({ select: { id: true, email: true, createdAt: true } }));
}
```
