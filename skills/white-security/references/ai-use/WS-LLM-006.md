<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-LLM-006 Misinformation · Overreliance(AI 답을 사실로 믿고 자동 처리)

주의 · LLM LLM07 · 에이전트 ASI09 · CWE-1426

## 체크 항목

- 금액·자격·기한 같은 사실은 AI가 아니라 서버 코드가 DB와 규칙으로 정한다. AI에게는 분류·초안 작성만 맡긴다.
- AI 답은 정해진 형식(JSON 스키마)으로 받아 검사하고, 틀리거나 규칙 밖이면 사람에게 넘긴다.
- 고객에게 보이는 AI 답에는 "AI 답변" 표시와 근거 문서 링크를 붙인다. 2024년 캐나다 분쟁 조정 기관은 챗봇이 틀린 환불 규정을 안내한 항공사에 배상(812.02 캐나다달러)을 명령했다.
- 사람이 승인하는 화면에는 AI의 설명이 아니라 실제로 바뀔 값(대상·금액)을 보여 준다.
- AI가 쓴 코드·패키지 이름·법령 조문은 공식 문서로 확인한다(패키지는 WS-SUP-001).

## 스킬이 잡는 방식

**찾는 곳**: LLM 응답을 받은 뒤 상태를 바꾸는 호출(`refund(`, `charge(`, `approve(`, `update(`, `send(`)과, 고객 화면에 AI 답을 내보내는 곳.

**검사 1. AI 답이 바로 결정이 된다**
- 지적할 때: LLM 응답 속 금액·승인 여부·대상이 검증 없이 돈·권한·데이터 변경 함수로 들어간다.
- 지적하지 않을 때: 응답은 분류 값만 쓰고, 금액·자격은 서버 규칙이 다시 계산한다. 또는 사람 승인 단계를 거친다.

**검사 2. AI 답이 사실처럼 나간다**
- 지적할 때: 정책·가격·의료·법률 안내를 AI가 쓴 그대로 고객에게 보내고, 근거나 AI 표시가 없다.
- 지적하지 않을 때: 정해진 문서에서 찾은 내용만 답하고 근거 링크를 붙인다.

**등급**
- 위험: AI 답만으로 환불·결제·권한 변경이 실행된다.
- 주의: 정책·가격 안내가 근거 없이 고객에게 나간다.
- 참고: AI 표시나 근거 링크가 없다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-LLM-006 support/auto.py:33 — 모델이 정한 refund_amount 로 바로 환불 실행 → 금액은 주문·정책 규칙으로 계산하고, 규칙 밖이면 사람 확인 대기열로`

## 코드

### python

✗ 뚫리는 코드

```python
def handle_ticket(ticket) -> None:
    d = json.loads(llm.chat(f"환불 여부와 금액을 JSON 으로 정해라: {ticket.body}"))
    if d["refund"]:
        payments.refund(ticket.order_id, d["amount"])  # AI가 정한 금액이 그대로 나간다
```

✓ 막는 코드

```python
def handle_ticket(ticket) -> None:
    try:
        intent = json.loads(llm.chat(f"요청 종류를 refund/question/other 중 하나로만 답해라: {ticket.body}"))["intent"]
    except (ValueError, KeyError, TypeError):
        intent = "other"
    order = orders.get_owned(ticket.order_id, owner_id=ticket.user_id)
    amount = refund_policy.amount_for(order) if order else 0   # 금액은 규칙이 정한다
    if intent == "refund" and amount > 0:
        review_queue.add(ticket.id, order_id=order.id, amount=amount)  # 사람이 대상·금액을 보고 승인
    else:
        review_queue.add(ticket.id, note="자동 처리 대상 아님")
```
