<!-- generated from white-security issues; do not edit -->
# WS-AGT-001 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
TOOLS = {
    "run_shell": lambda cmd: subprocess.run(cmd, shell=True, capture_output=True, text=True).stdout,
    "run_sql": lambda q: admin_db.execute(q).fetchall(),  # 운영 DB 관리자 계정
}
for call in reply.tool_calls:
    result = TOOLS[call.name](**call.arguments)  # 무엇이든 묻지 않고 실행한다
```

✓ 안전 코드 (safe example)

```python
READ_ONLY = {
    "list_orders": lambda status: readonly_db.list_orders(status=str(status)),  # 읽기 전용 계정
    "git_status": lambda: subprocess.run(["git", "status", "--short"], capture_output=True, text=True).stdout,
}
NEEDS_APPROVAL = {"cancel_order", "deploy"}

for call in reply.tool_calls:
    if call.name in READ_ONLY:
        result = READ_ONLY[call.name](**call.arguments)
    elif call.name in NEEDS_APPROVAL:  # 사람이 실제 대상·인자를 보고 [확인]해야 실행된다
        approvals.request(user_id=user.id, tool=call.name, args=call.arguments)
        result = "승인 대기 중"
    else:
        result = "허용되지 않은 도구"
```
