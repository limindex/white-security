<!-- generated from white-security issues; do not edit -->
# WS-PRV-006 코드 예시 — Shell

✗ 취약 코드 (vulnerable example — do not use)

```sh
# 운영 DB 를 평문으로 웹 루트에 쌓고, 개발 DB 에도 그대로 부음
pg_dump "$PROD_DATABASE_URL" > "/var/www/html/backup-$(date +%F).sql"
pg_dump "$PROD_DATABASE_URL" | psql "$DEV_DATABASE_URL"
```

✓ 안전 코드 (safe example)

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
