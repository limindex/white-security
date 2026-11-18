<!-- generated from white-security issues; do not edit -->
# data · 개인정보 보호

When: 개인정보의 저장·조회·로그·내보내기, Supabase·Firebase 접근 규칙
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.
Stacks: Next.js · Supabase · Firebase · SQL

- `service_role` `sb_secret_` `createClient(` `using (true)` `disable row level security` `user_metadata` `security_invoker` `create table` → WS-BAAS-001 Missing RLS · service_role Key Exposure (Supabase)
- `firestore.rules` `storage.rules` `database.rules.json` `if true` `request.time <` `firebase-admin` `service-account` `serviceAccountKey` → WS-BAAS-002 Insecure Firebase Security Rules · Admin SDK Key Exposure
