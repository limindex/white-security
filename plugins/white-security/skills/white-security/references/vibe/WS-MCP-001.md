<!-- generated from white-security issues; do not edit -->
# WS-MCP-001 Tool Poisoning · Rug Pull (MCP 도구 설명 오염·몰래 바꾸기)

## 탐지 규칙

- **찾는 곳**: `.mcp.json`, `.cursor/mcp.json`, `.vscode/mcp.json`, `claude_desktop_config.json`, `~/.claude.json`의 `mcpServers`, 코드 속 MCP 클라이언트(`list_tools`, `listTools`, `ClientSession`).
- **검사 1 — 고정되지 않은 버전·출처**: 지적할 때: `npx -y 이름`, `uvx 이름`, `@latest`, 버전 없는 Docker 이미지 태그로 서버를 띄우는 경우. 출처를 알 수 없는 개인 저장소 서버인 경우. / 지적하지 않을 때: 정확한 버전(또는 이미지 다이제스트)으로 고정된 경우. 저장소 안의 로컬 서버(`node ./mcp/server.js`)인 경우.
- **검사 2 — 수상한 도구 설명**: 지적할 때: 도구 설명에 보이지 않는 문자, 다른 도구·민감 파일(`~/.ssh`, `.env`) 언급, 특정 주소로 데이터를 보내라는 지시, "사용자에게 말하지 마라" 같은 문장이 있는 경우. 클라이언트 코드가 바뀐 도구 설명을 확인 없이 모델에 넘기는 경우. / 지적하지 않을 때: 설명이 그 도구의 기능만 말하는 경우(API 주소를 단순히 설명하는 문장 포함). 승인한 설명의 해시를 비교하는 경우.
- **등급**: 위험: 도구 설명에 숨은 지시가 있는 경우. 키를 넘기는 서버가 버전 없이 최신판을 받는 경우. / 주의: 버전 고정이 없는 경우. 키 값이 설정 파일에 그대로 있는 경우(WS-SEC-001도 확인). / 참고: 서버 목록·호출 기록이 없는 경우. 버전은 고정했지만 하위 의존성 잠금이 없는 경우.
- **보고 예**: `[주의] WS-MCP-001 .mcp.json:6 — mail 서버를 버전 없이 npx -y 로 실행하고 키 값을 그대로 적음 → 확인한 버전으로 고정, 키는 ${MAIL_API_KEY} 로`

## 점검 항목

- MCP 서버는 출처가 분명한 것만 쓰고 버전을 고정해야 하고(`이름@1.4.2`), 가능하면 잠금 파일이 있는 로컬 설치나 이미지 다이제스트로 고정해야 한다.
- 설치 전에 도구 설명 전문을 읽어야 한다.
- 승인한 뒤 도구 설명·입력 스키마가 바뀌면 다시 검토해야 한다.
- MCP 서버에는 그 일에 필요한 키만 넘기고, 키 값은 설정 파일에 적지 않고 도구별 환경 변수 참조(Claude Code `${VAR}`, Cursor `${env:VAR}`)로 넣어야 한다.
- 남의 글을 읽는 서버와 메일 발송·파일 쓰기 서버를 한 세션에 함께 켜 두지 않아야 한다.
- 등록한 MCP 서버 목록과 도구 호출 기록을 남겨야 한다(KISA AI 보안 안내서의 MCP 보안 점검 항목).

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-MCP-001.code.json.md` (JSON) · `WS-MCP-001.code.python.md` (Python)

기본 심각도: 위험 · CWE-494 · CWE-1427
