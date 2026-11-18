<!-- generated from white-security issues; do not edit -->
# WS-INJ-003 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 사용자가 쓴 안내 문구를 Blade 로 렌더링
public function preview(Request $request)
{
    return Blade::render($request->input('template'), ['user' => $request->user()]); // @php 등도 실행된다
}
```

✓ 안전 코드 (safe example)

```php
public function preview(Request $request)
{
    $data = $request->validate(['template' => ['required', 'string', 'max:2000']]);
    $values = [':name' => $request->user()->name, ':date' => now()->toDateString()]; // 정해진 자리만
    $text = strtr($data['template'], $values);                      // 코드로 해석하지 않는 단순 치환
    return view('notice.preview', ['text' => $text]);                // 화면은 {{ $text }} 로 이스케이프 출력
}
```
