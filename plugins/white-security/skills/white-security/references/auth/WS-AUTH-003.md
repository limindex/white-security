<!-- generated from white-security issues; do not edit -->
# WS-AUTH-003 Brute Force · Credential Stuffing (무차별 대입·인증 시도 제한 없음)

## 탐지 규칙

- **찾는 곳**: `Auth::attempt(`, `password_verify(`, `bcrypt.compare(`, `check_password(`, `authenticate(request`, `Credentials({`, 그 밖에 /login·/verify-otp·비밀번호 재확인 경로, throttle 미들웨어·RateLimiter 설정.
- **검사 1 — 시도 제한 없음**: 지적할 때: 비밀번호·인증번호·2차 인증 코드를 확인하는 핸들러에 실패 횟수 제한·지연·캡차가 없는 경우. / 지적하지 않을 때: `throttle:` 미들웨어, `RateLimiter`, django-axes, Laravel Fortify 기본 제한, 인증 서비스(Supabase Auth·Firebase Auth 등)의 기본 제한이 그 경로에 걸린 것을 찾은 경우.
- **검사 2 — 잘못된 제한 키·저장소**: 지적할 때: IP만으로 세는 경우, 요청 헤더 `X-Forwarded-For`를 그대로 키로 쓰는 경우, 프로세스 메모리(`new Map()`, 전역 dict)에 카운터를 둬 서버 여러 대·재시작마다 초기화되는 경우. / 지적하지 않을 때: 계정+IP 키와 계정 단위 상한이 Redis·DB에 있는 경우.
- **검사 3 — 계정 존재 노출**: 지적할 때: "없는 아이디"와 "비밀번호 틀림"의 문구·상태 코드가 다른 경우. / 지적하지 않을 때: 같은 문구·같은 코드로 답하는 경우.
- **등급**: 위험: 로그인·2차 인증에 제한이 전혀 없는 경우. / 주의: IP만으로 제한, 메모리 카운터, 누구나 남의 계정을 잠글 수 있는 영구 잠금. / 참고: 계정 존재가 응답으로 드러나는 경우, 실패 기록이 로그에 없는 경우.
- **보고 예**: `[위험] WS-AUTH-003 app/Http/Controllers/LoginController.php:18 — Auth::attempt 에 시도 제한이 없음 → RateLimiter 로 이메일+IP 키 5회/분, 이메일 단위 시간당 상한 추가`
- **고칠 방법**: 계정+IP 키의 짧은 창 제한과 계정 단위 긴 창 상한을 함께 걸어야 하고, 넘으면 잠시 대기·캡차로 처리해야 한다.

## 점검 항목

- 로그인·2단계 인증·비밀번호 확인 API는 실패 횟수를 세어 접근을 제한해야 한다.
- 제한 키는 계정과 IP를 함께 쓰고, 계정 단위 상한도 따로 둬야 한다.
- 영구 잠금 대신 시간이 지나면 풀리는 대기·캡차를 써야 한다(남이 계정을 계속 잠그지 못하게).
- 카운터는 여러 서버가 함께 보는 저장소(Redis·DB)에 둬야 한다.
- `X-Forwarded-For`는 신뢰하는 프록시가 붙인 값만 IP로 써야 한다.
- 없는 계정과 틀린 비밀번호는 같은 응답으로 답해야 한다.

## 검토 주의

`$request->ip()`·`REMOTE_ADDR`는 프록시 뒤에서 프록시 주소가 되므로 TrustProxies·AXES_IPWARE_PROXY_COUNT 같은 설정을 함께 봐야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-AUTH-003.code.laravel.md` (Laravel) · `WS-AUTH-003.code.nextjs.md` (Next.js) · `WS-AUTH-003.code.django.md` (Django)

기본 심각도: 위험 · CWE-307 · CWE-204
