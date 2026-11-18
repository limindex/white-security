<!-- generated from white-security issues; do not edit -->
# WS-XSS-001 XSS (크로스사이트 스크립팅)

## 탐지 규칙

- **찾는 곳**: `{!! $`, `dangerouslySetInnerHTML`, `mark_safe(`, `|safe`, `.innerHTML =`, 그 밖에 v-html, document.write, 사용자 값이 들어가는 href·src.
- **검사 1 — 이스케이프 끔**: 지적할 때: 요청·DB·외부 API·AI 출력 값이 `{!! !!}`, `dangerouslySetInnerHTML`, `|safe`, `mark_safe`, `innerHTML`로 새니타이저 없이 들어가는 경우. / 지적하지 않을 때: 코드에 고정된 HTML이거나 DOMPurify·nh3·HTMLPurifier를 거친 값인 경우.
- **검사 2 — 위험한 주소**: 지적할 때: 사용자 값이 스킴 검사 없이 `window.location`·`location.href =`, Blade·Django 템플릿의 `href`, React 18 이하 JSX의 `href`·`src`에 들어가는 경우. / 지적하지 않을 때: `new URL()`·`parse_url()`로 해석해 `http`·`https`만 허용하는 경우. React 19+ JSX의 `href`(`javascript:`는 React가 차단).
- **검사 3 — 스크립트 안 데이터**: 지적할 때: `<script>` 블록 안에 서버 값을 문자열로 직접 끼우는 경우. / 지적하지 않을 때: `@json`·`json_script`, 또는 `<`를 이스케이프한 JSON을 쓰는 경우.
- **등급**: 위험: 남이 쓴 글이 이스케이프 없이 HTML로 그려지는 경우. / 주의: 본인만 보는 값의 이스케이프 끔, `javascript:` 링크 가능, `<script>` 안에 값 직접 끼움, 남이 쓴 글·AI 출력을 그리는 화면에 CSP 없음, 세션 쿠키에 `HttpOnly` 없음(WS-SESS-001과 같은 등급). / 참고: 그 밖의 화면에 CSP 없음.
- **보고 예**: `[위험] WS-XSS-001 resources/views/posts/show.blade.php:14 — 댓글 본문을 {!! !!} 로 출력 → {{ }} 로 바꾸고 줄바꿈은 CSS 로 처리`
- **고칠 방법**: 이스케이프를 다시 켜야 하고, 줄바꿈 같은 서식이 필요하면 CSS(`white-space: pre-line`)나 이스케이프 다음 단계의 서식 함수로 처리해야 한다.

## 점검 항목

- 화면 출력은 기본 출력(`{{ }}`, JSX `{}`)으로 하고 이스케이프를 끄지 않아야 한다.
- HTML을 받아야 하면 허용 태그만 남기는 새니타이저를 거쳐야 한다.
- 사용자 링크의 `href`·`src`는 `http:`·`https:`만 허용해야 한다.
- `<script>` 안의 데이터는 `@json`·`json_script`처럼 이스케이프해 주는 기능으로 넣어야 한다.
- CSP로 인라인 스크립트를 막아 두 번째 방어선을 둬야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-XSS-001.code.laravel.md` (Laravel) · `WS-XSS-001.code.nextjs.md` (Next.js) · `WS-XSS-001.code.django.md` (Django)

기본 심각도: 위험 · CWE-79
