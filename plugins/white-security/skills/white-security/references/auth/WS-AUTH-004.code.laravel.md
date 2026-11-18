<!-- generated from white-security issues; do not edit -->
# WS-AUTH-004 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// app/Providers/TelescopeServiceProvider.php — 운영에서도 누구나 요청·쿼리·메일 기록을 본다
protected function gate(): void
{
    Gate::define('viewTelescope', fn ($user = null) => true);
}

// routes/web.php — 로그인만 확인, 역할은 메뉴에서만 숨김
Route::middleware('auth')->prefix('admin')->group(base_path('routes/admin.php'));
```

✓ 안전 코드 (safe example)

```php
// app/Providers/AppServiceProvider.php — Telescope 는 로컬에서만 등록(공식 문서의 로컬 전용 설치)
public function register(): void
{
    if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->app->register(TelescopeServiceProvider::class);
    }
}

public function boot(): void
{
    Gate::define('access-admin', fn (User $user) => $user->is_admin);  // DB 에 저장된 역할
}

// routes/web.php — 역할 확인 + 중요 화면 진입 전 비밀번호 재확인
Route::middleware(['auth', 'can:access-admin', 'password.confirm'])
    ->prefix('admin')->group(base_path('routes/admin.php'));
// 관리자 계정은 2단계 인증 필수, 접속은 웹 서버·방화벽에서 사무실·VPN IP 로 제한
```
