<!-- generated from white-security issues; do not edit -->
# WS-PRV-005 코드 예시 — SQL

✗ 취약 코드 (vulnerable example — do not use)

```sql
-- Supabase: 로그인한 누구나 profiles 행을 읽음 → 클라이언트가 select('*') 로 전화번호·주소 열까지
create policy "profiles_read" on public.profiles
  for select to authenticated using (true);
```

✓ 안전 코드 (safe example)

```sql
-- 남에게 보여 줄 열만 열 단위로 허용(이제 select('*') 는 권한 오류)
revoke select on table public.profiles from anon, authenticated;
grant select (id, nickname, avatar_url) on table public.profiles to authenticated;
create policy "profiles_read" on public.profiles
  for select to authenticated using (true);

-- 전화번호·주소는 본인만 읽는 별도 테이블로 분리
create table public.profile_private (
  user_id uuid primary key references auth.users (id),
  phone text,
  address text
);
alter table public.profile_private enable row level security;
create policy "own_private" on public.profile_private
  for select to authenticated using ((select auth.uid()) = user_id);
```
