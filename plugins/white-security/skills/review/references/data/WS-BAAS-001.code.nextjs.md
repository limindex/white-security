<!-- generated from white-security issues; do not edit -->
# WS-BAAS-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/notes/[id]/route.ts
import { createClient } from "@supabase/supabase-js";

const admin = createClient(process.env.SUPABASE_URL!, process.env.SUPABASE_SECRET_KEY!);

export async function GET(_req: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const { data } = await admin.from("notes").select("*").eq("id", id).single(); // 비밀 키는 RLS 를 건너뛴다
  return Response.json(data); // 로그인 확인이 없어 남의 노트도 열린다
}
```

✓ 안전 코드 (safe example)

```ts
// app/api/notes/[id]/route.ts
import { createServerClient } from "@supabase/ssr";
import { cookies } from "next/headers";

export async function GET(_req: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const store = await cookies();
  const supabase = createServerClient(process.env.NEXT_PUBLIC_SUPABASE_URL!, process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY!, {
    cookies: { getAll: () => store.getAll(), setAll: (list) => list.forEach(({ name, value, options }) => store.set(name, value, options)) },
  });
  const { data: { user } } = await supabase.auth.getUser();
  if (!user) return new Response(null, { status: 401 });
  const { data } = await supabase.from("notes").select("id, body").eq("id", id).maybeSingle(); // 사용자 세션으로 조회 → RLS 적용
  return data ? Response.json(data) : new Response(null, { status: 404 });
}
```
