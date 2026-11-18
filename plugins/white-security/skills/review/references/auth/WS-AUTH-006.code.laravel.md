<!-- generated from white-security issues; do not edit -->
# WS-AUTH-006 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
class User extends Authenticatable
{
    protected $guarded = [];                       // 모든 필드 대량 할당 허용
}

public function update(Request $request)
{
    $request->user()->update($request->all());     // 본문에 "role":"admin" 을 넣으면 그대로 저장
    return $request->user();                       // 모델 전체 응답
}
```

✓ 안전 코드 (safe example)

```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'nickname', 'marketing_opt_in'];  // role·points 는 목록에 없다
    protected $hidden = ['password', 'remember_token'];
}

public function update(Request $request)
{
    $data = $request->validate([                   // 규칙에 있는 키만 돌려준다
        'nickname' => 'sometimes|string|max:30',
        'marketing_opt_in' => 'sometimes|boolean',
    ]);
    $request->user()->update($data);
    return $request->user()->only(['id', 'nickname', 'marketing_opt_in']);
}
```
