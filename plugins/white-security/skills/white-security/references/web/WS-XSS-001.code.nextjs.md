<!-- generated from white-security issues; do not edit -->
# WS-XSS-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```tsx
export function Comment({ body, website }: { body: string; website: string }) {
  return (
    <div>
      <p dangerouslySetInnerHTML={{ __html: body }} />  {/* 남이 쓴 HTML 을 그대로 */}
      <a href={website}>홈페이지</a>                       {/* React 18 이하는 javascript: 주소가 실행된다(19+는 React 가 차단) */}
    </div>
  );
}
```

✓ 안전 코드 (safe example)

```tsx
import DOMPurify from "isomorphic-dompurify";

const safeUrl = (u: string) => {
  try { const p = new URL(u); return p.protocol === "https:" || p.protocol === "http:" ? p.href : null; }
  catch { return null; }
};

export function Comment({ body, html, website }: { body: string; html?: string; website: string }) {
  const href = safeUrl(website);
  return (
    <div>
      <p className="whitespace-pre-line">{body}</p>  {/* 글자로만 그린다 */}
      {html && <div dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(html) }} />}  {/* HTML 은 새니타이저 뒤에만 */}
      {href && <a href={href} rel="nofollow noopener">홈페이지</a>}
    </div>
  );
}
```
