---
id: WS-AUTH-003
title: Brute Force · Credential Stuffing (무차별 대입·인증 시도 제한 없음)
slug: brute-force-credential-stuffing
group: auth
severity: high
grade_hint: "로그인·2차 인증 시도 제한 전혀 없음"
always_on: "`Auth::attempt(` `bcrypt.compare(` → 로그인·2차 인증·비밀번호 확인은 계정·IP별 실패 횟수를 세어 제한해야 한다."
site_only: false
depth: brief
languages: [laravel, nextjs, django]
maps:
  owasp2025: [A07]
  api2023: [API2]
  llm2026: []
  agentic2026: []
  asvs5: [V6.1.1, V6.3.1, V6.3.8]
  cwe: [CWE-307, CWE-204]
  kr_guide: ["보안기능-16"]
  kr_ciip: [AU]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js, Django, 비밀번호·인증번호로 로그인하는 모든 서비스]
severity_reason: "영향: 회원 계정 탈취·개인정보 열람 · 악용: 유출된 아이디·비밀번호 목록과 자동화 도구만으로 가능"
sources:
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제5조 제6항
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: 개인정보보호위원회 개인정보의 안전성 확보조치 기준 안내서 (2026.11)
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=12611
    year: 2026
  - title: "NIST SP 800-63B-4 Digital Identity Guidelines: Authentication and Authenticator Management (3.2.2 Rate Limiting)"
    url: https://pages.nist.gov/800-63-4/sp800-63b.html
    year: 2025
  - title: OWASP Credential Stuffing Prevention Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Credential_Stuffing_Prevention_Cheat_Sheet.html
    year: 2026
  - title: "Laravel 12.x - Rate Limiting"
    url: https://laravel.com/docs/12.x/rate-limiting
    year: 2026
  - title: "django-axes - Configuration"
    url: https://django-axes.readthedocs.io/en/latest/4_configuration.html
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

로그인·비밀번호 확인·2단계 인증에 시도 횟수 제한이 없어, 흔한 비밀번호를 계속 넣어 보거나(Brute Force) 다른 곳에서 유출된 아이디·비밀번호 목록을 그대로 넣어 보는 공격(Credential Stuffing)이 통하는 문제(진단가이드 이름: 반복된 인증시도 제한 기능 부재).

## 위험 배경

- 「개인정보의 안전성 확보조치 기준」 제5조 ⑥은 일정 횟수 이상 인증에 실패하면 접근을 제한하도록 요구하고, 안내서(2026.11)는 그 대상에 일반 회원도 포함된다고 설명.

## 대응 방법

```text
로그인, 비밀번호 확인, 2단계 인증, 비밀번호 재설정 요청 API를 찾아 시도 횟수 제한이 있는지 알려 줘.
제한은 IP만이 아니라 '계정+IP' 키와 계정 단위 상한으로 걸고, 카운터는 Redis·DB 같은 공유 저장소에 둬 줘.
넘으면 잠시 막거나 캡차를 띄우고, 없는 아이디와 틀린 비밀번호에는 같은 문구·같은 상태 코드로 답하게 해 줘.
```

- 같은 계정에 틀린 비밀번호를 연속으로 넣으면 정한 횟수 뒤에 429나 대기 안내가 나와야 한다.
- 없는 아이디와 틀린 비밀번호의 응답 문구·상태 코드가 같아야 한다.

## 점검 항목

- 로그인·2단계 인증·비밀번호 확인 API는 실패 횟수를 세어 접근을 제한해야 한다.
- 제한 키는 계정과 IP를 함께 쓰고, 계정 단위 상한도 따로 둬야 한다.
- 영구 잠금 대신 시간이 지나면 풀리는 대기·캡차를 써야 한다(남이 계정을 계속 잠그지 못하게).
- 카운터는 여러 서버가 함께 보는 저장소(Redis·DB)에 둬야 한다.
- `X-Forwarded-For`는 신뢰하는 프록시가 붙인 값만 IP로 써야 한다.
- 없는 계정과 틀린 비밀번호는 같은 응답으로 답해야 한다.

## 탐지 규칙

- **찾는 곳**: `Auth::attempt(`, `password_verify(`, `bcrypt.compare(`, `check_password(`, `authenticate(request`, `Credentials({`, 그 밖에 /login·/verify-otp·비밀번호 재확인 경로, throttle 미들웨어·RateLimiter 설정.
- **검사 1 — 시도 제한 없음**: 지적할 때: 비밀번호·인증번호·2차 인증 코드를 확인하는 핸들러에 실패 횟수 제한·지연·캡차가 없는 경우. / 지적하지 않을 때: `throttle:` 미들웨어, `RateLimiter`, django-axes, Laravel Fortify 기본 제한, 인증 서비스(Supabase Auth·Firebase Auth 등)의 기본 제한이 그 경로에 걸린 것을 찾은 경우.
- **검사 2 — 잘못된 제한 키·저장소**: 지적할 때: IP만으로 세는 경우, 요청 헤더 `X-Forwarded-For`를 그대로 키로 쓰는 경우, 프로세스 메모리(`new Map()`, 전역 dict)에 카운터를 둬 서버 여러 대·재시작마다 초기화되는 경우. / 지적하지 않을 때: 계정+IP 키와 계정 단위 상한이 Redis·DB에 있는 경우.
- **검사 3 — 계정 존재 노출**: 지적할 때: "없는 아이디"와 "비밀번호 틀림"의 문구·상태 코드가 다른 경우. / 지적하지 않을 때: 같은 문구·같은 코드로 답하는 경우.
- **등급**: 위험: 로그인·2차 인증에 제한이 전혀 없는 경우. / 주의: IP만으로 제한, 메모리 카운터, 누구나 남의 계정을 잠글 수 있는 영구 잠금. / 참고: 계정 존재가 응답으로 드러나는 경우, 실패 기록이 로그에 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-AUTH-003 app/Http/Controllers/LoginController.php:18 — Auth::attempt 에 시도 제한이 없음 → RateLimiter 로 이메일+IP 키 5회/분, 이메일 단위 시간당 상한 추가`
- **검토 주의**: `$request->ip()`·`REMOTE_ADDR`는 프록시 뒤에서 프록시 주소가 되므로 TrustProxies·AXES_IPWARE_PROXY_COUNT 같은 설정을 함께 봐야 한다.
- **고칠 방법**: 계정+IP 키의 짧은 창 제한과 계정 단위 긴 창 상한을 함께 걸어야 하고, 넘으면 잠시 대기·캡차로 처리해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
public function login(Request $request)
{
    // 몇 번이든 다시 시도할 수 있다
    if (Auth::attempt($request->only('email', 'password'))) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }
    return back()->withErrors(['email' => '이메일 또는 비밀번호가 맞지 않습니다.']);
}
```

✓ 안전 코드

```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

public function login(Request $request)
{
    $cred = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
    $email = Str::lower($cred['email']);
    $pair = 'login:'.$email.'|'.$request->ip();      // 계정+IP (프록시 뒤라면 TrustProxies 설정 확인)
    $acct = 'login-acct:'.$email;                    // 계정 단위 상한(IP를 바꿔 가며 시도하는 경우)
    if (RateLimiter::tooManyAttempts($pair, 5) || RateLimiter::tooManyAttempts($acct, 30)) {
        abort(429, '잠시 뒤 다시 시도해 주세요.');
    }
    if (Auth::attempt($cred)) {
        RateLimiter::clear($pair);
        $request->session()->regenerate();
        return redirect()->intended('/');
    }
    RateLimiter::hit($pair, 60);                     // 1분 창
    RateLimiter::hit($acct, 3600);                   // 1시간 창
    return back()->withErrors(['email' => '이메일 또는 비밀번호가 맞지 않습니다.']);
}
```

### nextjs

✗ 취약 코드

```ts
// app/api/login/route.ts
const fails = new Map<string, number>();            // 서버 여러 대·재시작이면 초기화된다

export async function POST(req: Request) {
  const { email, password } = await req.json();
  const user = await db.user.findUnique({ where: { email } });
  if (!user) return Response.json({ error: "가입되지 않은 이메일" }, { status: 404 });  // 계정 존재 노출
  if (!(await verify(user.passwordHash, password))) {
    fails.set(email, (fails.get(email) ?? 0) + 1);  // 세기만 하고 막지 않음
    return Response.json({ error: "비밀번호가 틀림" }, { status: 401 });
  }
  return startSession(user);
}
```

✓ 안전 코드

```ts
import { Ratelimit } from "@upstash/ratelimit";
import { Redis } from "@upstash/redis";
import { verify } from "@node-rs/argon2";

const redis = Redis.fromEnv();
const pairLimit = new Ratelimit({ redis, limiter: Ratelimit.slidingWindow(5, "1 m"), prefix: "login" });
const acctLimit = new Ratelimit({ redis, limiter: Ratelimit.slidingWindow(30, "1 h"), prefix: "login-acct" });

export async function POST(req: Request) {
  const { email, password } = await req.json();
  const id = String(email).toLowerCase();
  const ip = clientIp(req);                          // 배포 환경 프록시가 붙인 값만 쓰는 함수
  const [a, b] = await Promise.all([pairLimit.limit(`${id}|${ip}`), acctLimit.limit(id)]);
  if (!a.success || !b.success) return Response.json({ error: "잠시 뒤 다시 시도해 주세요." }, { status: 429 });
  const user = await db.user.findUnique({ where: { email: id } });
  const ok = await verify(user?.passwordHash ?? DUMMY_HASH, String(password));  // 없는 계정도 미리 만든 해시로 같은 시간
  if (!user || !ok) return Response.json({ error: "이메일 또는 비밀번호가 맞지 않습니다." }, { status: 401 });
  return startSession(user);
}
```

### django

✗ 취약 코드

```python
def login_view(request):
    user = authenticate(username=request.POST["username"],   # request 를 넘기지 않아 제한 도구도 못 본다
                        password=request.POST["password"])
    if user is None:
        return render(request, "login.html", {"error": "로그인 실패"})
    login(request, user)
    return redirect("home")
# 시도 제한 설정 없음
```

✓ 안전 코드

```python
# settings.py — django-axes: 실패 횟수를 DB·캐시에 남기고 넘으면 잠시 막는다
from datetime import timedelta
INSTALLED_APPS += ["axes"]
MIDDLEWARE += ["axes.middleware.AxesMiddleware"]          # 목록 맨 끝
AUTHENTICATION_BACKENDS = ["axes.backends.AxesStandaloneBackend",
                           "django.contrib.auth.backends.ModelBackend"]
AXES_FAILURE_LIMIT = 10
AXES_COOLOFF_TIME = timedelta(minutes=15)                 # 영구 잠금 대신 15분 뒤 자동 해제
AXES_LOCKOUT_PARAMETERS = ["username"]                    # 계정 단위: IP를 바꿔 가며 시도해도 센다
# (한도가 하나라 계정·IP 조합을 더해도 결과가 같음. 15분 뒤 풀려 남이 잠가도 피해가 짧다)
AXES_IPWARE_PROXY_COUNT = 1                               # 프록시 한 대 뒤라면 실제 IP 위치를 맞춘다

# views.py — authenticate 에 request 를 넘겨야 axes 가 센다
user = authenticate(request, username=request.POST["username"], password=request.POST["password"])
```
