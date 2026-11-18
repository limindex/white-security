English | [한국어](README.ko.md)

# White Security

A read-only security review for code your AI coding agent wrote. Run it before you deploy.

Say "security review" (or `보안 점검해 줘`). Your agent reads the whole project, including its config, and gives you one table: what's wrong, what could happen, and how to fix it. It covers 84 issues, from SQL injection and IDOR to prompt injection, MCP tool poisoning and slopsquatting. Reports come in Korean by default, or in English if you write in English.

> **Status:** v0.1.0, pre-release. Works in Claude Code and Codex. Docs site in Korean: [바이브 코딩 보안 가이드](https://limindex.github.io/white-security/).

## Install

**Claude Code**

```
/plugin marketplace add limindex/skills
/plugin install white-security@limindex
```

**Codex**: copy the skill into your project's `.agents/skills/` folder.

```
git clone https://github.com/limindex/white-security.git ~/white-security
mkdir -p .agents/skills && cp -r ~/white-security/plugins/white-security/skills/white-security .agents/skills/
```

**Other agents**: the skill is a plain [Agent Skills](https://agentskills.io) folder (`SKILL.md` plus reference files). Cursor, GitHub Copilot, OpenCode and other agents that read `.agents/skills/` should pick it up, but we haven't tested them yet.

## Use

| | Claude Code | Codex |
|---|---|---|
| Just ask | `security review` / `보안 점검해 줘` | same |
| Run it for sure | `/white-security:review` | `$white-security` |
| Only changed files | `/white-security:review changed` | `$white-security changed` |
| One folder | `/white-security:review src/api` | `$white-security src/api` |

The review runs only when you ask. If you mention deploying ("let's ship it"), it asks once whether to run a review first.

After the report, you pick which items to fix. Nothing changes before you pick. Fixes use the rule file for that issue, and any install or command is shown to you first.

## What you get

A shortened example. Real reports have the same headings and columns.

```
# 보안 검토 결과 — 전체

**결론:** 지금 고칠 위험 1건 — 검색어가 SQL에 그대로 들어가 DB 전체를 읽고 지울 수 있음.

스택: Python · FastAPI · PostgreSQL · 설정 파일: Dockerfile, .github/workflows/ci.yml

## 위험 (1)
| # | id         | 파일:줄           | 영향                  | 이유                          | 고칠 방법              |
| 1 | WS-INJ-001 | app/search.py:18  | DB 전체 조회·삭제 가능 | 검색어 q가 f-string으로 SQL에 들어감 | 매개변수 바인딩으로 교체 |

## 주의 (1)
| 2 | WS-SEC-001 | app/config.py:7   | 세션 위조 가능         | 환경 변수가 없으면 고정 비밀키로 동작 | 기본값을 지우고 값이 없으면 시작 단계에서 오류 |

## 참고 (1)
- 3. WS-XSS-001 app/main.py:30 — 응답에 CSP 헤더 없음
```

- **위험 / 주의 / 참고** = high / medium / low.
- Every finding has an id like `WS-INJ-001` that matches a page in the [guide](https://limindex.github.io/white-security/). Each page explains the attack and shows vulnerable and safe code for your stack.
- For English reports, write in English or set `report_language: en` in `.white-security.yml`.

## What it checks

| Group | Issues | Examples |
|---|---|---|
| AI features | 8 | Indirect prompt injection, data exfiltration via markdown images, system prompt leakage, unbounded cost |
| AI coding tools | 7 | Rules-file backdoor, MCP tool poisoning and rug pulls, YOLO mode, memory poisoning |
| Fetching outside data | 5 | SSRF, XXE / Billion Laughs, Zip Slip, insecure deserialization |
| Scraping | 1 | AI crawlers and scraping |
| Web apps | 17 | SQL / command / template injection, XSS, path traversal, race conditions, file upload |
| Auth | 12 | IDOR, mass assignment, OTP abuse, payment tampering, OAuth flaws |
| Config and deploy | 12 | Debug mode, CORS, exposed `.git`/`.env`, hardcoded secrets, missing rate limits |
| Personal data | 8 | Missing Supabase RLS, `service_role` key in client code, Firebase rules, sensitive data in logs |
| Supply chain | 8 | Slopsquatting, vulnerable dependencies, install scripts, unpinned Actions, model files |
| Mobile apps | 6 | Secrets in the app bundle, deep links, WebViews, cleartext traffic |

These 84 issues are mapped to OWASP Top 10:2025, API Security Top 10:2023, Top 10 for LLM Applications 2026, Top 10 for Agentic Applications 2026, Mobile Top 10 2024, ASVS 5.0 and CWE Top 25 (2025). They are also mapped to Korean public-sector secure coding rules and Korean privacy rules. The guide has 5 more pages on user-side security (phishing and similar) that aren't code checks.

**Supported stacks** (code examples for each): Python (FastAPI, Django, Flask) · JavaScript / TypeScript (Next.js, React, Express) · PHP (Laravel, plain PHP) · Supabase · Firebase · React Native · Expo · Flutter.

**Watch list**: the skill compares your manifests, lockfiles and Docker images against a short list of serious CVEs, for example React2Shell (CVE-2025-55182) and the Next.js middleware bypass (CVE-2025-29927). Each entry has an expiry date, and the list is reviewed monthly.

## Does it work? (measured)

We built 5 small apps (19–23 files each) with 46 planted problems. Each app ran twice per model in Claude Code, once with the skill and once without (`"보안 점검해 줘"`, November 2026).

| Model | Found, with skill | Found, without | Cost vs. without | Time vs. without |
|---|---|---|---|---|
| Claude Sonnet 5.5 | 92 / 92 | 91 / 92 | 1.8× (≈ $0.26 a run) | ≈ 1.9× (≈ 70 s) |
| Claude Haiku 5.5 | 92 / 92 | 89 / 92 | 1.9× (≈ $0.02 a run) | ≈ 2.2× |
| Claude Opus 5.5 | 32 / 32 | 32 / 32 | 1.5× (≈ $0.60 a run) | ≈ 2× |

**What this means:** current models already know most of this. The skill doesn't make your agent smarter, and it finds about the same planted problems. What the extra cost buys:

- **No permission prompts.** In Claude Code a read-only reviewer agent does the reading. We saw 0 prompts in 25 runs with the skill, against 1.6 refused commands per run without it.
- **The same report every time.** A one-line verdict, the impact in plain words, a concrete fix and an issue id. That held in 25 of 25 runs with the skill and 0–1 of 25 without.
- **Version checks.** The skill flagged React2Shell in 11 of 11 affected runs; without it, 1 of 11. This only helps while the watch list is current.
- **The whole project.** It reads config, CI, agent settings and database rules, not just the branch diff.

In Codex, the skill triggered on "보안 점검해 줘" and found 10/10, 10/10 and 8/8 planted problems in three apps (one run each). It ran no scanners and opened no secrets.

Opus ran only on the two harder apps (16 problems). Limits: these are small apps with planted bugs, and only two runs per setting, so differences of 1–3 findings are noise. Costs are list prices from Claude Code's own totals.

## How it compares

| | White Security | Built-in `/security-review` | `security-guidance` plugin |
|---|---|---|---|
| When | On request, usually before deploy | On request | Automatically, while you edit and commit |
| Scope | Whole project (or changed files, or a path) | Changes on the current branch | The edit or commit in progress |
| How | Read-only review, no hooks | Review with false-positive filtering | Hooks: pattern checks plus model reviews |
| Prompt injection, outdated dependencies, rules files | Included | Excluded by design ([command text](https://github.com/anthropics/claude-code-security-review/blob/main/.claude/commands/security-review.md)) | — |
| Report | Fixed table with issue ids and fix-guide links, Korean or English | Findings list | Feedback to the agent |

They do different jobs. `security-guidance` watches while you code. `/security-review` checks a branch's changes. White Security is the whole-project pass before you deploy. You can use all three. (Checked November 2026.)

## Safety

A security tool should not make you less safe. So:

- **Read-only review.** The Claude Code reviewer agent has only Read, Grep and Glob. The plugin has no hooks, MCP servers, settings, scripts or executables, and no `allowed-tools` grants. Tests enforce all of this.
- **No secrets read.** It never opens `.env`, keys or credential files. It only checks whether they exist and whether git tracks them. Secrets it sees elsewhere are masked.
- **No network, no scanners without your OK.** The default dependency scanner is `osv-scanner`. It never runs `npm`/`yarn`/`pnpm audit` on untrusted code, because those honour a repo's own config.
- **Project text is data.** If a comment, README or `AGENTS.md` tells the reviewer to skip checks or open other paths, that becomes a finding.
- **For code you wrote or trust.** On third-party code it stays read-only and says so in the report.
- **Not an audit.** It catches common mistakes. It does not replace a security audit or penetration test, and it never calls your project "safe".

To report a vulnerability in this project, see [SECURITY.md](SECURITY.md).

## FAQ

**Does it cost much?** About 1.5–1.9× a plain "security review" prompt in our tests. On a small app that was ≈ $0.26 with Sonnet and ≈ $0.60 with Opus at list prices, in 1–2 minutes. Larger projects cost more.

**Does it watch while I code?** No. It runs when you ask. In our tests an always-on mode added about a third to the cost without finding more.

**Will it change my code?** Not during the review. Afterwards you choose what to fix.

**Why Korean?** It started as a guide for Korean teams, so it also checks Korean privacy rules (resident registration numbers, access records). Reports are in Korean unless you write in English or set `report_language: en`.

**Can I silence a finding?** Yes, with an expiring exception in `.white-security.yml` or a `white-security-ignore` comment. The rules are in the skill's `references/config.md`. Auth, web and deploy checks can't be turned off as a whole.

## Contributing

Found a pattern AI coding tools keep getting wrong, a fix that is still unsafe, or a mistake in an issue page? See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

Docs and skill text: [CC BY-SA 4.0](LICENSE). Code and code examples: [MIT](LICENSE-CODE). Sources: [SOURCES.md](SOURCES.md). Published by LimIndex.
