<!-- generated from white-security issues; do not edit -->
# WS-PWD-001 Weak Password Storage · Policy (비밀번호 저장·작성 규칙)

## 탐지 규칙

- **찾는 곳**: `MD5PasswordHasher`, `Crypt::encrypt(`, `PASSWORD_HASHERS`, `AUTH_PASSWORD_VALIDATORS`, `Hash::make(`, `make_password(`, `passwordHash`, 그 밖에 비밀번호 값에 붙은 md5·sha1·hashlib·createHash, password_hash 호출, bcrypt·argon2 설정, 비밀번호 규칙 정규식, 비밀번호 재설정 토큰 생성.
- **검사 1 — 약한 저장 방식**: 지적할 때: 비밀번호를 평문, 복호화되는 암호화(`Crypt::encrypt`, AES), `md5`·`sha1`·`sha256` 한 번(솔트 없이)으로 저장하거나 비교하는 경우. 운영 설정에 `MD5PasswordHasher`가 있는 경우. / 지적하지 않을 때: `Hash::make`·`password_hash(..., PASSWORD_DEFAULT|PASSWORD_ARGON2ID)`·`make_password`·argon2·bcrypt 라이브러리를 쓰는 경우. 비밀번호가 아닌 값(파일 체크섬·캐시 키)에 해시를 쓰는 경우.
- **검사 2 — 약한 설정·비교**: 지적할 때: bcrypt 비용 10 미만, PBKDF2-HMAC-SHA256 반복 60만 회 미만, Argon2id 메모리 19MiB 미만인 경우. 해시를 일반 비교(`==`, `===`)로 맞춰 보는 경우. 솔트를 붙였지만 SHA-256 한 번으로 끝나는 경우. / 지적하지 않을 때: 프레임워크 기본값(Laravel bcrypt, Django PBKDF2·Argon2)을 그대로 쓰는 경우.
- **검사 3 — 비밀번호 규칙**: 지적할 때: 최소 길이가 8자 미만인 경우, 2단계 인증 없이 비밀번호만으로 로그인하는데 최소 길이가 15자 미만인 경우, 최대 길이를 64자 미만(예: 16자)으로 막는 경우. 유출·흔한 비밀번호 차단이 없는 경우. / 지적하지 않을 때: `Password::min(...)->uncompromised()`, Django `CommonPasswordValidator`, 유출 비밀번호 조회가 있는 경우.
- **등급**: 위험: 평문·복호화 가능한 암호화·MD5·SHA-1·솔트 없는 해시로 저장하는 경우. / 주의: 솔트만 붙인 SHA-256 한 번, 비용·반복이 낮은 설정, 최소 8자 미만, 유출 비밀번호 차단 없음. / 참고: 비밀번호만으로 로그인하는데 최소 15자 미만, 문자 조합·정기 변경 강제, 붙여넣기 막음.
- **보고 예**: `[위험] WS-PWD-001 app/api/signup/route.ts:14 — 비밀번호를 createHash("sha256") 로 저장(솔트·반복 없음) → @node-rs/argon2 hash 로 바꾸고 로그인 때 옛 해시를 다시 저장`
- **고칠 방법**: 비밀번호 전용 해시로 바꿔야 하고, 옛 해시는 로그인 성공 때 새 방식으로 다시 저장(rehash)해 사용자가 비밀번호를 바꾸지 않아도 옮겨지게 해야 한다.

## 점검 항목

- 비밀번호는 Argon2id·bcrypt·scrypt·PBKDF2 같은 비밀번호 전용 해시로 저장해야 한다.
- MD5·SHA-1·SHA-256 같은 빠른 해시(솔트가 있어도)나 복호화되는 암호화(AES 등)로 저장하지 않아야 한다.
- 해시는 서버에서 만들고, 비교는 라이브러리의 확인 함수로 해야 한다.
- 비밀번호만으로 로그인하면 15자 이상, 2단계 인증과 함께 쓸 때만 8자 이상을 요구하고, 64자 이상도 허용해야 한다.
- 유출·흔한 비밀번호는 거절하고, 문자 조합·정기 변경은 강제하지 않아야 한다.
- 비밀번호 재설정 토큰은 무작위로 만들어 짧은 시간 안에 한 번만 쓰게 해야 한다.

## 검토 주의

국내 점검(주요정보통신기반시설 상세가이드의 약한 비밀번호 정책)은 아직 문자 조합을 보는 경우가 있으므로, 이미 있는 조합 규칙은 지적하지 않고 참고로만 적어야 한다. bcrypt는 72바이트를 넘는 부분을 버리므로 긴 비밀번호를 받으면 Argon2id가 낫다.

Code examples (read only the file for the stack you are writing): `WS-PWD-001.code.laravel.md` (Laravel) · `WS-PWD-001.code.nextjs.md` (Next.js) · `WS-PWD-001.code.django.md` (Django)

기본 심각도: 위험
기준: OWASP Top 10:2025 A07(인증 실패) · OWASP Top 10:2025 A04(암호화 실패) · OWASP API 2023 API2(인증 실패) · ASVS 5.0 V6.2.1 · ASVS 5.0 V6.2.5 · ASVS 5.0 V6.2.10 · ASVS 5.0 V6.2.12 · ASVS 5.0 V6.4.3 · ASVS 5.0 V11.4.2 · CWE-916 · CWE-759 · CWE-521 · CWE-328 · CWE-256 · CWE-640 · 국내 보안약점 49 보안기능-9(취약한 비밀번호 허용) · 국내 보안약점 49 보안기능-14(솔트 없이 일방향 해쉬 함수 사용) · 국내 보안약점 49 보안기능-4(취약한 암호화 알고리즘 사용) · 기반시설 웹 21 BF(약한 비밀번호 정책) · 기반시설 웹 21 PR(비밀번호 복구 취약)
