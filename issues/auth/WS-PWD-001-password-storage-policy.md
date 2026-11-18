---
id: WS-PWD-001
title: Weak Password Storage · Policy (비밀번호 저장·작성 규칙)
slug: password-storage-policy
group: auth
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs, django]
maps:
  owasp2025: [A07, A04]
  api2023: [API2]
  llm2026: []
  agentic2026: []
  asvs5: [V6.2.1, V6.2.5, V6.2.10, V6.2.12, V6.4.3, V11.4.2]
  cwe: [CWE-916, CWE-759, CWE-521, CWE-328, CWE-256, CWE-640]
  kr_guide: ["보안기능-9", "보안기능-14", "보안기능-4"]
  kr_ciip: [BF, PR]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js·Node.js, Django, 회원 비밀번호를 직접 저장하는 모든 서비스]
severity_reason: "영향: DB 유출 시 회원 비밀번호 원문 복원·다른 사이트 계정까지 탈취 · 악용: 새어 나간 해시를 GPU로 대입"
sources:
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제7조
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: 개인정보보호위원회 개인정보의 안전성 확보조치 기준 안내서 (2026.11, 비밀번호 작성규칙 FAQ)
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=12611
    year: 2026
  - title: "NIST SP 800-63B-4 Digital Identity Guidelines: Authentication and Authenticator Management (3.1.1 Passwords)"
    url: https://pages.nist.gov/800-63-4/sp800-63b.html
    year: 2025
  - title: OWASP Password Storage Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html
    year: 2026
  - title: "Django - Password management in Django"
    url: https://docs.djangoproject.com/en/5.2/topics/auth/passwords/
    year: 2026
  - title: "Laravel 12.x - Hashing"
    url: https://laravel.com/docs/12.x/hashing
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

비밀번호를 평문·복호화 가능한 암호화·MD5·SHA-1·솔트(사용자마다 다른 임의 값) 없는 SHA-256으로 저장해 DB가 새면 원래 비밀번호가 드러나거나, 길이와 유출 여부 대신 문자 조합·정기 변경만 강요해 쉬운 비밀번호가 남는 문제(진단가이드 이름: 취약한 비밀번호 허용·솔트 없이 일방향 해쉬 함수 사용). 「개인정보의 안전성 확보조치 기준」 제7조는 비밀번호를 복호화되지 않게 일방향 암호화(해시)해 저장하도록 요구.

## 대응 방법

```text
비밀번호를 저장·비교하는 코드를 찾아 어떤 방식(평문·암호화·MD5·SHA·bcrypt·Argon2)인지 알려 줘.
저장은 Argon2id나 bcrypt 같은 비밀번호 전용 해시로 바꾸고, 기존 해시는 다음 로그인 성공 때 새 방식으로 다시 저장하게 해 줘.
가입·변경 때 최소 길이와 유출·흔한 비밀번호 차단을 넣고, 문자 종류 조합 강제와 정기 변경 강제는 빼 줘.
```

- DB의 비밀번호 칸이 `$argon2id$`·`$2y$`·`$2b$`·`pbkdf2_sha256$`처럼 알고리즘 표시로 시작해야 한다.
- `12345678`이나 유출된 적 있는 비밀번호로 가입하면 거절돼야 한다.

## 점검 항목

- 비밀번호는 Argon2id·bcrypt·scrypt·PBKDF2 같은 비밀번호 전용 해시로 저장해야 한다.
- MD5·SHA-1·솔트 없는 SHA-256, 복호화되는 암호화(AES 등)로 저장하지 않아야 한다.
- 해시는 서버에서 만들고, 비교는 라이브러리의 확인 함수로 해야 한다.
- 최소 8자 이상(단독 비밀번호면 15자 권장)을 받고, 64자 이상 긴 비밀번호도 허용해야 한다.
- 유출·흔한 비밀번호는 거절하고, 문자 조합·정기 변경은 강제하지 않아야 한다.
- 비밀번호 재설정 토큰은 무작위로 만들어 짧은 시간 안에 한 번만 쓰게 해야 한다.

## 탐지 규칙

- **찾는 곳**: `md5(`, `sha1(`, `hashlib.sha256(`, `createHash(`, `Crypt::encrypt(`, `MD5PasswordHasher`, 그 밖에 Hash::make·password_hash·make_password 호출, bcrypt·argon2 설정, AUTH_PASSWORD_VALIDATORS, 비밀번호 규칙 정규식, 비밀번호 재설정 토큰 생성.
- **검사 1 — 약한 저장 방식**: 지적할 때: 비밀번호를 평문, 복호화되는 암호화(`Crypt::encrypt`, AES), `md5`·`sha1`·`sha256`(솔트·반복 없이)으로 저장하거나 비교하는 경우. 운영 설정에 `MD5PasswordHasher`가 있는 경우. / 지적하지 않을 때: `Hash::make`·`password_hash(..., PASSWORD_DEFAULT|PASSWORD_ARGON2ID)`·`make_password`·argon2·bcrypt 라이브러리를 쓰는 경우. 비밀번호가 아닌 값(파일 체크섬·캐시 키)에 해시를 쓰는 경우.
- **검사 2 — 약한 설정·비교**: 지적할 때: bcrypt 비용 10 미만, PBKDF2-HMAC-SHA256 반복 60만 회 미만, Argon2id 메모리 19MiB 미만인 경우. 해시를 일반 비교(`==`, `===`)로 맞춰 보는 경우. / 지적하지 않을 때: 프레임워크 기본값(Laravel bcrypt, Django PBKDF2·Argon2)을 그대로 쓰는 경우.
- **검사 3 — 비밀번호 규칙**: 지적할 때: 최소 길이가 8자 미만이거나 최대 길이를 짧게(예: 16자) 막는 경우. 유출·흔한 비밀번호 차단이 없는 경우. / 지적하지 않을 때: `Password::min(...)->uncompromised()`, Django `CommonPasswordValidator`, 유출 비밀번호 조회가 있는 경우.
- **등급**: 위험: 평문·복호화 가능한 암호화·MD5·SHA-1·솔트 없는 해시로 저장하는 경우. / 주의: 비용·반복이 낮은 설정, 최소 8자 미만, 유출 비밀번호 차단 없음. / 참고: 문자 조합·정기 변경 강제, 붙여넣기 막음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PWD-001 app/api/signup/route.ts:14 — 비밀번호를 createHash("sha256") 로 저장(솔트·반복 없음) → @node-rs/argon2 hash 로 바꾸고 로그인 때 옛 해시를 다시 저장`
- **검토 주의**: 국내 점검(주요정보통신기반시설 상세가이드의 약한 비밀번호 정책)은 아직 문자 조합을 보는 경우가 있으므로, 이미 있는 조합 규칙은 지적하지 않고 참고로만 적어야 한다. bcrypt는 72바이트를 넘는 부분을 버리므로 긴 비밀번호를 받으면 Argon2id가 낫다.
- **고칠 방법**: 비밀번호 전용 해시로 바꿔야 하고, 옛 해시는 로그인 성공 때 새 방식으로 다시 저장(rehash)해 사용자가 비밀번호를 바꾸지 않아도 옮겨지게 해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// 가입: 빠르고 솔트 없는 해시
User::create(['email' => $request->email, 'password' => md5($request->password)]);

// 로그인: 해시를 SQL 에서 문자열로 비교
$user = User::where('email', $request->email)
    ->where('password', md5($request->password))->first();

// 규칙: 문자 조합 강제 + 16자 상한
$request->validate(['password' => ['required', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*\W).{8,16}$/']]);
```

✓ 안전 코드

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

### nextjs

✗ 취약 코드

```ts
import { createHash } from "crypto";

// 가입: 솔트·반복 없음 → 같은 비밀번호는 언제나 같은 값, GPU 로 빠르게 대입된다
const passwordHash = createHash("sha256").update(password).digest("hex");
await db.user.create({ data: { email, passwordHash } });

// 로그인
const ok = user.passwordHash === createHash("sha256").update(password).digest("hex");
```

✓ 안전 코드

```ts
import { hash, verify } from "@node-rs/argon2";

const ARGON = { memoryCost: 19456, timeCost: 2, parallelism: 1 };  // OWASP 최소값(19MiB·2회·1), 기본 Argon2id

// 가입
if (password.length < 10 || password.length > 128) return Response.json({ error: "10~128자" }, { status: 400 });
if (await isBreached(password)) return Response.json({ error: "유출된 적 있는 비밀번호" }, { status: 400 });
await db.user.create({ data: { email, passwordHash: await hash(password, ARGON) } });
// isBreached: 유출 비밀번호 조회(예: Pwned Passwords range API, SHA-1 앞 5자만 보내는 방식)

// 로그인: 문자열 비교 대신 verify(솔트·설정은 해시 문자열 안에 있다)
const ok = await verify(user.passwordHash, password);
```

### django

✗ 취약 코드

```python
# settings.py — 테스트 속도용 설정이 운영까지 넘어옴
PASSWORD_HASHERS = ["django.contrib.auth.hashers.MD5PasswordHasher"]
AUTH_PASSWORD_VALIDATORS = []

# views.py
user = User.objects.create_user(username=username, password=pw)  # 규칙 확인 없이 저장
```

✓ 안전 코드

```python
# settings.py — 첫 번째 해셔로 저장, 나머지는 옛 해시 확인용(로그인 때 자동으로 새 방식으로 바뀜)
PASSWORD_HASHERS = [
    "django.contrib.auth.hashers.Argon2PasswordHasher",   # pip install "django[argon2]"
    "django.contrib.auth.hashers.PBKDF2PasswordHasher",
]
AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator"},
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator", "OPTIONS": {"min_length": 10}},
    {"NAME": "django.contrib.auth.password_validation.CommonPasswordValidator"},  # 흔한 비밀번호 목록
]

# views.py
validate_password(pw, user=User(username=username))   # 위 규칙 적용(ValidationError)
user = User.objects.create_user(username=username, password=pw)  # set_password 로 해시
```
