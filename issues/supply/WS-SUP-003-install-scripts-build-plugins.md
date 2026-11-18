---
id: WS-SUP-003
title: Install Scripts (postinstall) · Build Plugins (설치 스크립트·빌드 플러그인)
slug: install-scripts-build-plugins
group: supply
severity: high
grade_hint: "비밀값 있는 환경에서 낯선 설치 스크립트 실행"
always_on: "`postinstall` `preinstall` → CI·처음 받는 설치는 `--ignore-scripts`로 하고, 필요한 패키지의 스크립트만 허용해야 한다."
site_only: false
depth: brief
languages: [shell, yaml, json]
maps:
  owasp2025: [A03, A08]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V15.1.4, V15.2.5]
  cwe: [CWE-829, CWE-506]
  kr_guide: ["보안기능-15"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [npm·pnpm, Composer, pip(소스 배포판 빌드), Vite·webpack·Babel 같은 빌드 도구 플러그인, CI 설치 단계]
severity_reason: "영향: 패키지 설치만으로 개발 PC·CI의 토큰·클라우드 키가 털리고 내 패키지까지 감염 · 악용: 의존성 하나가 탈취되어 새 버전에 설치 스크립트를 넣는 것으로 충분"
sources:
  - title: "CISA - Widespread Supply Chain Compromise Impacting npm Ecosystem (2025-09-23)"
    url: https://www.cisa.gov/news-events/alerts/2025/09/23/widespread-supply-chain-compromise-impacting-npm-ecosystem
    year: 2025
  - title: "npm Docs - config (ignore-scripts, min-release-age)"
    url: https://docs.npmjs.com/cli/v11/using-npm/config
    year: 2026
  - title: "npm Docs - npm rebuild"
    url: https://docs.npmjs.com/cli/v11/commands/npm-rebuild
    year: 2026
  - title: "pnpm Docs - Build settings (allowBuilds, strictDepBuilds)"
    url: https://pnpm.io/settings/build
    year: 2026
  - title: "Composer Docs - config (allow-plugins)"
    url: https://getcomposer.org/doc/06-config.md
    year: 2026
  - title: "pip - Secure installs (--only-binary)"
    url: https://pip.pypa.io/en/stable/topics/secure-installs/
    year: 2026
---

## 요약

npm의 `preinstall`·`postinstall` 같은 설치 스크립트, Composer 플러그인, 파이썬 소스 배포판의 빌드 코드, Vite·webpack 같은 빌드 도구 플러그인처럼 설치·빌드하는 순간 실행되는 코드 때문에, 탈취된 의존성 하나가 개발 PC·CI의 토큰과 클라우드 키를 가져가는 문제(진단가이드 이름: 무결성 검사 없는 코드 다운로드).

## 위험 배경

- 2025년 9월 npm 생태계의 자기 복제 웜이 500개 넘는 패키지를 감염시켜 GitHub 토큰·클라우드 키를 빼낸 사고(CISA 경보).

## 대응 방법

```text
package.json, composer.json, pnpm-workspace.yaml, .npmrc, CI 설치 단계를 보고, 설치 중 스크립트·플러그인이 실행되는 의존성을 표로 보여 줘.
CI와 처음 받는 설치는 스크립트 없이 하고(npm ci --ignore-scripts), 빌드가 꼭 필요한 패키지만 허용 목록으로 다시 빌드하게 고쳐 줘.
Composer allow-plugins 는 쓰는 플러그인만 true 로 적고, 새 버전은 공개 후 며칠 지나 받도록 설정해 줘.
```

- CI 설치 로그에 허용 목록 밖 패키지의 `postinstall` 실행이 없어야 한다.
- `composer.json`의 `allow-plugins`가 `true` 한 줄이 아니라 패키지별 목록이어야 한다.

## 점검 항목

- CI·처음 받는 설치는 설치 스크립트 없이 해야 한다(`npm ci --ignore-scripts`).
- 빌드 스크립트가 필요한 패키지는 허용 목록으로만 실행해야 한다.
- Composer `allow-plugins`에는 쓰는 플러그인만 하나씩 허용해야 한다.
- 파이썬 패키지는 가능하면 휠만 받아 소스 빌드 코드가 돌지 않게 해야 한다.
- 설치 단계에는 배포 토큰·클라우드 키를 넘기지 않아야 한다.
- 새로 공개된 버전은 바로 받지 말고 며칠 기다리게(최소 공개 기간) 설정해야 한다.

## 탐지 규칙

- **찾는 곳**: `postinstall`, `preinstall`, `yarnPath`, `ignore-scripts`, `allowBuilds`, `dangerouslyAllowAllBuilds`, `onlyBuiltDependencies`, `allow-plugins`, `--only-binary`, `yarn-path`, 그 밖에 .npmrc·.yarnrc.yml·pnpm-workspace.yaml, CI 설치 단계와 그 단계의 환경 변수, 새로 추가한 빌드 도구 플러그인(vite.config·webpack.config·babel 설정).
- **검사 1 — 설치 중 실행 허용**: 지적할 때: CI·배포에서 `npm install`·`npm ci`를 스크립트 허용 상태로 돌리면서 허용 목록이 없는 경우, pnpm `dangerouslyAllowAllBuilds: true`, Composer `"allow-plugins": true`, 의존성이 새로 추가되면서 그 패키지에 설치 스크립트가 있는 경우, `.yarnrc.yml`의 `yarnPath`(Yarn 1은 `.yarnrc`의 `yarn-path`)가 `.yarn/releases/` 밖 파일을 가리키거나 `plugins:`가 출처를 모르는 파일을 불러오는 경우(모든 yarn 명령이 그 파일을 실행함). / 지적하지 않을 때: `yarn set version`이 만든 `.yarn/releases/yarn-<버전>.cjs`를 가리키는 경우(참고로만), `--ignore-scripts`로 설치하고 필요한 패키지만 `npm rebuild <이름>`으로 빌드하는 경우, pnpm `allowBuilds`·Composer `allow-plugins`에 패키지별 허용이 있는 경우(허용 목록에 든 esbuild·sharp·prisma처럼 널리 쓰이는 빌드 패키지는 참고로만).
- **검사 2 — 설치 단계의 비밀값**: 지적할 때: 설치 단계(job·step)에 배포 토큰·클라우드 키·`NPM_TOKEN`(쓰기 권한)이 환경 변수로 들어가는 경우. / 지적하지 않을 때: 비밀값을 배포 단계에만 주는 경우, 읽기 전용 토큰.
- **검사 3 — 새 빌드 플러그인·새 버전**: 지적할 때: 내려받은 수가 적거나 처음 보는 빌드 도구 플러그인을 추가하는 경우, 최소 공개 기간 설정 없이 범위 버전(`^`)으로 새 버전을 바로 받는 CI. / 지적하지 않을 때: 공식 문서가 안내하는 플러그인, 잠금 파일로 고정한 버전.
- **등급**: 위험: 처음 보는 패키지의 설치 스크립트가 비밀값이 있는 환경에서 실행됨, Composer 플러그인 전체 허용, `yarnPath`가 `.yarn/releases/` 밖 파일을 가리킴. / 주의: CI 설치가 스크립트 허용·허용 목록 없음, 최소 공개 기간 없음. / 참고: 허용 목록에 든 널리 쓰이는 빌드 패키지(esbuild·sharp·prisma), 소스 빌드가 필요한 파이썬 패키지, 빌드 플러그인 출처 확인 필요.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SUP-003 .github/workflows/release.yml:21 — NPM_TOKEN·AWS 키가 있는 단계에서 npm install 로 설치 스크립트 실행 → npm ci --ignore-scripts 로 바꾸고 비밀값은 배포 단계에만`
- **검토 주의**: `ignore-scripts`는 내 `package.json`의 스크립트도 막으므로, 설치 뒤 빌드가 깨지면 `npm rebuild <패키지>`로 필요한 것만 다시 빌드하라고 안내해야 한다(`.npmrc`에 `ignore-scripts=true`를 두면 rebuild도 스크립트를 건너뛰므로 `npm rebuild <패키지> --ignore-scripts=false`). pnpm 10은 `onlyBuiltDependencies`, pnpm 11 이상은 `allowBuilds`를 쓰고, pnpm 11 이상에서는 `strictDepBuilds` 기본값이 true라 목록 밖 패키지에 빌드 스크립트가 있으면 설치가 실패한다. 패키지 이름이 실재하는지는 WS-SUP-001, 잠금 파일은 WS-SUP-004에서 다룬다.
- **고칠 방법**: 설치는 스크립트 없이 하고 빌드가 필요한 패키지만 허용 목록으로 다시 빌드하며, 설치 단계에서 비밀값을 빼야 한다.

## 코드 예시

### shell

✗ 취약 코드

```sh
# CI: 배포 토큰이 있는 상태로 모든 의존성의 설치 스크립트 실행
export NPM_TOKEN AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY
npm install
pip install -r requirements.txt     # 소스 배포판이면 setup 코드도 실행
```

✓ 안전 코드

```sh
set -euo pipefail
# 1) 잠금 파일대로, 설치 스크립트 없이(비밀값 없는 단계에서)
npm ci --ignore-scripts
# 2) 빌드가 꼭 필요한 패키지만 골라 다시 빌드(허용 목록)
npm rebuild esbuild
# 3) 파이썬은 휠만 받아 소스 빌드 코드가 돌지 않게, 해시 고정
pip install --only-binary :all: --require-hashes -r requirements.txt
# 새 버전은 공개 후 며칠 지나 받기: .npmrc 에 min-release-age=3 (단위: 일)
```

### yaml

✗ 취약 코드

```yaml
# pnpm-workspace.yaml — 모든 의존성의 빌드 스크립트를 자동 실행
dangerouslyAllowAllBuilds: true
```

✓ 안전 코드

```yaml
# pnpm-workspace.yaml (pnpm 11 이상) — 검토한 패키지만 빌드 허용, 목록 밖은 설치 실패로 알림
allowBuilds:
  esbuild: true
  core-js: false
strictDepBuilds: true      # pnpm 11 기본값도 true, 끄지 않음을 드러내려고 적음
minimumReleaseAge: 4320   # 공개 후 3일(단위: 분) 지난 버전만
# pnpm 10 은 onlyBuiltDependencies: [esbuild]
```

### json

✗ 취약 코드

```json
{
  "require": { "laravel/framework": "^12.0" },
  "config": {
    "allow-plugins": true
  }
}
```

✓ 안전 코드

```json
{
  "require": { "laravel/framework": "^12.0" },
  "config": {
    "allow-plugins": {
      "pestphp/pest-plugin": true,
      "php-http/discovery": false
    }
  }
}
```
