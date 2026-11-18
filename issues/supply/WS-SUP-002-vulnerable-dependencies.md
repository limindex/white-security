---
id: WS-SUP-002
title: Vulnerable Dependencies (알려진 취약점이 있는 패키지)
slug: vulnerable-dependencies
group: supply
severity: high
grade_hint: "KEV·원격 코드 실행·인증 우회 취약 버전"
site_only: false
depth: brief
languages: [yaml, shell]
maps:
  owasp2025: [A03]
  api2023: []
  llm2026: []
  agentic2026: []
  owasp_mobile2024: [M2]
  asvs5: [V15.1.1, V15.1.2, V15.2.1]
  cwe: [CWE-1395, CWE-1104]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Node.js(npm·pnpm), Python(pip·uv·Poetry), PHP(Composer), GitHub Actions, React Native·Flutter 앱이 쓰는 SDK(npm·pub), 오픈소스 패키지를 쓰는 모든 앱]
severity_reason: "영향: 내 코드에 실수가 없어도 프레임워크·라이브러리의 공개된 취약점으로 원격 코드 실행·인증 우회 · 악용: 공개 공지를 보고 그 버전을 쓰는 사이트를 자동으로 찾아 공격"
sources:
  - title: "OWASP Top 10:2025 - A03 Software Supply Chain Failures"
    url: https://top10.owasp.org/2025/A03_2025-Software_Supply_Chain_Failures
    year: 2025
  - title: "OWASP ASVS 5.0 - V15.1 Secure Coding and Architecture Documentation, V15.2 Security Architecture and Dependencies"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제9조 ②
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: "GitHub Docs - Dependabot options reference"
    url: https://docs.github.com/en/code-security/dependabot/working-with-dependabot/dependabot-options-reference
    year: 2026
  - title: "pypa/pip-audit"
    url: https://github.com/pypa/pip-audit
    year: 2026
  - title: "Composer - Command-line interface (audit)"
    url: https://getcomposer.org/doc/03-cli.md
    year: 2026
  - title: "OSV-Scanner Docs - Usage"
    url: https://google.github.io/osv-scanner/usage/
    year: 2026
---

## 요약

이미 공개된 취약점(CVE·GHSA 공지)이 있는 버전의 프레임워크·라이브러리를 계속 쓰는 문제(OWASP Top 10:2025 A03). 공격자는 공지를 보고 그 버전을 쓰는 사이트를 자동으로 찾기 때문에, 내 코드에 실수가 없어도 뚫릴 수 있음.

## 대응 방법

```text
npm audit·pip-audit·composer audit(또는 osv-scanner)로 지금 의존성의 알려진 취약점을 찾아 심각도·고친 버전·직접/간접 의존성 여부를 표로 보여 줘.
고친 버전이 있으면 잠금 파일까지 함께 올리고, 메이저 버전이 바뀌는 것은 바뀌는 점을 먼저 알려 줘.
CI에 취약점 검사 단계를 넣고 Dependabot 설정 파일을 만들어 줘.
```

- 쓰는 언어의 검사 명령(`npm audit --audit-level=high` 등)이 오류 없이 끝나야 한다.
- 저장소에 `.github/dependabot.yml`(또는 Renovate 설정)이 있어야 한다.

## 점검 항목

- 의존성 취약점 검사(npm audit·pip-audit·composer audit·osv-scanner)를 CI에서 돌려야 한다.
- 높음·심각 취약점은 정해 둔 기한 안에 고친 버전으로 올려야 한다.
- 업데이트는 Dependabot·Renovate로 받아 테스트를 거쳐 합쳐야 한다.
- 간접 의존성 취약점은 상위 패키지 업데이트나 `overrides`로 고쳐야 한다.
- 지원이 끝난(EOL) 런타임·프레임워크 버전은 지원 버전으로 옮겨야 한다.
- 검사 무시 항목에는 사유와 다시 볼 날짜를 적어야 한다.

## 탐지 규칙

- **찾는 곳**: `package-lock.json`, `pnpm-lock.yaml`, `uv.lock`, `poetry.lock`, `composer.lock`, `dependabot.yml`, `renovate.json`, `npm audit fix --force`, 그 밖에 requirements.txt, CI 워크플로의 설치·검사 단계, Dockerfile 기본 이미지의 런타임 버전, 검사 무시 목록.
- **검사 1 — 알려진 취약점**: 지적할 때: 의존성이 바뀐 변경에서 실제로 돌린 검사 도구나 OSV 조회 결과에 높음·심각 취약점이 나오는 경우. / 지적하지 않을 때: 취약한 기능을 쓰지 않는다는 판단이 무시 목록에 사유·기한과 함께 남아 있는 경우. 도구를 돌리지 못했으면 안전하다고 하지 않고 "확인 못 함"으로 보고.
- **검사 2 — 업데이트 체계**: 지적할 때: Dependabot·Renovate 설정과 CI 검사가 모두 없는 경우, 런타임·프레임워크가 지원 종료 버전인 경우. / 지적하지 않을 때: 자동 업데이트 PR과 CI 검사가 있는 경우.
- **검사 3 — 무시·강제 수정**: 지적할 때: 무시 목록(`--ignore-vuln`·`audit.ignore`)에 사유·기한이 없는 경우, `npm audit fix --force`를 배포 과정에서 자동으로 돌려 검토 없이 메이저 버전이 바뀌는 경우. / 지적하지 않을 때: 사유와 다시 볼 날짜가 적힌 무시 항목.
- **등급**: 위험: CISA KEV(실제 악용 목록)에 오른 취약점, 원격 코드 실행·인증 우회 취약점이 있는 버전. / 주의: 높음 등급 취약점, 지원 종료 런타임, 업데이트 체계 없음. / 참고: 낮음·보통 등급, 사유 없는 무시 항목.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SUP-002 package-lock.json:1 — next 15.2.2 사용, CVE-2025-29927(미들웨어 인증 우회) → 15.2.3 이상으로 올리고 잠금 파일 함께 커밋`
- **검토 주의**: 모델이 기억하는 취약점 목록으로 단정하지 말고 검사 도구·OSV 조회 결과로만 판단해야 하며, 스캐너는 의존성이 바뀐 경우에만 돌려야 한다. 새 패키지의 실재 확인은 WS-SUP-001, 잠금 파일 무결성은 WS-SUP-004, 복사해 넣은 라이브러리 파일은 WS-SUP-006에서 다룬다. 개인정보처리자에 해당하면 고시 제9조 ②(제작업체의 보안 업데이트 공지가 있으면 정당한 사유가 없는 한 즉시 업데이트)의 취지를 참고 근거로 안내할 수 있다(조문 대상은 응용 프로그램·운영체제).
- **고칠 방법**: 고친 버전으로 올려 잠금 파일을 함께 커밋하고, 메이저 업데이트는 변경 사항을 확인한 뒤 테스트로 확인해야 한다.

## 코드 예시

### yaml

✗ 취약 코드

```yaml
# .github/workflows/deploy.yml — 검사 없이 배포, 업데이트 체계(Dependabot) 없음
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v6
      - run: npm install && npm audit fix --force   # 검토 없이 메이저 버전까지 바뀜
      - run: npm run build && ./deploy.sh
```

✓ 안전 코드

```yaml
# .github/workflows/ci.yml — 높음 이상 취약점이 있으면 실패
on: [pull_request, push]
permissions:
  contents: read
jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6.1.0
      - run: npm ci --ignore-scripts
      - run: npm audit --audit-level=high
---
# .github/dependabot.yml — 의존성·액션·기본 이미지 업데이트 PR 을 매주 받음
version: 2
updates:
  - package-ecosystem: "npm"
    directory: "/"
    schedule:
      interval: "weekly"
  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
  - package-ecosystem: "docker"          # Dockerfile FROM 의 태그·digest 갱신
    directory: "/"
    schedule:
      interval: "weekly"
```

### shell

✗ 취약 코드

```sh
# 배포 스크립트: 취약점 검사 없이 설치만(npm 경고도 끔)
npm install --no-audit
pip install -r requirements.txt
composer install --no-dev
```

✓ 안전 코드

```sh
set -euo pipefail
# Node: 잠금 파일대로 설치 후 높음 이상이면 실패
npm ci --ignore-scripts && npm audit --audit-level=high
# Python: 고정한 요구 사항 파일 검사(PyPA pip-audit)
pip-audit -r requirements.txt
# PHP: 잠금 파일 기준 검사(취약점이 있으면 종료 코드 1)
composer audit --locked
# 여러 언어를 한 번에: OSV-Scanner
osv-scanner scan source -r .
# 고칠 때: 고친 버전으로 올리고 잠금 파일을 함께 커밋(예: npm install next@15.2.3 --save-exact)
```
