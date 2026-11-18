<!-- generated from white-security issues; do not edit -->
# WS-SUP-005 CI/CD Integrity·Unverified Downloads (CI 파이프라인·검증 없는 다운로드)

## 탐지 규칙

- **찾는 곳**: `pull_request_target`, `workflow_run`, `${{ github.event.`, `${{ github.head_ref`, `write-all`, `persist-credentials`, `| bash`, `ADD http`, 그 밖에 .github/workflows의 uses: 줄과 permissions, curl·wget으로 받아 바로 실행하는 줄, Dockerfile의 FROM 태그, CDN script 태그의 integrity 속성, 앱 자동 업데이트 코드.
- **검사 1 — 고정·권한**: 지적할 때: 다른 저장소의 액션을 태그·브랜치(`@v4`·`@main`)로 부르는 경우, 워크플로에 `permissions:`가 없거나 `write-all`인 경우, 비밀값·쓰기 토큰이 있는 job에서 이름 모를 액션을 부르는 경우. / 지적하지 않을 때: 40자리 SHA 고정(태그는 주석), 같은 저장소 안 액션(`./.github/actions/…`), 최소 권한.
- **검사 2 — 신뢰하지 않는 입력 실행**: 지적할 때: `pull_request_target`·`workflow_run`에서 PR의 head 코드를 체크아웃해 설치·빌드·테스트하는 경우, `run:`에 `${{ github.event.pull_request.title }}`·`${{ github.head_ref }}`처럼 남이 정하는 값을 바로 넣는 경우. / 지적하지 않을 때: `pull_request` 트리거(포크 PR엔 비밀값이 없음), 값을 `env:`로 넘겨 `"$TITLE"`처럼 쓰는 경우.
- **검사 3 — 검증 없는 다운로드**: 지적할 때: `curl … | sh`·`| bash`, 버전·해시 없이 받는 도구, `:latest` 기본 이미지, 해시 없는 `ADD` 원격 파일, `integrity` 없는 CDN 스크립트, 서명·해시 확인 없는 앱 자동 업데이트. / 지적하지 않을 때: 버전 고정과 SHA-256 확인(`sha256sum -c`·`ADD --checksum`), digest 고정 이미지, SRI가 있는 CDN 스크립트, 서명 확인(Sigstore cosign 등).
- **등급**: 위험: `pull_request_target`에서 PR 코드 실행, `run:`에 이벤트 값 직접 삽입, 비밀값이 있는 단계의 `curl | sh`. / 주의: 태그로 부르는 외부 액션, `permissions` 없음·`write-all`, 해시 없는 다운로드, `:latest` 이미지. / 참고: SRI 없는 CDN 스크립트, digest를 고정하지 않은 공식 이미지.
- **보고 예**: `[위험] WS-SUP-005 .github/workflows/preview.yml:3 — pull_request_target 에서 PR head 를 체크아웃해 npm install 실행, 포크 PR 이 비밀값을 읽을 수 있음 → pull_request 로 바꾸고 비밀값이 필요한 배포는 합친 뒤 별도 워크플로에서`
- **고칠 방법**: 외부 액션은 SHA로 고정하고 토큰은 최소 권한으로 줄이며, 신뢰하지 않는 코드와 비밀값을 같은 job에 두지 않고, 다운로드는 버전·해시를 고정해 확인한 뒤 실행해야 한다.

## 점검 항목

- 외부 액션은 태그 대신 전체 커밋 SHA로 고정해야 한다.
- 워크플로 토큰은 `permissions:`로 읽기를 기본으로 하고, 쓰기는 필요한 job에만 줘야 한다.
- `pull_request_target`·`workflow_run`에서 외부 PR 코드를 체크아웃·실행하지 않아야 한다.
- `${{ github.event.* }}` 값은 `run:`에 바로 넣지 말고 환경 변수로 넘겨야 한다.
- 내려받는 스크립트·도구·이미지는 버전과 해시(digest)를 고정해 확인해야 한다.
- 외부 CDN 스크립트는 버전을 고정하고 SRI(`integrity` 속성)를 붙여야 한다.

## 검토 주의

SHA로 고정한 액션은 Dependabot 보안 알림이 오지 않으므로(GitHub 문서) Dependabot 버전 업데이트(`github-actions`)를 켜서 고정값을 갱신하라고 안내해야 한다. 의존성 잠금 파일은 WS-SUP-004, 설치 스크립트는 WS-SUP-003, 워크플로 파일 속 비밀값은 WS-SEC-001에서 다룬다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-SUP-005.code.yaml.md` (YAML) · `WS-SUP-005.code.dockerfile.md` (Dockerfile)

기본 심각도: 위험 · CWE-494 · CWE-829 · CWE-77 · CWE-345
