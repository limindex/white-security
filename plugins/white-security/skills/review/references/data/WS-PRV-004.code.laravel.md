<!-- generated from white-security issues; do not edit -->
# WS-PRV-004 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 회원가입: 나이 확인을 위해 주민등록번호를 필수로 받음
$data = $request->validate([
    'name'  => ['required', 'string'],
    'email' => ['required', 'email'],
    'jumin' => ['required', 'regex:/^\d{6}-?[1-8]\d{6}$/'],
]);
$user = User::create($data);          // 평문 저장
Log::info('signup', $data);           // 로그에도 원문
```

✓ 안전 코드 (safe example)

```php
// 회원가입: 주민등록번호 대신 생년월일(만 14세 미만은 법정대리인 동의 절차가 따로 필요해 여기선 받지 않음)
$data = $request->validate([
    'name'       => ['required', 'string', 'max:50'],
    'email'      => ['required', 'email'],
    'birth_date' => ['required', 'date', 'before_or_equal:' . now()->subYears(14)->toDateString()],
]);
$user = User::create($data);
Log::info('signup', ['user_id' => $user->id]);

// 법령 근거가 있어 꼭 받아야 하는 경우만: 회원가입과 분리된 화면, 암호화 저장·가림 표시
class TaxProfile extends Model
{
    protected $fillable = ['user_id', 'rrn', 'rrn_idx'];
    protected $hidden = ['rrn', 'rrn_idx'];               // JSON 응답에서 제외

    protected function casts(): array
    {
        return ['rrn' => 'encrypted'];                     // 하이픈 넣은 형식으로 저장
    }

    public function maskedRrn(): string
    {
        return substr($this->rrn, 0, 8) . '******';        // 900101-1******
    }
}
```
