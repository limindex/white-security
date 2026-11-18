<!-- generated from white-security issues; do not edit -->
# WS-PRV-003 코드 예시 — SQL

✗ 취약 코드 (vulnerable example — do not use)

```sql
-- 앱 계정이 접속기록을 고치고 지울 수 있음
grant all on table access_logs to app_user;
-- 정리 배치: 90일 지난 기록 삭제
delete from access_logs where accessed_at < now() - interval '90 days';
```

✓ 안전 코드 (safe example)

```sql
-- PostgreSQL. 접속기록은 별도 스키마, 소유자는 감사 관리 계정
create table audit.access_logs (
  id          bigint generated always as identity primary key,
  operator_id bigint      not null,                -- 식별자
  accessed_at timestamptz not null default now(), -- 접속일시
  ip          inet        not null,                -- 접속지 정보
  subjects    jsonb       not null,                -- 처리한 정보주체(회원 id, 원문 개인정보 금지)
  action      text        not null,                -- 수행업무(열람·검색·수정·삭제·다운로드 등)
  detail      text
);
grant usage on schema audit to app_user;
revoke all on table audit.access_logs from app_user;
grant insert on table audit.access_logs to app_user;   -- 앱은 쓰기만

-- 소유자라도 고치거나 지우지 못하게 막음(보관 기간 정리는 절차를 거쳐 트리거를 잠시 끄고)
create function audit.deny_change() returns trigger language plpgsql as $$
begin raise exception 'access_logs is append-only'; end $$;
create trigger access_logs_no_change before update or delete on audit.access_logs
  for each row execute function audit.deny_change();
create trigger access_logs_no_truncate before truncate on audit.access_logs
  for each statement execute function audit.deny_change();
```
