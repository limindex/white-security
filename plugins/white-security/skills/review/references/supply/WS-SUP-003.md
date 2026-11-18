<!-- generated from white-security issues; do not edit -->
# WS-SUP-003 Install Scripts (postinstall) · Build Plugins (설치 스크립트·빌드 플러그인)

## 탐지 규칙

- **찾는 곳**: `postinstall`, `preinstall`, `ignore-scripts`, `allowBuilds`, `dangerouslyAllowAllBuilds`, `onlyBuiltDependencies`, `allow-plugins`, `--only-binary`, 그 밖에 .npmrc·pnpm-workspace.yaml, CI 설치 단계와 그 단계의 환경 변수, 새로 추가한 빌드 도구 플러그인(vite.config·webpack.config·babel 설정).
- **검사 1 — 설치 중 실행 허용**: 지적할 때: CI·배포에서 `npm install`·`npm ci`를 스크립트 허용 상태로 돌리면서 허용 목록이 없는 경우, pnpm `dangerouslyAllowAllBuilds: true`, Composer `"allow-plugins": true`, 의존성이 새로 추가되면서 그 패키지에 설치 스크립트가 있는 경우. / 지적하지 않을 때: `--ignore-scripts`로 설치하고 필요한 패키지만 `npm rebuild <이름>`으로 빌드하는 경우, pnpm `allowBuilds`·Composer `allow-plugins`에 패키지별 허용이 있는 경우(허용 목록에 든 esbuild·sharp·prisma처럼 널리 쓰이는 빌드 패키지는 참고로만).
- **검사 2 — 설치 단계의 비밀값**: 지적할 때: 설치 단계(job·step)에 배포 토큰·클라우드 키·`NPM_TOKEN`(쓰기 권한)이 환경 변수로 들어가는 경우. / 지적하지 않을 때: 비밀값을 배포 단계에만 주는 경우, 읽기 전용 토큰.
- **검사 3 — 새 빌드 플러그인·새 버전**: 지적할 때: 내려받은 수가 적거나 처음 보는 빌드 도구 플러그인을 추가하는 경우, 최소 공개 기간 설정 없이 범위 버전(`^`)으로 새 버전을 바로 받는 CI. / 지적하지 않을 때: 공식 문서가 안내하는 플러그인, 잠금 파일로 고정한 버전.
- **등급**: 위험: 처음 보는 패키지의 설치 스크립트가 비밀값이 있는 환경에서 실행됨, Composer 플러그인 전체 허용. / 주의: CI 설치가 스크립트 허용·허용 목록 없음, 최소 공개 기간 없음. / 참고: 허용 목록에 든 널리 쓰이는 빌드 패키지(esbuild·sharp·prisma), 소스 빌드가 필요한 파이썬 패키지, 빌드 플러그인 출처 확인 필요.
- **보고 예**: `[위험] WS-SUP-003 .github/workflows/release.yml:21 — NPM_TOKEN·AWS 키가 있는 단계에서 npm install 로 설치 스크립트 실행 → npm ci --ignore-scripts 로 바꾸고 비밀값은 배포 단계에만`
- **고칠 방법**: 설치는 스크립트 없이 하고 빌드가 필요한 패키지만 허용 목록으로 다시 빌드하며, 설치 단계에서 비밀값을 빼야 한다.

## 점검 항목

- CI·처음 받는 설치는 설치 스크립트 없이 해야 한다(`npm ci --ignore-scripts`).
- 빌드 스크립트가 필요한 패키지는 허용 목록으로만 실행해야 한다.
- Composer `allow-plugins`에는 쓰는 플러그인만 하나씩 허용해야 한다.
- 파이썬 패키지는 가능하면 휠만 받아 소스 빌드 코드가 돌지 않게 해야 한다.
- 설치 단계에는 배포 토큰·클라우드 키를 넘기지 않아야 한다.
- 새로 공개된 버전은 바로 받지 말고 며칠 기다리게(최소 공개 기간) 설정해야 한다.

## 검토 주의

`ignore-scripts`는 내 `package.json`의 스크립트도 막으므로, 설치 뒤 빌드가 깨지면 `npm rebuild <패키지>`로 필요한 것만 다시 빌드하라고 안내해야 한다(`.npmrc`에 `ignore-scripts=true`를 두면 rebuild도 스크립트를 건너뛰므로 `npm rebuild <패키지> --ignore-scripts=false`). pnpm 10은 `onlyBuiltDependencies`, pnpm 11 이상은 `allowBuilds`를 쓰고, pnpm 11 이상에서는 `strictDepBuilds` 기본값이 true라 목록 밖 패키지에 빌드 스크립트가 있으면 설치가 실패한다. 패키지 이름이 실재하는지는 WS-SUP-001, 잠금 파일은 WS-SUP-004에서 다룬다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-SUP-003.code.shell.md` (Shell) · `WS-SUP-003.code.yaml.md` (YAML) · `WS-SUP-003.code.json.md` (JSON)

기본 심각도: 위험 · CWE-829 · CWE-506
