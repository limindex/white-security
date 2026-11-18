---
id: WS-XSS-001
title: XSS (크로스사이트 스크립팅)
title_en: XSS (Cross-Site Scripting)
slug: xss
group: web
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs, django]
maps:
  owasp2025: [A05]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.2.1, V1.2.2, V1.3.1, V3.2.2]
  cwe: [CWE-79]
  kr_guide: ["입력데이터 검증 및 표현-4"]
  kr_ciip: [XS]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel(Blade), Next.js·React, Django, 사용자 글을 화면에 보여 주는 모든 웹 애플리케이션]
severity_reason: "영향: 로그인 상태 도용·사용자 대신 글쓰기·화면 조작 · 악용: 게시글·댓글·링크 한 줄로 가능"
sources:
  - title: OWASP Cross Site Scripting Prevention Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html
    year: 2026
  - title: 2025 CWE Top 25 Most Dangerous Software Weaknesses (CWE-79 1위)
    url: https://cwe.mitre.org/top25/archive/2025/2025_cwe_top25.html
    year: 2025
  - title: "React - Common components: dangerouslySetInnerHTML"
    url: https://react.dev/reference/react-dom/components/common
    year: 2026
  - title: "Laravel 12.x Blade Templates - Displaying Data"
    url: https://laravel.com/docs/12.x/blade#displaying-data
    year: 2026
  - title: "Django - Automatic HTML escaping"
    url: https://docs.djangoproject.com/en/5.2/ref/templates/language/#automatic-html-escaping
    year: 2026
---

## 요약

게시글·댓글·검색어처럼 사용자가 넣은 글자가 이스케이프 없이 HTML로 그려져, 다른 사람의 브라우저에서 스크립트가 실행되는 문제(진단가이드 이름: 크로스사이트 스크립트). 템플릿의 자동 이스케이프를 끄는 `{!! !!}`·`dangerouslySetInnerHTML`·`|safe`가 흔한 원인.

## 대응 방법

```text
템플릿과 컴포넌트에서 이스케이프를 끄는 곳({!! !!}, dangerouslySetInnerHTML, |safe, mark_safe, innerHTML, v-html)을 모두 찾아 줘.
글자만 보여 주면 되는 곳은 기본 출력으로 바꾸고, HTML이 꼭 필요한 곳만 DOMPurify·nh3 같은 새니타이저를 거치게 해 줘.
사용자가 넣은 링크 주소는 http·https만 받게 해 줘.
```

- 댓글에 `<b>바나나</b>`를 쓰면 태그 글자가 그대로 보여야 한다.
- 홈페이지 칸에 `javascript:`로 시작하는 주소를 넣으면 링크가 만들어지지 않거나 눌러도 실행되지 않아야 한다.

## 점검 항목

- 화면 출력은 기본 출력(`{{ }}`, JSX `{}`)으로 하고 이스케이프를 끄지 않아야 한다.
- HTML을 받아야 하면 허용 태그만 남기는 새니타이저를 거쳐야 한다.
- 사용자 링크의 `href`·`src`는 `http:`·`https:`만 허용해야 한다.
- `<script>` 안의 데이터는 `@json`·`json_script`처럼 이스케이프해 주는 기능으로 넣어야 한다.
- CSP로 인라인 스크립트를 막아 두 번째 방어선을 둬야 한다.

## 탐지 규칙

- **찾는 곳**: `{!! $`, `dangerouslySetInnerHTML`, `mark_safe(`, `|safe`, `.innerHTML =`, 그 밖에 v-html, document.write, 사용자 값이 들어가는 href·src.
- **검사 1 — 이스케이프 끔**: 지적할 때: 요청·DB·외부 API·AI 출력 값이 `{!! !!}`, `dangerouslySetInnerHTML`, `|safe`, `mark_safe`, `innerHTML`로 새니타이저 없이 들어가는 경우. / 지적하지 않을 때: 코드에 고정된 HTML이거나 DOMPurify·nh3·HTMLPurifier를 거친 값인 경우.
- **검사 2 — 위험한 주소**: 지적할 때: 사용자 값이 스킴 검사 없이 `window.location`·`location.href =`, Blade·Django 템플릿의 `href`, React 18 이하 JSX의 `href`·`src`에 들어가는 경우. / 지적하지 않을 때: `new URL()`·`parse_url()`로 해석해 `http`·`https`만 허용하는 경우. React 19+ JSX의 `href`(`javascript:`는 React가 차단).
- **검사 3 — 스크립트 안 데이터**: 지적할 때: `<script>` 블록 안에 서버 값을 문자열로 직접 끼우는 경우. / 지적하지 않을 때: `@json`·`json_script`, 또는 `<`를 이스케이프한 JSON을 쓰는 경우.
- **등급**: 위험: 남이 쓴 글이 이스케이프 없이 HTML로 그려지는 경우. / 주의: 본인만 보는 값의 이스케이프 끔, `javascript:` 링크 가능, `<script>` 안에 값 직접 끼움, 남이 쓴 글·AI 출력을 그리는 화면에 CSP 없음, 세션 쿠키에 `HttpOnly` 없음(WS-SESS-001과 같은 등급). / 참고: 그 밖의 화면에 CSP 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-XSS-001 resources/views/posts/show.blade.php:14 — 댓글 본문을 {!! !!} 로 출력 → {{ }} 로 바꾸고 줄바꿈은 CSS 로 처리`
- **고칠 방법**: 이스케이프를 다시 켜야 하고, 줄바꿈 같은 서식이 필요하면 CSS(`white-space: pre-line`)나 이스케이프 다음 단계의 서식 함수로 처리해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```blade
{{-- resources/views/posts/show.blade.php --}}
<div class="comment">{!! $comment->body !!}</div>   {{-- 이스케이프를 끔 --}}
<a href="{{ $comment->website }}">홈페이지</a>       {{-- javascript: 주소도 링크가 된다 --}}
```

✓ 안전 코드

```blade
{{-- 글자로만 출력, 줄바꿈은 CSS(white-space: pre-line) --}}
<div class="comment comment-text">{{ $comment->body }}</div>
@if (in_array(parse_url((string) $comment->website, PHP_URL_SCHEME), ['http', 'https'], true))
  <a href="{{ $comment->website }}" rel="nofollow noopener">홈페이지</a>
@endif
<script type="application/json" id="post-data">@json($post->only(['id', 'title']))</script>
{{-- JS 에서 JSON.parse(document.getElementById('post-data').textContent) 로 읽는다(실행되지 않는 데이터 블록) --}}
```

### nextjs

✗ 취약 코드

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

✓ 안전 코드

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

### django

✗ 취약 코드

```python
from django.utils.safestring import mark_safe

def comment(request, pk):
    c = Comment.objects.get(pk=pk)
    return render(request, "comment.html", {"body": mark_safe(c.body)})  # 이스케이프를 끔
# comment.html: <div>{{ body }}</div>
```

✓ 안전 코드

```python
import nh3

def comment(request, pk):
    c = get_object_or_404(Comment, pk=pk)
    return render(request, "comment.html", {
        "body": c.body,                                                   # 템플릿이 자동 이스케이프
        "rich": nh3.clean(c.html, tags={"b", "i", "p", "ul", "li", "a"}),  # HTML 이 필요하면 허용 태그만
        "meta": {"id": c.pk, "author": c.author_name},                   # 스크립트에서 읽을 데이터
    })
# comment.html: <div>{{ body|linebreaksbr }}</div> <div>{{ rich|safe }}</div> {{ meta|json_script:"meta" }}
```
