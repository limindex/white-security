---
id: WS-CRY-001
title: Weak Cryptography (취약한 암호 알고리즘·난수·인증서 검증 끔)
slug: weak-cryptography
group: deploy
severity: high
grade_hint: "인증서 검증 끔, 예측 가능한 토큰 난수, ECB·고정 IV"
site_only: false
depth: brief
languages: [python, nextjs, laravel]
maps:
  owasp2025: [A04]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V11.2.3, V11.3.1, V11.3.2, V11.3.3, V11.4.1, V11.5.1, V12.2.1, V12.3.2]
  cwe: [CWE-327, CWE-326, CWE-330, CWE-338, CWE-295, CWE-319, CWE-329]
  kr_guide: ["보안기능-4", "보안기능-5", "보안기능-7", "보안기능-8", "보안기능-11"]
  kr_ciip: [SN]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, Node.js·Next.js 서버 코드, Laravel·PHP, 개인정보·토큰을 암호화하거나 외부 API를 HTTPS로 부르는 모든 서비스]
severity_reason: "영향: 암호화한 개인정보 복원·위조, 토큰 추측으로 계정 탈취, 통신 엿보기·변조 · 악용: 같은 망(공용 와이파이·클라우드 내부)이나 공개된 암호문만으로 가능"
sources:
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제7조
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: 개인정보보호위원회 개인정보의 안전성 확보조치 기준 안내서 (2026.11, 안전한 암호 알고리즘 예시)
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=12611
    year: 2026
  - title: "OWASP ASVS 5.0 - V11 Cryptography·V12 Secure Communication"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: OWASP Cryptographic Storage Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html
    year: 2026
  - title: "OWASP Top 10:2025 - A04 Cryptographic Failures"
    url: https://top10.owasp.org/2025/A04_2025-Cryptographic_Failures
    year: 2025
  - title: "Laravel 12.x - Encryption (AES-256-CBC + MAC)"
    url: https://laravel.com/docs/12.x/encryption
    year: 2026
  - title: "NIST SP 800-38D - GCM (무작위 IV 사용 횟수 한도 2^32)"
    url: https://csrc.nist.gov/pubs/sp/800/38/d/final
    year: 2007
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

ECB 모드·고정 IV·MD5·SHA-1·짧은 RSA 키처럼 약한 암호를 쓰거나, 토큰을 `Math.random()`·`random`·`rand()`로 만들거나, HTTPS 인증서 검증을 꺼(`verify=False`·`rejectUnauthorized: false`) 암호문 복원·토큰 추측·중간자 공격(통신 중간에서 엿보고 바꾸기)이 가능한 문제(진단가이드 이름: 취약한 암호화 알고리즘 사용).

## 위험 배경

- 「개인정보의 안전성 확보조치 기준」 제7조는 인증정보·고유식별정보 등의 안전한 암호화와 인터넷 구간 전송 암호화를 요구.

## 대응 방법

```text
암호화·해시·난수·TLS 설정 코드를 찾아 알고리즘·모드·키 길이·IV 만드는 방식·인증서 검증 여부를 표로 보여 줘.
암호화는 AES-GCM(매번 새 무작위 nonce)이나 프레임워크 암호화 함수로, 토큰·인증번호는 CSPRNG(암호용 난수 생성기)로 바꿔 줘.
verify=False·rejectUnauthorized: false·withoutVerifying() 같은 인증서 검증 끔은 지우고, 사설 인증서면 그 CA 만 신뢰하게 해 줘.
```

- 같은 평문을 두 번 암호화하면 결과가 달라야 하고, 암호문을 한 글자 바꾸면 복호화가 실패해야 한다.
- 보안 용도의 MD5·SHA-1·ECB·`Math.random`·인증서 검증 끔이 코드에 남아 있지 않아야 한다.

## 점검 항목

- 대칭키 암호는 AES-GCM 같은 인증 암호를 쓰고, ECB·고정 IV는 쓰지 않아야 한다.
- nonce·IV는 암호화할 때마다 CSPRNG로 새로 만들어야 한다.
- 서명·무결성 확인은 HMAC-SHA-256 이상으로 하고 MD5·SHA-1은 쓰지 않아야 한다.
- 토큰·재설정 링크 값은 CSPRNG로 128비트 이상, 인증번호도 CSPRNG로 만들어야 한다.
- RSA 키는 2048비트 이상(새로 만들면 3072비트 이상)이어야 한다.
- HTTPS 호출의 인증서 검증을 끄지 않고, 개인정보는 HTTPS로만 보내야 한다.

## 탐지 규칙

- **찾는 곳**: `verify=False`, `rejectUnauthorized: false`, `NODE_TLS_REJECT_UNAUTHORIZED`, `withoutVerifying(`, `MODE_ECB`, 그 밖에 aes-*-ecb·createCipheriv·openssl_encrypt 의 IV 인자, md5·sha1 로 만드는 서명, Math.random·random.choice·mt_rand·uniqid 로 만드는 토큰, CURLOPT_SSL_VERIFYPEER, RSA 키 길이.
- **검사 1 — 약한 알고리즘·모드**: 지적할 때: ECB 모드(`MODE_ECB`, `aes-*-ecb`), 상수·0으로 채운 IV나 키에서 만든 IV, DES·3DES·RC4, MD5·SHA-1을 서명·무결성·토큰 확인에 쓰는 경우, RSA 2048비트 미만, 인증 없는 CBC로 개인정보를 암호화하는 경우. / 지적하지 않을 때: 캐시 키·파일 중복 확인처럼 보안과 무관한 해시, `Crypt::encryptString`·Fernet·AES-GCM(무작위 nonce)을 쓰는 경우, 외부 서비스가 HMAC-SHA1 서명을 정해 보내는 웹훅을 그 방식대로 확인하는 경우(상수 시간 비교일 때).
- **검사 2 — 예측 가능한 난수**: 지적할 때: 세션·API 토큰·비밀번호 재설정·인증번호·비밀 링크 값을 `Math.random`, `random.random`·`random.choice`, `rand()`·`mt_rand()`·`uniqid()`, 현재 시각으로 만드는 경우. / 지적하지 않을 때: `secrets`, `crypto.randomBytes`·`crypto.getRandomValues`, `random_bytes`·`random_int`·`Str::random`을 쓰는 경우, 화면 효과·표본 추출 같은 보안과 무관한 용도.
- **검사 3 — 인증서 검증 끔·평문 전송**: 지적할 때: `verify=False`, `rejectUnauthorized: false`, `NODE_TLS_REJECT_UNAUTHORIZED=0`, `withoutVerifying()`, `CURLOPT_SSL_VERIFYPEER => false`, 개인정보·토큰을 `http://`로 보내는 경우. / 지적하지 않을 때: 테스트 코드에서 로컬 가짜 서버에만 쓰는 경우, 사설 CA 파일을 지정(`verify="/path/ca.pem"`)한 경우.
- **등급**: 위험: 운영 코드의 인증서 검증 끔, 토큰·인증번호를 예측 가능한 난수로 만듦, 개인정보 암호화에 ECB·고정 IV. / 주의: MD5·SHA-1 서명·무결성 확인, RSA 2048비트 미만, 인증 없는 CBC. / 참고: RSA 2048~3071비트(ASVS 11.2.3 L2는 3072비트), 쓰는 알고리즘·키 목록 없음, 키 교체 절차 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CRY-001 app/payments.py:22 — requests.post(..., verify=False) 라 결제 API 통신을 중간에서 엿보고 바꿀 수 있음 → verify 인자를 지우고 사설 CA 면 CA 파일 경로 지정`
- **검토 주의**: 비밀번호 저장은 WS-PWD-001, 키를 코드에 적는 문제는 WS-SEC-001에서 다루므로 여기서는 알고리즘·모드·난수·TLS만 봐야 한다. 국내 공공 사업은 검증필 암호모듈(KCMVP)을 요구받는 경우가 있으므로 해당하면 사용자에게 확인을 권해야 한다. 무작위 96비트 nonce를 쓰는 AES-GCM은 키 하나로 약 2^32개 메시지까지만 암호화하고 그 전에 키를 바꿔야 한다(NIST SP 800-38D).
- **고칠 방법**: 직접 짠 암호 코드를 프레임워크 암호화 함수나 AES-GCM으로 바꾸고, 기존 암호문은 새 방식으로 다시 암호화하는 이전 계획을 함께 세워야 한다.

## 코드 예시

### python

✗ 취약 코드

```python
import hashlib, random, requests
from Crypto.Cipher import AES   # pycryptodome

def encrypt(data: bytes, key: bytes) -> bytes:
    return AES.new(key, AES.MODE_ECB).encrypt(data.ljust(32))   # 같은 평문 → 같은 암호문

def reset_token() -> str:
    return "".join(random.choice("0123456789abcdef") for _ in range(32))   # 예측 가능

def sign(body: bytes) -> str:
    return hashlib.md5(SECRET + body).hexdigest()               # MD5, 비밀값 앞붙이기

requests.post("https://pay.example.com/api", json=payload, verify=False)   # 인증서 검증 끔
```

✓ 안전 코드

```python
import hashlib, hmac, os, secrets, requests
from cryptography.hazmat.primitives.ciphers.aead import AESGCM

KEY = bytes.fromhex(os.environ["DATA_KEY_HEX"])   # 32바이트(AES-256), 키는 KMS·실행 환경에서
SIGN_KEY = os.environ["SIGN_KEY"].encode()

def encrypt(data: bytes, aad: bytes = b"") -> bytes:
    nonce = os.urandom(12)                         # 매번 새 nonce
    return nonce + AESGCM(KEY).encrypt(nonce, data, aad)

def decrypt(blob: bytes, aad: bytes = b"") -> bytes:
    return AESGCM(KEY).decrypt(blob[:12], blob[12:], aad)   # 변조되면 InvalidTag

def reset_token() -> str:
    return secrets.token_urlsafe(32)               # 256비트

def sign(body: bytes) -> str:
    return hmac.new(SIGN_KEY, body, hashlib.sha256).hexdigest()   # 비교는 hmac.compare_digest

requests.post("https://pay.example.com/api", json=payload, timeout=10)   # verify 기본값(True) 유지
```

### nextjs

✗ 취약 코드

```ts
// lib/crypto.ts
import crypto from "node:crypto";

const KEY = Buffer.from(process.env.DATA_KEY_HEX!, "hex");
const IV = Buffer.alloc(16, 0);                                       // 고정 IV

export function encrypt(text: string) {
  const c = crypto.createCipheriv("aes-256-cbc", KEY, IV);            // 변조 검사 없는 CBC
  return c.update(text, "utf8", "base64") + c.final("base64");
}
export const inviteCode = () => Math.random().toString(36).slice(2);  // 예측 가능

process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";   // 사설 인증서 오류를 없애려고 전체 TLS 검증을 끔
```

✓ 안전 코드

```ts
// lib/crypto.ts
import "server-only";
import crypto from "node:crypto";

const KEY = Buffer.from(process.env.DATA_KEY_HEX ?? "", "hex");
if (KEY.length !== 32) throw new Error("DATA_KEY_HEX 는 32바이트(16진수 64자리)여야 합니다");

export function encrypt(text: string): string {
  const iv = crypto.randomBytes(12);                                  // 매번 새 nonce
  const c = crypto.createCipheriv("aes-256-gcm", KEY, iv);
  const body = Buffer.concat([c.update(text, "utf8"), c.final()]);
  return Buffer.concat([iv, c.getAuthTag(), body]).toString("base64");
}
export function decrypt(b64: string): string {
  const buf = Buffer.from(b64, "base64");
  if (buf.length < 28) throw new Error("암호문이 너무 짧습니다");
  const d = crypto.createDecipheriv("aes-256-gcm", KEY, buf.subarray(0, 12), { authTagLength: 16 });
  d.setAuthTag(buf.subarray(12, 28));                                 // 변조되면 final() 에서 오류
  return Buffer.concat([d.update(buf.subarray(28)), d.final()]).toString("utf8");
}
export const inviteCode = () => crypto.randomBytes(16).toString("base64url");   // 128비트
// 사설 CA 는 NODE_EXTRA_CA_CERTS=/path/ca.pem 으로 그 CA 만 더하고 검증은 켜 둔다
```

### laravel

✗ 취약 코드

```php
// 개인정보 암호화: ECB 모드 + 코드 속 키
$enc = openssl_encrypt($phone, 'aes-128-ecb', 'my-secret-key');

// 비밀번호 재설정 토큰: 예측 가능
$token = md5(uniqid((string) mt_rand(), true));

// 사설 인증서 오류를 없애려고 검증을 끔
$res = Http::withoutVerifying()->post('https://partner.example.com/api', $payload);
```

✓ 안전 코드

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
