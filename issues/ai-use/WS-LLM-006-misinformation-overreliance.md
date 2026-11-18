---
id: WS-LLM-006
title: Misinformation · Overreliance (AI 답을 사실로 믿고 자동 처리)
slug: misinformation-overreliance
group: ai-use
severity: medium
site_only: false
depth: brief
languages: [python]
maps:
  owasp2025: []
  api2023: []
  llm2026: [LLM07]
  agentic2026: [ASI09]
  asvs5: []
  cwe: [CWE-1426]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, AI 답변을 업무 처리에 쓰는 서비스]
severity_reason: "영향: 틀린 환불·안내 등 잘못된 업무 처리와 운영자 책임 · 악용: 공격 없이도 발생하나 피해 범위는 자동 처리에 맡긴 일로 한정"
sources:
  - title: "OWASP Top 10 for LLM Applications - LLM09:2025 Misinformation"
    url: https://genai.owasp.org/llmrisk/llm092025-misinformation/
    year: 2025
  - title: "BBC - Airline held liable for its chatbot giving passenger bad advice"
    url: https://www.bbc.com/travel/article/20240222-air-canada-chatbot-misinformation-what-travellers-should-know
    year: 2024
  - title: "NIST AI 600-1 - Artificial Intelligence Risk Management Framework: Generative Artificial Intelligence Profile"
    url: https://nvlpubs.nist.gov/nistpubs/ai/NIST.AI.600-1.pdf
    year: 2024
  - title: OWASP Top 10 for Agentic Applications 2026
    url: https://genai.owasp.org/resource/owasp-top-10-for-agentic-applications-for-2026/
    year: 2025
---

## 요약

AI가 틀린 내용도 자신 있게 말하는 현상(Hallucination, NIST 용어로는 Confabulation)과, 그 답을 확인 없이 환불·결제·고객 안내에 바로 쓰는 Overreliance(과신)에 대한 이슈. 틀린 결과의 책임은 서비스 운영자 몫.

## 대응 방법

```text
AI 답을 보고 바로 돈·권한·고객 안내가 바뀌는 곳을 찾아 줘.
금액·자격·정책은 AI가 정하지 말고 서버 코드가 DB와 규칙으로 정하게 바꿔 줘.
AI 답이 형식에 안 맞거나 규칙 밖이면 사람 확인 대기열로 보내 줘.
```

- 정책에 없는 질문(예: "지난달 주문도 반값 환불되나요?")을 했을 때, AI가 약속하는 문구가 나오더라도 실제 처리는 규칙대로 되어야 한다.

## 점검 항목

- 금액·자격·기한 같은 사실은 AI가 아니라 서버 코드가 DB와 규칙으로 정해야 하고, AI에게는 분류·초안 작성만 맡겨야 한다.
- AI 답은 정해진 형식(JSON 스키마)으로 받아 검사하고, 틀리거나 규칙 밖이면 사람에게 넘겨야 한다.
- 고객에게 보이는 AI 답에는 "AI 답변" 표시와 근거 문서 링크를 붙여야 한다. 2024년 캐나다 BC 민사분쟁심판소(BC Civil Resolution Tribunal)는 챗봇이 틀린 할인 환급 규정을 안내한 항공사에 손해배상·이자·심판 비용을 합쳐 812.02 캐나다달러 지급을 명령함.
- 사람이 승인하는 화면에는 AI의 설명이 아니라 실제로 바뀔 값(대상·금액)을 보여야 한다.
- AI가 쓴 코드·패키지 이름·법령 조문은 공식 문서로 확인해야 한다(패키지는 WS-SUP-001).

## 탐지 규칙

- **찾는 곳**: LLM 응답을 받은 뒤 상태를 바꾸는 호출(`refund(`, `charge(`, `approve(`, `update(`, `send(`)과, 고객 화면에 AI 답을 내보내는 곳.
- **검사 1 — AI 답이 곧바로 결정이 되는 구조**: 지적할 때: LLM 응답 속 금액·승인 여부·대상이 검증 없이 돈·권한·데이터 변경 함수로 들어가는 경우. / 지적하지 않을 때: 응답은 분류 값만 쓰고 금액·자격은 서버 규칙이 다시 계산하는 경우. 사람 승인 단계를 거치는 경우.
- **검사 2 — 사실처럼 나가는 AI 답**: 지적할 때: 정책·가격·의료·법률 안내를 AI가 쓴 그대로 고객에게 보내고, 근거나 AI 표시가 없는 경우. / 지적하지 않을 때: 정해진 문서에서 찾은 내용만 답하고 근거 링크를 붙이는 경우.
- **등급**: 위험: AI 답만으로 환불·결제·권한 변경이 실행되는 경우. / 주의: 정책·가격 안내가 근거 없이 고객에게 나가는 경우. / 참고: AI 표시나 근거 링크가 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-LLM-006 support/auto.py:33 — 모델이 정한 refund_amount 로 바로 환불 실행 → 금액은 주문·정책 규칙으로 계산하고, 규칙 밖이면 사람 확인 대기열로`

## 코드 예시

### python

✗ 취약 코드

```python
def handle_ticket(ticket) -> None:
    d = json.loads(llm.chat(f"환불 여부와 금액을 JSON 으로 정해라: {ticket.body}"))
    if d["refund"]:
        payments.refund(ticket.order_id, d["amount"])  # AI가 정한 금액이 그대로 나간다
```

✓ 안전 코드

```python
def handle_ticket(ticket) -> None:
    try:
        reply = llm.chat('요청 종류를 JSON {"intent": "refund" | "question" | "other"} 로만 답해라: ' + ticket.body)
        intent = json.loads(reply)["intent"]
    except (ValueError, KeyError, TypeError):
        intent = "other"
    order = orders.get_owned(ticket.order_id, owner_id=ticket.user_id)
    amount = refund_policy.amount_for(order) if order else 0   # 금액은 규칙이 정한다
    if intent == "refund" and amount > 0:
        review_queue.add(ticket.id, order_id=order.id, amount=amount)  # 사람이 대상·금액을 보고 승인
    else:
        review_queue.add(ticket.id, note="자동 처리 대상 아님")
```
