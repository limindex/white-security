<!-- generated from white-security issues; do not edit -->
# WS-MCP-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
tools = await session.list_tools()  # 서버가 준 설명을 매번 그대로 쓴다
reply = llm.chat(messages, tools=[t.model_dump() for t in tools.tools])  # 바뀐 설명 속 지시도 모델에 들어간다
```

✓ 안전 코드 (safe example)

```python
import hashlib
import json

def fingerprint(tools) -> str:
    canon = sorted(({"name": t.name, "description": t.description or "", "schema": t.inputSchema}
                    for t in tools), key=lambda t: t["name"])
    return hashlib.sha256(json.dumps(canon, sort_keys=True, ensure_ascii=False).encode()).hexdigest()

tools = (await session.list_tools()).tools
if fingerprint(tools) != approved_fingerprints.get(server_name):  # 사람이 검토하고 저장한 값
    raise PermissionError(f"{server_name}: 도구 설명이 바뀌었습니다. 다시 검토한 뒤 승인하세요")
reply = llm.chat(messages, tools=[t.model_dump() for t in tools])
```
