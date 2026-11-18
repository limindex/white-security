<!-- generated from white-security issues; do not edit -->
# WS-INJ-003 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
# 관리자가 정한 알림 문구 예: "{user.name}님, 주문이 접수되었습니다"
def render_notice(template: str, user) -> str:
    return template.format(user=user)  # 서식 안에서 객체 속성을 따라가 설정·비밀값까지 꺼낼 수 있다
```

✓ 안전 코드 (safe example)

```python
from string import Template

def render_notice(template: str, user, order) -> str:
    values = {"name": str(user.name), "order_no": str(order.no)}  # 쓸 수 있는 값만 문자열로
    return Template(template).safe_substitute(values)               # $name·$order_no 만 바뀐다, 속성 접근 없음
```
