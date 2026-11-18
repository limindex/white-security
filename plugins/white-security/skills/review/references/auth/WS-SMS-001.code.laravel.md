<!-- generated from white-security issues; do not edit -->
# WS-SMS-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
