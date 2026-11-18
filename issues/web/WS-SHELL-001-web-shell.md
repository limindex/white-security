---
id: WS-SHELL-001
title: Web Shell (웹셸)
slug: web-shell
group: web
severity: high
site_only: false
depth: brief
languages: [php, laravel, nginx]
maps:
  owasp2025: [A06, A09]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V5.3.1, V5.3.2, V4.1.4, V13.4.7]
  cwe: [CWE-434, CWE-553, CWE-732]
  kr_guide: ["입력데이터 검증 및 표현-6", "보안기능-3"]
  kr_ciip: [FU, WM]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [PHP, Laravel, JSP, Nginx·Apache, 파일을 올리거나 쓰는 모든 웹 서버]
severity_reason: "영향: 서버 명령 실행·DB 접속 정보 탈취·내부망 이동 · 악용: 스크립트 파일 하나를 올리고 주소로 여는 것만으로 가능"
sources:
  - title: "NSA·ASD, Detect and Prevent Web Shell Malware (CSI, nsacyber/Mitigating-Web-Shells)"
    url: https://github.com/nsacyber/Mitigating-Web-Shells
    year: 2020
  - title: "MITRE ATT&CK T1505.003 Server Software Component: Web Shell"
    url: https://attack.mitre.org/techniques/T1505/003/
    year: 2025
  - title: OWASP File Upload Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
    year: 2026
  - title: KISA 2026 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드 (FU 악성 파일 업로드·WM 불필요한 Method 악용)
    url: https://www.kisa.or.kr/2060204/form?postSeq=22
    year: 2025
---

## 요약

업로드나 파일 쓰기 기능으로 웹 루트 안에 `.php`·`.jsp` 같은 스크립트가 들어가고 웹 서버가 그 파일을 실행해, 브라우저로 서버 명령을 내리는 뒷문이 생기는 문제. Web Shell(웹셸)이라 부름(진단가이드 이름: 위험한 형식 파일 업로드).

## 대응 방법

```text
업로드·파일 저장 코드가 파일을 어디에 어떤 이름으로 쓰는지 모두 찾아 줘(첨부, 프로필 사진, 에디터 이미지, 테마·설정 저장).
웹 루트 밖이나 객체 저장소에 서버가 정한 이름으로 저장하고, 내려받기는 권한 확인 뒤 코드로만 내주게 바꿔 줘.
업로드 폴더에서 스크립트가 실행되지 않도록 웹 서버 설정도 고쳐 줘.
```

- 업로드 폴더에 `test.php`(내용 `<?php echo 'banana';`)를 두고 주소로 열면 banana 대신 403이나 파일 원문이 나와야 한다.
- 배포 뒤 웹 루트의 스크립트 파일 목록이 저장소와 같아야 한다.

## 점검 항목

- 업로드 파일은 웹 루트 밖이나 객체 저장소에 저장해야 한다.
- 저장 이름과 확장자는 서버가 무작위 이름과 허용 목록으로 정해야 한다.
- 업로드·캐시 폴더는 웹 서버 설정으로 스크립트 실행을 막아야 한다.
- 쓰지 않는 PUT·DELETE(WebDAV) 메서드는 웹 서버에서 꺼야 한다.
- 웹 루트는 앱 실행 계정이 쓸 수 없게 하고, 배포 밖의 파일 변경은 알림을 받아야 한다.
- 의심 파일을 찾으면 지우기 전에 사본·로그를 보존하고 키·비밀번호를 바꿔야 한다.

## 탐지 규칙

- **찾는 곳**: `move_uploaded_file(`, `getClientOriginalName(`, `public_path(`, `file_put_contents(`, `storeAs(`, `location ~ \.php$`, `.htaccess`, `AddHandler`, 업로드 폴더(`uploads/`, `public/`, `public_html/`)에 쓰는 코드.
- **검사 1 — 웹 루트 안 저장**: 지적할 때: 업로드·쓰기 경로가 웹 루트(`public/`, `public_html/`, `www/`, `static/`) 안이고 이름이나 확장자를 요청 값에서 가져오는 경우. / 지적하지 않을 때: 웹 루트 밖·객체 저장소에 서버가 만든 이름으로 저장하고, 컨트롤러가 권한 확인 뒤 내려주는 경우.
- **검사 2 — 실행 차단 없음**: 지적할 때: 웹에서 열리는 업로드 폴더에 Nginx·Apache의 스크립트 실행 차단이 없는 경우. 업로드로 `.htaccess`를 덮어쓸 수 있는 경우. / 지적하지 않을 때: 업로드 경로에 실행 차단(`return 403`, 정적 제공만)이 있거나, PHP 처리가 진입 파일(`index.php`) 하나에만 연결된 경우.
- **검사 3 — 요청이 정한 파일 쓰기**: 지적할 때: 요청 값이 경로·확장자·내용을 함께 정해 웹에서 열리는 곳에 쓰는 경우(테마·템플릿 편집, 설치 마법사, 로그 경로 설정). / 지적하지 않을 때: 경로·확장자가 코드에 고정되고 내용은 데이터로만 저장되는 경우.
- **등급**: 위험: 요청이 정한 이름·확장자로 웹 루트 안에 저장하는 경우. / 주의: 서버가 이름을 정하지만 업로드 폴더의 실행 차단이 보이지 않는 경우. / 참고: 웹 루트 쓰기 권한·파일 변경 감시처럼 코드 밖이라 운영 확인이 필요한 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SHELL-001 upload.php:9 — 원래 파일 이름 그대로 public_html/uploads 에 저장해 .php 도 실행됨 → 웹 루트 밖에 무작위 이름으로 저장, uploads 실행 차단`
- **고칠 방법**: 저장 위치를 옮길 때 기존 업로드 파일도 함께 옮기고, 옛 업로드 폴더에 이미 스크립트 파일이 있는지 확인해야 한다.

## 코드 예시

### php

✗ 취약 코드

```php
<?php
// public_html/upload.php
$name = $_FILES['file']['name'];                   // 사용자가 정한 이름(shell.php 도 가능)
move_uploaded_file($_FILES['file']['tmp_name'], __DIR__ . '/uploads/' . $name);
echo '/uploads/' . $name;                           // 주소로 열면 서버가 실행한다
```

✓ 안전 코드

```php
<?php
const UPLOAD_DIR = '/srv/app/storage/uploads';      // 웹 루트 밖
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];

$f = $_FILES['file'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 5_000_000) { http_response_code(400); exit; }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']); // 내용으로 형식 판단
if (!isset($allowed[$mime])) { http_response_code(415); exit; }

$id = bin2hex(random_bytes(16));                    // 이름·확장자는 서버가 정한다
if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . "/$id." . $allowed[$mime])) { http_response_code(500); exit; }
// DB 에 id·원래 이름·올린 사람을 남기고, 내려받기는 권한 확인 뒤 download.php?id= 로
```

### laravel

✗ 취약 코드

```php
// 관리자 '테마 파일 편집' 기능
public function save(Request $request)
{
    $path = public_path('themes/' . $request->input('file'));  // 이름·확장자를 요청이 정함
    file_put_contents($path, $request->input('content'));     // .php 로 쓰면 그대로 실행된다
    return back();
}
```

✓ 안전 코드

```php
public function save(Request $request)
{
    $data = $request->validate([
        'theme'   => ['required', 'string', 'regex:/^[a-z0-9-]{1,40}$/'],
        'content' => ['required', 'string', 'max:20000'],
    ]);
    // 웹에서 바로 열리지 않는 storage 에, 확장자를 .css 로 고정해 저장
    Storage::disk('local')->put("themes/{$data['theme']}.css", $data['content']);
    return back();
}
// 화면에는 이 파일을 Content-Type: text/css 로 내려주는 라우트로만 제공
```

### nginx

✗ 취약 코드

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

✓ 안전 코드

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
