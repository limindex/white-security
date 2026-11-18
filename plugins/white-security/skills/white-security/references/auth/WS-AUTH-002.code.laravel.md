<!-- generated from white-security issues; do not edit -->
# WS-AUTH-002 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// routes/api.php
Route::get('/orders/export', [OrderExportController::class, 'download']);  // 로그인 확인 없음
Route::post('/coupons', [CouponController::class, 'store']);
Route::post('/cron/settle', [SettleController::class, 'run']);             // "내부용"이라 그냥 열어 둠
```

✓ 안전 코드 (safe example)

```php
// routes/api.php — 묶음 전체를 기본 잠금
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders/export', [OrderExportController::class, 'download']);
    Route::post('/coupons', [CouponController::class, 'store'])->middleware('can:manage-coupons');
});

// routes/console.php — 정산은 웹 경로 대신 스케줄러로
use Illuminate\Support\Facades\Schedule;
Schedule::command('orders:settle')->dailyAt('03:00');
```
