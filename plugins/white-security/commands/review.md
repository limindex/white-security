---
description: 프로젝트 보안 검토(읽기 전용) — all · changed · 경로
argument-hint: "[all|changed|<path>]"
disable-model-invocation: true
---

Run a White Security review (read-only). Scope: `$ARGUMENTS` (empty means `all`).

1. If the scope is `changed`, build the file list yourself: changed tracked files (`git diff --name-only HEAD`) plus untracked files (`git ls-files --others --exclude-standard`). In a repository with no commits yet (no `HEAD`), use `git ls-files` plus the untracked list instead. If this is not a git repository or nothing changed, tell the user and ask whether to review `all` instead.
2. Delegate to the `white-security:white-security-reviewer` subagent with the scope (and the file list for `changed`). If it answers `RULES_UNREADABLE`, or subagents aren't available, follow the white-security skill's fallback: run `${CLAUDE_PLUGIN_ROOT}/skills/white-security/references/review.md` yourself, reading each rule file with one quoted `cat "<absolute path>"`, and tell the user in one line how to allow the reviewer to read the rules (`/permissions` or `/add-dir`). Never change settings yourself.
3. Show the report as returned. Do not modify any file during the review.
4. Finally, ask in Korean which numbered items to fix, and whether to save the report to a file (for example `security-review-YYYY-MM-DD.md`). Save only if the user says yes.
