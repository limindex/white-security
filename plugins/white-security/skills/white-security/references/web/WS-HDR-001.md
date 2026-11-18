<!-- generated from white-security issues; do not edit -->
# WS-HDR-001 HTTP Response Splitting·Header Injection (HTTP 응답 분할·헤더 삽입)

심각도: 주의
기준: OWASP Top 10:2025 A05(삽입) · ASVS 5.0 V1.2.1 · ASVS 5.0 V1.3.11 · ASVS 5.0 V5.4.2 · CWE-113 · CWE-93 · 국내 보안약점 49 입력데이터 검증 및 표현-13(HTTP 응답분할)

## 점검 항목

- 헤더에 넣는 값에 `\r`·`\n`이 있으면 거절해야 한다.
- 메일 헤더는 문자열로 직접 만들지 말고 라이브러리 API로 넣어야 한다.
- 이메일 주소는 `FILTER_VALIDATE_EMAIL` 같은 형식 검사를 거쳐야 한다.
- 내려받기 파일 이름은 `filename*=UTF-8''` 형식으로 인코딩해야 한다(한글 이름 포함).
- 응답 헤더 값은 직접 이어 붙이지 말고 프레임워크 함수로 넣어야 한다.

## 탐지 규칙

- **찾는 곳**: `Content-Disposition`, `smtplib`, `setHeader(`, `From: $`, `From: {`, 그 밖에 PHP mail() 의 추가 헤더, header()·Location·Set-Cookie 에 요청 값을 넣는 코드.
- **검사 1 — 메일 헤더 직접 조립**: 지적할 때: 요청 값이 PHP `mail()`의 추가 헤더 문자열이나 `smtplib`로 보내는 원문 메시지 헤더에 들어가는 경우. / 지적하지 않을 때: Symfony Mailer·Laravel Mail·`EmailMessage`·nodemailer처럼 헤더를 따로 다루는 라이브러리를 쓰고 값의 줄바꿈을 거절하는 경우.
- **검사 2 — 응답 헤더에 입력**: 지적할 때: 요청 값을 `header()`·`setHeader()`·`Content-Disposition` 문자열에 직접 넣는 경우. / 지적하지 않을 때: `res.attachment()`·`res.download()`·Laravel `download()`처럼 인코딩해 주는 함수를 쓰는 경우. 값이 코드 상수인 경우.
- **등급**: 위험: 메일 헤더에 검사 없는 요청 값(스팸 발송 통로). / 주의: `Content-Disposition`·`Location`·`Set-Cookie`에 요청 값 직접 연결. / 참고: 줄바꿈을 막는 런타임이라도 따옴표·세미콜론으로 헤더 값이 바뀌는 경우.
- **보고 예**: `[위험] WS-HDR-001 contact.php:11 — 사용자가 쓴 이메일을 mail() 의 From 헤더 문자열에 이어 붙임 → FILTER_VALIDATE_EMAIL 검사 후 헤더 배열의 Reply-To 로만 사용`
- **고칠 방법**: 보내는 주소(From)는 서버 주소로 고정하고 사용자 주소는 검사 뒤 Reply-To에만 넣어야 하며, 거절할 때 받은 값을 오류 문구에 되돌려 보여 주지 않아야 한다.

Code examples (read only the file for the stack you are writing): `WS-HDR-001.code.php.md` (PHP) · `WS-HDR-001.code.python.md` (Python) · `WS-HDR-001.code.express.md` (Express)
