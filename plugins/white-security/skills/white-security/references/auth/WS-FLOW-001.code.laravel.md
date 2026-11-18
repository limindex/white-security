<!-- generated from white-security issues; do not edit -->
# WS-FLOW-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function store(Request $request)
{
    $user = User::create($request->validate(['email' => 'required|email|unique:users', 'name' => 'required']));
    $user->increment('points', 5000);                    // 가입만 하면 지급 → 계정을 계속 만든다
    if ($ref = User::where('referral_code', $request->input('ref'))->first()) {
        $ref->increment('points', 3000);                 // 자기 계정을 추천인으로 넣어 무한 적립
    }
    return redirect('/');
}
```

✓ 안전 코드 (safe example)

```php
// 마이그레이션: rewards 테이블의 reward_key 에 unique 인덱스
// 휴대폰 본인 인증이 끝난 뒤에만 호출(가입 즉시 지급하지 않음)
public function grantSignupReward(User $user): void
{
    $key = 'signup:'.hash_hmac('sha256', $user->verified_phone, config('app.key'));  // 사람 단위 키
    try {
        DB::transaction(function () use ($user, $key) {
            Reward::create(['user_id' => $user->id, 'reward_key' => $key, 'points' => 5000]);
            $user->increment('points', 5000);
        });
    } catch (UniqueConstraintViolationException) {
        // 같은 번호로 이미 받은 혜택 → 지급하지 않음
    }
}
// 추천 보상도 같은 방식: 추천인·가입자 번호가 다르고, 가입자가 첫 구매를 마친 뒤 지급
// 가입 라우트: ->middleware('throttle:signup') + 캡차 토큰 서버 검증
```
