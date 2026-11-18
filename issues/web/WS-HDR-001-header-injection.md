---
id: WS-HDR-001
title: HTTP Response Splitting·Header Injection (HTTP 응답 분할·헤더 삽입)
slug: header-injection
group: web
severity: medium
site_only: false
depth: brief
languages: [php, python, express]
maps:
  owasp2025: [A05]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.2.1, V1.3.11, V5.4.2]
  cwe: [CWE-113, CWE-93]
  kr_guide: ["입력데이터 검증 및 표현-13"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [PHP, Python, Express·Node.js, 문의 메일·파일 내려받기 기능]
severity_reason: "영향: 메일 수신자 몰래 추가(스팸 발송 통로)·응답 헤더 조작·내려받기 이름 위조 · 악용: 이메일·파일 이름 칸에 줄바꿈 글자를 넣는 것만으로 가능"
sources:
  - title: "CWE-113: Improper Neutralization of CRLF Sequences in HTTP Headers ('HTTP Request/Response Splitting')"
    url: https://cwe.mitre.org/data/definitions/113.html
    year: 2025
  - title: "CWE-93: Improper Neutralization of CRLF Sequences ('CRLF Injection')"
    url: https://cwe.mitre.org/data/definitions/93.html
    year: 2025
  - title: "PHP Manual - mail() (additional_headers 주의)"
    url: https://www.php.net/manual/en/function.mail.php
    year: 2026
  - title: "RFC 6266 - Use of the Content-Disposition Header Field in HTTP"
    url: https://www.rfc-editor.org/rfc/rfc6266
    year: 2011
---

## 요약

입력값을 HTTP 응답 헤더나 메일 헤더에 넣을 때 줄바꿈(CR·LF)을 막지 않아 새 헤더가 끼어드는 문제(진단가이드 이름: HTTP 응답분할). 요즘 프레임워크는 응답 헤더의 줄바꿈을 대부분 막지만, 메일 헤더를 직접 만드는 코드와 파일 이름을 넣는 `Content-Disposition`에서는 여전히 흔함.

## 대응 방법

```text
요청 값이 응답 헤더(Location, Set-Cookie, Content-Disposition)나 메일 헤더(From, Reply-To, Subject, To)에 들어가는 곳을 찾아 줘.
메일은 라이브러리의 주소·제목 API로 보내고, 값에 \r·\n이 있으면 거절하게 해 줘.
내려받기 파일 이름은 프레임워크의 attachment·download 함수로 RFC 6266 형식(filename*=UTF-8'')에 맞춰 줘.
```

- 문의 폼의 이메일 칸에 줄바꿈이 든 값을 보내면 400이 나와야 한다.

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
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-HDR-001 contact.php:11 — 사용자가 쓴 이메일을 mail() 의 From 헤더 문자열에 이어 붙임 → FILTER_VALIDATE_EMAIL 검사 후 헤더 배열의 Reply-To 로만 사용`
- **고칠 방법**: 보내는 주소(From)는 서버 주소로 고정하고 사용자 주소는 검사 뒤 Reply-To에만 넣어야 하며, 거절할 때 받은 값을 오류 문구에 되돌려 보여 주지 않아야 한다.

## 코드 예시

### php

✗ 취약 코드

```php
<?php
$from = $_POST['email'];                    // 줄바꿈과 함께 다른 헤더가 섞일 수 있다
mail('help@example.com', '문의', $_POST['message'], "From: $from\r\nReply-To: $from");
```

✓ 안전 코드

```php
<?php
$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);   // 줄바꿈이 든 주소는 false
if ($email === false) { http_response_code(400); exit('이메일 형식이 맞지 않습니다'); }

mail('help@example.com', '=?UTF-8?B?' . base64_encode('문의') . '?=', (string) ($_POST['message'] ?? ''), [
    'From' => 'no-reply@example.com',        // 보내는 주소는 고정
    'Reply-To' => $email,                    // 검사를 통과한 값만
    'Content-Type' => 'text/plain; charset=UTF-8',
]);
```

### python

✗ 취약 코드

```python
import smtplib

def send_contact(email: str, subject: str, message: str):
    raw = f"From: {email}\r\nTo: help@example.com\r\nSubject: {subject}\r\n\r\n{message}"  # 헤더를 문자열로 조립
    with smtplib.SMTP("localhost") as s:
        s.sendmail("no-reply@example.com", ["help@example.com"], raw.encode())
```

✓ 안전 코드

```python
import smtplib
from email.message import EmailMessage
from email.utils import parseaddr

def header_value(value: str) -> str:
    if "\r" in value or "\n" in value:
        raise ValueError("헤더 값에 줄바꿈을 넣을 수 없습니다")
    return value

def send_contact(email: str, subject: str, message: str):
    if parseaddr(header_value(email))[1] != email or "@" not in email:
        raise ValueError("이메일 형식이 맞지 않습니다")
    msg = EmailMessage()
    msg["From"], msg["To"], msg["Reply-To"] = "no-reply@example.com", "help@example.com", email
    msg["Subject"] = header_value(subject)[:200]
    msg.set_content(message)                 # 본문은 헤더와 따로
    with smtplib.SMTP("localhost") as s:
        s.send_message(msg)
```

### express

✗ 취약 코드

```js
app.get("/download/:id", async (req, res) => {
  const file = await File.findById(req.params.id);
  // 따옴표·세미콜론이 든 이름은 헤더 값을 바꾸고, 한글 이름은 오류(ERR_INVALID_CHAR)를 낸다
  res.setHeader("Content-Disposition", `attachment; filename="${file.name}"`);
  res.sendFile(file.path);
});
```

✓ 안전 코드

```js
app.get("/download/:id", async (req, res) => {
  const file = await File.findById(req.params.id);
  if (!file || String(file.ownerId) !== String(req.user?.id)) return res.sendStatus(404);
  res.download(file.path, file.name); // filename*=UTF-8'' 형식으로 인코딩해 준다
});
```
