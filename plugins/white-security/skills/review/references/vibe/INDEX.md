<!-- generated from white-security issues; do not edit -->
# vibe · AI 코딩 도구

- `tools=` `@tool` `function_declarations` `tool_calls` `function_call` → WS-AGT-001: 운영 셸·SQL·삭제·배포 도구를 확인 없이 실행
- `CLAUDE.md` `AGENTS.md` `.claude/skills/` `.claude/agents/` `.claude/**/*.md` `.cursorrules` `.cursor/rules/**` `.mcp.json` → WS-AGT-002: 규칙 파일 속 숨은 문자 지시·외부 전송·보안 끄기
- `memory.add(` `save_memory` `remember(` `store.put(` → WS-AGT-003: 공유 메모리에 외부 글 자동 저장
- `@tool` `tools=` `as_user` `/agent` `/a2a` → WS-AGT-004: 에이전트로 남의 데이터 접근, 인증 없는 쓰기
- `while True` `while not done` `max_iterations` `max_turns` `recursion_limit` → WS-AGT-005: 쓰기·결제·발송 에이전트에 멈춤 조건 없음
- `.claude/settings.json` `extraKnownMarketplaces` `enabledPlugins` `.claude/settings.local.json` `.vscode/settings.json` `.cursor/**` `.mcp.json` `.github/workflows/*.yml` → WS-AGT-006: 커밋된 설정의 전체 허용·확인 건너뛰기
- `.mcp.json` `.cursor/mcp.json` `.vscode/mcp.json` `claude_desktop_config.json` `~/.claude.json` `mcpServers` `list_tools` `listTools` → WS-MCP-001: 도구 설명 속 숨은 지시, 키 받는 서버 버전 미고정
