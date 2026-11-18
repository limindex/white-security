<!-- generated from white-security issues; do not edit -->
# WS-UPL-001 Unrestricted File Upload (위험한 형식 파일 업로드)

심각도: 위험
기준: OWASP Top 10:2025 A06(안전하지 않은 설계) · ASVS 5.0 V5.2.1 · ASVS 5.0 V5.2.2 · ASVS 5.0 V5.2.4 · ASVS 5.0 V5.3.2 · CWE-434(위험한 형식 파일 업로드) · CWE-770(한도 없는 자원 할당) · 국내 보안약점 49 입력데이터 검증 및 표현-6(위험한 형식 파일 업로드) · 기반시설 웹 21 FU(악성 파일 업로드)

## 점검 항목

- 확장자는 차단 목록이 아니라 허용 목록으로 검사해야 한다.
- 확장자와 실제 내용(매직 바이트) 형식이 모두 허용 목록에 맞아야 한다.
- 파일 크기·개수·사용자별 총량 제한을 서버에서 걸어야 한다.
- 저장 이름은 서버가 만들고, 원래 이름은 DB에만 둬야 한다.
- HTML·SVG는 받지 않거나 다른 도메인에서 내려받기로만 제공해야 한다.
- 이미지는 다시 인코딩해 숨은 데이터와 메타데이터를 지워야 한다.

## 탐지 규칙

- **찾는 곳**: `multer(`, `UploadFile`, `->store(`, `request.FILES`, 그 밖에 getClientOriginalName·originalname·file.filename·move_uploaded_file.
- **검사 1 — 형식 검사 없음·차단 목록**: 지적할 때: 확장자·형식 검사가 없거나 `.php`만 막는 차단 목록인 경우. 클라이언트가 보낸 `Content-Type`(`mimetype`, `content_type`)만 믿는 경우. / 지적하지 않을 때: 허용 확장자 목록과 내용 검사(`finfo`, `file-type`, Pillow, Laravel `mimes:`)를 함께 하는 경우.
- **검사 2 — 원래 이름으로 저장·크기 제한 없음**: 지적할 때: `originalname`·`getClientOriginalName()`·`file.filename`으로 저장 경로를 만드는 경우. 크기 제한(`limits.fileSize`, `max:`, 본문 크기 상한)이 없는 경우. / 지적하지 않을 때: 무작위 이름(`hashName()`, UUID)으로 저장하고 크기 상한이 있는 경우.
- **등급**: 위험: 형식 검사 없이 웹에서 열리는 곳에 저장하는 경우. / 주의: 차단 목록·`Content-Type`만 검사, 원래 이름으로 저장, 크기 제한 없음. / 참고: HTML·SVG 허용, 이미지 재인코딩 없음.
- **보고 예**: `[주의] WS-UPL-001 routes/upload.js:5 — multer 가 originalname 으로 저장하고 크기 제한 없음 → 무작위 이름·limits.fileSize·내용 형식 검사`
- **고칠 방법**: 검사에 실패한 파일은 저장 전에 지워야 하고, 이미 올라온 파일 중 허용 목록 밖 형식이 있는지 확인해야 한다.

Code examples (read only the file for the stack you are writing): `WS-UPL-001.code.laravel.md` (Laravel) · `WS-UPL-001.code.express.md` (Express) · `WS-UPL-001.code.fastapi.md` (FastAPI)
