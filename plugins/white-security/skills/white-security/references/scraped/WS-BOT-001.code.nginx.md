<!-- generated from white-security issues; do not edit -->
# WS-BOT-001 코드 예시 — Nginx

✗ 취약 코드 (vulnerable example — do not use)

```nginx
location /api/ {
    proxy_pass http://app;  # 횟수 제한 없이 앱으로 넘긴다
}
```

✓ 안전 코드 (safe example)

```nginx
# http 블록: IP 마다 초당 2회, 잠깐 몰리는 요청은 20개까지 허용
limit_req_zone $binary_remote_addr zone=api:10m rate=2r/s;

location /api/ {
    limit_req zone=api burst=20 nodelay;
    limit_req_status 429;
    proxy_pass http://app;
}
```
