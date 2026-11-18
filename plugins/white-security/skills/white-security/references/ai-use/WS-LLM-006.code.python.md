<!-- generated from white-security issues; do not edit -->
# WS-LLM-006 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
def handle_ticket(ticket) -> None:
    d = json.loads(llm.chat(f"환불 여부와 금액을 JSON 으로 정해라: {ticket.body}"))
    if d["refund"]:
        payments.refund(ticket.order_id, d["amount"])  # AI가 정한 금액이 그대로 나간다
```

✓ 안전 코드 (safe example)

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
