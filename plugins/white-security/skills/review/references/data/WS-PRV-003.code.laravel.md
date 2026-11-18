<!-- generated from white-security issues; do not edit -->
# WS-PRV-003 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// routes/web.php — 회원 열람·엑셀 내려받기에 기록 없음
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/admin/members/{member}', [MemberController::class, 'show']);
    Route::get('/admin/members-export', [MemberController::class, 'export']);
});

// routes/console.php — 90일 지난 접속기록 삭제
Schedule::call(fn () => AccessLog::where('created_at', '<', now()->subDays(90))->delete())->daily();
```

✓ 안전 코드 (safe example)

```php
// app/Http/Middleware/RecordAccess.php — 관리자 경로에 붙임
class RecordAccess
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $response = $next($request);
        DB::connection('audit')->table('access_logs')->insert([   // INSERT 권한만 가진 별도 DB 계정
            'operator_id' => $request->user()->id,                // 식별자(취급자별 계정)
            'accessed_at' => now('UTC'),                          // 접속일시
            'ip'          => $request->ip(),                      // 접속지(프록시 뒤면 TrustProxies 설정 확인)
            'subjects'    => json_encode($request->attributes->get('subjects', [])), // 처리한 정보주체(회원 id)
            'action'      => $action,                             // 수행업무
            'detail'      => $request->path(),
        ]);
        return $response;
    }
}

// routes/web.php
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/admin/members/{member}', [MemberController::class, 'show'])->middleware(RecordAccess::class.':열람');
    Route::get('/admin/members-export', [MemberController::class, 'export'])->middleware(RecordAccess::class.':다운로드');
});
// 컨트롤러: $request->attributes->set('subjects', $members->pluck('id'));
// 정리 배치는 감사 관리 계정으로, 1년(2년 조건이면 2년)보다 오래된 기록만
```
