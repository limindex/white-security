<!-- generated from white-security issues; do not edit -->
# WS-CFG-008 코드 예시 — Nginx

✗ 취약 코드 (vulnerable example — do not use)

```nginx
server {
    listen 443 ssl;
    server_name api.example.com;
    client_max_body_size 0;            # 0 = 본문 크기 검사 끔

    location /api/ {
        proxy_pass http://app:8000;    # 횟수 제한·시간 제한 없음
    }
}
```

✓ 안전 코드 (safe example)

```nginx
# conf.d 파일은 http 블록 안에 들어간다
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=heavy:10m rate=5r/m;

server {
    listen 443 ssl;
    server_name api.example.com;
    client_max_body_size 2m;           # 업로드 경로만 따로 크게
    client_body_timeout 10s;
    limit_req_status 429;              # 기본값은 503

    location /api/ {
        limit_req zone=api burst=20 nodelay;
        proxy_read_timeout 30s;
        proxy_pass http://app:8000;
    }
    location = /api/reports {
        limit_req zone=heavy burst=2 nodelay;
        proxy_pass http://app:8000;
    }
}
# CDN·로드밸런서 뒤라면 real_ip 모듈로 실제 접속 IP 를 복원한 뒤 센다
```
