---
id: WS-CFG-006
title: Default Credentials (기본 계정·기본 비밀번호)
slug: default-credentials
group: deploy
severity: high
grade_hint: "외부에 닿는 서비스의 기본·고정 비밀번호"
always_on: "`MYSQL_ROOT_PASSWORD` `POSTGRES_HOST_AUTH_METHOD` → DB·관리 계정은 길고 무작위한 비밀번호를 쓰고 `trust` 인증을 쓰지 않아야 한다."
site_only: false
depth: brief
languages: [yaml, laravel, django]
maps:
  owasp2025: [A07, A02]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  asvs5: [V6.3.2, V13.2.3]
  cwe: [CWE-1392, CWE-1393, CWE-798, CWE-521]
  kr_guide: ["보안기능-6", "보안기능-9"]
  kr_ciip: [BF]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Docker Compose로 띄운 DB·캐시·관리 콘솔, Laravel 시더, Django 마이그레이션·관리자 생성 스크립트, 설치형 관리 도구]
severity_reason: "영향: 관리자·DB 계정 탈취로 전체 데이터 조회·변조 · 악용: 공개된 기본 조합을 차례로 넣어 보는 자동 도구로 가능"
sources:
  - title: "OWASP ASVS 5.0 - V6.3.2 Default user accounts·V13.2.3 Default credentials"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "CISA Secure by Design Alert - How Manufacturers Can Protect Customers by Eliminating Default Passwords"
    url: https://www.cisa.gov/resources-tools/resources/secure-design-alert-how-manufacturers-can-protect-customers-eliminating-default-passwords
    year: 2023
  - title: "CWE-1392: Use of Default Credentials"
    url: https://cwe.mitre.org/data/definitions/1392.html
    year: 2025
  - title: "Laravel 12.x - Database: Seeding (laravel/laravel UserFactory 기본 비밀번호 'password')"
    url: https://laravel.com/docs/12.x/seeding
    year: 2026
  - title: "Django 6.0 - django-admin createsuperuser (--noinput 은 비밀번호 검사기를 거치지 않음)"
    url: https://docs.djangoproject.com/en/6.0/ref/django-admin/
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

DB·관리 콘솔·시드(seed, 초기 데이터) 관리자 계정을 `root/root`·`admin/admin`·`password`·`postgres`처럼 이미지 문서·예제에 나온 기본값 그대로 운영에 올려, 누구나 아는 비밀번호로 로그인되는 문제(진단가이드 이름: 하드코드된 중요정보·취약한 비밀번호 허용). 공격 도구는 이런 기본 조합부터 넣어 봄.

## 대응 방법

```text
docker-compose·시더·마이그레이션·설치 스크립트에서 기본 계정과 고정 비밀번호(admin·root·password·postgres·guest 등)를 모두 찾아 줘.
고정 비밀번호는 비밀값 파일이나 환경 변수에서 읽게 바꾸고, 값이 없으면 시작을 멈추게 해 줘.
시드의 테스트 계정은 로컬·테스트 환경에서만 만들어지게 하고, 운영 관리자는 배포 뒤 직접 만드는 명령으로 바꿔 줘.
```

- 운영 DB에 `test@example.com`·`admin` 같은 시드 계정이 없어야 한다.
- 운영 서비스와 관리 콘솔에 문서의 기본 비밀번호로 로그인되지 않아야 한다.

## 점검 항목

- 운영 계정 비밀번호는 서비스마다 길고 무작위한 값이어야 한다.
- 비밀번호는 compose·코드가 아니라 비밀값 파일이나 환경 변수에서 읽어야 한다.
- 시드의 테스트 계정은 운영 환경에서 만들어지지 않아야 한다.
- DB·관리 도구의 기본 관리자 계정(root·admin·sa)은 끄거나 바꿔야 한다.
- 첫 관리자는 배포 뒤 직접 만들고, 비밀번호는 15자 이상으로 정해야 한다.

## 탐지 규칙

- **찾는 곳**: `MYSQL_ROOT_PASSWORD`, `POSTGRES_PASSWORD`, `POSTGRES_HOST_AUTH_METHOD`, `createsuperuser`, `db:seed --force`, 그 밖에 시더·데이터 마이그레이션의 User 생성, make_password·Hash::make 에 넣은 고정 문자열, 관리 콘솔 이미지의 관리자 환경 변수.
- **검사 1 — 고정 기본 비밀번호**: 지적할 때: 운영에 쓰이는 compose·배포 설정·설치 스크립트에 `MYSQL_ROOT_PASSWORD: root`, `POSTGRES_PASSWORD: postgres`, `admin/admin`, `guest/guest`처럼 이미지 문서·예제의 값이나 짧은 고정값이 있는 경우, `POSTGRES_HOST_AUTH_METHOD: trust`(비밀번호 없이 접속 허용)인 경우. / 지적하지 않을 때: `${DB_PASSWORD:?}`처럼 실행 환경 값을 요구하거나 `*_FILE`·secrets로 읽는 경우, 테스트·CI 전용 compose 파일인 경우.
- **검사 2 — 운영에 생기는 시드 계정**: 지적할 때: 시더·마이그레이션·`createsuperuser --noinput` 스크립트가 고정 이메일·비밀번호로 관리자나 테스트 계정을 만들고, 배포 때 실행되는 경우(`db:seed --force`, 배포 스크립트의 시드 실행). / 지적하지 않을 때: `app()->environment('local', 'testing')`처럼 환경을 확인한 뒤에만 만드는 경우.
- **등급**: 위험: 인터넷에 닿는 서비스·관리 콘솔의 기본 비밀번호, 운영에 생기는 고정 비밀번호 관리자 계정, `trust` 인증. / 주의: 내부망 DB의 기본 비밀번호, 환경 구분 없는 시드. / 참고: 첫 관리자 비밀번호 규칙 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CFG-006 database/seeders/DatabaseSeeder.php:18 — 배포 때 db:seed --force 로 admin@example.com 관리자가 고정 비밀번호로 생김 → 시드는 local 에서만, 운영 관리자는 배포 뒤 대화형 명령으로 생성`
- **검토 주의**: 비밀값을 코드에 적는 일반 문제는 WS-SEC-001에서 다루므로 여기서는 기본 계정·기본 비밀번호·시드 계정만 봐야 한다. 이미 운영에 기본 비밀번호가 쓰였다면 코드 수정과 함께 그 계정의 비밀번호를 지금 바꾸라고 알려야 한다.
- **고칠 방법**: 고정 비밀번호를 지우고 실행 환경의 비밀값으로 바꿔야 하며, 운영에 이미 만들어진 기본·테스트 계정은 사용자 확인 뒤 지우거나 비밀번호를 바꿔야 한다.

## 코드 예시

### yaml

✗ 취약 코드

```yaml
# docker-compose.yml (운영)
services:
  db:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD: root          # 문서 예제의 값 그대로
      MYSQL_DATABASE: shop
      MYSQL_USER: app
      MYSQL_PASSWORD: password
```

✓ 안전 코드

```yaml
# docker-compose.yml (운영)
services:
  db:
    image: mysql:8.4
    environment:
      MYSQL_ROOT_PASSWORD_FILE: /run/secrets/db_root_password
      MYSQL_DATABASE: shop
      MYSQL_USER: app
      MYSQL_PASSWORD_FILE: /run/secrets/db_app_password
    secrets: [db_root_password, db_app_password]
secrets:
  db_root_password:
    file: ./secrets/db_root_password.txt   # openssl rand -base64 32 로 만들고 저장소에 올리지 않음
  db_app_password:
    file: ./secrets/db_app_password.txt
```

### laravel

✗ 취약 코드

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

✓ 안전 코드

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

### django

✗ 취약 코드

```python
# accounts/migrations/0002_create_admin.py — 모든 환경에서 고정 비밀번호 관리자 생성
from django.contrib.auth.hashers import make_password
from django.db import migrations

def create_admin(apps, schema_editor):
    User = apps.get_model("auth", "User")
    User.objects.create(username="admin", is_staff=True, is_superuser=True,
                        password=make_password("admin"))

class Migration(migrations.Migration):
    dependencies = [("accounts", "0001_initial")]
    operations = [migrations.RunPython(create_admin)]
```

✓ 안전 코드

```python
# 마이그레이션에서는 관리자를 만들지 않는다.
# 배포 뒤 서버에서 한 번, 대화형으로: python manage.py createsuperuser
# (--noinput + DJANGO_SUPERUSER_PASSWORD 방식은 아래 검사기를 거치지 않으므로 쓰지 않는다)

# settings.py — 짧거나 흔한 비밀번호를 막는 검사기(createsuperuser 대화형 입력에도 적용)
AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator",
     "OPTIONS": {"min_length": 15}},
    {"NAME": "django.contrib.auth.password_validation.CommonPasswordValidator"},
    {"NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator"},
]
```
