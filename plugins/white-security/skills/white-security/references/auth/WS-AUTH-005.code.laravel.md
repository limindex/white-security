<!-- generated from white-security issues; do not edit -->
# WS-AUTH-005 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 본인확인 팝업이 끝나면 결과 페이지가 이름·번호를 hidden 필드로 가입 폼에 넘긴다
public function register(Request $request)
{
    if ($request->input('cert_ok') !== 'Y') abort(403);   // 브라우저가 보낸 값
    User::create([
        'name'     => $request->input('name'),             // 다른 이름·번호로 바꿔 보내도 통과
        'phone'    => $request->input('phone'),
        'birth'    => $request->input('birth'),
        'password' => Hash::make($request->input('password')),
    ]);
}
```

✓ 안전 코드 (safe example)

```php
// 콜백: 요청 때 세션에 둔 요청번호와 대조, 결과는 서버 모듈로 복호화·검증
public function callback(Request $request, IdentityVerifier $verifier)  // 기관 서버 모듈을 감싼 클래스
{
    $r = $verifier->decryptAndVerify((string) $request->input('enc_data'));
    abort_unless($r && hash_equals((string) session()->pull('cert_req_no'), $r->requestNo), 403);
    session(['cert' => ['name' => $r->name, 'phone' => $r->phone, 'birth' => $r->birth,
                        'di' => $r->di, 'at' => now()->timestamp]]);
}

public function register(Request $request)
{
    $cert = session()->pull('cert');                                 // 한 번 쓰면 사라진다
    abort_if(! $cert || now()->timestamp - $cert['at'] > 600, 403);  // 10분 안에만
    abort_if(User::where('di', $cert['di'])->exists(), 409);        // 같은 사람 중복 가입 차단
    $data = $request->validate(['password' => 'required|string|min:8']);
    User::create(['name' => $cert['name'], 'phone' => $cert['phone'], 'birth' => $cert['birth'],
                  'di' => $cert['di'], 'password' => Hash::make($data['password'])]);  // 값은 세션에서
}
```
