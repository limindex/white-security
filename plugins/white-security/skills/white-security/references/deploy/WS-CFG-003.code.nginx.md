<!-- generated from white-security issues; do not edit -->
# WS-CFG-003 코드 예시 — Nginx

✗ 취약 코드 (vulnerable example — do not use)

```nginx
server {
    listen 443 ssl;
    server_name example.com;
    add_header X-Content-Type-Options "nosniff";      # always 없음 → 404·500 응답에는 빠짐

    location /static/ {
        add_header Cache-Control "public, max-age=31536000";
        # 이 블록에 add_header 가 있으므로 위 server 의 add_header 는 상속되지 않음
    }
}
```

✓ 안전 코드 (safe example)

```nginx
# /etc/nginx/snippets/security-headers.conf
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Content-Security-Policy "default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'none'" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# /etc/nginx/conf.d/example.conf
server {
    listen 443 ssl;
    server_name example.com;
    include snippets/security-headers.conf;

    location /static/ {
        include snippets/security-headers.conf;      # add_header 가 있는 블록마다 다시 포함
        add_header Cache-Control "public, max-age=31536000" always;
    }
}
server {
    listen 80;
    server_name example.com;
    return 301 https://$host$request_uri;            # HTTP 는 HTTPS 로만
}
```
