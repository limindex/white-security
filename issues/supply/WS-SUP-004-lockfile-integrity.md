---
id: WS-SUP-004
title: Lockfile Integrity (잠금 파일·무결성 해시 없음)
slug: lockfile-integrity
group: supply
severity: medium
site_only: false
depth: brief
languages: [yaml, dockerfile]
maps:
  owasp2025: [A03, A08]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V15.1.2, V15.2.4]
  cwe: [CWE-494, CWE-353, CWE-829]
  kr_guide: ["보안기능-15"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [npm·pnpm, pip·uv·Poetry, Composer, GitHub Actions, Docker 이미지 빌드]
severity_reason: "영향: 빌드마다 검토하지 않은 버전·파일이 들어와 악성 버전이 조용히 배포됨 · 악용: 범위 버전에 맞는 새 악성 버전 공개, 잠금 파일 주소 바꿔치기, 같은 이름의 공개 패키지 등록"
sources:
  - title: "OWASP Top 10:2025 - A08 Software or Data Integrity Failures"
    url: https://top10.owasp.org/2025/A08_2025-Software_or_Data_Integrity_Failures
    year: 2025
  - title: "npm Docs - npm ci"
    url: https://docs.npmjs.com/cli/v11/commands/npm-ci
    year: 2026
  - title: "pip - Secure installs (--require-hashes)"
    url: https://pip.pypa.io/en/stable/topics/secure-installs/
    year: 2026
  - title: "uv Docs - Locking and syncing (--locked)"
    url: https://docs.astral.sh/uv/concepts/projects/sync/
    year: 2026
  - title: "Composer - Command-line interface (install, validate)"
    url: https://getcomposer.org/doc/03-cli.md
    year: 2026
  - title: "lirantal/lockfile-lint"
    url: https://github.com/lirantal/lockfile-lint
    year: 2026
---

## 요약

잠금 파일(설치한 버전과 파일 해시를 적어 둔 `package-lock.json`·`pnpm-lock.yaml`·`uv.lock`·`poetry.lock`·`composer.lock`)이 없거나, CI·Docker가 이를 무시하고 `npm install`·해시 없는 `pip install -r`로 설치해 빌드마다 검토하지 않은 버전이 들어오는 문제(진단가이드 이름: 무결성 검사 없는 코드 다운로드).

## 위험 배경

- 잠금 파일 속 주소(`resolved`)를 바꿔치기한 변경이나 여러 저장소를 섞어 쓰는 설정도 같은 결과로 이어지는 구조.

## 대응 방법

```text
잠금 파일이 커밋돼 있는지, CI·Dockerfile·배포 스크립트가 잠금 파일대로 설치하는지 표로 보여 줘.
설치 명령을 npm ci·pnpm install --frozen-lockfile·uv sync --locked·composer install 로 바꾸고, pip 요구 사항 파일은 해시까지 고정해 줘.
잠금 파일의 주소가 공식 레지스트리·https 인지 확인하는 검사(lockfile-lint)를 CI에 넣어 줘.
```

- `package.json`만 고치고 잠금 파일을 안 고친 커밋은 CI가 실패해야 한다.
- 잠금 파일의 모든 패키지 주소가 공식(또는 사내) 레지스트리의 https 주소여야 한다.

## 점검 항목

- 앱 저장소는 잠금 파일을 커밋해야 한다.
- CI·Docker는 잠금 파일대로만 설치해야 한다(`npm ci`·`uv sync --locked`·`composer install`).
- 파이썬 요구 사항은 해시까지 고정하고 `--require-hashes`로 설치해야 한다.
- 잠금 파일 변경은 리뷰에서 바뀐 주소·해시를 확인해야 한다.
- 패키지 저장소는 공식·사내 미러 하나로 고정하고 출처를 섞지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `npm install`, `npm ci`, `--frozen-lockfile`, `--extra-index-url`, `_authToken=${`, `"resolved":`, `--require-hashes`, `npmAuthToken`, `composer update`, `uv sync`, 그 밖에 Dockerfile의 RUN 설치 줄, CI 설치 단계, .gitignore 속 잠금 파일, .npmrc·pip.conf의 레지스트리 설정.
- **검사 1 — 잠금 파일 없음·무시**: 지적할 때: 앱 저장소에 잠금 파일이 없거나 .gitignore로 빠진 경우, CI·Dockerfile이 `npm install`·`pnpm install --no-frozen-lockfile`·`composer update`나 버전 고정(`==`)이 빠진 요구 사항 파일로 `pip install -r` 하는 경우. / 지적하지 않을 때: `npm ci`·`pnpm install --frozen-lockfile`·`uv sync --locked`·`composer install`, 일부러 잠금 파일을 두지 않는 라이브러리 저장소(참고로만).
- **검사 2 — 잠금 파일 바꿔치기**: 지적할 때: 잠금 파일의 `resolved`가 공식 레지스트리가 아닌 주소·`http://`·깃 주소로 바뀐 경우, `integrity`가 빠진 항목, 의존성 변경이 없는데 잠금 파일만 크게 바뀐 PR. / 지적하지 않을 때: 설정 파일에 근거가 있는 사내 미러 주소로 일관되게 바뀐 경우.
- **검사 3 — 섞인 출처**: 지적할 때: `--extra-index-url`이나 범위 지정 없는 여러 레지스트리 설정으로, 사내 패키지와 같은 이름을 공개 저장소에서도 받을 수 있는 경우(Dependency Confusion, 의존성 혼동). 저장소의 `.npmrc`·`.yarnrc.yml`이 공식이 아닌 레지스트리에 `_authToken=${NPM_TOKEN}`·`npmAuthToken: ${…}`처럼 환경 변수 토큰을 붙여 보내는 경우(설치·audit 명령 때 토큰이 그 서버로 감). / 지적하지 않을 때: `--index-url` 하나만 쓰거나 npm 범위(`@회사/`)별로 레지스트리를 고정한 경우.
- **등급**: 위험: 잠금 파일 주소가 낯선 서버·http로 바뀜, 사내 패키지 이름을 공개 저장소에서도 찾는 설정, 낯선 레지스트리로 환경 변수 토큰을 보내는 설정. / 주의: 잠금 파일 없음·커밋 안 함, CI·Docker가 잠금 파일 무시, 버전 고정 없는 requirements. / 참고: 모든 줄이 `==`로 고정됐지만 해시가 없는 requirements, 라이브러리 저장소의 잠금 파일 정책.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[주의] WS-SUP-004 Dockerfile:5 — RUN npm install 로 잠금 파일과 다른 버전이 설치될 수 있음 → package*.json 만 먼저 복사하고 RUN npm ci`
- **검토 주의**: 새 패키지 이름의 실재 확인은 WS-SUP-001, 취약한 버전은 WS-SUP-002, 설치 스크립트는 WS-SUP-003, CI 액션·외부 스크립트 다운로드는 WS-SUP-005에서 다룬다. `npm ci`는 잠금 파일과 `package.json`이 맞지 않으면 설치하지 않고 실패하며, `composer install`은 잠금 파일이 오래돼도 경고만 하므로 `composer validate`(잠금 파일이 최신이 아니면 오류)를 함께 돌리라고 안내해야 한다.
- **고칠 방법**: 잠금 파일을 커밋하고 모든 자동 설치를 잠금 파일 고정 명령으로 바꾸며, 잠금 파일 주소·해시 검사를 CI에 넣어야 한다.

## 코드 예시

### yaml

✗ 취약 코드

```yaml
# .github/workflows/ci.yml
steps:
  - uses: actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6.1.0
  - run: npm install                       # 잠금 파일과 다르면 새 버전을 풀고 잠금 파일을 고침
  - run: pip install -r requirements.txt   # 버전·해시 고정 없음
  - run: composer update --no-interaction  # 잠금 파일을 무시하고 새로 계산
```

✓ 안전 코드

```yaml
# .github/workflows/ci.yml
permissions:
  contents: read
steps:
  - uses: actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6.1.0
  - run: npm ci --ignore-scripts             # 잠금 파일과 package.json 이 다르면 실패, integrity 확인
  - run: npx --yes lockfile-lint@5.1.0 --path package-lock.json --type npm --allowed-hosts npm --validate-https --validate-integrity
  - run: pip install --require-hashes -r requirements.txt   # 해시는 uv pip compile --generate-hashes 로 생성
  - run: composer validate && composer install --no-interaction   # validate: 잠금 파일이 최신이 아니면 실패
```

### dockerfile

✗ 취약 코드

```dockerfile
FROM node:24-slim
WORKDIR /app
COPY . .
RUN npm install
CMD ["node", "server.js"]
```

✓ 안전 코드

```dockerfile
FROM node:24-slim
WORKDIR /app
# 잠금 파일을 먼저 복사해 그대로 설치(다르면 빌드 실패)
COPY package.json package-lock.json ./
RUN npm ci --omit=dev --ignore-scripts
COPY . .
USER node
CMD ["node", "server.js"]
# 파이썬 이미지라면: COPY requirements.txt . 후 RUN pip install --no-deps --require-hashes -r requirements.txt
```
