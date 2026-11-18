<!-- generated from white-security issues; do not edit -->
# WS-CFG-009 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// routes/api.php
Route::prefix('v1')->group(function () {             // 옛 앱용, 인증 없이 남아 있음
    Route::get('/orders/{order}', [V1\OrderController::class, 'show']);
});
Route::prefix('v2')->middleware('auth:sanctum')->group(function () {
    Route::get('/orders/{order}', [OrderController::class, 'show'])->can('view', 'order');
});
Route::get('/test/reset-db', [DevController::class, 'reset']);   // 개발 중 만든 경로
```

✓ 안전 코드 (safe example)

```php
// routes/api.php — 버전과 관계없이 같은 인증·권한
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('v2')->group(function () {
        Route::get('/orders/{order}', [OrderController::class, 'show'])->can('view', 'order');
    });
    // v1 은 2027-03-31 종료 예정, 남는 동안 같은 컨트롤러·같은 정책
    Route::prefix('v1')->group(function () {
        Route::get('/orders/{order}', [OrderController::class, 'show'])->can('view', 'order');
    });
});

if (app()->environment('local')) {
    Route::get('/test/reset-db', [DevController::class, 'reset']);   // 로컬에서만 등록
}
// 배포 전 php artisan route:list --path=api 로 목록을 뽑아 API 목록과 대조한다
```
