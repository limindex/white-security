<!-- generated from white-security issues; do not edit -->
# WS-CFG-004 Public Bucket · Exposed Database (공개된 저장소 버킷·DB·관리 콘솔)

심각도: 위험
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP Top 10:2025 A01(접근 통제 실패) · OWASP API 2023 API8(보안 설정 오류) · ASVS 5.0 V13.4.5 · ASVS 5.0 V13.2.2 · CWE-284(접근 통제 미흡) · CWE-668 · CWE-200(권한 없는 자에게 민감 정보 노출) · CWE-306(중요 기능 인증 누락) · 국내 보안약점 49 보안기능-3(중요한 자원에 대한 잘못된 권한 설정) · 기반시설 웹 21 AE(관리자 페이지 노출) · 기반시설 웹 21 IL(정보 누출)

## 점검 항목

- 사용자 업로드·개인정보 파일은 비공개 버킷에 두고 서명 URL로만 내줘야 한다.
- 공개용 정적 파일과 개인 파일은 다른 버킷에 나눠야 한다.
- S3는 버킷과 계정 단위 공개 차단(Block Public Access)을 모두 켜야 한다.
- GCS는 공개 액세스 방지(`publicAccessPrevention: enforced`)와 균일한 버킷 수준 액세스를 켜야 한다.
- DB·캐시·검색엔진 포트는 인터넷에 열지 않아야 한다.
- 관리 콘솔(Adminer·Kibana 등)은 인터넷에 직접 열지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `getPublicUrl(`, `public: true`, `"Principal": "*"`, `allUsers`, `allAuthenticatedUsers`, `ports:`, 그 밖에 S3 BlockPublicAcls 등 공개 차단 설정, GCS publicAccessPrevention, createBucket 호출, 버킷 정책·IAM 설정, docker-compose의 DB·Redis·Elasticsearch·MongoDB·관리 콘솔 서비스, 클라우드 DB의 공개 접근 설정.
- **검사 1 — 공개 버킷**: 지적할 때: 개인정보·사용자 업로드 파일을 담는 버킷을 `public: true`, `getPublicUrl(`, `"Principal": "*"`, `allUsers`·`allAuthenticatedUsers`(GCS, 후자도 구글 계정만 있으면 누구나)로 여는 경우, 공개 차단을 끄는 명령·설정(`BlockPublicPolicy=false` 등)이 있는 경우. / 지적하지 않을 때: 로고·공개 이미지처럼 원래 공개용 파일만 담는 버킷인 경우(이름·용도로 판단이 안 되면 "확인 필요"로 표시). `"Principal": "*"`이라도 `"Effect": "Deny"` 문이거나, Allow에 출처 조건(VPC 엔드포인트·조직 ID·CloudFront OAC의 `AWS:SourceArn`)이 붙은 경우.
- **검사 2 — 열린 DB·관리 포트**: 지적할 때: 운영 compose·배포 설정이 DB·Redis·검색엔진·관리 콘솔 포트를 `"5432:5432"`처럼 호스트 주소 없이 공개하거나 `0.0.0.0`에 묶는 경우, 클라우드 DB의 공개 접근이나 `0.0.0.0/0` 허용 규칙. / 지적하지 않을 때: `127.0.0.1:` 바인딩, 포트를 열지 않고 내부 네트워크로만 잇는 경우, 개발 전용 compose 파일(`compose.dev.yml` 등)인 경우.
- **등급**: 위험: 개인정보·업로드 파일 버킷 공개, 인증 없는 DB·Redis가 인터넷에 열린 경우. / 주의: 비밀번호만 걸린 DB 포트가 인터넷에 열린 경우, 공개 차단 해제 설정. / 참고: 공개용 파일과 개인 파일 버킷이 나뉘지 않은 경우.
- **보고 예**: `[위험] WS-CFG-004 docker-compose.yml:12 — redis 서비스가 "6379:6379" 로 모든 주소에 열려 방화벽도 거치지 않음 → ports 를 지우고 같은 compose 네트워크에서만 접속`
- **검토 주의**: Firebase Storage 규칙은 WS-BAAS-002, Supabase 테이블 RLS는 WS-BAAS-001, 관리자 화면 노출은 WS-AUTH-004에서 다루므로 여기서는 버킷 공개 설정과 열린 포트만 봐야 한다.
- **고칠 방법**: 개인 파일 버킷은 비공개로 바꾸고 서명 URL로 내줘야 하며, DB·관리 포트는 내부 네트워크로 옮기고 이미 열려 있던 기간의 접속 기록과 데이터 유출 여부를 확인해야 한다.

Code examples (read only the file for the stack you are writing): `WS-CFG-004.code.yaml.md` (YAML) · `WS-CFG-004.code.supabase.md` (Supabase) · `WS-CFG-004.code.shell.md` (Shell)
