---
id: WS-AGT-001
title: Excessive Agency (과도한 권한 위임)
slug: excessive-agency
group: vibe
severity: high
grade_hint: "운영 셸·SQL·삭제·배포 도구를 확인 없이 실행"
always_on: "`tools=` `@tool` → 에이전트에는 필요한 도구만 주고, 삭제·배포·결제 도구는 사람 확인 뒤 실행해야 한다."
site_only: false
depth: brief
languages: [python]
maps:
  owasp2025: []
  api2023: []
  llm2026: [LLM03]
  agentic2026: [ASI02, ASI09]
  asvs5: [V13.2.2]
  cwe: [CWE-250]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, 도구를 쓰는 AI 에이전트]
severity_reason: "영향: 삭제·배포·결제 등 되돌릴 수 없는 작업 · 악용: AI의 착각이나 숨은 지시 하나로 가능"
sources:
  - title: "OWASP Top 10 for LLM Applications 2026 - LLM03:2026 Excessive Agency"
    url: https://genai.owasp.org/resource/owasp-genai-llm-top-10-2026/
    year: 2026
  - title: OWASP AI Agent Security Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/AI_Agent_Security_Cheat_Sheet.html
    year: 2026
  - title: "Model Context Protocol Specification - Tools (human in the loop)"
    url: https://modelcontextprotocol.io/specification/2025-11-25/server/tools
    year: 2025
  - title: "The Register - Vibe coding service Replit deleted user's production database"
    url: https://www.theregister.com/2025/07/21/replit_saastr_vibe_coding_incident/
    year: 2025
---

## 요약

AI 에이전트(여러 단계를 스스로 처리하는 AI)에게 삭제·배포·결제 도구를 넉넉히 주고 확인 없이 실행하게 할 때, AI의 착각이나 속임수 하나로 되돌릴 수 없는 일이 생기는 문제. Excessive Agency(과도한 권한 위임)라 부름.

## 위험 배경

- 2025년 7월 Replit의 AI 에이전트가 "변경 금지" 지시를 어기고 운영 DB를 지운 사례 보도.

## 대응 방법

```text
에이전트가 쓸 수 있는 도구 목록과 각 도구가 쓰는 계정 권한을 표로 보여 줘.
셸·SQL을 통째로 실행하는 도구는 하는 일이 정해진 좁은 도구로 바꿔 줘.
지우기·배포·결제·메일 보내기는 내가 실제 명령과 대상을 보고 [확인]을 눌러야 실행되게 해 줘.
```

- 에이전트에게 "테스트 주문 하나 취소해 줘"라고 요청했을 때, 바로 취소되지 않고 승인 대기 목록에 올라와야 한다.

## 점검 항목

- 에이전트에게는 그 일에 꼭 필요한 도구만 주어야 한다(최소 권한: 필요한 만큼만 권한을 주는 원칙). 범용 `run_shell`·`run_sql` 대신 하는 일이 정해진 도구를 만들어야 한다.
- 삭제·배포·결제·메일 발송처럼 되돌리기 어려운 도구는 사람이 [확인]을 눌러야 실행되어야 하고, 확인 창에는 AI의 설명이 아니라 실제 명령·대상·금액을 보여야 한다.
- 에이전트가 쓰는 DB·클라우드 계정은 읽기 전용이나 좁은 권한이어야 하고, 개발용 에이전트가 운영 DB 접속 정보를 쓸 수 없어야 한다.
- 백업과 되돌리기 방법을 먼저 마련해야 한다.
- 도구 호출(누가·무엇을·언제·결과)을 기록해야 한다.

## 탐지 규칙

- **찾는 곳**: 에이전트 도구 정의(`tools=`, `@tool`, `function_declarations`, MCP 서버의 도구 등록)와 도구 호출 실행부(`tool_calls`, `function_call`), 도구가 쓰는 DB·클라우드 계정 설정.
- **검사 1 — 지나치게 넓은 도구**: 지적할 때: 모델이 셸 명령·SQL·파일 경로·URL을 자유롭게 넘기는 범용 도구가 있는 경우. / 지적하지 않을 때: 인자가 정해진 형식(ID·열거값)이고 서버가 대상 범위를 고정하는 경우. 격리된 샌드박스(운영 자원 접근 없는 컨테이너) 안에서만 실행되는 경우.
- **검사 2 — 확인 없는 되돌릴 수 없는 작업**: 지적할 때: 삭제·배포·결제·외부 발송 도구가 사람 확인 없이 실행되는 경우. / 지적하지 않을 때: 승인 대기열·확인 버튼을 거치는 경우. 시험용 데이터만 건드리도록 코드로 고정된 경우.
- **등급**: 위험: 운영 자원에 닿는 셸·SQL·삭제·배포 도구가 확인 없이 실행되는 경우. / 주의: 도구 계정 권한이 필요보다 넓은 경우(관리자 DB 계정 등). 확인 창이 AI 설명만 보여 주는 경우. / 참고: 도구 호출 기록이 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-AGT-001 agent/tools.py:12 — run_sql 도구가 운영 관리자 계정으로 모델이 쓴 SQL 을 확인 없이 실행 → 읽기 전용 조회 도구로 좁히고, 변경 도구는 승인 대기열로`

## 코드 예시

### python

✗ 취약 코드

```python
TOOLS = {
    "run_shell": lambda cmd: subprocess.run(cmd, shell=True, capture_output=True, text=True).stdout,
    "run_sql": lambda q: admin_db.execute(q).fetchall(),  # 운영 DB 관리자 계정
}
for call in reply.tool_calls:
    result = TOOLS[call.name](**call.arguments)  # 무엇이든 묻지 않고 실행한다
```

✓ 안전 코드

```python
READ_ONLY = {
    "list_orders": lambda status: readonly_db.list_orders(status=str(status)),  # 읽기 전용 계정
    "git_status": lambda: subprocess.run(["git", "status", "--short"], capture_output=True, text=True).stdout,
}
NEEDS_APPROVAL = {"cancel_order", "deploy"}

for call in reply.tool_calls:
    if call.name in READ_ONLY:
        result = READ_ONLY[call.name](**call.arguments)
    elif call.name in NEEDS_APPROVAL:  # 사람이 실제 대상·인자를 보고 [확인]해야 실행된다
        approvals.request(user_id=user.id, tool=call.name, args=call.arguments)
        result = "승인 대기 중"
    else:
        result = "허용되지 않은 도구"
```
