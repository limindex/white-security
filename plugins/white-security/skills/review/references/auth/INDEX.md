<!-- generated from white-security issues; do not edit -->
# auth · 인증·인가

- `.get(id)` `findUnique` `findById` `find($id)` `WHERE id = ?` `/admin` `/manage` `is_admin` → WS-AUTH-001: 남의 데이터 조회·변경, 권한 없는 관리 기능
- `use server` `function POST(` `withoutMiddleware(` `middleware.ts` `proxy.ts` `routes/api.php` `/cron` `/internal` → WS-AUTH-002: 로그인 없이 개인정보 조회·변경·결제·발송
- `Auth::attempt(` `password_verify(` `bcrypt.compare(` `check_password(` `authenticate(request` `Credentials({` → WS-AUTH-003: 로그인·2차 인증 시도 제한 전혀 없음
- `admin.site.urls` `viewTelescope` `viewHorizon` `app/admin/` `adminer.php` `phpmyadmin` → WS-AUTH-004: 관리 기능·DB 도구가 로그인·역할 확인 없이 열림
- `phoneVerified` `is_adult` `cert_ok` `enc_data` `verifyIdToken(` `confirmationResult.confirm(` → WS-AUTH-005: 값 하나 바꿔 본인확인·재설정 건너뜀
- `$guarded = []` `$request->all()` `forceFill(` `"__all__"` `data: body` `**payload` → WS-AUTH-006: 요청으로 역할·잔액·소유자 변경 가능
- `/coupons` `/redeem` `/referral` `/signup` `siteverify` `@ratelimit(` → WS-FLOW-001: 계정만 바꿔 혜택 반복, 앞 단계 건너뛰기
- `->stateless()` `allowDangerousEmailAccountLinking` `redirect_uri` `Socialite::driver(` `verify_oauth2_token(` `/oauth/callback` → WS-OAUTH-001: state·PKCE 둘 다 없음, 요청 값 redirect_uri
- `constructEvent(` `construct_event(` `stripe-signature` `/payments/confirm` `/webhooks/` → WS-PAY-001: 요청 값 금액, 확인 없이 믿는 결제 결과·웹훅
- `MD5PasswordHasher` `Crypt::encrypt(` `PASSWORD_HASHERS` `AUTH_PASSWORD_VALIDATORS` `Hash::make(` `make_password(` `passwordHash` → WS-PWD-001: 평문·복호화 가능·MD5·SHA-1·솔트 없는 저장
- `jwt.decode(` `decodeJwt(` `jwtVerify(` `ignoreExpiration` `verify_signature` `session_regenerate_id(` → WS-SESS-001: 서명 확인 없는 토큰, none·알고리즘 혼용, 세션 고정
- `otp_code` `verification_code` `/sms/send` `/otp/verify` `/send-code` → WS-SMS-001: 시도 제한 없음, 예측 가능한 번호, 응답에 번호
