<!-- generated from white-security issues; do not edit -->
# data · 개인정보 보호

When: 개인정보의 저장·조회·로그·내보내기, Supabase·Firebase 접근 규칙
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.
Stacks: FastAPI · Django · Next.js · Laravel · Supabase · Firebase · SQL

- `service_role` `sb_secret_` `createClient(` `using (true)` `disable row level security` `security definer` `user_metadata` `storage.objects` → WS-BAAS-001 Missing RLS · service_role Key Exposure (Supabase)
- `firestore.rules` `storage.rules` `database.rules.json` `if true` `request.time <` `now <` `firebase-admin` `serviceAccountKey` → WS-BAAS-002 Insecure Firebase Security Rules · Admin SDK Key Exposure
- `card_number` `account_number` `passport_no` `license_no` `biometric` `=> 'encrypted'` `AESGCM(` `createCipheriv(` → WS-PRV-001 Unencrypted Sensitive Data at Rest
- `sentry_sdk.init(` `Sentry.init(` `send_default_pii` `sendDefaultPii` `maskAllText` `before_send` `beforeSend` `redact:` → WS-PRV-002 Sensitive Data in Logs·Error Trackers
- `access_log` `audit_log` `AccessLog` `activity()->` `auditlog.register(` `LogEntry` `/admin` `'days' =>` → WS-PRV-003 Missing Access Records
