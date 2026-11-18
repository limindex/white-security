<!-- generated from white-security issues; do not edit -->
# WS-UPL-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function store(Request $request)
{
    $file = $request->file('photo');
    $file->move(public_path('uploads'), $file->getClientOriginalName()); // 형식·크기 검사 없음, 이름 그대로
    return back();
}
```

✓ 안전 코드 (safe example)

```php
public function store(Request $request)
{
    $request->validate([
        'photo' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp'], // 내용·확장자, 5MB
    ]);
    $file = $request->file('photo');
    $path = $file->store('photos', 'local');                    // 무작위 이름(hashName), 웹 루트 밖
    $request->user()->photos()->create([
        'path' => $path,
        'original_name' => $file->getClientOriginalName(),     // 원래 이름은 DB 에만
    ]);
    return back();
}
```
