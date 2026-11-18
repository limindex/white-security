<!-- generated from white-security issues; do not edit -->
# auth · 인증·인가

When: 로그인·권한·본인 데이터 접근 기능
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.
Stacks: Python · FastAPI · Django · JavaScript · Next.js · PHP · Laravel

- `.get(id)` `findUnique` `findById` `find($id)` `WHERE id = ?` `/admin` `/manage` `is_admin` → WS-AUTH-001 IDOR · 접근 제어 실패 (Broken Access Control)
- `"use server"` `export async function POST` `withoutMiddleware(` `@router.post(` `middleware.ts` `/cron` → WS-AUTH-002 Missing Authentication
- `Auth::attempt(` `password_verify(` `bcrypt.compare(` `check_password(` `authenticate(request` `Credentials({` → WS-AUTH-003 Brute Force · Credential Stuffing
- `admin.site.urls` `viewTelescope` `viewHorizon` `app/admin/` `adminer.php` `phpmyadmin` `useSession(` → WS-AUTH-004 Exposed Admin Interface
- `phoneVerified` `is_adult` `cert_ok` `enc_data` `verifyIdToken(` `confirmationResult.confirm(` → WS-AUTH-005 Authentication Bypass
