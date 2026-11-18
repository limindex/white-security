<!-- generated from white-security issues; do not edit -->
# WS-SUP-006 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
{{-- resources/views/layouts/app.blade.php — 몇 년 전 복사한 파일, 업데이트 알림·검사 대상 밖 --}}
<script src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('vendor/summernote-0.8.9/summernote-lite.min.js') }}"></script>
```

✓ 안전 코드 (safe example)

```php
{{-- npm install jquery summernote 로 package.json 에서 관리 → Dependabot·npm audit 대상 --}}
{{-- resources/js/app.js: import $ from "jquery"; window.$ = window.jQuery = $; import "summernote/dist/summernote-lite.js"; --}}
@vite('resources/js/app.js')
```
