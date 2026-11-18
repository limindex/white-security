<!-- generated from white-security issues; do not edit -->
# WS-OAUTH-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// routes/web.php
Route::get('/auth/google', fn () => Socialite::driver('google')->stateless()->redirect());

Route::get('/auth/google/callback', function () {
    $g = Socialite::driver('google')->stateless()->user();  // state 확인을 끔 → 로그인 CSRF
    $user = User::firstOrCreate(['email' => $g->getEmail()], ['name' => $g->getName()]);  // 이메일로 기존 계정에 연결
    Auth::login($user);
    return redirect('/');
});
```

✓ 안전 코드 (safe example)

```php
// state 는 Socialite 가 기본으로 세션에 저장·확인, PKCE 는 시작·콜백 모두 켠다
Route::get('/auth/google', fn () => Socialite::driver('google')->enablePKCE()->redirect());

Route::get('/auth/google/callback', function () {
    $g = Socialite::driver('google')->enablePKCE()->user();   // state 가 다르면 InvalidStateException
    $account = SocialAccount::firstWhere(['provider' => 'google', 'provider_id' => $g->getId()]);
    if (! $account) {
        abort_if(User::where('email', $g->getEmail())->exists(), 409, '기존 계정으로 로그인한 뒤 연결해 주세요.');
        $user = User::create(['name' => $g->getName(), 'email' => $g->getEmail()]);
        $account = $user->socialAccounts()->create(['provider' => 'google', 'provider_id' => $g->getId()]);
    }
    Auth::login($account->user);
    return redirect()->intended('/');                          // 우리 앱 안 주소만
});
// config/services.php 'redirect' 는 제공자 콘솔에 등록한 주소와 정확히 같은 값 하나
```
