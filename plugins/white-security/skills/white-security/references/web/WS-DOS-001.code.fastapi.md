<!-- generated from white-security issues; do not edit -->
# WS-DOS-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/items")
def items(limit: int = 20):
    return db.query(Item).limit(limit).all()           # limit=10000000 도 그대로

def count_nodes(tree: dict) -> int:                    # 요청 JSON 을 깊이·개수 제한 없이 재귀
    return 1 + sum(count_nodes(c) for c in tree.get("children", []))
```

✓ 안전 코드 (safe example)

```python
from fastapi import Query

@app.get("/items")
def items(limit: int = Query(20, ge=1, le=100)):       # 서버 상한
    return db.query(Item).limit(limit).all()

def count_nodes(tree: dict, max_nodes: int = 1000, max_depth: int = 20) -> int:
    stack, seen = [(tree, 0)], 0
    while stack:                                        # 재귀 대신 반복 + 전체 개수·깊이 상한
        node, depth = stack.pop()
        seen += 1
        if seen > max_nodes or depth > max_depth:
            raise HTTPException(400, "구조가 너무 큽니다")
        kids = node.get("children", [])
        if isinstance(kids, list):
            stack.extend((c, depth + 1) for c in kids if isinstance(c, dict))
    return seen
```
