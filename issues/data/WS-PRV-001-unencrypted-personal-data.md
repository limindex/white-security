---
id: WS-PRV-001
title: Unencrypted Sensitive Data at Rest (개인정보 암호화 저장 누락)
slug: unencrypted-personal-data
group: data
severity: high
grade_hint: "고유식별정보·카드·계좌를 평문 저장, 키가 소스에"
site_only: false
depth: brief
languages: [laravel, django, nextjs]
maps:
  owasp2025: [A04]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V14.1.1, V14.2.4, V11.3.3, V13.3.1]
  cwe: [CWE-311, CWE-312, CWE-359]
  kr_guide: ["보안기능-5"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Django, Next.js·Node.js, 여권번호·운전면허번호·외국인등록번호·카드번호·계좌번호·생체인식정보를 저장하는 서비스]
severity_reason: "영향: DB·백업이 한 번 새면 고유식별정보·금융정보가 원문 그대로 유출 · 악용: SQL 삽입·DB 계정 탈취·백업 파일 노출 중 하나면 충분"
sources:
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제7조
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: 개인정보 보호법 (법률 제21445호) 제24조 고유식별정보의 처리 제한
    url: https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EB%B3%B4%ED%98%B8%EB%B2%95
    year: 2026
  - title: 개인정보보호위원회 개인정보의 안전성 확보조치 기준 안내서 (2026.11, 암호화·암호 키 관리 항목)
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=12611
    year: 2026
  - title: OWASP Cryptographic Storage Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html
    year: 2026
  - title: "Laravel 12.x - Eloquent: Mutators & Casting (Encrypted Casting)"
    url: https://laravel.com/docs/12.x/eloquent-mutators
    year: 2026
---

## 요약

여권번호·운전면허번호·외국인등록번호 같은 고유식별정보(법령이 사람마다 부여한 번호)와 카드번호·계좌번호·생체인식정보를 DB에 평문으로 저장해, DB나 백업이 한 번 새면 원문이 그대로 나가는 문제(진단가이드 이름: 암호화되지 않은 중요정보). 「개인정보의 안전성 확보조치 기준」 제7조 ②는 이용자의 이 정보들(주민등록번호 포함, WS-PRV-004)을 안전한 암호 알고리즘으로 암호화해 저장하도록 정함.

## 대응 방법

```text
DB 스키마·모델에서 여권번호·운전면허번호·외국인등록번호·카드번호·계좌번호·생체인식정보 컬럼을 찾아 암호화 여부를 표로 보여 줘.
평문 컬럼은 AES-GCM 같은 인증 암호(변조도 잡는 암호)로 저장하고, 키는 DB와 코드 밖(환경 변수·KMS)에서 읽게 고쳐 줘.
검색이 필요한 값은 별도 키로 만든 HMAC 값 컬럼으로 찾게 하고, 카드번호는 결제대행사 토큰으로 바꿔 줘.
```

- DB에서 해당 컬럼을 직접 조회하면 원문이 아니라 암호문이 보여야 한다.
- 암호화 키가 저장소 파일·DB 테이블 어디에도 없어야 한다.

## 점검 항목

- 고유식별정보·카드번호·계좌번호·생체인식정보는 암호화해 저장해야 한다.
- 암호화는 검증된 라이브러리의 인증 암호(AES-GCM 등)를 써야 한다.
- 암호화 키는 코드·DB 밖(환경 변수·KMS)에 두고 교체 절차를 정해야 한다.
- 검색은 원문 컬럼 대신 별도 키로 만든 HMAC 값으로 해야 한다.
- 카드번호는 꼭 필요하지 않으면 저장하지 말고 결제대행사 토큰을 써야 한다.
- 캐시·검색 색인·분석 테이블에 평문 사본을 두지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `card_number`, `account_number`, `passport_no`, `license_no`, `biometric`, `=> 'encrypted'`, `AESGCM(`, `createCipheriv(`, 그 밖에 DB 마이그레이션·모델·Prisma 스키마의 외국인등록번호·얼굴 특징값 컬럼, 결제·정산 테이블, 암호화 키를 읽는 설정.
- **검사 1 — 평문 저장**: 지적할 때: 위 정보 컬럼이 문자열·`VARCHAR`·`CharField`에 원문 그대로 들어가는 경우, 번호 일부만 암호화하고 나머지를 평문으로 두는 경우, Base64 같은 인코딩을 암호화로 쓰는 경우. / 지적하지 않을 때: 암호화 캐스트(`'encrypted'`)나 인증 암호로 저장하는 경우, 화면 표시용으로 끝 몇 자리만 따로 두는 경우.
- **검사 2 — 약한 방식·키 위치**: 지적할 때: ECB·고정 IV·MD5·SHA-1을 쓰는 경우, 키 없는 해시(SHA-256 단독)로 저장해 가능한 번호를 모두 대입하면 되돌릴 수 있는 경우, 키가 소스·DB·같은 테이블에 있는 경우. / 지적하지 않을 때: 키를 환경 변수·KMS에서 읽고 AES-GCM·Laravel `Crypt`처럼 검증된 방식을 쓰는 경우.
- **검사 3 — 평문 사본**: 지적할 때: 암호화한 값을 찾으려고 원문 컬럼을 따로 두거나, 캐시·검색 색인·분석용 테이블에 원문을 복사하는 경우. / 지적하지 않을 때: 별도 키 HMAC 값 컬럼으로 찾는 경우.
- **등급**: 위험: 고유식별정보·카드번호·계좌번호·생체인식정보를 평문이나 키 없는 해시로 저장(고시 제7조 ② 목록), 암호화 키가 소스에 있음. / 주의: ECB·고정 IV, 캐시·색인의 평문 사본. / 참고: 키 교체 절차가 보이지 않음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PRV-001 database/migrations/2026_11_01_create_payouts_table.php:14 — account_number 를 string 평문으로 저장 → encrypted 캐스트로 바꾸고 검색은 HMAC 컬럼으로`
- **검토 주의**: 고시 제7조 ②는 정보통신서비스 이용자의 개인정보 기준이고, 직원·거래처처럼 이용자가 아닌 정보주체의 고유식별정보는 제7조 ③(인터넷·DMZ 저장은 암호화, 내부망은 주민등록번호 외엔 영향평가 대상 공공기관은 영향평가 결과, 그 밖에는 위험도 분석 결과에 따라 정할 수 있음)을 따르므로 해당하는 경우를 나눠 말하고 법적 판단은 사용자에게 확인을 권해야 한다. 비밀번호는 WS-PWD-001, 알고리즘 세부는 WS-CRY-001, 주민등록번호는 WS-PRV-004에서 다룬다.
- **고칠 방법**: 암호화 컬럼을 새로 만들어 기존 값을 옮긴 뒤 평문 컬럼과 그 사본(백업 포함)을 지워야 하고, 키는 환경 변수·KMS에서만 읽어야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// 마이그레이션: $table->string('account_number');  — 평문 저장
class Payout extends Model
{
    protected $fillable = ['user_id', 'account_number'];
}

Payout::create(['user_id' => $user->id, 'account_number' => $request->input('account_number')]);
$found = Payout::where('account_number', $input)->first();   // 원문으로 검색
```

✓ 안전 코드

```php
// 마이그레이션: $table->text('account_number'); $table->char('account_number_idx', 64)->index();
// config/app.php: 'index_key' => env('INDEX_KEY'),  — APP_KEY 와 다른 검색 전용 키
class Payout extends Model
{
    protected $fillable = ['user_id', 'account_number', 'account_number_idx'];

    protected function casts(): array
    {
        return ['account_number' => 'encrypted'];   // APP_KEY 로 암호화(AES-256-CBC + MAC)
    }

    public static function blindIndex(string $plain): string
    {
        $normalized = preg_replace('/[\s-]/', '', $plain);   // 하이픈·공백을 빼야 같은 번호가 같은 값이 됨
        return hash_hmac('sha256', $normalized, config('app.index_key'));
    }
}

$no = $request->validated('account_number');
Payout::create(['user_id' => $user->id, 'account_number' => $no, 'account_number_idx' => Payout::blindIndex($no)]);
$found = Payout::where('account_number_idx', Payout::blindIndex($input))->first();
```

### django

✗ 취약 코드

```python
import hashlib
from django.db import models

class Customer(models.Model):
    passport_no = models.CharField(max_length=20)            # 평문
    passport_hash = models.CharField(max_length=64)          # 키 없는 SHA-256: 번호를 대입하면 되돌릴 수 있음

def save_customer(passport_no: str) -> Customer:
    return Customer.objects.create(passport_no=passport_no,
                                   passport_hash=hashlib.sha256(passport_no.encode()).hexdigest())
```

✓ 안전 코드

```python
# privacy/crypto.py — 키는 환경 변수에서(settings 가 os.environ 으로 읽음), 32바이트 Base64
import base64, hashlib, hmac, os
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from django.conf import settings

_KEY = base64.b64decode(settings.FIELD_ENCRYPTION_KEY)
_INDEX_KEY = base64.b64decode(settings.FIELD_INDEX_KEY)     # 검색 전용 별도 키

def encrypt(plain: str, aad: bytes) -> str:
    nonce = os.urandom(12)                                    # 암호화마다 새 nonce
    return base64.b64encode(nonce + AESGCM(_KEY).encrypt(nonce, plain.encode(), aad)).decode()

def decrypt(token: str, aad: bytes) -> str:
    raw = base64.b64decode(token)
    return AESGCM(_KEY).decrypt(raw[:12], raw[12:], aad).decode()   # 변조되면 InvalidTag

def blind_index(plain: str) -> str:
    normalized = "".join(plain.split()).replace("-", "")     # 하이픈·공백을 빼야 같은 번호가 같은 값이 됨
    return hmac.new(_INDEX_KEY, normalized.encode(), hashlib.sha256).hexdigest()

# models.py
class Customer(models.Model):
    passport_no_enc = models.TextField()
    passport_no_idx = models.CharField(max_length=64, db_index=True)

AAD = b"customer.passport_no"                                 # 다른 컬럼으로 옮겨 붙인 암호문은 거절
c = Customer.objects.create(passport_no_enc=encrypt(no, AAD), passport_no_idx=blind_index(no))
```

### nextjs

✗ 취약 코드

```ts
// app/api/billing/route.ts
export async function POST(req: Request) {
  const { cardNumber, expiry } = await req.json();
  await db.card.create({ data: { userId: session.userId, cardNumber, expiry } }); // 카드번호 평문 저장
  return Response.json({ ok: true });
}
```

✓ 안전 코드

```ts
// lib/field-crypto.ts — 서버 전용. FIELD_KEY 는 32바이트 Base64(NEXT_PUBLIC_ 금지)
import "server-only";
import { createCipheriv, createDecipheriv, randomBytes } from "node:crypto";

const key = Buffer.from(process.env.FIELD_KEY!, "base64");

export function encryptField(plain: string, aad: string): string {
  const iv = randomBytes(12);
  const c = createCipheriv("aes-256-gcm", key, iv, { authTagLength: 16 }).setAAD(Buffer.from(aad));
  const body = Buffer.concat([c.update(plain, "utf8"), c.final()]);
  return Buffer.concat([iv, c.getAuthTag(), body]).toString("base64");
}

export function decryptField(token: string, aad: string): string {
  const raw = Buffer.from(token, "base64");
  const d = createDecipheriv("aes-256-gcm", key, raw.subarray(0, 12), { authTagLength: 16 }).setAAD(Buffer.from(aad));
  d.setAuthTag(raw.subarray(12, 28));
  return Buffer.concat([d.update(raw.subarray(28)), d.final()]).toString("utf8"); // 변조되면 예외
}

// 카드번호는 받지 않는다: 결제대행사 결제창에서 받은 빌링키(토큰)만 저장
// await db.card.create({ data: { userId, billingKey: encryptField(billingKey, "card.billingKey"), last4 } });
```
