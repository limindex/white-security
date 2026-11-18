<!-- generated from white-security issues; do not edit -->
# WS-CRY-001 Weak Cryptography (취약한 암호 알고리즘·난수·인증서 검증 끔)

## 탐지 규칙

- **찾는 곳**: `verify=False`, `rejectUnauthorized: false`, `NODE_TLS_REJECT_UNAUTHORIZED`, `withoutVerifying(`, `MODE_ECB`, 그 밖에 aes-*-ecb·createCipheriv·openssl_encrypt 의 IV 인자, md5·sha1 로 만드는 서명, Math.random·random.choice·mt_rand·uniqid 로 만드는 토큰, CURLOPT_SSL_VERIFYPEER, RSA 키 길이.
- **검사 1 — 약한 알고리즘·모드**: 지적할 때: ECB 모드(`MODE_ECB`, `aes-*-ecb`), 상수·0으로 채운 IV나 키에서 만든 IV, DES·3DES·RC4, MD5·SHA-1을 서명·무결성·토큰 확인에 쓰는 경우, RSA 2048비트 미만, 인증 없는 CBC로 개인정보를 암호화하는 경우. / 지적하지 않을 때: 캐시 키·파일 중복 확인처럼 보안과 무관한 해시, `Crypt::encryptString`·Fernet·AES-GCM(무작위 nonce)을 쓰는 경우, 외부 서비스가 HMAC-SHA1 서명을 정해 보내는 웹훅을 그 방식대로 확인하는 경우(상수 시간 비교일 때).
- **검사 2 — 예측 가능한 난수**: 지적할 때: 세션·API 토큰·비밀번호 재설정·인증번호·비밀 링크 값을 `Math.random`, `random.random`·`random.choice`, `rand()`·`mt_rand()`·`uniqid()`, 현재 시각으로 만드는 경우. / 지적하지 않을 때: `secrets`, `crypto.randomBytes`·`crypto.getRandomValues`, `random_bytes`·`random_int`·`Str::random`을 쓰는 경우, 화면 효과·표본 추출 같은 보안과 무관한 용도.
- **검사 3 — 인증서 검증 끔·평문 전송**: 지적할 때: `verify=False`, `rejectUnauthorized: false`, `NODE_TLS_REJECT_UNAUTHORIZED=0`, `withoutVerifying()`, `CURLOPT_SSL_VERIFYPEER => false`, 개인정보·토큰을 `http://`로 보내는 경우. / 지적하지 않을 때: 테스트 코드에서 로컬 가짜 서버에만 쓰는 경우, 사설 CA 파일을 지정(`verify="/path/ca.pem"`)한 경우.
- **등급**: 위험: 운영 코드의 인증서 검증 끔, 토큰·인증번호를 예측 가능한 난수로 만듦, 개인정보 암호화에 ECB·고정 IV. / 주의: MD5·SHA-1 서명·무결성 확인, RSA 2048비트 미만, 인증 없는 CBC. / 참고: RSA 2048~3071비트(ASVS 11.2.3 L2는 3072비트), 쓰는 알고리즘·키 목록 없음, 키 교체 절차 없음.
- **보고 예**: `[위험] WS-CRY-001 app/payments.py:22 — requests.post(..., verify=False) 라 결제 API 통신을 중간에서 엿보고 바꿀 수 있음 → verify 인자를 지우고 사설 CA 면 CA 파일 경로 지정`
- **고칠 방법**: 직접 짠 암호 코드를 프레임워크 암호화 함수나 AES-GCM으로 바꾸고, 기존 암호문은 새 방식으로 다시 암호화하는 이전 계획을 함께 세워야 한다.

## 점검 항목

- 대칭키 암호는 AES-GCM 같은 인증 암호를 쓰고, ECB·고정 IV는 쓰지 않아야 한다.
- nonce·IV는 암호화할 때마다 CSPRNG로 새로 만들어야 한다.
- 서명·무결성 확인은 HMAC-SHA-256 이상으로 하고 MD5·SHA-1은 쓰지 않아야 한다.
- 토큰·재설정 링크 값은 CSPRNG로 128비트 이상, 인증번호도 CSPRNG로 만들어야 한다.
- RSA 키는 2048비트 이상(새로 만들면 3072비트 이상)이어야 한다.
- HTTPS 호출의 인증서 검증을 끄지 않고, 개인정보는 HTTPS로만 보내야 한다.

## 검토 주의

비밀번호 저장은 WS-PWD-001, 키를 코드에 적는 문제는 WS-SEC-001에서 다루므로 여기서는 알고리즘·모드·난수·TLS만 봐야 한다. 국내 공공 사업은 검증필 암호모듈(KCMVP)을 요구받는 경우가 있으므로 해당하면 사용자에게 확인을 권해야 한다. 무작위 96비트 nonce를 쓰는 AES-GCM은 키 하나로 약 2^32개 메시지까지만 암호화하고 그 전에 키를 바꿔야 한다(NIST SP 800-38D).

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-CRY-001.code.python.md` (Python) · `WS-CRY-001.code.nextjs.md` (Next.js) · `WS-CRY-001.code.laravel.md` (Laravel)

기본 심각도: 위험 · CWE-327 · CWE-326 · CWE-330 · CWE-338 · CWE-295 · CWE-319 · CWE-329
