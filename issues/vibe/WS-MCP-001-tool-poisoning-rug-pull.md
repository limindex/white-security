---
id: WS-MCP-001
title: "Tool Poisoning · Rug Pull (MCP)"
slug: mcp-tool-poisoning-rug-pull
group: vibe
severity: high
site_only: false
depth: brief
languages: [json, python]
maps:
  owasp2025: [A03]
  api2023: []
  llm2026: [LLM01, LLM04]
  agentic2026: [ASI04, ASI02]
  asvs5: []
  cwe: [CWE-494, CWE-1427]
  kr_guide: ["보안기능-15"]
  kr_ciip: []
checked: 2026-11-18
sources:
  - title: "Invariant Labs - MCP Security Notification: Tool Poisoning Attacks"
    url: https://invariantlabs.ai/blog/mcp-security-notification-tool-poisoning-attacks
    year: 2025
  - title: "Model Context Protocol Specification - Tools"
    url: https://modelcontextprotocol.io/specification/2025-11-25/server/tools
    year: 2025
  - title: "Snyk - Malicious MCP Server on npm postmark-mcp Harvests Emails"
    url: https://snyk.io/blog/malicious-mcp-server-on-npm-postmark-mcp-harvests-emails/
    year: 2025
  - title: 과학기술정보통신부·KISA 인공지능(AI) 보안 안내서
    url: https://www.kisa.or.kr/2060204/form?postSeq=19
    year: 2025
  - title: "Claude Code Docs - MCP (환경 변수 확장)"
    url: https://code.claude.com/docs/en/mcp
    year: 2026
---

## 한 줄 요약

MCP(AI에게 바깥 도구를 붙이는 표준 연결 방식) 서버의 도구 설명에 AI만 읽는 숨은 지시를 넣는 것을 Tool Poisoning, 승인받은 뒤 업데이트로 설명이나 동작을 몰래 바꾸는 것을 Rug Pull이라고 한다. 둘 다 AI가 내 파일·메일·키를 밖으로 보내게 만들 수 있다(진단가이드 이름: 무결성 검사 없는 코드 다운로드).

## 나는 뭘 하면 되나

```text
.mcp.json 같은 MCP 설정 파일에 등록된 서버를 모두 표로 보여 줘(출처, 버전 고정 여부, 넘기는 키).
버전이 없는 서버는 지금 쓰는 버전으로 고정하고, 키는 값 대신 ${환경 변수}로 바꿔 줘.
각 서버의 도구 설명 전문을 보여 주고, 숨은 문자나 다른 도구·파일을 언급하는 문장이 있으면 표시해 줘.
```

- 업데이트 알림이 오면 바로 올리지 말고, 바뀐 도구 설명과 변경 내역을 먼저 본다.

## 체크 항목

- MCP 서버는 출처가 분명한 것만 쓰고 버전을 고정한다(`이름@1.4.2`). 버전 없이 `npx -y 이름`으로 쓰면 실행할 때마다 최신판을 받는다.
- 설치 전에 도구 설명 전문을 읽는다. 사람이 보는 화면에는 설명이 줄어 보일 수 있다. MCP 명세도 믿을 수 있는 서버가 아니면 도구 정보(annotations)를 믿지 말라고 한다.
- 승인한 뒤 도구 설명·입력 스키마가 바뀌면 다시 검토한다. 2025년 9월 postmark-mcp는 1.0.16부터 보내는 메일을 몰래 숨은 참조(BCC)로 빼돌렸다는 분석이 나왔다.
- MCP 서버에는 그 일에 필요한 키만 넘기고, 키 값은 설정 파일에 적지 않는다.
- 남의 글을 읽는 서버와 메일 보내기·파일 쓰기 서버를 한 세션에 함께 켜 두지 않는다. 한 서버의 설명이 다른 서버 도구의 동작을 바꿀 수 있다.
- 등록한 MCP 서버 목록과 도구 호출 기록을 남긴다(KISA AI 보안 안내서의 MCP 보안 점검 항목).

## 스킬이 잡는 방식

**찾는 곳**: `.mcp.json`, `.cursor/mcp.json`, `.vscode/mcp.json`, `claude_desktop_config.json`, `~/.claude.json`의 `mcpServers`, 코드 속 MCP 클라이언트(`list_tools`, `listTools`, `ClientSession`).

**검사 1. 버전·출처가 고정되지 않았다**
- 지적할 때: `npx -y 이름`, `uvx 이름`, `@latest`, 버전 없는 Docker 이미지 태그로 서버를 띄운다. 또는 출처를 알 수 없는 개인 저장소 서버다.
- 지적하지 않을 때: 정확한 버전(또는 이미지 다이제스트)으로 고정돼 있다. 저장소 안의 로컬 서버(`node ./mcp/server.js`)다.

**검사 2. 도구 설명이 수상하다**
- 지적할 때: 도구 설명에 보이지 않는 문자, 다른 도구·파일(`~/.ssh`, `.env`)·외부 주소 언급, "사용자에게 말하지 마라" 같은 문장이 있다. 클라이언트 코드가 바뀐 도구 설명을 확인 없이 모델에 넘긴다.
- 지적하지 않을 때: 설명이 그 도구의 기능만 말하고, 승인한 설명의 해시를 비교한다.

**등급**
- 위험: 도구 설명에 숨은 지시가 있다. 또는 키를 넘기는 서버가 버전 없이 최신판을 받는다.
- 주의: 버전 고정이 없다. 키 값이 설정 파일에 그대로 있다(WS-SEC-001도 확인).
- 참고: 서버 목록·호출 기록이 없다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[주의] WS-MCP-001 .mcp.json:6 — mail 서버를 버전 없이 npx -y 로 실행하고 키 값을 그대로 적음 → 확인한 버전으로 고정, 키는 ${MAIL_API_KEY} 로`

## 코드

### json

✗ 뚫리는 코드

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

✓ 막는 코드

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

✗ 뚫리는 코드

```python
tools = await session.list_tools()  # 서버가 준 설명을 매번 그대로 쓴다
reply = llm.chat(messages, tools=[t.model_dump() for t in tools.tools])  # 바뀐 설명 속 지시도 모델에 들어간다
```

✓ 막는 코드

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
