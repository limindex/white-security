<!-- generated from white-security issues; do not edit -->
# WS-CFG-008 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// bootstrap/app.php — Laravel 11+ 기본 골격: api 경로에 횟수 제한 없음
->withMiddleware(function (Middleware $middleware): void {
    //
})

// routes/api.php
Route::get('/products', fn (Request $r) => Product::paginate($r->integer('per_page', 20)));  // per_page=1000000 가능
Route::post('/reports', [ReportController::class, 'store']);   // 무거운 작업, 제한 없음
```

✓ 안전 코드 (safe example)

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    RateLimiter::for('api', fn (Request $r) =>
        Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
    RateLimiter::for('heavy', fn (Request $r) =>
        Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));
}

// bootstrap/app.php — api 경로 전체에 'api' 한도
->withMiddleware(function (Middleware $middleware): void {
    $middleware->throttleApi();
    // 로드밸런서·CDN 뒤라면 그 프록시만 신뢰해야 $r->ip() 가 실제 접속 IP 가 된다
    // $middleware->trustProxies(at: ['10.0.0.0/8']);   // 내 프록시 대역, '*' 금지
})

// routes/api.php
Route::get('/products', fn (Request $r) =>
    Product::paginate(min(max($r->integer('per_page', 20), 1), 100)));   // 페이지 크기 상한
Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:heavy');
// 서버가 여럿이면 캐시 저장소를 Redis 로(카운터 공유), 본문 크기는 웹 서버 client_max_body_size 로
```
