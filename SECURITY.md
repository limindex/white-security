# Security Policy

[한국어](#한국어)

## Scope

This policy covers:

- The documentation site generator in this repository (`build.php`, `index.php`, `src/`, `templates/`, `assets/`) and the static site it builds.
- The plugin in `plugins/white-security/`: the `white-security` skill (`/white-security:review` in Claude Code), the read-only security reviewer agent for big projects, and the rule files used for fixes.
- Content errors in the issue documents (`issues/`) or the rule files that could lead to insecure advice, for example a "safe" code example that is still exploitable, or skill guidance that makes the review skip a real risk.
- Marketplaces or plugins that impersonate this project.

## Reporting a vulnerability

Report privately through GitHub Security Advisories: open the repository's **Security** tab and choose **Report a vulnerability**, or go to <https://github.com/limindex/white-security/security/advisories/new>.

Please don't open a public issue, discussion or pull request for a vulnerability that isn't fixed yet.

Helpful details:

- The affected file, and the plugin version (from `plugins/white-security/.claude-plugin/plugin.json`) or commit.
- Steps to reproduce, using your own test project.
- The impact you observed.
- A suggested fix, if you have one.

Reports in Korean or English are both fine.

## Out of scope

- Vulnerabilities in projects you reviewed with the plugin. Report those to the project's owners.
- Findings the review missed or reported wrongly in your own code, when no rule gives insecure advice. The review is a guided model review and doesn't replace a security audit. Open a regular issue instead.
- Content errors with no security impact (typos, broken links). Open a regular issue.
- Vulnerabilities in Claude Code, GitHub, GitHub Pages or other platforms. Report them to those vendors.
- Headers that a static host can't set (such as `frame-ancestors` on GitHub Pages), missing rate limits on the static site, and scanner output without a demonstrated impact.
- Social engineering, physical attacks and denial of service.

## What to expect

This is a personal project run by a single maintainer. Reports are reviewed during the monthly maintenance pass, and critical issues sooner when possible. There is no guaranteed response time.

Progress is shared inside the private advisory. Fixes ship as a new plugin version with a `CHANGELOG.md` entry. If you want, you are credited once the fix is published.

## No bounty

There is no bug bounty and no payment for reports.

## Safe harbor

No legal action will be taken or supported against good-faith security research that follows this policy:

- Test only your own installations: the plugin on your own machine, a local build of the site, or your own fork.
- Don't test against other people's sites, systems or repositories.
- Don't access, change or delete data that isn't yours.
- Keep the details private until a fix is published.

This applies only to this project. It can't authorize testing of third-party systems, including GitHub.

## Supported versions

Only the latest plugin version receives fixes. Update with `/plugin marketplace update limindex`.

---

## 한국어

### 범위

- 이 저장소의 문서 사이트 생성기(`build.php`·`index.php`·`src/`·`templates/`·`assets/`)와 생성된 정적 사이트.
- `plugins/white-security/` 의 plugin: `white-security` 스킬(Claude Code 에서는 `/white-security:review`), 큰 프로젝트용 읽기 전용 보안 검토 에이전트, 고칠 때 쓰는 규칙 파일.
- 이슈 문서(`issues/`)·규칙 파일의 내용 오류 중 안전하지 않은 조언으로 이어질 수 있는 것. 예: 여전히 공격 가능한 안전 코드 예시, 실제 위험을 건너뛰게 만드는 스킬 안내.
- 이 프로젝트를 사칭하는 marketplace·plugin.

### 제보 방법

GitHub Security Advisories 비공개 제보만 사용. 저장소 **Security** 탭에서 **Report a vulnerability** 를 누르거나 <https://github.com/limindex/white-security/security/advisories/new> 로 이동.

고쳐지지 않은 취약점은 공개 Issue·Discussion·Pull Request 로 올리지 않아야 한다.

있으면 좋은 내용:

- 해당 파일, 플러그인 버전(`plugins/white-security/.claude-plugin/plugin.json`) 또는 커밋.
- 본인 시험용 프로젝트로 재현하는 단계.
- 확인한 영향.
- 고칠 방법(있으면).

한국어·영어 모두 가능.

### 제보 대상이 아닌 것

- 플러그인으로 검토한 다른 프로젝트의 취약점. 그 프로젝트 관리자에게 제보.
- 내 코드에서 검토가 놓치거나 잘못 지적한 결과 중 안전하지 않은 조언을 하는 규칙이 원인이 아닌 것. 검토는 안내에 따른 모델 검토이며 보안 감사를 대신하지 않음. 일반 Issue 로 제보.
- 보안 영향이 없는 내용 오류(오타·깨진 링크). 일반 Issue 로 제보.
- Claude Code·GitHub·GitHub Pages 등 플랫폼 자체의 취약점. 각 제공사에 제보.
- 정적 호스팅에서 설정할 수 없는 헤더(GitHub Pages 의 `frame-ancestors` 등), 정적 사이트의 요청 횟수 제한 부재, 실제 영향이 확인되지 않은 스캐너 결과.
- 사회공학, 물리적 공격, 서비스 거부(DoS).

### 응답

관리자 한 명이 운영하는 개인 프로젝트. 제보는 월간 정기 점검 때 확인하고, 심각한 문제는 가능하면 더 빨리 확인. 정해진 응답 기한 없음.

진행 상황은 비공개 advisory 안에서 공유. 수정은 새 플러그인 버전과 `CHANGELOG.md` 기록으로 배포. 원하면 수정 공개 뒤 제보자 이름 표기.

### 보상

버그 바운티·보상 없음.

### 선의의 연구(Safe harbor)

이 정책을 따른 선의의 보안 연구에는 법적 조치를 하거나 지원하지 않음. 조건:

- 본인 설치본(내 PC 의 플러그인, 로컬에서 빌드한 사이트, 내 fork)에서만 시험해야 한다.
- 다른 사람의 사이트·시스템·저장소를 대상으로 시험하지 않아야 한다.
- 본인 것이 아닌 데이터에 접근하거나 바꾸거나 지우지 않아야 한다.
- 수정이 공개될 때까지 세부 내용을 공개하지 않아야 한다.

이 약속은 이 프로젝트에만 해당하며, GitHub 를 포함한 제3자 시스템의 시험을 허락하지 않음.

### 지원 버전

최신 플러그인 버전만 수정. 업데이트는 `/plugin marketplace update limindex`.
