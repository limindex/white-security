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
// lib/dal.ts — 관리자 확인을 한 함수로 모은다(role 은 세션 콜백에서 DB 값으로 넣은 것)
import "server-only";
import { auth } from "@/auth";
import { notFound } from "next/navigation";

export async function requireAdmin() {
  const session = await auth();
  if (session?.user?.role !== "admin") notFound();      // 남에게는 없는 페이지처럼
  return session.user;
}

// app/admin/layout.tsx — 레이아웃은 1차 거름망, page·데이터 함수에서도 확인
export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  await requireAdmin();
  return <>{children}</>;
}

// app/admin/users/page.tsx — 페이지마다 다시 확인(레이아웃은 이동할 때 다시 실행되지 않을 수 있음)
export default async function UsersPage() {
  await requireAdmin();
  return <UserTable users={await db.user.findMany({ select: { id: true, email: true } })} />;
}

// app/api/admin/users/route.ts·Server Action 도 같은 함수로 확인
export async function GET() {
  await requireAdmin();
  return Response.json(await db.user.findMany({ select: { id: true, email: true, createdAt: true } }));
}
```
