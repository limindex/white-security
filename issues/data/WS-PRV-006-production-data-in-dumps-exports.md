---
id: WS-PRV-006
title: Production Data in Dumps·Exports·Test Data (백업·내보내기·테스트 데이터 속 실데이터)
slug: production-data-in-dumps-exports
group: data
severity: medium
site_only: false
depth: brief
languages: [laravel, django, shell]
maps:
  owasp2025: [A02, A04]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V14.1.2, V14.2.7]
  cwe: [CWE-200, CWE-312, CWE-530, CWE-1236]
  kr_guide: []
  kr_ciip: [IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Django, PostgreSQL·MySQL 백업 스크립트, 관리자 엑셀·CSV 내려받기, 개발·스테이징 DB와 테스트 픽스처]
severity_reason: "영향: 보호가 약한 개발 PC·스테이징·파일 사본으로 운영 개인정보 전체가 퍼짐 · 악용: 노트북 분실, 공개 저장소 커밋, 메신저로 보낸 엑셀 하나면 충분"
sources:
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제7조 ⑤·제8조 ②·제12조·제13조
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: 개인정보 보호법 (법률 제21445호) 제2조 제1호의2 가명처리·제21조 개인정보의 파기
    url: https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EB%B3%B4%ED%98%B8%EB%B2%95
    year: 2026
  - title: "OWASP ASVS 5.0 - V14 Data Protection"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "PHP Manual - ZipArchive::setEncryptionName"
    url: https://www.php.net/manual/en/ziparchive.setencryptionname.php
    year: 2026
  - title: "OWASP - CSV Injection"
    url: https://owasp.org/www-community/attacks/CSV_Injection
    year: 2026
---

## 요약

운영 DB 덤프를 개발 PC·스테이징·테스트 픽스처에 그대로 쓰거나, 관리자 엑셀 내려받기가 모든 컬럼을 암호 없는 파일로 만들거나, 백업을 암호화·보관 기한 없이 쌓아 두어 운영 밖으로 개인정보가 퍼지는 문제. 「개인정보의 안전성 확보조치 기준」 제7조 ⑤는 취급자 컴퓨터·보조저장매체 저장 시 암호화, 제12조 ①은 출력 항목 최소화를, 「개인정보 보호법」 제21조는 필요 없어진 개인정보의 지체 없는 파기를 정함.

## 대응 방법

```text
운영 DB 덤프·백업·엑셀/CSV 내보내기·시드·픽스처를 만드는 코드와 스크립트를 찾아 실제 개인정보가 들어가는지 표로 보여 줘.
개발·테스트 데이터는 운영 덤프 대신 가짜 데이터 생성기로 바꾸고, 저장소에 들어간 실데이터 파일은 지우는 방법을 알려 줘.
관리자 내려받기는 필요한 컬럼만 암호 건 파일로 만들고 사유·접속기록을 남기게, 백업은 암호화와 보관 기간을 정하게 고쳐 줘.
```

- 저장소와 개발 DB에서 실제 회원 전화번호·이메일을 검색하면 나오지 않아야 한다.
- 관리자 내려받기 파일은 비밀번호 없이 열리지 않아야 한다.

## 점검 항목

- 개발·테스트에는 운영 개인정보 대신 가짜 데이터를 써야 한다.
- 덤프·백업·내보내기 파일은 저장소·웹 루트·공개 버킷에 두지 않아야 한다.
- 관리자 내려받기는 필요한 항목만 담아 암호화한 파일로 만들어야 한다.
- 내려받기는 사유와 접속기록을 남기고 대량 내려받기를 점검해야 한다.
- 백업은 암호화하고, 정한 보관 기간이 지나면 지워야 한다.
- 보유기간이 지난 개인정보는 사본·내보낸 파일까지 정리 절차를 둬야 한다.

## 탐지 규칙

- **찾는 곳**: `pg_dump`, `mysqldump`, `dumpdata`, `loaddata`, `db:seed`, `Excel::download(`, `fputcsv(`, `*.sql`, 그 밖에 fixtures·seeders 폴더의 실제 같은 이름·전화번호, 관리자 내보내기 라우트, 백업 스크립트와 cron, `.gitignore`에 빠진 덤프·CSV.
- **검사 1 — 운영 밖의 실데이터**: 지적할 때: 운영 DB를 덤프해 개발·스테이징에 그대로 붓는 스크립트, 실제 전화번호·이메일·주소가 든 픽스처·시드·테스트 파일, 저장소에 커밋된 덤프·CSV·엑셀. / 지적하지 않을 때: 가짜 데이터 생성기(Faker 등), 가명처리 단계를 거친 덤프, `example.com`·`010-0000-0000` 같은 예시 값.
- **검사 2 — 내려받기·내보내기**: 지적할 때: 관리자 내보내기가 모든 컬럼·모든 회원을 평문 CSV·엑셀로 만드는 경우, 사유·접속기록이 없는 경우, `=`·`+`·`-`·`@`로 시작하는 값을 그대로 써서 엑셀에서 수식으로 실행되는 경우(CSV Injection). / 지적하지 않을 때: 필요한 컬럼만, 암호화한 파일, 접속기록(WS-PRV-003)이 있는 경우.
- **검사 3 — 백업**: 지적할 때: 백업을 암호화 없이 저장하거나 웹 루트·공개 버킷에 두는 경우, 보관 기간·삭제가 없는 경우. / 지적하지 않을 때: 암호화·접근 제한·보관 기간이 보이는 경우(인프라 설정이라 저장소에 없으면 "확인 필요").
- **등급**: 위험: 저장소·웹 루트·공개 버킷의 실데이터 덤프, 운영 개인정보를 그대로 부은 개발 DB. / 주의: 평문 전체 내보내기, 암호화 없는 백업, CSV Injection. / 참고: 보관 기간·파기 절차를 저장소에서 확인할 수 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PRV-006 tests/fixtures/users.json:1 — 운영에서 dumpdata 로 뽑은 실제 회원 1,200명이 저장소에 커밋됨 → 파일 삭제·저장소 이력 정리, 팩토리로 가짜 데이터 생성`
- **검토 주의**: 웹에서 열리는 백업 파일 자체는 WS-CFG-005, 공개 버킷은 WS-CFG-004, AI 도구에 붙여 넣는 운영 데이터는 WS-LLM-003에서 다룬다. 엑셀의 시트 보호·숨기기는 암호화가 아니므로 파일 암호화로 보지 않아야 한다. 이름만 바꾼 덤프는 「개인정보 보호법」 제2조 제1호의2의 가명처리(추가 정보 없이는 특정 개인을 알아볼 수 없도록 처리)라 보기 어렵다고 안내해야 한다.
- **고칠 방법**: 개발·테스트 데이터는 가짜 데이터 생성기로 바꾸고, 커밋된 실데이터는 지운 뒤 저장소 이력에서도 정리해야 하며, 내보내기는 항목 최소화·파일 암호화·접속기록을, 백업은 암호화·보관 기간을 함께 적용해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// 관리자 회원 내려받기: 모든 컬럼·모든 회원, 평문 CSV
public function export()
{
    return response()->streamDownload(function () {
        $out = fopen('php://output', 'w');
        foreach (User::cursor() as $u) {
            fputcsv($out, $u->toArray(), escape: '');
        }
    }, 'members.csv');
}
```

✓ 안전 코드

```php
public function export(ExportRequest $request)          // 사유(reason) 필수 입력
{
    $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : $v;  // 수식 실행 막기
    $csv = fopen('php://temp', 'w+');
    fputcsv($csv, ['회원번호', '이름', '가입일'], escape: '');    // 업무에 필요한 항목만
    foreach (User::select('id', 'name', 'created_at')->cursor() as $u) {
        fputcsv($csv, [$u->id, $safe($u->name), $u->created_at->toDateString()], escape: '');
    }
    rewind($csv);

    $password = Str::password(16);
    $path = storage_path('app/private/exports/' . Str::uuid() . '.zip');
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('members.csv', stream_get_contents($csv));
    $zip->setEncryptionName('members.csv', ZipArchive::EM_AES_256, $password);  // AES-256 암호 압축
    $zip->close();

    // 접속기록(WS-PRV-003): 운영자·IP·대상·"다운로드"·사유. 비밀번호는 파일과 다른 경로(화면 1회 표시 등)로 전달
    return response()->download($path)->deleteFileAfterSend();
}
```

### django

✗ 취약 코드

```python
# 운영 DB 에서: python manage.py dumpdata accounts.User --output tests/fixtures/users.json
class OrderTest(TestCase):
    fixtures = ["users.json"]          # 실제 회원 이름·전화번호·주소가 저장소에 커밋됨
```

✓ 안전 코드

```python
# tests/factories.py — factory_boy + Faker 로 만든 가짜 회원
import factory
from accounts.models import User

class UserFactory(factory.django.DjangoModelFactory):
    class Meta:
        model = User

    username = factory.Sequence(lambda n: f"user{n}")
    name = factory.Faker("name", locale="ko_KR")
    email = factory.Sequence(lambda n: f"user{n}@example.com")   # 예시용으로 예약된 도메인
    phone = factory.Sequence(lambda n: f"010-0000-{n:04d}")

class OrderTest(TestCase):
    def setUp(self):
        self.user = UserFactory()
```

### shell

✗ 취약 코드

```sh
# 운영 DB 를 평문으로 웹 루트에 쌓고, 개발 DB 에도 그대로 부음
pg_dump "$PROD_DATABASE_URL" > "/var/www/html/backup-$(date +%F).sql"
pg_dump "$PROD_DATABASE_URL" | psql "$DEV_DATABASE_URL"
```

✓ 안전 코드

```sh
# 백업: 웹 루트 밖, 공개키로 암호화(age), 보관 기간이 지난 파일 삭제
set -euo pipefail
umask 077
pg_dump --format=custom "$PROD_DATABASE_URL" \
  | age -r "$BACKUP_PUBLIC_KEY" > "/srv/backups/db-$(date +%F).dump.age"
find /srv/backups -name 'db-*.dump.age' -mtime +30 -delete   # 30일은 예시, 내부 기준에 맞게

# 개발 DB 는 운영 덤프 대신 시드로 채움
php artisan migrate:fresh --seed
```
