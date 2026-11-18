<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-AGT-003 Memory Poisoning(에이전트 메모리 오염)

주의 · LLM LLM01 · 에이전트 ASI06

## 체크 항목

- 메모리에는 사용자가 확인한 내용만 저장한다. 웹페이지·메일·도구 응답 내용이 자동으로 메모리에 들어가지 않게 한다.
- 메모리는 사용자별로 나눈다. 여러 사용자가 함께 쓰는 공용 메모리는 두지 않는다. 2025년 MINJA 연구는 평범한 질문만 보내서도 남이 쓰는 에이전트 메모리에 악성 기록을 넣을 수 있음을 보였다.
- 메모리마다 출처·저장 시각을 남기고, 만료 기간과 개수 상한을 둔다.
- 꺼낸 메모리는 지시가 아니라 참고 자료로 넣는다.
- 사용자가 저장된 메모리를 보고 지울 수 있게 한다. 2024년에는 프롬프트 인젝션으로 ChatGPT 메모리에 가짜 기억을 심어 이후 대화를 계속 빼낼 수 있던 문제(SpAIware)가 보고됐다(유출 통로는 2024년 9월 수정).

## 스킬이 잡는 방식

**찾는 곳**: 메모리 저장·검색 호출(`memory.add(`, `save_memory`, `remember(`, `store.put(`, 대화 기록을 벡터 DB에 넣는 코드)과, 꺼낸 메모리를 프롬프트에 넣는 곳.

**검사 1. 남의 글이 자동으로 기억된다**
- 지적할 때: 도구 응답·웹페이지·메일·검색 결과가 사용자 확인 없이 장기 메모리에 저장된다.
- 지적하지 않을 때: 사용자가 직접 확인한 내용만 저장한다. 또는 메모리가 그 세션에서만 쓰이고 끝나면 지워진다.

**검사 2. 기억이 섞이거나 지시가 된다**
- 지적할 때: 메모리에 사용자 구분이 없다. 또는 꺼낸 메모리를 시스템 지시문에 이어 붙인다.
- 지적하지 않을 때: 사용자별로 나뉘고 참고 자료 칸에 들어간다.

**등급**
- 위험: 여러 사용자가 공유하는 메모리에 외부 글이 자동 저장된다.
- 주의: 사용자별 메모리지만 외부 글이 자동 저장된다. 메모리가 시스템 지시문에 들어간다.
- 참고: 만료·개수 상한이나 사용자 삭제 기능이 없다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-AGT-003 agent/memory.py:21 — 웹 검색 결과를 모든 사용자 공용 메모리에 자동 저장 → 사용자별 메모리로 나누고, 사용자가 확인한 내용만 저장`

## 코드

### python

✗ 뚫리는 코드

```python
def after_turn(agent, user_msg: str, tool_results: str, reply: str) -> None:
    agent.memory.add(f"{user_msg}\n{tool_results}\n{reply}")  # 읽은 웹페이지까지 공용 메모리에

def build_prompt(agent, user_msg: str) -> str:
    return SYSTEM + "\n기억해 둔 지시:\n" + "\n".join(agent.memory.search(user_msg)) + "\n" + user_msg
```

✓ 막는 코드

```python
from datetime import datetime, timedelta, timezone

def remember(user_id: str, fact: str, confirmed_by_user: bool) -> None:
    if not confirmed_by_user:  # 사용자가 [기억하기]를 눌렀을 때만
        return
    now = datetime.now(timezone.utc)
    memory.add(user_id=user_id, text=fact[:500], source="user", created_at=now,
               expires_at=now + timedelta(days=90))
    memory.trim(user_id=user_id, keep=100)

def build_messages(user_id: str, user_msg: str) -> list[dict]:
    notes = memory.search(user_id=user_id, query=user_msg, limit=5)  # 이 사용자 것만
    ref = "\n".join(f"- {n.text}" for n in notes)
    return [{"role": "system", "content": SYSTEM},
            {"role": "user", "content": f"참고 메모(지시 아님):\n{ref}\n\n질문: {user_msg}"}]
```
