<!-- generated from white-security issues; do not edit -->
# WS-PI-002 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
import markdown

def render_answer(ai_text: str) -> str:
    return markdown.markdown(ai_text)  # 템플릿에서 |safe 로 그린다
```

✓ 안전 코드 (safe example)

```python
import markdown
import nh3
from urllib.parse import urlsplit

IMG_HOSTS = {"img.example.com"}  # 내가 운영하는 곳만
TAGS = {"p", "br", "strong", "em", "code", "pre", "ul", "ol", "li", "a", "img", "blockquote"}

def _keep(tag: str, attr: str, value: str) -> str | None:
    if tag == "img" and attr == "src" and urlsplit(value).hostname not in IMG_HOSTS:
        return None  # 주소가 없으면 요청도 없다
    return value

def render_answer(ai_text: str) -> str:
    return nh3.clean(markdown.markdown(ai_text), tags=TAGS,
                     attributes={"a": {"href", "title"}, "img": {"src", "alt"}},
                     url_schemes={"https"}, attribute_filter=_keep)
```
