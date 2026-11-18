<!-- generated from white-security issues; do not edit -->
# WS-PWD-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 가입: 빠르고 솔트 없는 해시
User::create(['email' => $request->email, 'password' => md5($request->password)]);

// 로그인: 해시를 SQL 에서 문자열로 비교
$user = User::where('email', $request->email)
    ->where('password', md5($request->password))->first();

// 규칙: 문자 조합 강제 + 16자 상한
$request->validate(['password' => ['required', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*\W).{8,16}$/']]);
```

✓ 안전 코드 (safe example)

```php
use Illuminate\Validation\Rules\Password;

$data = $request->validate([
    'email' => 'required|email|unique:users',
    'password' => ['required', 'string', 'max:128', Password::min(10)->uncompromised()],  // 유출 목록 확인
]);
User::create(['email' => $data['email'], 'password' => Hash::make($data['password'])]);  // 기본 bcrypt

// 로그인: 비교는 Auth::attempt(내부에서 Hash::check)가 한다. 설정이 바뀌면 로그인 때 다시 해시
if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
    $request->session()->regenerate();
}
// .env: HASH_DRIVER=argon2id 로 바꾸면 Argon2id
```
