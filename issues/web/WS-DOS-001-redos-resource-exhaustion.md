---
id: WS-DOS-001
title: ReDoS · Resource Exhaustion (정규식 서비스 거부·자원 고갈)
slug: redos-resource-exhaustion
group: web
severity: medium
grade_hint: "공개 경로 ReDoS, 입력으로 만든 정규식"
site_only: false
depth: brief
languages: [express, fastapi, laravel]
maps:
  owasp2025: []
  api2023: [API4]
  llm2026: []
  agentic2026: []
  asvs5: [V1.3.12, V15.2.2]
  cwe: [CWE-1333, CWE-400, CWE-770, CWE-674, CWE-835]
  kr_guide: ["시간 및 상태-2"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Express·Node.js, FastAPI·Python, Laravel·PHP, 입력 검사·목록·내보내기 기능]
severity_reason: "영향: 서버 CPU·메모리 독점으로 서비스 멈춤 · 악용: 특정 모양의 긴 문자열이나 큰 숫자 하나로 가능"
sources:
  - title: "OWASP - Regular expression Denial of Service (ReDoS)"
    url: https://owasp.org/www-community/attacks/Regular_expression_Denial_of_Service_-_ReDoS
    year: 2026
  - title: "Cloudflare, Details of the Cloudflare outage on July 2, 2019 (정규식 역추적으로 CPU 고갈)"
    url: https://blog.cloudflare.com/details-of-the-cloudflare-outage-on-july-2-2019/
    year: 2019
  - title: "CWE-1333: Inefficient Regular Expression Complexity"
    url: https://cwe.mitre.org/data/definitions/1333.html
    year: 2025
  - title: "OWASP API Security Top 10 2023 - API4 Unrestricted Resource Consumption"
    url: https://owasp.org/API-Security/editions/2023/en/0xa4-unrestricted-resource-consumption/
    year: 2023
---

## 요약

중첩 반복이 있는 정규식(`(a+)+`), 끝나는 조건이 외부에 달린 반복·재귀, 상한 없는 `limit`·중첩 깊이 때문에 요청 하나가 서버 CPU·메모리를 오래 붙잡는 문제(진단가이드 이름: 종료되지 않는 반복문 또는 재귀 함수). 정규식 쪽은 ReDoS(정규식 서비스 거부)라 부름.

## 대응 방법

```text
사용자 입력에 쓰는 정규식 중 중첩 반복((a+)+, (\w+\s?)*)이나 겹치는 선택((a|a)*)이 있는 것을 찾아 겹치지 않는 모양으로 고쳐 줘.
정규식 검사 전에 입력 길이 상한을 두고, 페이지 크기(limit·per_page)·재귀 깊이·반복 횟수에도 서버 상한을 넣어 줘.
```

- 고친 정규식에 `a`를 3만 개 넣고 끝에 `!`를 붙인 입력을 줘도 바로 거절되어야 한다.
- `?per_page=1000000`을 보내도 400이 나오거나 상한만큼만 돌아와야 한다.

## 점검 항목

- 사용자 입력에 쓰는 정규식에 중첩 반복·겹치는 선택이 없어야 한다.
- 정규식·파싱 전에 입력 길이 상한을 둬야 한다.
- 페이지 크기·내보내기 행 수에 서버 상한을 둬야 한다.
- 재귀는 깊이 상한을, 반복문은 횟수·전체 개수 상한을 가져야 한다.
- 사용자가 정규식을 직접 입력하는 기능은 RE2 같은 선형 엔진을 써야 한다.

## 탐지 규칙

- **찾는 곳**: `new RegExp(`, `re.compile(`, `per_page`, `paginate($request`, 그 밖에 preg_match, limit·page_size·depth 요청 값, 외부 JSON을 도는 재귀 함수, while(true).
- **검사 1 — 위험한 정규식**: 지적할 때: 외부 입력에 쓰는 정규식에 중첩 반복(`(x+)+`, `(x*)*`, `(\w+\s?)*`)이나 겹치는 선택이 있는 경우. 사용자 입력으로 `new RegExp()`를 만드는 경우. / 지적하지 않을 때: 반복이 한 겹이거나 반복 사이에 구분 글자가 꼭 있고 입력 길이 상한이 앞에 있는 경우. RE2·`re2` 패키지를 쓰는 경우.
- **검사 2 — 상한 없는 크기·반복**: 지적할 때: `limit`·`per_page`·`page_size`·`depth`를 요청에서 받아 상한 없이 쓰는 경우. 외부 데이터 구조를 깊이·개수 제한 없이 재귀로 도는 경우. 외부 응답에 따라서만 끝나는 `while (true)`. / 지적하지 않을 때: 검증 규칙(`max:100`, `le=100`)이나 `min(값, 상한)`으로 자르는 경우.
- **등급**: 위험: 로그인·검색처럼 인증 없이 열린 곳의 위험한 정규식, 사용자 입력으로 만든 정규식. / 주의: 상한 없는 페이지 크기·재귀 깊이. / 참고: 입력 길이 상한만 없는 단순 정규식.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[주의] WS-DOS-001 src/validate.js:3 — 이름 검사 정규식에 중첩 반복 ([a-z0-9]+\s?)* → 길이 50 상한 후 [a-z0-9]+( [a-z0-9]+)* 로 교체`
- **고칠 방법**: 정규식을 바꾼 뒤 기존에 통과하던 값이 그대로 통과하는지 테스트하고, 긴 실패 입력의 처리 시간 테스트를 하나 추가해야 한다.

## 코드 예시

### express

✗ 취약 코드

```js
const NAME = /^([a-zA-Z0-9]+\s?)*$/;   // 중첩 반복: 끝이 틀린 긴 입력에서 오래 걸린다
app.post("/signup", (req, res) => {
  if (!NAME.test(req.body.name)) return res.status(400).send("이름 형식이 맞지 않습니다");
  res.json({ ok: true });
});
```

✓ 안전 코드

```js
const NAME = /^[a-zA-Z0-9]+(?: [a-zA-Z0-9]+)*$/;   // 단어 사이 공백 하나, 겹치지 않는 반복
app.post("/signup", (req, res) => {
  const name = String(req.body.name ?? "");
  if (name.length > 50 || !NAME.test(name)) return res.status(400).send("이름 형식이 맞지 않습니다"); // 길이 먼저
  res.json({ ok: true });
});
```

### fastapi

✗ 취약 코드

```python
@app.get("/items")
def items(limit: int = 20):
    return db.query(Item).limit(limit).all()           # limit=10000000 도 그대로

def count_nodes(tree: dict) -> int:                    # 요청 JSON 을 깊이·개수 제한 없이 재귀
    return 1 + sum(count_nodes(c) for c in tree.get("children", []))
```

✓ 안전 코드

```python
from fastapi import Query

@app.get("/items")
def items(limit: int = Query(20, ge=1, le=100)):       # 서버 상한
    return db.query(Item).limit(limit).all()

def count_nodes(tree: dict, max_nodes: int = 1000, max_depth: int = 20) -> int:
    stack, seen = [(tree, 0)], 0
    while stack:                                        # 재귀 대신 반복 + 전체 개수·깊이 상한
        node, depth = stack.pop()
        seen += 1
        if seen > max_nodes or depth > max_depth:
            raise HTTPException(400, "구조가 너무 큽니다")
        kids = node.get("children", [])
        if not isinstance(kids, list) or len(kids) > max_nodes:   # 넣기 전에 개수 확인
            raise HTTPException(400, "구조가 너무 큽니다")
        stack.extend((c, depth + 1) for c in kids if isinstance(c, dict))
    return seen
```

### laravel

✗ 취약 코드

```php
public function index(Request $request)
{
    return Post::paginate($request->input('per_page', 20));   // per_page=1000000 도 그대로
}
```

✓ 안전 코드

```php
public function index(Request $request)
{
    $data = $request->validate(['per_page' => ['nullable', 'integer', 'min:1', 'max:100']]); // 상한 100
    return Post::select(['id', 'title', 'created_at'])->paginate($data['per_page'] ?? 20);
}
```
