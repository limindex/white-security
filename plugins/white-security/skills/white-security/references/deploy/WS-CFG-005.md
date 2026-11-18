<!-- generated from white-security issues; do not edit -->
# WS-CFG-005 Exposed .git · .env · Backup Files (.git·.env·백업 파일 웹 노출)

## 탐지 규칙

- **찾는 곳**: `autoindex on`, `Options Indexes`, `productionBrowserSourceMaps`, `sourcemap: true`, `.htaccess`, 그 밖에 nginx root·Apache DocumentRoot 줄, 점 파일 차단 location, 배포 스크립트의 rsync·scp·cp 대상, public 폴더 속 *.sql·*.zip·*.bak.
- **검사 1 — 문서 루트·점 파일**: 지적할 때: nginx `root`·Apache `DocumentRoot`가 `.env`·`.git`이 있는 프로젝트 최상위를 가리키는 경우, 점 파일을 막는 규칙(`location ~ /\.(?!well-known)`, Apache `Require all denied`)이 없는 경우, 배포 스크립트가 `.git`째 웹 폴더로 복사하는 경우. / 지적하지 않을 때: 문서 루트가 `public/`·`dist/`이고 점 파일 차단 규칙이 있는 경우, 정적 호스팅이 빌드 결과 폴더만 올리는 경우.
- **검사 2 — 목록·백업·소스맵**: 지적할 때: `autoindex on`, `Options Indexes`, 공개 폴더 속 `*.sql`·`*.bak`·`*.zip`·`*.tar.gz`, 운영 소스맵 공개(`productionBrowserSourceMaps: true`, Vite `build.sourcemap: true`)인 경우. / 지적하지 않을 때: Vite `sourcemap: "hidden"`처럼 브라우저에 연결하지 않고 오류 추적 도구에만 올린 뒤 배포물에서 지우는 경우.
- **등급**: 위험: `.git`·`.env`·DB 덤프가 웹에서 열리는 구조. / 주의: 디렉터리 인덱싱, 공개 폴더 속 백업 파일. / 참고: 운영 소스맵 공개.
- **보고 예**: `[위험] WS-CFG-005 deploy/nginx.conf:4 — root 가 프로젝트 최상위라 /.env·/.git/config 가 그대로 내려받아짐 → root 를 public 폴더로 바꾸고 점 파일 차단 location 추가`
- **고칠 방법**: 문서 루트를 공개 폴더로 좁히고 점 파일·백업 확장자 차단 규칙을 넣어야 하며, 공개 폴더 속 백업 파일은 사용자 확인 후 지워야 한다.

## 점검 항목

- 문서 루트는 `public/`처럼 공개용 폴더만 가리켜야 한다.
- 점 파일·폴더는 `/.well-known/`만 빼고 웹에서 막아야 한다.
- `*.sql`·`*.bak`·`*.zip`·`*~` 같은 백업·임시 파일은 공개 폴더에 두지 않아야 한다.
- 디렉터리 인덱싱(nginx `autoindex`, Apache `Indexes`)은 꺼야 한다.
- 운영 빌드의 소스맵은 공개하지 않고, 필요하면 오류 추적 도구에만 올려야 한다.
- 배포물(이미지·압축 파일)에 `.git`을 넣지 않아야 한다.

## 검토 주의

실제로 열리는지 확인하려고 운영 사이트에 요청을 보내지 않고 설정만 읽어야 하며, 사용자가 직접 확인할 주소만 알려 줘야 한다. 이미 열려 있었다면 `.env`·커밋 기록 속 비밀값은 WS-SEC-001 기준으로 교체해야 한다고 알려야 한다.

Code examples (read only the file for the stack you are writing): `WS-CFG-005.code.nginx.md` (Nginx) · `WS-CFG-005.code.nextjs.md` (Next.js)

기본 심각도: 위험
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP API 2023 API8(보안 설정 오류) · ASVS 5.0 V13.4.1 · ASVS 5.0 V13.4.3 · ASVS 5.0 V13.4.7 · CWE-538 · CWE-527 · CWE-530 · CWE-548 · CWE-540 · 기반시설 웹 21 DI(디렉터리 인덱싱) · 기반시설 웹 21 IL(정보 누출)
