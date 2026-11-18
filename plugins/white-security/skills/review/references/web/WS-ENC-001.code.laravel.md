<!-- generated from white-security issues; do not edit -->
# WS-ENC-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// routes/web.php
Route::get('/debug', fn () => phpinfo());              // 서버 경로·환경 변수·모듈 목록 노출

// UserController
public function me(Request $request)
{
    return $request->user();                           // $hidden 에 없는 열(전화번호·내부 메모)이 모두 나간다
}
```

✓ 안전 코드 (safe example)

```php
// routes/web.php 의 /debug 라우트는 지운다

// app/Http/Resources/MeResource.php
class MeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email]; // 보낼 필드만
    }
}

// UserController
public function me(Request $request)
{
    return new MeResource($request->user());
}
```
