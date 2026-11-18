---
id: WS-BAAS-001
title: Missing RLS (Supabase 행 수준 보안 누락)
title_en: Missing RLS · service_role Key Exposure (Supabase)
slug: supabase-rls
group: data
severity: high
site_only: false
depth: brief
languages: [supabase, sql, nextjs]
maps:
  owasp2025: [A01, A02]
  api2023: [API1, API8]
  llm2026: []
  agentic2026: []
  asvs5: [V8.2.2, V8.3.1, V13.3.2]
  cwe: [CWE-284, CWE-862, CWE-639, CWE-798]
  kr_guide: ["보안기능-2", "보안기능-6"]
  kr_ciip: [IN]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Supabase, Next.js, React, Supabase를 백엔드로 쓰는 웹·모바일 앱]
severity_reason: "영향: 회원 데이터 전체 조회·변조·삭제 · 악용: 앱에 공개된 키와 브라우저 개발자 도구만으로 가능"
sources:
  - title: "Supabase Docs - Row Level Security"
    url: https://supabase.com/docs/guides/database/postgres/row-level-security
    year: 2026
  - title: "Supabase Docs - Understanding API keys (publishable·secret, anon·service_role)"
    url: https://supabase.com/docs/guides/api/api-keys
    year: 2026
  - title: "CVE-2025-48757 (AI 앱 빌더가 만든 사이트의 RLS 정책 부족, 공급사 이의 제기)"
    url: https://www.cve.org/CVERecord?id=CVE-2025-48757
    year: 2025
  - title: "OWASP API Security Top 10 2023 - API1 Broken Object Level Authorization"
    url: https://owasp.org/API-Security/editions/2023/en/0xa1-broken-object-level-authorization/
    year: 2023
---

## 요약

Supabase 테이블에 RLS(Row Level Security, 행 수준 보안)를 켜지 않거나 정책을 `using (true)`로 열어 두어, 앱에 공개된 anon(publishable) 키만으로 남의 데이터를 읽고 고칠 수 있는 문제(진단가이드 이름: 부적절한 인가). service_role(secret) 키가 브라우저 코드에 들어가면 RLS를 아예 건너뜀.

## 대응 방법

```text
Supabase public 스키마의 테이블마다 RLS가 켜져 있는지, 정책이 using (true)로 열려 있지 않은지 표로 보여 줘.
RLS를 켜고 select·insert·update·delete 정책을 (select auth.uid()) = user_id처럼 본인 행만 허용하게 고쳐 줘.
service_role·sb_secret_ 키가 브라우저 코드나 NEXT_PUBLIC_ 변수에 있으면 서버 코드로 옮겨 줘.
```

- 다른 계정으로 로그인해 남의 행 id로 조회하면 빈 결과가 나와야 한다.
- Supabase 대시보드 Security Advisor에 `0013_rls_disabled_in_public` 경고가 없어야 한다.

## 점검 항목

- public 스키마의 모든 테이블에 RLS를 켜야 한다.
- 정책은 `using (true)` 대신 `(select auth.uid()) = user_id`처럼 본인 행만 허용해야 한다.
- update 정책에는 `with check`도 넣어 남의 user_id로 바꾸지 못하게 해야 한다.
- 권한 판단에 사용자가 고칠 수 있는 `user_metadata`를 쓰지 않아야 한다.
- 뷰는 `security_invoker = true`로 만들어 RLS를 건너뛰지 않게 해야 한다.
- service_role·`sb_secret_` 키는 서버에만 두고, 브라우저에는 publishable(anon) 키만 써야 한다.

## 탐지 규칙

- **찾는 곳**: `service_role`, `sb_secret_`, `createClient(`, `using (true)`, `disable row level security`, `user_metadata`, `security_invoker`, Supabase 마이그레이션의 `create table`, `NEXT_PUBLIC_`·`VITE_`·`EXPO_PUBLIC_` 환경 변수.
- **검사 1 — RLS 없음·열린 정책**: 지적할 때: public 테이블을 만들고 `enable row level security`가 없는 경우. 정책이 `using (true)`·`with check (true)`로 모든 행의 쓰기를 여는 경우. / 지적하지 않을 때: RLS를 켜고 `auth.uid()`로 본인 행만 허용하는 경우. 누구나 읽어도 되는 공개 데이터에만 `select` 정책이 `true`인 경우.
- **검사 2 — 비밀 키 노출**: 지적할 때: service_role·`sb_secret_` 키가 브라우저·앱 코드, `NEXT_PUBLIC_`·`VITE_`·`EXPO_PUBLIC_` 변수, 저장소 파일에 있는 경우. / 지적하지 않을 때: anon·publishable 키가 클라이언트에 있는 경우(공개용이라 정상). 비밀 키를 서버 전용 환경 변수에서만 읽는 경우.
- **검사 3 — 비밀 키로 대신 조회**: 지적할 때: 서버 코드가 비밀 키 클라이언트로 요청 값(id)만 보고 조회·수정하고 로그인 사용자 확인이 없는 경우. / 지적하지 않을 때: 사용자 세션 클라이언트(`createServerClient`)로 RLS를 그대로 적용하는 경우. 비밀 키를 쓰기 전에 `auth.getUser()`로 사용자를 확인하고 그 id로 조건을 거는 경우.
- **등급**: 위험: 비밀 키가 클라이언트에 있는 경우. 개인 데이터 테이블에 RLS가 없거나 `using (true)`인 경우. / 주의: update 정책에 `with check`가 없는 경우. 정책이 `user_metadata`에 기대는 경우. RLS를 건너뛰는 뷰. / 참고: 테이블 SQL이 저장소에 없어 대시보드에서 확인이 필요한 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-BAAS-001 supabase/migrations/0001_init.sql:3 — notes 테이블에 RLS 없음, anon 키로 전체 조회 가능 → enable row level security 와 본인 행 정책 추가`
- **고칠 방법**: RLS를 켜는 마이그레이션을 새로 추가해야 하고, 기존 화면의 조회마다 정책이 허용하는지 다른 계정으로 확인해야 한다.

## 코드 예시

### supabase

✗ 취약 코드

```ts
import { createClient } from "@supabase/supabase-js";

// 브라우저 번들에 들어가는 변수에 비밀 키를 넣음 → RLS 를 전부 건너뛴다
export const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_SERVICE_ROLE_KEY!,
);
```

✓ 안전 코드

```ts
import { createClient } from "@supabase/supabase-js";

// 브라우저에는 공개용 publishable(옛 anon) 키만. 접근 범위는 RLS 정책이 정한다
export const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY!,
);

const { data } = await supabase.from("notes").select("id, body"); // 로그인한 본인 행만 돌아온다
```

### sql

✗ 취약 코드

```sql
create table public.notes (
  id bigint generated always as identity primary key,
  user_id uuid not null references auth.users (id),
  body text
);
-- RLS 를 켜지 않음: anon 키만 있으면 누구나 전체 조회·수정
-- 켜더라도 아래 정책은 같은 결과
alter table public.notes enable row level security;
create policy "notes_all" on public.notes for all using (true) with check (true);
```

✓ 안전 코드

```sql
alter table public.notes enable row level security;

create policy "notes_select_own" on public.notes for select
  to authenticated using ((select auth.uid()) = user_id);
create policy "notes_insert_own" on public.notes for insert
  to authenticated with check ((select auth.uid()) = user_id);
create policy "notes_update_own" on public.notes for update
  to authenticated using ((select auth.uid()) = user_id)
  with check ((select auth.uid()) = user_id);   -- 남의 user_id 로 바꾸기 금지
create policy "notes_delete_own" on public.notes for delete
  to authenticated using ((select auth.uid()) = user_id);
```

### nextjs

✗ 취약 코드

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

✓ 안전 코드

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
