<!-- generated from white-security issues; do not edit -->
# WS-PRV-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 마이그레이션: $table->string('account_number');  — 평문 저장
class Payout extends Model
{
    protected $fillable = ['user_id', 'account_number'];
}

Payout::create(['user_id' => $user->id, 'account_number' => $request->input('account_number')]);
$found = Payout::where('account_number', $input)->first();   // 원문으로 검색
```

✓ 안전 코드 (safe example)

```php
// 마이그레이션: $table->text('account_number'); $table->char('account_number_idx', 64)->index();
// config/app.php: 'index_key' => env('INDEX_KEY'),  — APP_KEY 와 다른 검색 전용 키
class Payout extends Model
{
    protected $fillable = ['user_id', 'account_number', 'account_number_idx'];

    protected function casts(): array
    {
        return ['account_number' => 'encrypted'];   // APP_KEY 로 암호화(AES-256-CBC + MAC)
    }

    public static function blindIndex(string $plain): string
    {
        return hash_hmac('sha256', $plain, config('app.index_key'));
    }
}

$no = $request->validated('account_number');
Payout::create(['user_id' => $user->id, 'account_number' => $no, 'account_number_idx' => Payout::blindIndex($no)]);
$found = Payout::where('account_number_idx', Payout::blindIndex($input))->first();
```
