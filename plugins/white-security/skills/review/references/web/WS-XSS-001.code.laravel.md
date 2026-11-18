<!-- generated from white-security issues; do not edit -->
# WS-XSS-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```blade
{{-- resources/views/posts/show.blade.php --}}
<div class="comment">{!! $comment->body !!}</div>   {{-- 이스케이프를 끔 --}}
<a href="{{ $comment->website }}">홈페이지</a>       {{-- javascript: 주소도 링크가 된다 --}}
```

✓ 안전 코드 (safe example)

```blade
{{-- 글자로만 출력, 줄바꿈은 CSS(white-space: pre-line) --}}
<div class="comment comment-text">{{ $comment->body }}</div>
@if (in_array(parse_url((string) $comment->website, PHP_URL_SCHEME), ['http', 'https'], true))
  <a href="{{ $comment->website }}" rel="nofollow noopener">홈페이지</a>
@endif
<script type="application/json" id="post-data">@json($post->only(['id', 'title']))</script>
{{-- JS 에서 JSON.parse(document.getElementById('post-data').textContent) 로 읽는다(실행되지 않는 데이터 블록) --}}
```
