<!-- generated from white-security issues; do not edit -->
# WS-ERR-002 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function order(Request $request)
{
    $qty = (int) $request->input('qty');                       // -5 도, 아주 큰 수도 그대로
    $total = $qty * Product::findOrFail($request->input('product_id'))->price;
    return Order::create(['user_id' => $request->user()->id, 'qty' => $qty, 'total' => $total]);
}
```

✓ 안전 코드 (safe example)

```php
public function order(Request $request)
{
    $data = $request->validate([
        'product_id' => ['required', 'integer', 'exists:products,id'],
        'qty' => ['required', 'integer', 'min:1', 'max:100'],    // 정수·범위
    ]);
    $product = Product::findOrFail($data['product_id']);
    $total = $data['qty'] * $product->price;                   // 가격은 서버 값, 원 단위 정수
    return Order::create(['user_id' => $request->user()->id, 'qty' => $data['qty'], 'total' => $total]);
}
```
