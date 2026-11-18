---
id: WS-SUP-005
title: CI/CD Integrity · Unverified Downloads (CI 파이프라인·검증 없는 다운로드)
title_en: CI/CD Integrity · Unverified Downloads (GitHub Actions, curl | sh)
slug: cicd-integrity-unverified-downloads
group: supply
severity: high
grade_hint: "pull_request_target에서 PR 코드 실행, run:에 입력"
always_on: "`pull_request_target` `${{ github.event.` → `pull_request_target`에서 PR 코드를 실행하지 않고, 이벤트 값은 `run:`에 직접 넣지 않아야 한다."
site_only: false
depth: brief
languages: [yaml, dockerfile]
maps:
  owasp2025: [A08, A03]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V3.6.1, V15.2.4, V13.3.2]
  cwe: [CWE-494, CWE-829, CWE-77, CWE-345]
  kr_guide: ["보안기능-15", "보안기능-10"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [GitHub Actions, Dockerfile, 설치 스크립트(curl | sh), CDN에서 불러오는 스크립트, 앱 자동 업데이트]
severity_reason: "영향: CI 비밀값·배포 권한 탈취, 빌드 결과물에 악성 코드가 섞여 모든 사용자에게 배포 · 악용: 남의 액션 탈취, 포크 PR, 다운로드 서버 바꿔치기"
sources:
  - title: "CISA - Supply Chain Compromise of Third-Party tj-actions/changed-files (CVE-2025-30066) and reviewdog/action-setup@v1 (CVE-2025-30154)"
    url: https://www.cisa.gov/news-events/alerts/2025/03/18/supply-chain-compromise-third-party-tj-actionschanged-files-cve-2025-30066-and-reviewdogaction
    year: 2025
  - title: "GitHub Docs - Secure use reference (Actions)"
    url: https://docs.github.com/en/actions/reference/security/secure-use
    year: 2026
  - title: "OWASP Top 10:2025 - A08 Software or Data Integrity Failures"
    url: https://top10.owasp.org/2025/A08_2025-Software_or_Data_Integrity_Failures
    year: 2025
  - title: "Docker Docs - Dockerfile reference (ADD --checksum, FROM digest)"
    url: https://docs.docker.com/reference/dockerfile/
    year: 2026
  - title: "OWASP ASVS 5.0 - V3.6.1 Subresource Integrity"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
---

## 요약

GitHub Actions에서 남의 액션을 태그(`@v4`)로 부르고, 토큰 권한을 넓게 두고, `pull_request_target`에서 외부 PR 코드를 실행하고, `curl … | sh`·태그만 지정한 이미지·CDN 스크립트를 확인 없이 받아 쓰는 문제(진단가이드 이름: 무결성 검사 없는 코드 다운로드).

## 위험 배경

- 2025년 3월 tj-actions/changed-files 액션 탈취로 이 액션을 쓰던 저장소의 CI 비밀값이 노출될 수 있었던 사고, CISA가 CVE-2025-30066으로 경보.

## 대응 방법

```text
.github/workflows·Dockerfile·설치 스크립트에서 커밋 SHA로 고정하지 않은 액션, 넓은 토큰 권한, pull_request_target, 검증 없는 다운로드를 찾아 표로 보여 줘.
외부 액션은 전체 커밋 SHA로 고정하고(태그는 주석으로), 워크플로 맨 위에 permissions: contents: read 를 두고 필요한 job 에만 권한을 더해 줘.
curl | sh 는 버전을 고정해 받은 파일의 SHA-256을 확인한 뒤 실행하게, 기본 이미지는 digest 로 고정하게 바꿔 줘.
```

- 같은 저장소 밖 액션의 `uses:`가 모두 40자리 커밋 SHA여야 한다.
- 포크에서 온 PR이 비밀값이 있는 워크플로에서 PR 코드를 실행하지 않아야 한다.

## 점검 항목

- 외부 액션은 태그 대신 전체 커밋 SHA로 고정해야 한다.
- 워크플로 토큰은 `permissions:`로 읽기를 기본으로 하고, 쓰기는 필요한 job에만 줘야 한다.
- `pull_request_target`·`workflow_run`에서 외부 PR 코드를 체크아웃·실행하지 않아야 한다.
- `${{ github.event.* }}` 값은 `run:`에 바로 넣지 말고 환경 변수로 넘겨야 한다.
- 내려받는 스크립트·도구·이미지는 버전과 해시(digest)를 고정해 확인해야 한다.
- 외부 CDN 스크립트는 버전을 고정하고 SRI(`integrity` 속성)를 붙여야 한다.

## 탐지 규칙

- **찾는 곳**: `pull_request_target`, `workflow_run`, `${{ github.event.`, `${{ github.head_ref`, `write-all`, `persist-credentials`, `| bash`, `ADD http`, 그 밖에 .github/workflows의 uses: 줄과 permissions, curl·wget으로 받아 바로 실행하는 줄, Dockerfile의 FROM 태그, CDN script 태그의 integrity 속성, 앱 자동 업데이트 코드.
- **검사 1 — 고정·권한**: 지적할 때: 다른 저장소의 액션을 태그·브랜치(`@v4`·`@main`)로 부르는 경우, 워크플로에 `permissions:`가 없거나 `write-all`인 경우, 비밀값·쓰기 토큰이 있는 job에서 이름 모를 액션을 부르는 경우. / 지적하지 않을 때: 40자리 SHA 고정(태그는 주석), 같은 저장소 안 액션(`./.github/actions/…`), 최소 권한.
- **검사 2 — 신뢰하지 않는 입력 실행**: 지적할 때: `pull_request_target`·`workflow_run`에서 PR의 head 코드를 체크아웃해 설치·빌드·테스트하는 경우, `run:`에 `${{ github.event.pull_request.title }}`·`${{ github.head_ref }}`처럼 남이 정하는 값을 바로 넣는 경우. / 지적하지 않을 때: `pull_request` 트리거(포크 PR엔 비밀값이 없음), 값을 `env:`로 넘겨 `"$TITLE"`처럼 쓰는 경우.
- **검사 3 — 검증 없는 다운로드**: 지적할 때: `curl … | sh`·`| bash`, 버전·해시 없이 받는 도구, `:latest` 기본 이미지, 해시 없는 `ADD` 원격 파일, `integrity` 없는 CDN 스크립트, 서명·해시 확인 없는 앱 자동 업데이트. / 지적하지 않을 때: 버전 고정과 SHA-256 확인(`sha256sum -c`·`ADD --checksum`), digest 고정 이미지, SRI가 있는 CDN 스크립트, 서명 확인(Sigstore cosign 등).
- **등급**: 위험: `pull_request_target`에서 PR 코드 실행, `run:`에 이벤트 값 직접 삽입, 비밀값이 있는 단계의 `curl | sh`. / 주의: 태그로 부르는 외부 액션, `permissions` 없음·`write-all`, 해시 없는 다운로드, `:latest` 이미지. / 참고: SRI 없는 CDN 스크립트, digest를 고정하지 않은 공식 이미지.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SUP-005 .github/workflows/preview.yml:3 — pull_request_target 에서 PR head 를 체크아웃해 npm install 실행, 포크 PR 이 비밀값을 읽을 수 있음 → pull_request 로 바꾸고 비밀값이 필요한 배포는 합친 뒤 별도 워크플로에서`
- **검토 주의**: SHA로 고정한 액션은 Dependabot 보안 알림이 오지 않으므로(GitHub 문서) Dependabot 버전 업데이트(`github-actions`)를 켜서 고정값을 갱신하라고 안내해야 한다. 의존성 잠금 파일은 WS-SUP-004, 설치 스크립트는 WS-SUP-003, 워크플로 파일 속 비밀값은 WS-SEC-001에서 다룬다.
- **고칠 방법**: 외부 액션은 SHA로 고정하고 토큰은 최소 권한으로 줄이며, 신뢰하지 않는 코드와 비밀값을 같은 job에 두지 않고, 다운로드는 버전·해시를 고정해 확인한 뒤 실행해야 한다.

## 코드 예시

### yaml

✗ 취약 코드

```yaml
# .github/workflows/preview.yml
on: pull_request_target          # 포크 PR 에도 비밀값·쓰기 토큰이 있는 상태로 실행
jobs:
  preview:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v6
        with:
          ref: ${{ github.event.pull_request.head.sha }}   # 남의 PR 코드를 체크아웃
      - run: npm install && npm run build                  # PR 의 설치·빌드 스크립트가 비밀값과 함께 실행
      - run: echo "${{ github.event.pull_request.title }}"   # PR 제목이 그대로 셸 코드가 됨
      - uses: example-org/deploy-preview@main              # 브랜치 고정: 바뀐 코드를 그대로 실행
        with:
          token: ${{ secrets.DEPLOY_TOKEN }}
```

✓ 안전 코드

```yaml
# .github/workflows/ci.yml — 포크 PR 은 비밀값 없이, 읽기 토큰으로
on: pull_request
permissions:
  contents: read
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6.1.0
        with:
          persist-credentials: false
      - run: npm ci --ignore-scripts && npm run build
      - env:
          TITLE: ${{ github.event.pull_request.title }}    # 남이 정하는 값은 환경 변수로만
        run: echo "$TITLE"
# 비밀값이 필요한 배포는 main 에 합친 뒤(push) 별도 워크플로에서, 그 job 에만 필요한 권한을 줌
```

### dockerfile

✗ 취약 코드

```dockerfile
FROM node:latest
# 받는 내용이 바뀌어도 그대로 실행
RUN curl -fsSL https://example.com/install.sh | bash
# 해시 확인 없는 원격 파일
ADD https://example.com/tool/latest/tool.tar.gz /opt/
```

✓ 안전 코드

```dockerfile
# syntax=docker/dockerfile:1
# 기본 이미지는 digest 로 고정(2026-11-18 node:24-slim, Dependabot docker 업데이트로 갱신)
FROM node:24-slim@sha256:dc26ae92c9fb15cf6b1d63e20f0944b2efe0f115b20e00be48fb23fceb061c54
# 버전을 고정한 파일을 받고, 배포처가 공개한 SHA-256 과 다르면 빌드 실패
ADD --checksum=sha256:<배포처가 공개한 SHA-256> https://example.com/tool/v1.4.2/tool.tar.gz /tmp/tool.tar.gz
RUN tar -xzf /tmp/tool.tar.gz -C /usr/local/bin && rm /tmp/tool.tar.gz
```
