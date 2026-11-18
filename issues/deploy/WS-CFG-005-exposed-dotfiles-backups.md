---
id: WS-CFG-005
title: Exposed .git · .env · Backup Files (.git·.env·백업 파일 웹 노출)
slug: exposed-dotfiles-backups
group: deploy
severity: high
grade_hint: ".git·.env·DB 덤프가 웹에서 열림"
always_on: "`autoindex on` `Options Indexes` → 디렉터리 목록은 끄고, 문서 루트는 `public/` 같은 공개 폴더만 가리켜야 한다."
site_only: false
depth: brief
languages: [nginx, nextjs]
maps:
  owasp2025: [A02]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  asvs5: [V13.4.1, V13.4.3, V13.4.7]
  cwe: [CWE-538, CWE-527, CWE-530, CWE-548, CWE-540]
  kr_guide: []
  kr_ciip: [DI, IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Nginx·Apache로 직접 운영하는 PHP·Laravel·Python 서버, Next.js·Vite 같은 프런트엔드 빌드 결과, 정적 파일 업로드 폴더]
severity_reason: "영향: 전체 소스·과거 커밋 속 비밀값·DB 덤프 유출 · 악용: 잘 알려진 주소(/.git/HEAD, /.env)를 자동으로 두드리는 스캐너로 가능"
sources:
  - title: "OWASP ASVS 5.0 - V13.4 Unintended Information Leakage (13.4.1·13.4.3·13.4.7)"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "Laravel 12.x - Deployment (Nginx 설정: public 폴더만 문서 루트, 점 파일 차단)"
    url: https://laravel.com/docs/12.x/deployment
    year: 2026
  - title: "nginx - ngx_http_autoindex_module (autoindex 기본값 off)"
    url: https://nginx.org/en/docs/http/ngx_http_autoindex_module.html
    year: 2026
  - title: "Next.js - productionBrowserSourceMaps"
    url: https://nextjs.org/docs/app/api-reference/config/next-config-js/productionBrowserSourceMaps
    year: 2026
  - title: KISA 2026 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드 (디렉터리 인덱싱·정보 누출)
    url: https://www.kisa.or.kr/2060204/form?postSeq=22
    year: 2025
---

## 요약

웹 서버의 문서 루트(document root, 웹에 공개되는 폴더)를 프로젝트 폴더 전체로 잡거나 점(.) 파일을 막지 않아 `/.git/`·`/.env`·`backup.sql`·`*.bak`·소스맵이 주소 하나로 내려받아지거나, 디렉터리 인덱싱(폴더 주소에서 파일 목록 자동 표시)이 켜져 파일 이름이 다 보이는 문제. `.git` 폴더가 열리면 전체 소스와 과거 커밋 속 비밀값까지 복원됨.

## 대응 방법

```text
웹 서버 설정(nginx·Apache·.htaccess)과 배포 방식을 보고 문서 루트가 public 같은 공개 폴더만 가리키는지 알려 줘.
.well-known 을 뺀 점(.) 파일·폴더와 *.sql·*.bak·*.zip·*.map 을 웹에서 막고, 디렉터리 목록 표시는 꺼 줘.
공개 폴더와 배포물에 남은 백업·덤프 파일은 목록만 보여 주고, 지우기 전에 나에게 물어봐 줘.
```

- 내 사이트의 `/.git/HEAD`·`/.env`·`/backup.sql` 주소를 열면 404나 403이 나와야 한다.
- `/uploads/`처럼 파일 이름 없는 폴더 주소를 열면 파일 목록이 나오지 않아야 한다.

## 점검 항목

- 문서 루트는 `public/`처럼 공개용 폴더만 가리켜야 한다.
- 점 파일·폴더는 `/.well-known/`만 빼고 웹에서 막아야 한다.
- `*.sql`·`*.bak`·`*.zip`·`*~` 같은 백업·임시 파일은 공개 폴더에 두지 않아야 한다.
- 디렉터리 인덱싱(nginx `autoindex`, Apache `Indexes`)은 꺼야 한다.
- 운영 빌드의 소스맵은 공개하지 않고, 필요하면 오류 추적 도구에만 올려야 한다.
- 배포물(이미지·압축 파일)에 `.git`을 넣지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `autoindex on`, `Options Indexes`, `productionBrowserSourceMaps`, `sourcemap: true`, `.htaccess`, 그 밖에 nginx root·Apache DocumentRoot 줄, 점 파일 차단 location, 배포 스크립트의 rsync·scp·cp 대상, public 폴더 속 *.sql·*.zip·*.bak.
- **검사 1 — 문서 루트·점 파일**: 지적할 때: nginx `root`·Apache `DocumentRoot`가 `.env`·`.git`이 있는 프로젝트 최상위를 가리키는 경우, 점 파일을 막는 규칙(`location ~ /\.(?!well-known)`, Apache `Require all denied`)이 없는 경우, 배포 스크립트가 `.git`째 웹 폴더로 복사하는 경우. / 지적하지 않을 때: 문서 루트가 `public/`·`dist/`이고 점 파일 차단 규칙이 있는 경우, 정적 호스팅이 빌드 결과 폴더만 올리는 경우.
- **검사 2 — 목록·백업·소스맵**: 지적할 때: `autoindex on`, `Options Indexes`, 공개 폴더 속 `*.sql`·`*.bak`·`*.zip`·`*.tar.gz`, 운영 소스맵 공개(`productionBrowserSourceMaps: true`, Vite `build.sourcemap: true`)인 경우. / 지적하지 않을 때: Vite `sourcemap: "hidden"`처럼 브라우저에 연결하지 않고 오류 추적 도구에만 올린 뒤 배포물에서 지우는 경우.
- **등급**: 위험: `.git`·`.env`·DB 덤프가 웹에서 열리는 구조. / 주의: 디렉터리 인덱싱, 공개 폴더 속 백업 파일. / 참고: 운영 소스맵 공개.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CFG-005 deploy/nginx.conf:4 — root 가 프로젝트 최상위라 /.env·/.git/config 가 그대로 내려받아짐 → root 를 public 폴더로 바꾸고 점 파일 차단 location 추가`
- **검토 주의**: 실제로 열리는지 확인하려고 운영 사이트에 요청을 보내지 않고 설정만 읽어야 하며, 사용자가 직접 확인할 주소만 알려 줘야 한다. 이미 열려 있었다면 `.env`·커밋 기록 속 비밀값은 WS-SEC-001 기준으로 교체해야 한다고 알려야 한다.
- **고칠 방법**: 문서 루트를 공개 폴더로 좁히고 점 파일·백업 확장자 차단 규칙을 넣어야 하며, 공개 폴더 속 백업 파일은 사용자 확인 후 지워야 한다.

## 코드 예시

### nginx

✗ 취약 코드

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

✓ 안전 코드

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

### nextjs

✗ 취약 코드

```js
// next.config.js — 운영 빌드에서 소스맵 공개(원본 코드·주석이 그대로 보임)
module.exports = {
  productionBrowserSourceMaps: true,
};
// public/ 에 둔 db-backup.sql·.env.local.bak 도 https://도메인/db-backup.sql 로 내려받아진다
```

✓ 안전 코드

```js
// next.config.js — 기본값(false) 그대로 둔다
module.exports = {
  productionBrowserSourceMaps: false,
};
// public/ 에는 공개해도 되는 정적 파일만 둔다
// 백업·덤프는 저장소와 배포물 밖에 보관하고 .gitignore 에 *.sql·*.bak·*.zip 을 넣는다
// 오류 추적용 소스맵이 필요하면 빌드 뒤 추적 도구에 올리고 배포물에서는 지운다
```
