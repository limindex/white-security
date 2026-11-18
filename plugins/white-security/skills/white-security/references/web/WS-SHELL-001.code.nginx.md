<!-- generated from white-security issues; do not edit -->
# WS-SHELL-001 코드 예시 — Nginx

✗ 취약 코드 (vulnerable example — do not use)

```nginx
server {
    root /var/www/html;
    location ~ \.php$ {                        # 어느 폴더든 .php 면 실행
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
}
```

✓ 안전 코드 (safe example)

```nginx
server {
    root /var/www/html/public;
    location ^~ /uploads/ {                    # 업로드 폴더는 정적 파일로만
        add_header X-Content-Type-Options nosniff;
        location ~* \.(php\d?|phar|phtml|jsp|cgi)$ { return 403; }
    }
    location = /index.php {                    # PHP 는 진입 파일 하나만 실행
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
    location ~ \.php$ { return 404; }
    location / { try_files $uri /index.php?$query_string; }
}
```
