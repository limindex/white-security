<!-- generated from white-security issues; do not edit -->
# WS-RACE-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function redeem(Request $request, Coupon $coupon)
{
    if ($coupon->used_at !== null) abort(409);            // 확인
    $request->user()->increment('points', $coupon->points);
    $coupon->update(['used_at' => now()]);                 // 따로 변경: 동시 요청은 둘 다 통과
    return back();
}
```

✓ 안전 코드 (safe example)

```php
public function redeem(Request $request, Coupon $coupon)
{
    DB::transaction(function () use ($request, $coupon) {
        $used = Coupon::whereKey($coupon->id)->whereNull('used_at')
            ->update(['used_at' => now(), 'used_by' => $request->user()->id]); // 확인과 변경을 한 문장으로
        abort_if($used !== 1, 409, '이미 사용한 쿠폰입니다');                   // 바뀐 행 수로 판단
        $request->user()->increment('points', $coupon->points);              // 같은 트랜잭션
    });
    return back();
}
```
