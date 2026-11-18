<!-- generated from white-security issues; do not edit -->
# WS-CRY-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 개인정보 암호화: ECB 모드 + 코드 속 키
$enc = openssl_encrypt($phone, 'aes-128-ecb', 'my-secret-key');

// 비밀번호 재설정 토큰: 예측 가능
$token = md5(uniqid((string) mt_rand(), true));

// 사설 인증서 오류를 없애려고 검증을 끔
$res = Http::withoutVerifying()->post('https://partner.example.com/api', $payload);
```

✓ 안전 코드 (safe example)

```php
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// 프레임워크 암호화: APP_KEY 로 AES-256-CBC + MAC(변조 검사), 매번 새 IV
$enc = Crypt::encryptString($phone);
$phone = Crypt::decryptString($enc);              // 변조되면 DecryptException

// CSPRNG(random_bytes) 기반 토큰. 비밀번호 재설정은 Laravel Password 브로커를 쓰면 더 좋다
$token = Str::random(64);

// 검증은 켜 두고, 사설 CA 면 그 CA 파일만 지정
$res = Http::withOptions(['verify' => storage_path('certs/partner-ca.pem')])
    ->timeout(10)
    ->post('https://partner.example.com/api', $payload);
```
