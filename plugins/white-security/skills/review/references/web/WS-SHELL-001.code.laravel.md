<!-- generated from white-security issues; do not edit -->
# WS-SHELL-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 관리자 '테마 파일 편집' 기능
public function save(Request $request)
{
    $path = public_path('themes/' . $request->input('file'));  // 이름·확장자를 요청이 정함
    file_put_contents($path, $request->input('content'));     // .php 로 쓰면 그대로 실행된다
    return back();
}
```

✓ 안전 코드 (safe example)

```php
public function save(Request $request)
{
    $data = $request->validate([
        'theme'   => ['required', 'string', 'regex:/^[a-z0-9-]{1,40}$/'],
        'content' => ['required', 'string', 'max:20000'],
    ]);
    // 웹에서 바로 열리지 않는 storage 에, 확장자를 .css 로 고정해 저장
    Storage::disk('local')->put("themes/{$data['theme']}.css", $data['content']);
    return back();
}
// 화면에는 이 파일을 Content-Type: text/css 로 내려주는 라우트로만 제공
```
