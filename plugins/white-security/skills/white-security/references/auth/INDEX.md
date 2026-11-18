<!-- generated from white-security issues; do not edit -->
# auth · 인증·인가

When: 로그인·권한·본인 데이터 접근 기능
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.
Stacks: Python · FastAPI · Django · JavaScript · Next.js · PHP · Laravel

- `.get(id)` `findUnique` `findById` `find($id)` `WHERE id = ?` `/admin` `/manage` `is_admin` → WS-AUTH-001 IDOR · 접근 제어 실패 (Broken Access Control)
- `withoutMiddleware(` `middleware.ts` `proxy.ts` `routes/api.php` `/cron` `/internal` → WS-AUTH-002 Missing Authentication
- `Auth::attempt(` `password_verify(` `bcrypt.compare(` `check_password(` `authenticate(request` `Credentials({` → WS-AUTH-003 Brute Force · Credential Stuffing
- `admin.site.urls` `viewTelescope` `viewHorizon` `app/admin/` `adminer.php` `phpmyadmin` → WS-AUTH-004 Exposed Admin Interface
- `phoneVerified` `is_adult` `cert_ok` `enc_data` `verifyIdToken(` `confirmationResult.confirm(` → WS-AUTH-005 Authentication Bypass
- `$guarded = []` `$request->all()` `forceFill(` `"__all__"` `data: body` `**payload` → WS-AUTH-006 Mass Assignment
- `/coupons` `/redeem` `/referral` `/signup` `siteverify` `@ratelimit(` → WS-FLOW-001 Business Flow Abuse
- `->stateless()` `allowDangerousEmailAccountLinking` `redirect_uri` `Socialite::driver(` `verify_oauth2_token(` `/oauth/callback` → WS-OAUTH-001 OAuth Login Flaws
- `constructEvent(` `construct_event(` `stripe-signature` `/payments/confirm` `/webhooks/` → WS-PAY-001 Payment Tampering
- `MD5PasswordHasher` `Crypt::encrypt(` `PASSWORD_HASHERS` `AUTH_PASSWORD_VALIDATORS` `Hash::make(` `make_password(` `passwordHash` → WS-PWD-001 Weak Password Storage · Policy
- `jwt.decode(` `decodeJwt(` `jwtVerify(` `ignoreExpiration` `verify_signature` `session_regenerate_id(` → WS-SESS-001 Broken Session Management
- `otp_code` `verification_code` `/sms/send` `/otp/verify` `/send-code` → WS-SMS-001 OTP Abuse (SMS Code Brute Force · SMS Pumping)
