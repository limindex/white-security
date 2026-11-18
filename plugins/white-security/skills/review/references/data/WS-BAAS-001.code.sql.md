<!-- generated from white-security issues; do not edit -->
# WS-BAAS-001 코드 예시 — SQL

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
