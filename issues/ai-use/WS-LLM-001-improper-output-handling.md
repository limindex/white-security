---
id: WS-LLM-001
title: "Improper Output Handling(AI 출력을 그대로 실행·표시)"
slug: improper-output-handling
group: ai-use
severity: high
site_only: false
depth: brief
languages: [python, js, php]
maps:
  owasp2025: [A05]
  api2023: []
  llm2026: [LLM10]
  agentic2026: [ASI05]
  asvs5: [V1.3.2, V1.2.4, V1.2.5, V3.2.2]
  cwe: [CWE-1426, CWE-94, CWE-79, CWE-89, CWE-78]
  kr_guide: ["입력데이터 검증 및 표현-1", "입력데이터 검증 및 표현-2", "입력데이터 검증 및 표현-4", "입력데이터 검증 및 표현-5"]
  kr_ciip: [CI, SI, XS]
checked: 2026-11-18
sources:
  - title: "OWASP Top 10 for LLM Applications - LLM05:2025 Improper Output Handling"
    url: https://genai.owasp.org/llmrisk/llm052025-improper-output-handling/
    year: 2025
  - title: "NVD CVE-2023-29374 (LangChain LLMMathChain, 프롬프트 인젝션으로 exec 실행)"
    url: https://nvd.nist.gov/vuln/detail/CVE-2023-29374
    year: 2023
  - title: "NVD CVE-2024-5565 (Vanna, 프롬프트 인젝션으로 시각화 코드 대신 임의 파이썬 실행)"
    url: https://nvd.nist.gov/vuln/detail/CVE-2024-5565
    year: 2024
  - title: 과학기술정보통신부 AI 보안 위협 대응 매뉴얼
    url: https://www.msit.go.kr/bbs/view.do?sCode=user&mId=328&mPid=243&bbsSeqNo=127&nttSeqNo=3139462
    year: 2026
---

## 한 줄 요약

AI가 쓴 글·코드·SQL·명령을 확인 없이 `eval`, 셸, DB, `innerHTML`에 넘기면, AI를 속인 사람의 코드가 내 서버나 사용자 화면에서 돈다. 이것을 Improper Output Handling(부적절한 출력 처리)이라고 한다(진단가이드 이름: 코드 삽입·SQL 삽입·운영체제 명령어 삽입·크로스사이트 스크립트).

## 나는 뭘 하면 되나

```text
AI 답을 eval·exec·셸 명령·SQL·innerHTML 로 넘기는 곳을 모두 찾아 줘.
AI 답은 정해진 JSON 형식으로만 받아 검사하고, 실행할 일은 미리 정한 목록에서만 고르게 바꿔 줘.
화면에는 글자로만 그려 줘.
```

- AI에게 "답에 `<b>굵게</b>`를 넣어 줘"라고 시켜 본다. 화면에 태그 글자가 그대로 보이면 텍스트로 그리고 있는 것이다.

## 체크 항목

- AI 출력은 사용자 입력과 똑같이 믿지 않는다. 받은 쪽에서 형식(JSON 스키마·길이·허용 값)을 검사한다.
- AI 출력을 `eval`·`exec`·`new Function`·셸 문자열로 실행하지 않는다. 할 일은 서버에 미리 정한 목록에서 고르게 한다.
- AI가 SQL을 쓰게 하지 않는다. 검색 조건만 JSON으로 받아 허용 목록으로 검사하고 매개변수 바인딩으로 조회한다. 꼭 필요하면 읽기 전용 계정과 행 수 제한을 둔다.
- 화면에는 `textContent`나 자동 이스케이프로 그린다. HTML이 필요하면 새니타이저를 거친다(데이터 엑스필트레이션은 WS-PI-002).
- AI가 고른 파일 경로·URL도 허용 목록으로 검사한다.

## 스킬이 잡는 방식

**찾는 곳**: LLM 응답 변수(`reply`, `completion`, `response.text`, `output`, `content`)가 흘러가는 곳 — `eval(`, `exec(`, `new Function(`, `subprocess`, `child_process.exec(`, `shell_exec(`, `query(`·`execute(`의 SQL 문자열, `innerHTML`, `dangerouslySetInnerHTML`, `v-html`, 이스케이프 없는 `echo`.

**검사 1. AI 출력이 실행된다**
- 지적할 때: LLM 응답 문자열이 코드 실행·셸·SQL 문자열로 그대로 들어간다.
- 지적하지 않을 때: 응답을 JSON으로 파싱해 허용 목록의 값만 골라 쓰고, 실행은 고정된 함수·매개변수 바인딩으로 한다. 또는 격리된 샌드박스(네트워크·파일 접근 없는 별도 컨테이너)에서만 돈다.

**검사 2. AI 출력이 HTML로 그려진다**
- 지적할 때: LLM 응답을 새니타이저 없이 HTML로 넣는다.
- 지적하지 않을 때: 텍스트로 그리거나 새니타이저를 거친다.

**등급**
- 위험: AI 출력이 `eval`·셸·SQL로 들어간다. 또는 남이 쓴 글이 섞인 AI 출력이 HTML로 그려진다.
- 주의: 사용자 본인 입력만 들어가는 AI 출력이 HTML로 그려진다. 출력 형식 검사가 없다.
- 참고: 샌드박스에서만 실행하지만 시간·메모리 제한이 없다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-LLM-001 api/report.py:57 — 모델이 쓴 SQL 을 cursor.execute 로 그대로 실행 → 조건만 JSON 으로 받아 허용 목록 검사 후 매개변수 바인딩`

## 코드

### python

✗ 뚫리는 코드

```python
def calculate(question: str) -> str:
    expr = llm.complete(f"이 계산을 파이썬 식으로 써라: {question}")
    return str(eval(expr))  # AI가 쓴 코드를 그대로 실행한다
```

✓ 막는 코드

```python
import ast
import operator

OPS = {ast.Add: operator.add, ast.Sub: operator.sub, ast.Mult: operator.mul, ast.Div: operator.truediv}

def _eval(node):
    if isinstance(node, ast.Constant) and type(node.value) in (int, float):
        return node.value
    if isinstance(node, ast.UnaryOp) and isinstance(node.op, ast.USub):
        return -_eval(node.operand)
    if isinstance(node, ast.BinOp) and type(node.op) in OPS:
        return OPS[type(node.op)](_eval(node.left), _eval(node.right))
    raise ValueError("숫자와 + - * / 만 계산합니다")

def calculate(question: str) -> str:
    expr = llm.complete(f"이 계산을 숫자와 + - * / 만 쓴 식으로 써라: {question}")[:200]
    return str(_eval(ast.parse(expr, mode="eval").body))  # 실행하지 않고 직접 계산한다
```

### js

✗ 뚫리는 코드

```js
import { exec } from "node:child_process";

app.post("/api/git", async (req, res) => {
  const reply = await llm.chat(`사용자 요청을 git 명령으로 바꿔라: ${req.body.text}`);
  exec(reply.text, (err, out) => res.send(out)); // AI가 쓴 명령을 셸로 실행한다
});
// 브라우저: resultEl.innerHTML = AI 답  → AI가 쓴 태그가 그대로 화면에 들어간다
```

✓ 막는 코드

```js
import { execFile } from "node:child_process";

const ACTIONS = {
  status: ["git", ["status", "--short"]],
  log: ["git", ["log", "-5", "--oneline"]],
};
app.post("/api/git", async (req, res) => {
  const reply = await llm.chat(`요청을 {"action": "status" | "log"} JSON 으로만 답해라: ${req.body.text}`);
  let action;
  try { action = JSON.parse(reply.text).action; } catch { action = null; }
  if (!Object.hasOwn(ACTIONS, action)) return res.status(422).send("할 수 없는 요청입니다");
  const [cmd, args] = ACTIONS[action];
  execFile(cmd, args, { timeout: 5000 }, (err, out) => res.json({ out }));
});
// 브라우저: resultEl.textContent = AI 답  → 글자로만 보인다
```

### php

✗ 뚫리는 코드

```php
<?php
$sql = llm_chat("이 질문에 맞는 SQL 을 써라: " . $_GET['q']);
foreach ($pdo->query($sql) as $row) {          // AI가 쓴 SQL 을 그대로 실행한다
    echo $row['name'], '<br>';
}
```

✓ 막는 코드

```php
<?php
$f = json_decode(llm_chat('질문을 JSON {"category": 문자열, "max_price": 숫자} 로만 바꿔라: ' . $_GET['q']), true);
$categories = ['shoes', 'bags', 'hats'];
if (!is_array($f) || !in_array($f['category'] ?? null, $categories, true) || !is_numeric($f['max_price'] ?? null)) {
    http_response_code(422);
    exit('질문을 이해하지 못했습니다');
}
$st = $pdo->prepare('SELECT name FROM products WHERE category = ? AND price <= ? LIMIT 50');
$st->execute([$f['category'], (int) $f['max_price']]);
foreach ($st as $row) {
    echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'), '<br>';
}
```
