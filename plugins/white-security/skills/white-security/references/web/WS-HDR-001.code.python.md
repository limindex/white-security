<!-- generated from white-security issues; do not edit -->
# WS-HDR-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
import smtplib

def send_contact(email: str, subject: str, message: str):
    raw = f"From: {email}\r\nTo: help@example.com\r\nSubject: {subject}\r\n\r\n{message}"  # 헤더를 문자열로 조립
    with smtplib.SMTP("localhost") as s:
        s.sendmail("no-reply@example.com", ["help@example.com"], raw.encode())
```

✓ 안전 코드 (safe example)

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
