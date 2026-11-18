<!-- generated from white-security issues; do not edit -->
# WS-SHELL-001 Web Shell (웹셸)

## 탐지 규칙

- **찾는 곳**: `move_uploaded_file(`, `getClientOriginalName(`, `public_path(`, `location ~ \.php$`, `.htaccess`, 그 밖에 file_put_contents·storeAs·Apache AddHandler, 업로드 폴더(uploads·public·public_html)에 쓰는 코드.
- **검사 1 — 웹 루트 안 저장**: 지적할 때: 업로드·쓰기 경로가 웹 루트(`public/`, `public_html/`, `www/`, `static/`) 안이고 이름이나 확장자를 요청 값에서 가져오는 경우. / 지적하지 않을 때: 웹 루트 밖·객체 저장소에 서버가 만든 이름으로 저장하고, 컨트롤러가 권한 확인 뒤 내려주는 경우.
- **검사 2 — 실행 차단 없음**: 지적할 때: 웹에서 열리는 업로드 폴더에 Nginx·Apache의 스크립트 실행 차단이 없는 경우. 업로드로 `.htaccess`를 덮어쓸 수 있는 경우. / 지적하지 않을 때: 업로드 경로에 실행 차단(`return 403`, 정적 제공만)이 있거나, PHP 처리가 진입 파일(`index.php`) 하나에만 연결된 경우.
- **검사 3 — 요청이 정한 파일 쓰기**: 지적할 때: 요청 값이 경로·확장자·내용을 함께 정해 웹에서 열리는 곳에 쓰는 경우(테마·템플릿 편집, 설치 마법사, 로그 경로 설정). / 지적하지 않을 때: 경로·확장자가 코드에 고정되고 내용은 데이터로만 저장되는 경우.
- **등급**: 위험: 요청이 정한 이름·확장자로 웹 루트 안에 저장하는 경우. / 주의: 서버가 이름을 정하지만 업로드 폴더의 실행 차단이 보이지 않는 경우. / 참고: 웹 루트 쓰기 권한·파일 변경 감시처럼 코드 밖이라 운영 확인이 필요한 경우.
- **보고 예**: `[위험] WS-SHELL-001 upload.php:9 — 원래 파일 이름 그대로 public_html/uploads 에 저장해 .php 도 실행됨 → 웹 루트 밖에 무작위 이름으로 저장, uploads 실행 차단`
- **고칠 방법**: 저장 위치를 옮길 때 기존 업로드 파일도 함께 옮기고, 옛 업로드 폴더에 이미 스크립트 파일이 있는지 확인해야 한다.

## 점검 항목

- 업로드 파일은 웹 루트 밖이나 객체 저장소에 저장해야 한다.
- 저장 이름과 확장자는 서버가 무작위 이름과 허용 목록으로 정해야 한다.
- 업로드·캐시 폴더는 웹 서버 설정으로 스크립트 실행을 막아야 한다.
- 쓰지 않는 PUT·DELETE(WebDAV) 메서드는 웹 서버에서 꺼야 한다.
- 웹 루트는 앱 실행 계정이 쓸 수 없게 하고, 배포 밖의 파일 변경은 알림을 받아야 한다.
- 의심 파일을 찾으면 지우기 전에 사본·로그를 보존하고 키·비밀번호를 바꿔야 한다.

Code examples (read only the file for the stack you are writing): `WS-SHELL-001.code.php.md` (PHP) · `WS-SHELL-001.code.laravel.md` (Laravel) · `WS-SHELL-001.code.nginx.md` (Nginx)

기본 심각도: 위험
기준: OWASP Top 10:2025 A06(안전하지 않은 설계) · OWASP Top 10:2025 A09(로깅·경보 실패) · ASVS 5.0 V5.3.1 · ASVS 5.0 V5.3.2 · ASVS 5.0 V4.1.4 · ASVS 5.0 V13.4.7 · CWE-434(위험한 형식 파일 업로드) · CWE-553 · CWE-732 · 국내 보안약점 49 입력데이터 검증 및 표현-6(위험한 형식 파일 업로드) · 국내 보안약점 49 보안기능-3(중요한 자원에 대한 잘못된 권한 설정) · 기반시설 웹 21 FU(악성 파일 업로드) · 기반시설 웹 21 WM(불필요한 Method 악용)
