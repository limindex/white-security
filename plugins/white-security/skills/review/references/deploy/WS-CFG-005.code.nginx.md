<!-- generated from white-security issues; do not edit -->
# WS-CFG-005 코드 예시 — Nginx

✗ 취약 코드 (vulnerable example — do not use)

```nginx
server {
    listen 80;
    server_name example.com;
    root /srv/example.com;             # 프로젝트 최상위: .env·.git·storage 가 함께 열림
    autoindex on;                      # 폴더 주소에서 파일 목록 표시
    index index.php;

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
    }
}
```

✓ 안전 코드 (safe example)

```nginx
# Laravel 공식 배포 문서의 nginx 설정을 바탕으로 백업 확장자 차단을 더함(TLS 설정은 생략)
server {
    listen 80;
    server_name example.com;
    root /srv/example.com/public;      # 공개 폴더만, autoindex 는 기본값 off 그대로
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    location ~ /\.(?!well-known).* { deny all; }                                # .git·.env·.htaccess
    location ~* (\.(sql|bak|old|orig|swp|zip|tar|gz|7z|log|map)|~)$ { deny all; }  # 백업·덤프·소스맵·임시 파일
}
```
