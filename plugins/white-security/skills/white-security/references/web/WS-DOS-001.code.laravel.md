<!-- generated from white-security issues; do not edit -->
# WS-DOS-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function index(Request $request)
{
    return Post::paginate($request->input('per_page', 20));   // per_page=1000000 도 그대로
}
```

✓ 안전 코드 (safe example)

```php
public function index(Request $request)
{
    $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]); // 상한 100
    return Post::select(['id', 'title', 'created_at'])->paginate($data['per_page'] ?? 20);
}
```
