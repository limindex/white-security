<!-- generated from white-security issues; do not edit -->
# WS-AGT-005 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
def run(agent, goal):
    while True:  # 모델이 끝났다고 할 때까지 돈다
        step = agent.next_step(goal)
        if step.done:
            return step.result
        agent.observe(TOOLS[step.tool](**step.args))
```

✓ 안전 코드 (safe example)

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
