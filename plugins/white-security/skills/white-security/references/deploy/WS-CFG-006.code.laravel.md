<!-- generated from white-security issues; do not edit -->
# WS-CFG-006 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// database/seeders/DatabaseSeeder.php — 배포 스크립트가 php artisan db:seed --force 로 운영에서도 실행
public function run(): void
{
    User::forceCreate([
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => Hash::make('admin1234'),
        'is_admin' => true,
    ]);
}
```

✓ 안전 코드 (safe example)

```php
// database/seeders/DatabaseSeeder.php — 테스트 계정은 로컬·테스트에서만
public function run(): void
{
    if (app()->environment('local', 'testing')) {
        User::factory()->create(['email' => 'test@example.com']);   // 팩토리 기본 비밀번호 'password'
    }
}

// app/Console/Commands/CreateAdmin.php — 운영 첫 관리자는 배포 뒤 서버에서 직접(비밀번호는 화면에 안 보임)
public function handle(): int
{
    $email = $this->ask('관리자 이메일');
    $password = $this->secret('비밀번호(15자 이상)');
    Validator::make(['password' => $password], ['password' => [Password::min(15)->uncompromised()]])->validate();
    User::forceCreate(['name' => 'Admin', 'email' => $email, 'password' => Hash::make($password), 'is_admin' => true]);
    return self::SUCCESS;
}
```
