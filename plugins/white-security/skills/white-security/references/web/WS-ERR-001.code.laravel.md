<!-- generated from white-security issues; do not edit -->
# WS-ERR-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function show(int $id)
{
    try {
        return Order::findOrFail($id);
    } catch (\Throwable $e) {
        return response($e->getMessage() . "\n" . $e->getTraceAsString(), 500); // SQL·경로가 그대로 나간다
    }
}
```

✓ 안전 코드 (safe example)

```php
public function show(Request $request, int $id)
{
    $order = Order::whereKey($id)->where('user_id', $request->user()->id)->first();
    abort_if(! $order, 404);                  // 예상한 상황은 상태 코드로
    return $order;
}
// 예상 못 한 예외는 잡지 않고 Laravel 처리기에 맡긴다.
// APP_DEBUG=false 면 화면은 고정 문구, 원문은 storage/logs 에만 남는다.
```
