<!-- generated from white-security issues; do not edit -->
# WS-LLM-003 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
prompt = f"이 고객 문의에 답장을 써라.\n고객 정보: {customer.__dict__}\n문의: {ticket.body}"
logger.info("prompt=%s", prompt)  # 주민등록번호·전화번호까지 로그에 남는다
reply = llm.chat(prompt)
```

✓ 안전 코드 (safe example)

```python
import re

MASKS = [
    (re.compile(r"(?<!\d)\d{6}-?[1-8]\d{6}(?!\d)"), "[주민등록번호]"),
    (re.compile(r"(?<!\d)01[016789]-?\d{3,4}-?\d{4}(?!\d)"), "[전화번호]"),
    (re.compile(r"[\w.+-]+@[\w-]+\.[\w.-]+"), "[이메일]"),
]

def mask(text: str) -> str:
    for pattern, label in MASKS:
        text = pattern.sub(label, text)
    return text

def draft_reply(customer, ticket) -> str:
    context = {"이름": customer.first_name, "등급": customer.tier}  # 보낼 필드만 고른다
    reply = llm.chat(f"이 고객 문의에 답장을 써라.\n고객: {context}\n문의: {mask(ticket.body)}")
    logger.info("draft ticket=%s len=%d", ticket.id, len(reply))  # 원문은 남기지 않는다
    return reply
```
