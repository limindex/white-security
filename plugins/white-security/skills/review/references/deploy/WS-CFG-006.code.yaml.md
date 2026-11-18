<!-- generated from white-security issues; do not edit -->
# WS-CFG-006 코드 예시 — YAML

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
