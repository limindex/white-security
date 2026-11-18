<!-- generated from white-security issues; do not edit -->
# WS-SESS-001 Broken Session Management (세션·토큰 관리 미흡)

## 탐지 규칙

- **찾는 곳**: `jwt.decode(`, `decodeJwt(`, `jwtVerify(`, `ignoreExpiration`, `verify_signature`, `session_regenerate_id(`, 그 밖에 jsonwebtoken·PyJWT·jose 사용, config/session.php, 쿠키 옵션(httpOnly·secure·sameSite), localStorage 에 토큰 저장.
- **검사 1 — 서명·알고리즘·클레임 확인**: 지적할 때: jsonwebtoken `jwt.decode`·jose `decodeJwt`로만 읽고 그 값을 믿는 경우. PyJWT에 `algorithms`가 없거나 `verify_signature: False`인 경우. 허용 알고리즘에 `none`이 있거나 HS·RS 계열이 섞인 경우. `exp`가 없거나 `ignoreExpiration: true`인 경우, 여러 서비스가 키를 함께 쓰는데 `aud`·`iss` 검사가 없는 경우. / 지적하지 않을 때: `jwt.verify`·`jwtVerify`·PyJWT `jwt.decode(..., algorithms=[하나])`로 서명과 만료를 확인하는 경우.
- **검사 2 — 세션 고정·로그아웃**: 지적할 때: 직접 만든 로그인 처리에서 성공 뒤 세션 ID를 바꾸지 않는 경우(PHP `session_regenerate_id(true)`·Express `req.session.regenerate` 없음). 로그아웃이 클라이언트 토큰 삭제뿐이고 서버 세션·리프레시 토큰은 살아 있는 경우. / 지적하지 않을 때: Django `login()`·Laravel 인증 가드·Auth.js처럼 프레임워크가 로그인 때 세션 ID를 바꿔 주는 경우.
- **검사 3 — 쿠키 속성**: 지적할 때: 세션·로그인 토큰 쿠키에 `HttpOnly`·`Secure`·`SameSite`가 빠진 경우(`'http_only' => false`, 운영의 `SESSION_SECURE_COOKIE=false`). / 지적하지 않을 때: 프레임워크 기본값을 쓰고 운영에서 HTTPS·Secure가 켜진 경우.
- **등급**: 위험: 서명 확인 없이 토큰을 믿는 경우, `none`·알고리즘 혼용 허용, 세션 고정. / 주의: 만료 없음, 로그아웃 뒤에도 서버 세션 유지, 쿠키 HttpOnly·Secure 빠짐, 짧은 고정 서명 키. / 참고: 토큰을 localStorage에 보관, 세션 최대 유지 시간 없음.
- **보고 예**: `[위험] WS-SESS-001 app/auth.py:12 — jwt.decode 에 verify_signature False 로 서명을 확인하지 않음 → 키와 algorithms=["HS256"], audience·issuer, require exp 로 검증`
- **고칠 방법**: 토큰은 검증 함수 하나로만 읽게 모아야 하고, 세션은 로그인·로그아웃·비밀번호 변경 때 서버에서 새로 만들거나 지워야 한다.

## 점검 항목

- 로그인·권한 상승 때 세션 ID를 새로 만들고, 로그아웃 때 서버 쪽 세션을 지워야 한다.
- JWT는 서명을 확인하는 함수로 읽어야 하고, 내용만 읽는 `decode`를 믿지 않아야 한다.
- 허용 알고리즘은 하나로 고정하고 `exp`·`iss`·`aud`를 검사해야 한다.
- 서명 키는 32바이트 이상 무작위 값으로 코드 밖에 둬야 한다(HS256 기준).
- 세션 쿠키는 `HttpOnly`·`Secure`·`SameSite`를 켜고, 로그인 토큰은 가능하면 `localStorage`를 피해야 한다.
- 세션에는 비활동·최대 유지 시간을 두고, 비밀번호 변경 때 다른 세션을 끊을 수 있어야 한다.

Code examples (read only the file for the stack you are writing): `WS-SESS-001.code.laravel.md` (Laravel) · `WS-SESS-001.code.nextjs.md` (Next.js) · `WS-SESS-001.code.fastapi.md` (FastAPI)

기본 심각도: 위험
기준: OWASP Top 10:2025 A07(인증 실패) · OWASP API 2023 API2(인증 실패) · ASVS 5.0 V7.2.4 · ASVS 5.0 V7.4.1 · ASVS 5.0 V9.1.1 · ASVS 5.0 V9.1.2 · ASVS 5.0 V9.2.1 · ASVS 5.0 V9.2.3 · ASVS 5.0 V3.3.1 · ASVS 5.0 V3.3.4 · CWE-384 · CWE-347 · CWE-613 · CWE-1004 · CWE-614 · 국내 보안약점 49 보안기능-10(부적절한 전자서명 확인) · 국내 보안약점 49 보안기능-12(사용자 하드디스크에 저장되는 쿠키를 통한 정보 노출) · 기반시설 웹 21 IS(불충분한 세션 관리) · 기반시설 웹 21 CC(쿠키 변조)
