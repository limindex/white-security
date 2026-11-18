# Dependency and runtime check

Known-vulnerable package versions are found with a scanner, not by memory. Report findings as `WS-SUP-002` (vulnerable dependencies) with the grade from the table at the end.

## Trusted code only

Scanners run only on code the user wrote or trusts, from the main conversation during a review (the reviewer subagent has no shell; the always-on skill never runs scanners). Untrusted or third-party code: run nothing — compare versions with `advisories.md` and say the scan was skipped.

## Consent first

The default scanner, `osv-scanner`, reads lockfiles and manifests as text and runs no project code, but it sends the dependency list (package names and versions) to the OSV service (osv.dev). Ask once per session before the first run:

> 의존성 목록(패키지 이름·버전)을 <서비스>에 보내 알려진 취약점을 조회합니다. 프로젝트 코드는 실행하지 않고, 잠금 파일·고정 버전만 읽는 방식으로 실행합니다. 실행할까요?

If the user declines, compare versions with `advisories.md` only and say the scan was not run. If a scanner is missing, point to its official install page and let the user decide; never install it yourself without a yes. If a scan runs longer than 30 seconds, ask before waiting further.

## Scanners

| Ecosystem | Needs | Command |
|---|---|---|
| Any (npm/pnpm/Yarn, Python, PHP, Dart/Flutter `pubspec.lock`, Go, Rust, Maven) — **default** | lockfiles | `osv-scanner scan source -r .` (v2) or `osv-scanner -r .` (v1) |
| Python | every line pinned `==` (or hashed) | `pip-audit -r requirements.txt --no-deps --disable-pip -f json` (add `--require-hashes` if the file has hashes) |
| PHP | `composer.lock` | `composer audit --no-plugins --no-scripts --format=json` (Composer 2.4+) |
| Container image | an image that already exists | `docker scout cves <image>` or `trivy image <image>` (optional; never build an image for this) |

- Never run plain `pip-audit -r <file>` on a project you are checking: without `--no-deps --disable-pip` it resolves the file with pip in a temporary environment, which is the same as `pip install` (package build code runs). Unpinned `requirements.txt` (`>=`, no version) → don't run pip-audit; use `osv-scanner` and report the missing pins (`WS-SUP-004`).
- `npm audit`, `pnpm audit`, `yarn audit` / `yarn npm audit` are **not** in the default path: they follow the project's `.npmrc`, `.yarnrc`, `.yarnrc.yml` and `package.json` `packageManager` — `yarnPath`/`yarn-path` and Yarn `plugins` run code from the repository, `packageManager` downloads and runs a package-manager version, and a `registry` with `_authToken=${…}`/`npmAuthToken` sends your tokens to that server. Use them only on trusted code, and only after reading those files and finding none of these settings; otherwise use `osv-scanner`.
- Output: don't paste long scanner output (JSON or more than ~100 lines) into the conversation. Turn it into finding lines — package, installed version, advisory id, fixed version — grouped by package, with a count of the rest.
- No lockfile: say so — versions are not pinned, so results are approximate (`WS-SUP-004`).
- Dependabot: if `.github/dependabot.yml` exists, alerts live on GitHub. Reading them (`gh api repos/<owner>/<repo>/dependabot/alerts`) needs network and auth — ask first. Treat open alerts like scanner results.
- Runtimes: read versions (`node --version`, `python3 --version`, `php -v`, `java -version`, Docker `FROM` tags) and compare with `advisories.md`.

## Secrets in git history (optional)

Hardcoded keys that were committed once stay in history. With the user's consent (it reads the whole history; no network), run `gitleaks git --redact --no-banner` (gitleaks 8.19+; older: `gitleaks detect --redact --no-banner`). Report hits as `WS-SEC-001` with masked values only, and tell the user that a leaked key must be revoked and reissued — deleting the line is not enough.

## Reading results

Keep only packages the project actually installs. For each advisory note: package, installed version, advisory id (GHSA/CVE), severity, fixed version, direct or transitive, runtime or dev-only.

| Grade | When |
|---|---|
| 위험 | Critical/High (CVSS ≥ 7) in a runtime dependency, or listed in CISA KEV, or in `advisories.md` |
| 주의 | Moderate, or High in a dev-only/build-only dependency |
| 참고 | Low, or no fixed version yet (note the workaround from the advisory) |

Fix: upgrade to the lowest fixed version in the same major line when possible, update the lockfile in the same commit, and re-run the scanner. Installing or upgrading packages needs the user's separate yes. Don't use `npm audit fix --force` without showing the major-version jumps it makes.

Finding line example: `[위험] WS-SUP-002 package-lock.json:1 — next 15.2.2 (CVE-2025-29927, 미들웨어 인증 우회) → 15.2.3 이상으로 올리고 잠금 파일 함께 커밋`
