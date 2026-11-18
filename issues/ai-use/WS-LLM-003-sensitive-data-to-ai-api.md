---
id: WS-LLM-003
title: "Sensitive Information Disclosure — AI API로 보내는 개인정보·키"
slug: sensitive-data-to-ai-api
group: ai-use
severity: medium
site_only: false
depth: brief
languages: [python]
maps:
  owasp2025: []
  api2023: []
  llm2026: [LLM02]
  agentic2026: []
  asvs5: [V14.2.3, V16.2.5]
  cwe: [CWE-359, CWE-201, CWE-532]
  kr_guide: []
  kr_ciip: []
checked: 2026-11-18
sources:
  - title: "OWASP Top 10 for LLM Applications - LLM02:2025 Sensitive Information Disclosure"
    url: https://genai.owasp.org/llmrisk/llm022025-sensitive-information-disclosure/
    year: 2025
  - title: 개인정보보호위원회 생성형 인공지능(AI) 개발·활용을 위한 개인정보 처리 안내서
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=11439
    year: 2025
  - title: 개인정보보호위원회 개인정보 처리방침 작성지침(2026.4. 개정)
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=12018
    year: 2026
  - title: "OpenAI Platform Docs - Your data"
    url: https://platform.openai.com/docs/guides/your-data
    year: 2026
---

## 한 줄 요약

고객 정보나 키를 프롬프트에 통째로 넣어 외부 AI API로 보내면, 그 데이터는 내 서버 밖(AI 회사의 서버와 로그)에 남는다. OWASP는 이를 Sensitive Information Disclosure(민감 정보 노출)로 다룬다.

## 나는 뭘 하면 되나

```text
외부 AI API로 보내는 프롬프트에 어떤 필드가 들어가는지 표로 보여 줘.
답을 쓰는 데 꼭 필요한 필드만 남기고, 문의 글 속 전화번호·이메일·주민등록번호는 가려서 보내게 바꿔 줘.
프롬프트와 응답 원문이 로그에 남는 곳도 찾아서 빼 줘.
```

- 로그 파일에서 테스트 고객의 전화번호를 검색해 본다. 안 나오면 된다.

## 체크 항목

- AI에 보낼 필드를 허용 목록으로 고른다. 고유식별정보(주민등록번호·여권번호·운전면허번호·외국인등록번호)·카드번호·계좌번호·비밀번호·API 키는 보내지 않는다.
- 문의 내용처럼 자유롭게 쓴 글에 섞인 전화번호·이메일·주민등록번호는 보내기 전에 가린다. 가리기는 보조 수단이고, 필드를 고르는 것이 먼저다.
- 프롬프트·응답 원문을 로그에 남기지 않는다. 꼭 남겨야 하면 가린 뒤 보관 기간을 정한다.
- 쓰는 AI API가 보낸 데이터를 학습에 쓰는지, 얼마나 보관하는지 공식 문서로 확인한다. 예: OpenAI API는 2023-03-01부터 기본으로 학습에 쓰지 않지만, 남용 감시 로그는 기본 최대 30일 보관한다.
- 외부 AI 회사에 개인정보를 넘기면 개인정보 처리 위탁이나 국외 이전에 해당할 수 있다. 개인정보 처리방침(회사가 개인정보를 어떻게 다루는지 공개하는 문서)에 적혀 있는지 확인한다.

## 스킬이 잡는 방식

**찾는 곳**: 외부 LLM API 호출부(`openai`, `anthropic`, `generativeai`, `fetch("https://api.`)에 넘기는 프롬프트, 그 프롬프트를 남기는 로그 호출(`logger.`, `console.log`, `error_log`, 오류 추적 도구).

**검사 1. 개인정보·키가 프롬프트로 나간다**
- 지적할 때: DB 행·사용자 객체를 통째로(`__dict__`, `toJSON()`, `json.dumps(user)`, `print_r`) 넣거나, 주민등록번호·카드번호·비밀번호·키 필드가 프롬프트에 들어간다.
- 지적하지 않을 때: 필요한 필드만 골라 넣는다. 또는 회사 안에서 돌리는 모델이라 데이터가 밖으로 나가지 않는다(이때도 로그 검사는 한다).

**검사 2. 프롬프트 원문이 로그에 남는다**
- 지적할 때: 프롬프트나 응답 원문을 로그·오류 추적 도구로 보낸다.
- 지적하지 않을 때: ID·길이·소요 시간 같은 메타데이터만 남긴다.

**등급**
- 위험: 고유식별정보·카드번호·비밀번호·API 키가 외부 AI API나 로그로 나간다.
- 주의: 이름·전화번호·이메일이 가리지 않고 나간다. 프롬프트 원문 로그가 남는다.
- 참고: 처리방침에 AI 위탁 내용이 있는지 확인이 필요하다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-LLM-003 support/ai.py:22 — customer.__dict__ 를 통째로 프롬프트에 넣어 주민등록번호까지 외부 API로 전송 → 보낼 필드를 허용 목록으로 고르고 문의 글은 가려서 전송`

## 코드

### python

✗ 뚫리는 코드

```python
prompt = f"이 고객 문의에 답장을 써라.\n고객 정보: {customer.__dict__}\n문의: {ticket.body}"
logger.info("prompt=%s", prompt)  # 주민등록번호·전화번호까지 로그에 남는다
reply = llm.chat(prompt)
```

✓ 막는 코드

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
