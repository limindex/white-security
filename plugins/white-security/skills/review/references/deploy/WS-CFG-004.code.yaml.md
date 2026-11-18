<!-- generated from white-security issues; do not edit -->
# WS-CFG-004 코드 예시 — YAML

✗ 취약 코드 (vulnerable example — do not use)

```yaml
# docker-compose.yml (운영)
services:
  db:
    image: postgres:17
    ports:
      - "5432:5432"      # 서버의 모든 주소에 열림, ufw 규칙도 거치지 않음
  redis:
    image: redis:8
    ports:
      - "6379:6379"      # 비밀번호 없이 인터넷에 열림
```

✓ 안전 코드 (safe example)

```yaml
# docker-compose.yml (운영)
services:
  app:
    build: .
    ports:
      - "127.0.0.1:8000:8000"   # 같은 서버의 nginx 만 접근
    depends_on: [db, redis]
  db:
    image: postgres:17
    environment:
      POSTGRES_PASSWORD_FILE: /run/secrets/db_password
    secrets: [db_password]
    # ports 없음: 같은 compose 네트워크의 app 만 db:5432 로 접속
  redis:
    image: redis:8
    # ports 없음
secrets:
  db_password:
    file: ./secrets/db_password.txt   # 저장소에 올리지 않음
```
