<!-- generated from white-security issues; do not edit -->
# WS-CFG-006 Default Credentials (기본 계정·기본 비밀번호)

심각도: 위험
기준: OWASP Top 10:2025 A07(인증 실패) · OWASP Top 10:2025 A02(보안 설정 오류) · OWASP API 2023 API8(보안 설정 오류) · ASVS 5.0 V6.3.2 · ASVS 5.0 V13.2.3 · CWE-1392 · CWE-1393 · CWE-798 · CWE-521 · 국내 보안약점 49 보안기능-6(하드코드된 중요정보) · 국내 보안약점 49 보안기능-9(취약한 비밀번호 허용) · 기반시설 웹 21 BF(약한 비밀번호 정책)

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
- **보고 예**: `[위험] WS-CFG-006 database/seeders/DatabaseSeeder.php:18 — 배포 때 db:seed --force 로 admin@example.com 관리자가 고정 비밀번호로 생김 → 시드는 local 에서만, 운영 관리자는 배포 뒤 대화형 명령으로 생성`
- **검토 주의**: 비밀값을 코드에 적는 일반 문제는 WS-SEC-001에서 다루므로 여기서는 기본 계정·기본 비밀번호·시드 계정만 봐야 한다. 이미 운영에 기본 비밀번호가 쓰였다면 코드 수정과 함께 그 계정의 비밀번호를 지금 바꾸라고 알려야 한다.
- **고칠 방법**: 고정 비밀번호를 지우고 실행 환경의 비밀값으로 바꿔야 하며, 운영에 이미 만들어진 기본·테스트 계정은 사용자 확인 뒤 지우거나 비밀번호를 바꿔야 한다.

Code examples (read only the file for the stack you are writing): `WS-CFG-006.code.yaml.md` (YAML) · `WS-CFG-006.code.laravel.md` (Laravel) · `WS-CFG-006.code.django.md` (Django)
