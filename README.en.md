<div align="center">

<img src="assets/img/favicon.svg" width="72" height="72" alt="White Security logo">

# White Security

**A read-only security review for code your AI coding agent wrote.<br>Run it before you deploy.**

[![CI](https://github.com/limindex/white-security/actions/workflows/ci.yml/badge.svg)](https://github.com/limindex/white-security/actions/workflows/ci.yml)
[![Version](https://img.shields.io/badge/version-0.1.1-2f6feb)](CHANGELOG.md)
[![License](https://img.shields.io/badge/license-CC%20BY--SA%204.0%20%2B%20MIT-555)](#license)

[한국어](README.md) | **English**

</div>

White Security is a skill that finds common security mistakes in code written by AI coding agents. Ask for a "security review" and it reads the whole project, config included, without changing anything, then reports each problem with its impact and a fix. Claude Code's built-in `/security-review` checks a branch's changes; White Security is the whole-project pass before you deploy.

Every issue has a page with vulnerable and safe code examples in the [guide](https://limindex.github.io/white-security/) (in Korean).

## Install

**Claude Code**

```
/plugin marketplace add limindex/skills
/plugin install white-security@limindex
```

**Codex**

Run this in your project folder. The skill is copied into `.agents/skills/`, where Codex picks it up.

```
git clone https://github.com/limindex/white-security.git ~/white-security
mkdir -p .agents/skills && cp -r ~/white-security/plugins/white-security/skills/white-security .agents/skills/
```

Other agents that read `.agents/skills/` (Cursor, GitHub Copilot, OpenCode and others) should work the same way, but we haven't tested them yet.

## Usage

| | Claude Code | Codex |
|---|---|---|
| Whole project | `/white-security:review` | `$white-security` |
| Changed files | `/white-security:review changed` | `$white-security changed` |
| One folder | `/white-security:review src/api` | `$white-security src/api` |

In both, just asking for a "security review" runs the whole-project check. The review runs only when you ask and changes no files. After the report, you pick which items to fix.

## Example

**결론 (verdict):** 1 high-severity issue to fix now — the search term goes straight into SQL, so the whole database can be read or deleted.

| # | id | 파일:줄 | 영향 | 이유 | 고칠 방법 |
|---|---|---|---|---|---|
| 1 | [WS-INJ-001](https://limindex.github.io/white-security/issues/WS-INJ-001) | `app/search.py:18` | DB 유출 | 검색어를 SQL에 붙임 | 바인딩 |
| 2 | [WS-SEC-001](https://limindex.github.io/white-security/issues/WS-SEC-001) | `app/config.py:7` | 로그인 위조 | 비밀키 기본값 노출 | 기본값 삭제 |

Reports are grouped by severity (위험 / 주의 / 참고 = high / medium / low) and written in Korean by default. Ask in English, or set `report_language: en` in `.white-security.yml`, for an English report.

## What it checks

| Group | Issues | Examples |
|---|---|---|
| AI features | 8 | Indirect prompt injection, data exfiltration via markdown images, unbounded cost |
| AI coding tools | 7 | Rules-file backdoor, MCP tool poisoning, YOLO mode |
| Fetching outside data | 5 | SSRF, XXE, Zip Slip, insecure deserialization |
| Scraping | 1 | AI crawlers and scraping |
| Web apps | 17 | SQL and command injection, XSS, path traversal, file upload |
| Auth | 12 | IDOR, mass assignment, OTP abuse, payment tampering |
| Config and deploy | 12 | Debug mode, exposed `.env`, hardcoded secrets, missing rate limits |
| Personal data | 8 | Missing Supabase RLS, exposed `service_role` key, sensitive data in logs |
| Supply chain | 8 | Slopsquatting, vulnerable dependencies, unpinned Actions |
| Mobile apps | 6 | Secrets in the app bundle, deep links, WebViews |

Mapped to OWASP Top 10:2025, the LLM and Agentic Top 10 2026, ASVS 5.0 and CWE Top 25. It also covers Korean standards: the 49 secure coding weaknesses from the Ministry of the Interior and Safety, the 21 web checks for critical information infrastructure, and Korean privacy rules (resident registration numbers, encryption of unique identifiers, access records). See the [coverage table](https://limindex.github.io/white-security/coverage). Supported stacks: Python, JavaScript/TypeScript, PHP, Supabase, Firebase, React Native, Expo and Flutter. A watch list of serious CVEs is checked against your manifests and lockfiles.

## Measured

We planted 46 problems in 5 small apps and ran Claude Code with and without the skill (November 2026, two runs per app).

| Model | Skill on | Skill off | Per run | Cost | Time |
|---|:---:|:---:|:---:|:---:|:---:|
| Sonnet 5.5 | **92 / 92** | 91 / 92 | ≈ $0.26 | 1.8× | ≈ 1.9× |
| Haiku 5.5 | **92 / 92** | 89 / 92 | ≈ $0.02 | 1.9× | ≈ 2.2× |
| Opus 5.5 | **32 / 32** | 32 / 32 | ≈ $0.60 | 1.5× | ≈ 2× |

It finds about as much as asking without the skill. The extra cost buys a review with no permission prompts, the same report format every time, and CVE checks against the watch list. The `changed` scope needs one git command approved to list changed files.

## Safety

- The review is read-only. The plugin has no hooks, MCP servers, settings changes or executables.
- It never opens `.env`, keys or credential files, and masks secrets it sees.
- Network calls and scanners run only after you agree.
- If text in the project tells the reviewer to skip checks, that instruction is reported as a finding.

White Security has no server of its own; your code goes only to the model provider of the agent you already use. It catches common mistakes and does not replace a security audit or penetration test.

## Contributing and reporting

- How to contribute: [CONTRIBUTING.md](CONTRIBUTING.md)
- Reporting a vulnerability in this project: [SECURITY.md](SECURITY.md)
- Changes: [CHANGELOG.md](CHANGELOG.md)

## License

Docs and skill text: [CC BY-SA 4.0](LICENSE). Code and code examples: [MIT](LICENSE-CODE). Published by LimIndex.
