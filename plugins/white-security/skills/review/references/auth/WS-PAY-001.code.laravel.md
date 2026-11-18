<!-- generated from white-security issues; do not edit -->
# WS-PAY-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 결제창이 돌아오면 쿼리 값을 그대로 믿는다: /pay/return?order_id=..&status=success&amount=100
public function return(Request $request)
{
    $order = Order::findOrFail($request->order_id);
    if ($request->status === 'success') {
        $order->update(['status' => 'paid', 'paid_amount' => $request->amount]);
    }
    return redirect('/orders/'.$order->id);
}
```

✓ 안전 코드 (safe example)

```php
public function return(Request $request)
{
    $order = Order::where('id', $request->input('order_id'))->where('status', 'pending')->firstOrFail();
    // 결제대행(PG) 승인 API 를 서버가 직접 부른다(주소·인증·필드 이름은 PG 문서대로)
    $res = Http::withBasicAuth(config('services.pg.secret_key'), '')
        ->post(config('services.pg.confirm_url'), [
            'payment_id' => (string) $request->input('payment_id'),
            'order_id' => (string) $order->id,
            'amount' => $order->total_amount,              // 금액은 서버의 주문 기록
        ])->throw()->json();
    abort_unless(($res['order_id'] ?? null) === (string) $order->id
        && (int) ($res['amount'] ?? -1) === (int) $order->total_amount    // DB 값이 문자열로 올 수 있어 정수로 맞춘다
        && ($res['currency'] ?? '') === $order->currency && ($res['status'] ?? '') === 'paid', 409);
    $done = Order::where('id', $order->id)->where('status', 'pending')       // 대기 상태일 때만 → 한 번만
        ->update(['status' => 'paid', 'payment_id' => $res['payment_id']]);  // payment_id 는 unique 컬럼
    abort_if($done === 0, 409);
    return redirect('/orders/'.$order->id);
}
```
