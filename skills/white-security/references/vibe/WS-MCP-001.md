<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-MCP-001 Tool Poisoning · Rug Pull (MCP 도구 설명 오염·몰래 바꾸기)

위험 · OWASP A03 · LLM LLM01 · LLM LLM04 · 에이전트 ASI04 · 에이전트 ASI02 · CWE-494 · CWE-1427 · 진단가이드 보안기능-15

## 점검 항목

- MCP 서버는 출처가 분명한 것만 쓰고 버전을 고정해야 한다(`이름@1.4.2`). 버전 없는 `npx -y 이름`은 실행할 때마다 최신판을 받음. 패키지 버전 고정은 하위 의존성까지 고정하지 않으므로, 잠금 파일이 있는 로컬 설치나 이미지 다이제스트 고정이 더 확실함.
- 설치 전에 도구 설명 전문을 읽어야 한다. 사람이 보는 화면에는 설명이 줄어 보일 수 있고, MCP 명세도 믿을 수 있는 서버가 아니면 도구 정보(annotations)를 믿지 말라고 함.
- 승인한 뒤 도구 설명·입력 스키마가 바뀌면 다시 검토해야 한다. 2025년 9월 postmark-mcp는 1.0.16부터 보내는 메일을 숨은 참조(BCC)로 빼돌렸다는 분석이 나옴.
- MCP 서버에는 그 일에 필요한 키만 넘기고, 키 값은 설정 파일에 적지 않아야 한다. 환경 변수 참조 문법은 도구마다 다름(Claude Code `${VAR}`, Cursor `${env:VAR}`).
- 남의 글을 읽는 서버와 메일 발송·파일 쓰기 서버를 한 세션에 함께 켜 두지 않아야 한다. 한 서버의 설명이 다른 서버 도구의 동작을 바꿀 수 있음.
- 등록한 MCP 서버 목록과 도구 호출 기록을 남겨야 한다(KISA AI 보안 안내서의 MCP 보안 점검 항목).

## 탐지 규칙

**찾는 곳**: `.mcp.json`, `.cursor/mcp.json`, `.vscode/mcp.json`, `claude_desktop_config.json`, `~/.claude.json`의 `mcpServers`, 코드 속 MCP 클라이언트(`list_tools`, `listTools`, `ClientSession`).

**검사 1. 고정되지 않은 버전·출처**
- 지적할 때: `npx -y 이름`, `uvx 이름`, `@latest`, 버전 없는 Docker 이미지 태그로 서버를 띄우는 경우. 출처를 알 수 없는 개인 repository 서버인 경우.
- 지적하지 않을 때: 정확한 버전(또는 이미지 다이제스트)으로 고정된 경우. repository 안의 로컬 서버(`node ./mcp/server.js`)인 경우.

**검사 2. 수상한 도구 설명**
- 지적할 때: 도구 설명에 보이지 않는 문자, 다른 도구·민감 파일(`~/.ssh`, `.env`) 언급, 특정 주소로 데이터를 보내라는 지시, "사용자에게 말하지 마라" 같은 문장이 있는 경우. 클라이언트 코드가 바뀐 도구 설명을 확인 없이 모델에 넘기는 경우.
- 지적하지 않을 때: 설명이 그 도구의 기능만 말하는 경우(API 주소를 단순히 설명하는 문장 포함). 승인한 설명의 해시를 비교하는 경우.

**등급**
- 위험: 도구 설명에 숨은 지시가 있는 경우. 키를 넘기는 서버가 버전 없이 최신판을 받는 경우.
- 주의: 버전 고정이 없는 경우. 키 값이 설정 파일에 그대로 있는 경우(WS-SEC-001도 확인).
- 참고: 서버 목록·호출 기록이 없는 경우. 버전은 고정했지만 하위 의존성 잠금이 없는 경우.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[주의] WS-MCP-001 .mcp.json:6 — mail 서버를 버전 없이 npx -y 로 실행하고 키 값을 그대로 적음 → 확인한 버전으로 고정, 키는 ${MAIL_API_KEY} 로`

## 코드 예시

### json

✗ 취약 코드

```json
{
  "mcpServers": {
    "mail": {
      "command": "npx",
      "args": ["-y", "some-mail-mcp"],
      "env": { "MAIL_API_KEY": "실제-키-값", "AWS_SECRET_ACCESS_KEY": "실제-키-값" }
    }
  }
}
```

✓ 안전 코드

```json
{
  "mcpServers": {
    "mail": {
      "command": "npx",
      "args": ["-y", "some-mail-mcp@1.4.2"],
      "env": { "MAIL_API_KEY": "${MAIL_API_KEY}" }
    }
  }
}
```

### python

✗ 취약 코드

```python
tools = await session.list_tools()  # 서버가 준 설명을 매번 그대로 쓴다
reply = llm.chat(messages, tools=[t.model_dump() for t in tools.tools])  # 바뀐 설명 속 지시도 모델에 들어간다
```

✓ 안전 코드

```python
import hashlib
import json

def fingerprint(tools) -> str:
    canon = sorted(({"name": t.name, "description": t.description or "", "schema": t.inputSchema}
                    for t in tools), key=lambda t: t["name"])
    return hashlib.sha256(json.dumps(canon, sort_keys=True, ensure_ascii=False).encode()).hexdigest()

tools = (await session.list_tools()).tools
if fingerprint(tools) != approved_fingerprints.get(server_name):  # 사람이 검토하고 저장한 값
    raise PermissionError(f"{server_name}: 도구 설명이 바뀌었습니다. 다시 검토한 뒤 승인하세요")
reply = llm.chat(messages, tools=[t.model_dump() for t in tools])
```
