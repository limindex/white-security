<!-- generated from white-security issues; do not edit -->
# vibe · AI 코딩 도구

When: AI 코딩 도구에 코드 작성·설정 변경·명령 실행을 맡기는 경우
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.

- `tools=` `@tool` `function_declarations` `tool_calls` `function_call` → WS-AGT-001 Excessive Agency
- `CLAUDE.md` `AGENTS.md` `GEMINI.md` `.cursorrules` `.cursor/rules/**` `.windsurfrules` `.github/copilot-instructions.md` `.claude/**/*.md` → WS-AGT-002 Rules File Backdoor
- `memory.add(` `save_memory` `remember(` `store.put(` → WS-AGT-003 Memory Poisoning
- `@tool` `tools=` `as_user` `/agent` `/a2a` → WS-AGT-004 Confused Deputy
- `while True` `while not done` `max_iterations` `max_turns` `recursion_limit` → WS-AGT-005 Cascading Failures · Rogue Agent
- `.claude/settings.json` `.claude/settings.local.json` `.vscode/settings.json` `.cursor/**` `.mcp.json` `.github/workflows/*.yml` → WS-AGT-006 YOLO Mode
- `.mcp.json` `.cursor/mcp.json` `.vscode/mcp.json` `claude_desktop_config.json` `~/.claude.json` `mcpServers` `list_tools` `listTools` → WS-MCP-001 Tool Poisoning · Rug Pull
