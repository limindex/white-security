<!-- generated from white-security issues; do not edit -->
# WS-BAAS-001 Missing RLS (Supabase 행 수준 보안 누락)

## 탐지 규칙

- **찾는 곳**: `service_role`, `sb_secret_`, `createClient(`, `using (true)`, `disable row level security`, `security definer`, `user_metadata`, `storage.objects`, 그 밖에 security_invoker, Supabase 마이그레이션의 create table, NEXT_PUBLIC_·VITE_·EXPO_PUBLIC_ 환경 변수.
- **검사 1 — RLS 없음·열린 정책**: 지적할 때: public 테이블을 만들었는데 그 테이블에 `enable row level security`를 하는 마이그레이션이 어디에도 없는 경우. 정책이 `using (true)`·`with check (true)`로 모든 행의 쓰기를 여는 경우. 노출 스키마의 `security definer` 함수(RPC로 불리면 RLS를 건너뜀)가 `auth.uid()` 확인 없이 테이블을 읽고 쓰는 경우. `storage.objects` 정책이 모든 사용자의 쓰기·삭제를 여는 경우. / 지적하지 않을 때: RLS를 켜고 `auth.uid()`로 본인 행만 허용하는 경우. 누구나 읽어도 되는 공개 데이터에만 `select` 정책이 `true`인 경우.
- **검사 2 — 비밀 키 노출**: 지적할 때: service_role·`sb_secret_` 키가 브라우저·앱 코드, `NEXT_PUBLIC_`·`VITE_`·`EXPO_PUBLIC_` 변수, 저장소 파일에 있는 경우. / 지적하지 않을 때: anon·publishable 키가 클라이언트에 있는 경우(공개용이라 정상). 비밀 키를 서버 전용 환경 변수에서만 읽는 경우.
- **검사 3 — 비밀 키로 대신 조회**: 지적할 때: 서버 코드가 비밀 키 클라이언트로 요청 값(id)만 보고 조회·수정하고 로그인 사용자 확인이 없는 경우. / 지적하지 않을 때: 사용자 세션 클라이언트(`createServerClient`)로 RLS를 그대로 적용하는 경우. 비밀 키를 쓰기 전에 `auth.getUser()`로 사용자를 확인하고 그 id로 조건을 거는 경우.
- **등급**: 위험: 비밀 키가 클라이언트에 있는 경우. 개인 데이터 테이블에 RLS가 없거나 `using (true)`인 경우. / 주의: update 정책에 `with check`가 없는 경우. 정책이 `user_metadata`에 기대는 경우. RLS를 건너뛰는 뷰. / 참고: 테이블 SQL이 저장소에 없어 대시보드에서 확인이 필요한 경우.
- **보고 예**: `[위험] WS-BAAS-001 supabase/migrations/0001_init.sql:3 — notes 테이블에 RLS 없음, anon 키로 전체 조회 가능 → enable row level security 와 본인 행 정책 추가`
- **고칠 방법**: RLS를 켜는 마이그레이션을 새로 추가해야 하고, 기존 화면의 조회마다 정책이 허용하는지 다른 계정으로 확인해야 한다.

## 점검 항목

- public 스키마의 모든 테이블에 RLS를 켜야 한다.
- 정책은 `using (true)` 대신 `(select auth.uid()) = user_id`처럼 본인 행만 허용해야 한다.
- update 정책에는 `with check`도 넣어 남의 user_id로 바꾸지 못하게 해야 한다.
- 권한 판단에 사용자가 고칠 수 있는 `user_metadata`를 쓰지 않아야 한다.
- 뷰는 `security_invoker = true`로 만들어 RLS를 건너뛰지 않게 해야 한다.
- service_role·`sb_secret_` 키는 서버에만 두고, 브라우저엔 publishable 키만 써야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-BAAS-001.code.supabase.md` (Supabase) · `WS-BAAS-001.code.sql.md` (SQL) · `WS-BAAS-001.code.nextjs.md` (Next.js)

기본 심각도: 위험 · CWE-284 · CWE-862 · CWE-639 · CWE-798
