<!-- generated from white-security issues; do not edit -->
# WS-LLM-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
def calculate(question: str) -> str:
    expr = llm.complete(f"이 계산을 파이썬 식으로 써라: {question}")
    return str(eval(expr))  # AI가 쓴 코드를 그대로 실행한다
```

✓ 안전 코드 (safe example)

```python
import ast
import operator

OPS = {ast.Add: operator.add, ast.Sub: operator.sub, ast.Mult: operator.mul, ast.Div: operator.truediv}

def _eval(node):
    if isinstance(node, ast.Constant) and type(node.value) in (int, float):
        return node.value
    if isinstance(node, ast.UnaryOp) and isinstance(node.op, ast.USub):
        return -_eval(node.operand)
    if isinstance(node, ast.BinOp) and type(node.op) in OPS:
        return OPS[type(node.op)](_eval(node.left), _eval(node.right))
    raise ValueError("숫자와 + - * / 만 계산합니다")

def calculate(question: str) -> str:
    expr = llm.complete(f"이 계산을 숫자와 + - * / 만 쓴 식으로 써라: {question}")[:200]
    return str(_eval(ast.parse(expr, mode="eval").body))  # 실행하지 않고 직접 계산한다
```
