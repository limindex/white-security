<!-- generated from white-security issues; do not edit -->
# WS-AGT-004 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
@tool
def get_invoice(invoice_id: str, user_id: str) -> dict:  # user_id 를 모델이 채운다
    return service_db.invoices.find_one({"id": invoice_id})  # 서비스 계정은 모든 고객 송장을 본다
```

✓ 안전 코드 (safe example)

```python
def make_tools(session_user):  # 요청마다 서버가 세션 사용자로 도구를 만든다
    @tool
    def get_invoice(invoice_id: str) -> dict:
        inv = service_db.invoices.find_one({"id": invoice_id, "customer_id": session_user.customer_id})
        return inv or {"error": "송장을 찾을 수 없습니다"}
    return [get_invoice]
```
