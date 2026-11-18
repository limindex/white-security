<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-AGT-005 Cascading Failures · Rogue Agent(멈추지 않는 에이전트)

주의 · LLM LLM06 · 에이전트 ASI08 · 에이전트 ASI10 · ASVS V15.2.2 · CWE-834 · CWE-770 · 진단가이드 시간 및 상태-2

## 점검 항목

- 에이전트 반복에는 최대 단계 수·전체 시간·토큰·비용 한도를 두어야 한다. Anthropic도 최대 반복 횟수 같은 멈춤 조건을 권함.
- 같은 도구를 같은 인자로 되풀이하거나 실패가 이어지면 멈추어야 한다(circuit breaker: 연속 실패 때 호출을 끊는 장치).
- 운영자가 바로 끌 수 있는 멈춤 스위치(kill switch)가 있어야 한다.
- 쓰기 작업은 같은 요청이 두 번 와도 한 번만 반영되어야 한다(멱등 키: 요청마다 붙이는 고유 번호).
- 에이전트가 다른 에이전트를 부르거나 새 작업을 만드는 횟수와 깊이를 제한해야 한다.
- 다른 에이전트·도구가 준 결과는 다음 단계에 넣기 전에 형식을 검사해야 한다.

## 탐지 규칙

**찾는 곳**: 에이전트 실행 반복(`while True`, `while not done`, `for` 없이 도는 재귀, `max_iterations`·`max_turns`·`recursion_limit` 설정), 작업 큐에 새 작업을 넣는 도구, 재시도 설정.

**검사 1. 멈춤 조건 없음**
- 지적할 때: 모델이 "끝"이라고 해야만 멈추는 반복이 있고 단계·시간·비용 상한이 없는 경우. 프레임워크의 반복 상한을 `None`이나 아주 큰 값으로 바꾼 경우.
- 지적하지 않을 때: 단계·시간 상한이 있는 경우. 프레임워크 기본 상한을 그대로 쓰는 경우(값을 확인해 참고로 적음).

**검사 2. 번지는 실패와 반복**
- 지적할 때: 실패를 무한 재시도하는 경우. 에이전트가 스스로 작업이나 하위 에이전트를 만들 수 있는데 개수·깊이 제한이 없는 경우.
- 지적하지 않을 때: 재시도 횟수와 대기 시간이 정해져 있고 연속 실패 시 멈추는 경우. 같은 호출 반복이 상태 확인(polling)처럼 의도된 것이고 횟수 상한이 있는 경우.

**등급**
- 위험: 쓰기·결제·발송 도구를 가진 에이전트에 멈춤 조건이 없는 경우.
- 주의: 읽기 전용 에이전트에 멈춤 조건·비용 상한이 없는 경우. 무한 재시도가 있는 경우.
- 참고: 멈춤 스위치나 멱등 키가 없는 경우.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-AGT-005 agent/loop.py:15 — send_email 도구를 가진 에이전트가 while True 로 돌고 상한이 없음 → 최대 20단계·5분·토큰 한도, 같은 행동 반복·연속 실패 시 중단`

## 코드 예시

### python

✗ 취약 코드

```python
def run(agent, goal):
    while True:  # 모델이 끝났다고 할 때까지 돈다
        step = agent.next_step(goal)
        if step.done:
            return step.result
        agent.observe(TOOLS[step.tool](**step.args))
```

✓ 안전 코드

```python
import time

MAX_STEPS, MAX_SECONDS, MAX_TOKENS = 20, 300, 200_000

def run(agent, goal):
    start, seen, failures = time.monotonic(), set(), 0
    for _ in range(MAX_STEPS):
        if kill_switch.is_on() or time.monotonic() - start > MAX_SECONDS or agent.tokens_used > MAX_TOKENS:
            return stop(agent, "한도에 걸려 멈췄습니다")
        step = agent.next_step(goal)
        if step.done:
            return step.result
        key = (step.tool, repr(sorted(step.args.items())))
        if key in seen:
            return stop(agent, "같은 행동을 되풀이해 멈췄습니다")
        seen.add(key)
        try:
            agent.observe(TOOLS[step.tool](**step.args))
            failures = 0
        except Exception as e:
            failures += 1
            if failures >= 3:
                return stop(agent, "세 번 연속 실패해 멈췄습니다")
            agent.observe({"error": str(e)[:500]})
    return stop(agent, "최대 단계 수에 닿아 멈췄습니다")
```
