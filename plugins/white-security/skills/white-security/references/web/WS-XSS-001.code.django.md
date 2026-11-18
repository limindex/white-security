<!-- generated from white-security issues; do not edit -->
# WS-XSS-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
from django.utils.safestring import mark_safe

def comment(request, pk):
    c = Comment.objects.get(pk=pk)
    return render(request, "comment.html", {"body": mark_safe(c.body)})  # 이스케이프를 끔
# comment.html: <div>{{ body }}</div>
```

✓ 안전 코드 (safe example)

```python
import nh3

def comment(request, pk):
    c = get_object_or_404(Comment, pk=pk)
    return render(request, "comment.html", {
        "body": c.body,                                                   # 템플릿이 자동 이스케이프
        "rich": nh3.clean(c.html, tags={"b", "i", "p", "ul", "li", "a"}),  # HTML 이 필요하면 허용 태그만
    })
# comment.html: <div>{{ body|linebreaksbr }}</div> <div>{{ rich|safe }}</div> {{ meta|json_script:"meta" }}
```
