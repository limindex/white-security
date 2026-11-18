---
id: WS-SMS-001
title: OTP Abuse (문자 인증번호 무차별 대입·발송 남용)
title_en: OTP Abuse (SMS Code Brute Force · SMS Pumping)
slug: otp-abuse
group: auth
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs, fastapi]
maps:
  owasp2025: [A07]
  api2023: [API2, API4]
  llm2026: []
  agentic2026: []
  asvs5: [V6.5.1, V6.5.3, V6.5.5, V6.6.2, V6.6.3, V11.5.1]
  cwe: [CWE-307, CWE-330, CWE-640, CWE-799]
  kr_guide: ["보안기능-16", "보안기능-8"]
  kr_ciip: [AU, PR]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js, FastAPI, 문자 인증번호로 가입·로그인·비밀번호 찾기를 하는 모든 서비스]
severity_reason: "영향: 남의 휴대폰 인증 통과·비밀번호 재설정, 문자 요금 폭탄 · 악용: 확인·발송 API를 자동으로 반복 호출하는 것만으로 가능"
sources:
  - title: KISA 2026 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드 (자동화 공격·취약한 비밀번호 복구 절차)
    url: https://www.kisa.or.kr/2060204/form?postSeq=22
    year: 2025
  - title: "NIST SP 800-63B-4 (3.1.3 Out-of-Band Devices·3.2.2 Rate Limiting)"
    url: https://pages.nist.gov/800-63-4/sp800-63b.html
    year: 2025
  - title: "OWASP API Security Top 10:2023 - API4 Unrestricted Resource Consumption"
    url: https://owasp.org/API-Security/editions/2023/en/0xa4-unrestricted-resource-consumption/
    year: 2023
  - title: "Twilio Docs - Preventing SMS pumping (toll fraud)"
    url: https://www.twilio.com/docs/verify/preventing-toll-fraud
    year: 2026
  - title: OWASP Forgot Password Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

휴대폰 문자 인증번호(OTP, 한 번 쓰는 번호)에 확인 횟수·유효시간·발송 횟수 제한이 없어, 6자리 번호를 끝까지 대입하거나 발송 API를 반복 호출해 문자 요금을 쓰게 만드는(SMS Pumping, 문자 펌핑) 문제(진단가이드 이름: 반복된 인증시도 제한 기능 부재·적절하지 않은 난수 값 사용). 비밀번호 찾기에서 수신번호를 요청 값으로 받아 공격자 휴대폰으로 인증번호가 가는 수신번호 변조도 같은 범위.

## 대응 방법

```text
인증번호 생성·발송·확인 API를 찾아 생성 방식, 유효시간, 확인 시도 제한, 발송 횟수 제한이 있는지 표로 보여 줘.
번호는 암호학적 난수 6자리로 만들어 5분 안에 한 번만 쓰게 하고, 틀린 입력이 5번 쌓이면 그 인증 건을 무효로 해 줘.
발송은 번호·IP·계정별 분당·일별 한도와 재발송 대기 시간을 넣고, 비밀번호 찾기는 DB에 등록된 번호로만 보내 줘.
인증번호는 응답 JSON과 로그에 넣지 말아 줘.
```

- 같은 인증 건에 틀린 번호를 5번 넣으면 그 인증 건이 무효가 돼 새로 받아야 한다.
- 발송 API를 1분에 여러 번 부르면 정한 횟수 뒤에 429가 나와야 한다.

## 점검 항목

- 인증번호는 암호학적 난수(`random_int`·`secrets`·`crypto.randomInt`)로 6자리 이상 만들어야 한다.
- 유효시간은 짧게(10분 이내) 두고, 성공하면 바로 지워야 한다.
- 같은 인증 건의 오입력은 정한 횟수(예: 5회)를 넘으면 무효로 해야 한다.
- 발송은 번호·IP·계정별 한도와 재발송 대기 시간을 두고, 서비스하지 않는 나라의 전화번호는 막아야 한다.
- 비밀번호 찾기 문자는 요청 값이 아니라 DB에 등록된 번호로만 보내야 한다.
- 인증번호는 응답·로그에 넣지 않고, 성공 결과는 번호·목적·세션에 묶어 서버가 관리해야 한다.

## 탐지 규칙

- **찾는 곳**: `otp_code`, `verification_code`, `/sms/send`, `/otp/verify`, `/send-code`, 그 밖에 인증번호를 만드는 Math.random·random.randint·mt_rand, 문자 발송 SDK 호출, 인증번호 확인 경로, 비밀번호 찾기의 수신번호 처리.
- **검사 1 — 대입 가능한 인증번호**: 지적할 때: 인증번호를 `Math.random`·`random.randint`·`mt_rand`·`rand`로 만드는 경우. 확인 API에 인증 건당 실패 횟수 제한이나 유효시간이 없거나, 성공 뒤 번호를 지우지 않는 경우. / 지적하지 않을 때: 암호학적 난수로 만들고 실패 횟수·만료·1회 사용이 있는 경우. 발송·확인을 외부 인증 서비스(Twilio Verify, Firebase 전화 인증 등)에 맡겨 그 서비스가 제한하는 경우.
- **검사 2 — 발송 남용**: 지적할 때: 발송 API에 번호·IP·계정별 횟수 제한이나 재발송 대기 시간이 없는 경우. 로그인 없이 부를 수 있는데 캡차·나라별 전화번호 제한도 없는 경우. / 지적하지 않을 때: Redis·DB 같은 공유 저장소 기반 한도와 대기 시간이 있는 경우.
- **검사 3 — 수신번호 변조·번호 노출**: 지적할 때: 비밀번호 찾기·본인 확인에서 수신번호를 요청 값으로 받아 그대로 발송하는 경우. 응답 JSON·로그에 인증번호가 들어가는 경우. 인증 성공 표시를 클라이언트가 들고 다니는 경우. / 지적하지 않을 때: DB에 등록된 번호로만 보내고, 성공 상태를 서버에 두는 경우.
- **등급**: 위험: 확인 시도 제한 없음, 예측 가능한 난수, 수신번호 변조, 응답에 인증번호 포함. / 주의: 발송 한도·대기 시간 없음, 유효시간 10분 초과. / 참고: 계정 존재 여부가 발송 응답으로 드러나는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SMS-001 app/api/password-reset/sms/route.ts:6 — 요청 본문의 phone 으로 재설정 인증번호를 보냄(수신번호 변조) → 이메일로 사용자를 찾아 DB 에 등록된 번호로만 발송`
- **고칠 방법**: 확인 쪽은 인증 건당 실패 횟수와 만료를, 발송 쪽은 번호·IP·계정 한도와 일별 상한을 함께 걸어야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
public function send(Request $request)
{
    $code = (string) mt_rand(100000, 999999);              // 예측 가능한 난수
    Cache::put('otp:'.$request->phone, $code);             // 만료 없음
    Sms::send($request->phone, "인증번호 {$code}");         // 몇 번이든 발송
    return ['sent' => true, 'debug' => $code];             // 응답에 번호
}

public function verify(Request $request)
{
    return ['ok' => Cache::get('otp:'.$request->phone) === $request->code];  // 무한 대입, 성공 뒤에도 남는다
}
```

✓ 안전 코드

```php
public function send(Request $request)
{
    $phone = $request->validate(['phone' => ['required', 'regex:/^01[016789]\d{7,8}$/']])['phone'];  // 국내 휴대폰만
    $limits = ["sms:p:$phone" => [3, 600], "sms:pd:$phone" => [10, 86400], 'sms:ip:'.$request->ip() => [20, 3600]];
    foreach ($limits as $key => [$max, $sec]) {            // 번호 10분 3회·하루 10회, IP 시간당 20회
        abort_if(RateLimiter::tooManyAttempts($key, $max), 429, '잠시 뒤 다시 요청해 주세요.');
        RateLimiter::hit($key, $sec);
    }
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);  // 암호학적 난수
    Cache::put("otp:$phone", $code, now()->addMinutes(5));
    Cache::put("otp-tries:$phone", 0, now()->addMinutes(5));
    Sms::send($phone, "[인증번호] {$code} (5분 안에 입력)");
    return ['sent' => true];                               // 번호는 응답·로그에 넣지 않는다
}

public function verify(Request $request)
{
    $phone = (string) $request->input('phone');
    $code = Cache::get("otp:$phone");
    abort_if(! $code || Cache::increment("otp-tries:$phone") > 5, 422, '인증번호를 다시 받아 주세요.');
    abort_unless(hash_equals($code, (string) $request->input('code')), 422, '인증번호가 맞지 않습니다.');
    Cache::forget("otp:$phone");                           // 한 번만 쓴다
    session(['phone_verified' => ['phone' => $phone, 'at' => now()->timestamp]]);  // 결과는 서버 세션에
    return ['ok' => true];
}
```

### nextjs

✗ 취약 코드

```ts
// app/api/password-reset/sms/route.ts
export async function POST(req: Request) {
  const { userId, phone } = await req.json();               // 화면에 보여 준 번호를 요청에 다시 실어 보낸다
  const code = String(Math.floor(Math.random() * 1e6)).padStart(6, "0");  // 예측 가능한 난수
  await redis.set(`reset:${userId}`, code);                 // 만료 없음
  await sendSms(phone, `인증번호 ${code}`);                  // 번호를 바꾸면 공격자 휴대폰으로 간다
  return Response.json({ ok: true });
}
```

✓ 안전 코드

```ts
import { randomInt } from "node:crypto";

export async function POST(req: Request) {
  const { email } = await req.json();
  const user = await db.user.findUnique({ where: { email: String(email).toLowerCase() } });
  if (user?.phone) {
    const sent = await redis.incr(`reset-sent:${user.id}`);            // 계정별 하루 발송 한도(IP 한도는 미들웨어에서)
    if (sent === 1) await redis.expire(`reset-sent:${user.id}`, 86400);
    if (sent <= 5) {
      const code = randomInt(0, 1_000_000).toString().padStart(6, "0"); // 암호학적 난수
      await redis.set(`reset:${user.id}`, code, { ex: 300 });          // 5분(Upstash Redis 문법)
      await redis.set(`reset-tries:${user.id}`, 0, { ex: 300 });
      await sendSms(user.phone, `[비밀번호 재설정] 인증번호 ${code}`); // DB 에 등록된 번호로만
    }
  }
  return Response.json({ ok: true });                                  // 계정 유무와 관계없이 같은 응답
}
```

### fastapi

✗ 취약 코드

```python
import random

def issue_code(phone: str) -> str:
    code = str(random.randint(100000, 999999))   # 예측 가능한 난수
    r.set(f"otp:{phone}", code)                  # 만료 없음
    return code

@app.post("/otp/verify")
def verify(body: VerifyIn):
    code = r.get(f"otp:{body.phone}")
    if code and code.decode() == body.code:      # 틀려도 몇 번이든 다시
        return {"ok": True, "phone_verified": True}  # 성공 표시를 클라이언트에 맡긴다
    return {"ok": False}
```

✓ 안전 코드

```python
import hmac, secrets, time
from typing import Literal
from pydantic import BaseModel, Field

class VerifyIn(BaseModel):
    phone: str = Field(pattern=r"^01[016789]\d{7,8}$")
    code: str = Field(pattern=r"^\d{6}$")                  # 숫자 6자리만
    purpose: Literal["signup", "reset"]

def issue_code(phone: str, purpose: str) -> str:
    code = f"{secrets.randbelow(1_000_000):06d}"            # 암호학적 난수
    key = f"otp:{purpose}:{phone}"                          # 목적별로 따로 저장
    r.set(key, code, ex=300)                                # 5분
    r.set(f"{key}:tries", 0, ex=300)
    return code

@app.post("/otp/verify")
def verify(body: VerifyIn, request: Request):
    key = f"otp:{body.purpose}:{body.phone}"                # 발급 때 목적과 같아야 찾힌다
    code = r.get(key)
    if code is None or r.incr(f"{key}:tries") > 5:          # 인증 건당 5번까지
        r.delete(key)
        raise HTTPException(400, "인증번호를 다시 받아 주세요")
    if not hmac.compare_digest(code.decode(), body.code):
        raise HTTPException(400, "인증번호가 맞지 않습니다")
    r.delete(key, f"{key}:tries")                           # 한 번만
    request.session["phone_verified"] = {"phone": body.phone, "purpose": body.purpose, "at": int(time.time())}
    return {"ok": True}

def require_verified(request: Request, phone: str, purpose: str) -> None:  # 가입·재설정 단계에서 호출
    v = request.session.pop("phone_verified", None)         # 서명된 세션(SessionMiddleware), 꺼내면 삭제
    if not v or v["phone"] != phone or v["purpose"] != purpose or time.time() - v["at"] > 600:
        raise HTTPException(403, "휴대폰 인증을 다시 해 주세요")
```
