<!-- generated from white-security issues; do not edit -->
# WS-LOG-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 로그인 실패는 아무 데도 남기지 않고, 성공 때는 요청 전체를 남김
public function login(Request $request)
{
    if (! Auth::attempt($request->only('email', 'password'))) {
        return back()->withErrors(['email' => '로그인 실패']);
    }
    Log::info('login', $request->all());          // 비밀번호까지 로그에
    return redirect('/');
}
// config/logging.php: daily 채널 'days' => 14 — 2주 뒤 삭제
```

✓ 안전 코드 (safe example)

```php
// app/Providers/AppServiceProvider.php — 로그인 사건을 한곳에서 기록(비밀번호는 넣지 않음)
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;

public function boot(): void
{
    Event::listen(fn (Failed $e) => Log::channel('security')->warning('auth.failed', [
        'email' => $e->credentials['email'] ?? null, 'ip' => request()->ip(),
    ]));
    Event::listen(fn (Login $e) => Log::channel('security')->info('auth.login', [
        'user_id' => $e->user->getAuthIdentifier(), 'ip' => request()->ip(),
    ]));
}

// 관리자의 개인정보 내려받기(접속기록: 누가·언제·어디서·누구의 정보를·무엇을)
Log::channel('security')->info('admin.export', [
    'admin_id' => $request->user()->id, 'ip' => $request->ip(),
    'subject_ids' => $ids, 'action' => 'download', 'reason' => $request->input('reason'),   // 내려받기 사유
]);

// config/logging.php — 'security' 채널은 별도 수집 서버로(앱이 지울 수 없게), 1년 이상 보관
// 'security' => ['driver' => 'syslog', 'level' => 'info', 'facility' => LOG_AUTH],
```
