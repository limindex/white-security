# white-security

Read-only security review for code your AI coding agent wrote, run on request, typically before deploy. Ask "security review" (or `보안 점검해 줘`) or run `/white-security:review [all | changed [<base>] | <path>]`.

The review reads the whole project, including config, CI, agent settings and database rules, and returns one table per severity (위험 / 주의 / 참고 = high / medium / low). Each row has the impact in plain words, a concrete fix and an issue id that matches a page in the guide. Reports are Korean by default, or English if you ask in English or set `report_language: en`.

- `skills/white-security/`: the skill (Agent Skills format). Rule files in `references/` are opened only when you pick an item to fix.
- `agents/white-security-reviewer.md`: the read-only reviewer that does the reading in Claude Code (tools: Read, Grep, Glob).
- `commands/review.md`: the `/white-security:review` command.

No hooks, MCP servers, settings, scripts or `allowed-tools` grants. It never reads `.env` or key files, and it runs no scanners or network calls without your consent. It is not a security audit.

Docs and full README: https://github.com/limindex/white-security · Text CC BY-SA 4.0, code MIT.
