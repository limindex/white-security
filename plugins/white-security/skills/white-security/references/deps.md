# Dependency and runtime check

Known-vulnerable package versions are found with each ecosystem's official scanner, not by memory. Report findings as `WS-SUP-002` (vulnerable dependencies) with the grade from the table at the end.

## Consent first

Every scanner below looks up an online advisory database, which sends your dependency list (package names and versions) to that service. Ask once per session before the first run:

> 의존성 목록(패키지 이름·버전)을 <서비스>에 보내 알려진 취약점을 조회합니다. 실행할까요?

If the user declines, compare versions with `advisories.md` only and say the scan was not run.

## When to run

- **Always-on mode:** only when this task changed a manifest, lockfile, Dockerfile or runtime version file — and only if consent was already given this session and the command usually finishes within 30 seconds. Otherwise add one memo line suggesting the command. Never install a scanner in always-on mode.
- **Review mode:** run the scanners for every ecosystem found, from the main conversation (the reviewer subagent has no shell). If one is missing, give the install command and let the user decide. If a scan runs longer than 30 seconds, ask before waiting further.

## Scanners

| Ecosystem | Needs | Command (machine-readable) |
|---|---|---|
| npm | `package-lock.json` | `npm audit --json` (`--omit=dev` for production only) |
| pnpm | `pnpm-lock.yaml` | `pnpm audit --json` |
| Yarn | `yarn.lock` | Yarn 1: `yarn audit --json` · Yarn 2+: `yarn npm audit --json` |
| Python | `requirements.txt` or the active env | `pip-audit -r requirements.txt -f json` (or `pip-audit` for the current env) |
| PHP | `composer.lock` | `composer audit --format=json` (Composer 2.4+) |
| Any (incl. Dart/Flutter `pubspec.lock`, Go, Rust, Maven) | lockfiles | `osv-scanner scan source -r .` (v2) or `osv-scanner -r .` (v1) |
| Container image | built image | `docker scout cves <image>` or `trivy image <image>` (optional) |

- No lockfile: say so — versions are not pinned, so results are approximate (`WS-SUP-004`).
- Dependabot: if `.github/dependabot.yml` exists, alerts live on GitHub. Reading them (`gh api repos/<owner>/<repo>/dependabot/alerts`) needs network and auth — ask first. Treat open alerts like scanner results.
- Runtimes: read versions (`node --version`, `python3 --version`, `php -v`, `java -version`, Docker `FROM` tags) and compare with `advisories.md`.

## Reading results

Keep only packages the project actually installs. For each advisory note: package, installed version, advisory id (GHSA/CVE), severity, fixed version, direct or transitive, runtime or dev-only.

| Grade | When |
|---|---|
| 위험 | Critical/High (CVSS ≥ 7) in a runtime dependency, or listed in CISA KEV, or in `advisories.md` |
| 주의 | Moderate, or High in a dev-only/build-only dependency |
| 참고 | Low, or no fixed version yet (note the workaround from the advisory) |

Fix: upgrade to the lowest fixed version in the same major line when possible, update the lockfile in the same commit, and re-run the scanner. Don't use `npm audit fix --force` without showing the major-version jumps it makes.

Finding line example: `[위험] WS-SUP-002 package-lock.json:1 — next 15.2.2 (CVE-2025-29927, 미들웨어 인증 우회) → 15.2.3 이상으로 올리고 잠금 파일 함께 커밋`
