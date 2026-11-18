<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-LLM-003 Sensitive Information Disclosure (AI API로 보내는 개인정보·키)

주의 · LLM LLM02 · ASVS V14.2.3 · ASVS V16.2.5 · CWE-359 · CWE-201 · CWE-532

## 점검 항목

- AI에 보낼 필드는 허용 목록으로 골라야 한다. 고유식별정보(주민등록번호·여권번호·운전면허번호·외국인등록번호처럼 법으로 처리를 제한하는 번호)·카드번호·계좌번호·비밀번호·API 키는 보내지 않아야 한다.
- 문의 내용처럼 자유롭게 쓴 글에 섞인 전화번호·이메일·주민등록번호는 보내기 전에 가려야 한다. 가리기는 보조 수단이고, 필드 선별이 먼저여야 한다.
- 프롬프트·응답 원문은 로그에 남기지 않아야 하고, 꼭 남길 때는 가린 뒤 보관 기간을 정해야 한다.
- 쓰는 AI API의 학습 사용 여부와 보관 기간을 공식 문서로 확인해야 한다(예: OpenAI API는 2023-03-01부터 기본으로 학습에 쓰지 않지만, 남용 감시 로그는 기본 최대 30일 보관).
- 외부 AI 회사에 개인정보를 넘기는 일은 개인정보 처리 위탁이나 국외 이전에 해당할 수 있으므로, 개인정보 처리방침(회사가 개인정보를 어떻게 다루는지 공개하는 문서)에 적혀 있는지 확인해야 한다.

## 탐지 규칙

**찾는 곳**: 외부 LLM API 호출부(`openai`, `anthropic`, `generativeai`, `fetch("https://api.`)에 넘기는 프롬프트, 그 프롬프트를 남기는 로그 호출(`logger.`, `console.log`, `error_log`, 오류 추적 도구).

**검사 1. 프롬프트로 나가는 개인정보·키**
- 지적할 때: DB 행·사용자 객체를 통째로(`__dict__`, `toJSON()`, `json.dumps(user)`, `print_r`) 넣거나, 주민등록번호·카드번호·비밀번호·키 필드가 프롬프트에 들어가는 경우.
- 지적하지 않을 때: 필요한 필드만 골라 넣는 경우. 회사 안에서 돌리는 모델이라 데이터가 밖으로 나가지 않는 경우(로그 검사는 별도 진행).

**검사 2. 로그에 남는 프롬프트 원문**
- 지적할 때: 프롬프트나 응답 원문을 로그·오류 추적 도구로 보내는 경우.
- 지적하지 않을 때: ID·길이·소요 시간 같은 메타데이터만 남기는 경우.

**등급**
- 위험: 고유식별정보·카드번호·비밀번호·API 키가 외부 AI API나 로그로 나가는 경우.
- 주의: 이름·전화번호·이메일이 가려지지 않고 나가는 경우. 프롬프트 원문 로그가 남는 경우.
- 참고: 처리방침에 AI 위탁 내용이 있는지 확인이 필요한 경우.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-LLM-003 support/ai.py:22 — customer.__dict__ 를 통째로 프롬프트에 넣어 주민등록번호까지 외부 API로 전송 → 보낼 필드를 허용 목록으로 고르고 문의 글은 가려서 전송`

## 코드 예시

### python

✗ 취약 코드

```python
prompt = f"이 고객 문의에 답장을 써라.\n고객 정보: {customer.__dict__}\n문의: {ticket.body}"
logger.info("prompt=%s", prompt)  # 주민등록번호·전화번호까지 로그에 남는다
reply = llm.chat(prompt)
```

✓ 안전 코드

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
