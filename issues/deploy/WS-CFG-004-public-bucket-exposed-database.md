---
id: WS-CFG-004
title: Public Bucket · Exposed Database (공개된 저장소 버킷·DB·관리 콘솔)
slug: public-bucket-exposed-database
group: deploy
severity: high
grade_hint: "개인정보 버킷 공개, 인증 없는 DB·Redis 노출"
always_on: "`getPublicUrl(` `public: true` → 개인 파일은 비공개 버킷에 두고 짧은 서명 URL로만 내줘야 한다."
site_only: false
depth: brief
languages: [yaml, supabase, shell]
maps:
  owasp2025: [A02, A01]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  asvs5: [V13.4.5, V13.2.2]
  cwe: [CWE-284, CWE-668, CWE-200, CWE-306]
  kr_guide: ["보안기능-3"]
  kr_ciip: [AE, IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [AWS S3, Google Cloud Storage, Supabase Storage, Docker Compose로 띄운 DB·Redis·검색엔진·관리 콘솔]
severity_reason: "영향: 사용자 업로드 파일·DB 전체 유출과 삭제 · 악용: 인터넷 전체를 훑는 자동 스캐너가 열린 포트·버킷을 찾아냄"
sources:
  - title: "AWS - Blocking public access to your Amazon S3 storage"
    url: https://docs.aws.amazon.com/AmazonS3/latest/userguide/access-control-block-public-access.html
    year: 2026
  - title: "Supabase Docs - Storage Buckets (Public·Private buckets, createSignedUrl)"
    url: https://supabase.com/docs/guides/storage/buckets/fundamentals
    year: 2026
  - title: "Docker Docs - Port publishing and mapping (기본은 모든 호스트 주소)"
    url: https://docs.docker.com/engine/network/port-publishing/
    year: 2026
  - title: "Docker Docs - Packet filtering and firewalls (Docker and ufw)"
    url: https://docs.docker.com/engine/network/packet-filtering-firewalls/
    year: 2026
  - title: "OWASP Top 10:2025 - A02 Security Misconfiguration"
    url: https://top10.owasp.org/2025/A02_2025-Security_Misconfiguration
    year: 2025
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

S3·GCS·Supabase Storage 버킷을 공개로 두거나 DB·Redis·Elasticsearch·관리 콘솔 포트를 인터넷에 열어 둬, 주소만 알면 파일과 데이터를 가져가는 문제(진단가이드 이름: 중요한 자원에 대한 잘못된 권한 설정). Docker의 `ports: "5432:5432"`는 서버의 모든 주소에 열리고 ufw 같은 방화벽 규칙도 거치지 않음.

## 대응 방법

```text
이 프로젝트가 만드는 저장소 버킷과 docker-compose·배포 설정이 여는 포트를 모두 찾아 공개 여부를 표로 보여 줘.
사용자 업로드·개인정보 버킷은 비공개로 바꾸고 파일은 짧게 유효한 서명 URL 로만 내주게 해 줘.
DB·Redis·관리 콘솔 포트는 외부에 열지 말고 같은 내부 네트워크나 127.0.0.1 에서만 닿게 바꿔 줘.
```

- 로그인하지 않은 브라우저에서 개인 파일 주소를 열면 거절돼야 한다.
- 서버 밖에서 DB·Redis 포트로 접속하면 연결되지 않아야 한다.

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
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CFG-004 docker-compose.yml:12 — redis 서비스가 "6379:6379" 로 모든 주소에 열려 방화벽도 거치지 않음 → ports 를 지우고 같은 compose 네트워크에서만 접속`
- **검토 주의**: Firebase Storage 규칙은 WS-BAAS-002, Supabase 테이블 RLS는 WS-BAAS-001, 관리자 화면 노출은 WS-AUTH-004에서 다루므로 여기서는 버킷 공개 설정과 열린 포트만 봐야 한다.
- **고칠 방법**: 개인 파일 버킷은 비공개로 바꾸고 서명 URL로 내줘야 하며, DB·관리 포트는 내부 네트워크로 옮기고 이미 열려 있던 기간의 접속 기록과 데이터 유출 여부를 확인해야 한다.

## 코드 예시

### yaml

✗ 취약 코드

```yaml
# docker-compose.yml (운영)
services:
  db:
    image: postgres:17
    ports:
      - "5432:5432"      # 서버의 모든 주소에 열림, ufw 규칙도 거치지 않음
  redis:
    image: redis:8
    ports:
      - "6379:6379"      # 비밀번호 없이 인터넷에 열림
```

✓ 안전 코드

```yaml
# docker-compose.yml (운영)
services:
  app:
    build: .
    ports:
      - "127.0.0.1:8000:8000"   # 같은 서버의 nginx 만 접근
    depends_on: [db, redis]
  db:
    image: postgres:17
    environment:
      POSTGRES_PASSWORD_FILE: /run/secrets/db_password
    secrets: [db_password]
    # ports 없음: 같은 compose 네트워크의 app 만 db:5432 로 접속
  redis:
    image: redis:8
    # ports 없음
secrets:
  db_password:
    file: ./secrets/db_password.txt   # 저장소에 올리지 않음
```

### supabase

✗ 취약 코드

```ts
// 신분증 사진을 공개 버킷에 올림 — 주소만 알면 누구나 열람
await supabaseAdmin.storage.createBucket("id-cards", { public: true });

const { data } = supabase.storage.from("id-cards").getPublicUrl(`${user.id}.jpg`);
await saveProfile({ idCardUrl: data.publicUrl });
```

✓ 안전 코드

```ts
// 서버에서 한 번: 비공개 버킷
await supabaseAdmin.storage.createBucket("id-cards", { public: false });

// 본인 폴더만 읽게 하는 storage.objects 정책(SQL)
// create policy "id_cards_owner_read" on storage.objects for select to authenticated
//   using (bucket_id = 'id-cards' and (storage.foldername(name))[1] = (select auth.uid())::text);

// 볼 때마다 60초짜리 서명 URL 을 만든다(로그인한 사용자 클라이언트로, 정책이 적용됨)
const { data, error } = await supabase.storage
  .from("id-cards")
  .createSignedUrl(`${user.id}/front.jpg`, 60);
```

### shell

✗ 취약 코드

```sh
# 업로드 버킷의 공개 차단을 끄고 버킷 전체를 누구나 읽게 함
aws s3api put-public-access-block --bucket my-uploads \
  --public-access-block-configuration BlockPublicAcls=false,IgnorePublicAcls=false,BlockPublicPolicy=false,RestrictPublicBuckets=false
aws s3api put-bucket-policy --bucket my-uploads --policy \
  '{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Principal":"*","Action":"s3:GetObject","Resource":"arn:aws:s3:::my-uploads/*"}]}'
```

✓ 안전 코드

```sh
# 버킷과 계정 단위 공개 차단 4가지를 모두 켠다
aws s3api put-public-access-block --bucket my-uploads \
  --public-access-block-configuration BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true
aws s3control put-public-access-block --account-id 111122223333 \
  --public-access-block-configuration BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true
aws s3api get-public-access-block --bucket my-uploads   # 네 값이 모두 true 인지 확인

# 파일은 짧게 유효한 서명 URL 로만(앱 서버는 SDK 의 presign 함수 사용)
aws s3 presign s3://my-uploads/receipts/123.pdf --expires-in 300
# 꼭 공개해야 하는 정적 파일은 별도 버킷 + CloudFront(OAC)로만 내보낸다
```
