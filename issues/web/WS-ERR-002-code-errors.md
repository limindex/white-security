---
id: WS-ERR-002
title: Code Errors (Null 역참조·자원 누수·정수 오버플로)
slug: code-errors
group: web
severity: medium
grade_hint: "금액 음수·범위 검사 없음"
site_only: false
depth: brief
languages: [express, laravel, fastapi]
maps:
  owasp2025: [A10]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.4.2, V1.4.3, V15.3.5]
  cwe: [CWE-476, CWE-404, CWE-672, CWE-457, CWE-190, CWE-20]
  kr_guide: ["코드오류-1", "코드오류-2", "코드오류-4", "입력데이터 검증 및 표현-14"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Express·Node.js(node-postgres), Laravel·PHP, FastAPI·SQLAlchemy, DB 연결·금액 계산이 있는 서버]
severity_reason: "영향: 연결 고갈로 서비스 멈춤, 음수·넘친 수량으로 잘못된 금액 · 악용: 특정 값을 반복해 보내는 것만으로 가능"
sources:
  - title: "CWE-476: NULL Pointer Dereference"
    url: https://cwe.mitre.org/data/definitions/476.html
    year: 2025
  - title: "CWE-190: Integer Overflow or Wraparound"
    url: https://cwe.mitre.org/data/definitions/190.html
    year: 2025
  - title: "node-postgres - Pooling (빌린 클라이언트는 반드시 release)"
    url: https://node-postgres.com/features/pooling
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021, 코드오류)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

값이 없을 때(Null)의 처리, 빌린 자원(DB 연결·파일) 반납, 숫자 범위 확인을 빠뜨려 요청 몇 번에 500 오류가 나거나 연결이 바닥나고, 음수·넘친 수량으로 금액이 틀어지는 문제(진단가이드 이름: Null Pointer 역참조·부적절한 자원 해제·정수형 오버플로우 등). 자바스크립트·파이썬·PHP에서는 메모리 오류 대신 서비스 거부·계산 오류로 나타남.

## 대응 방법

```text
DB 연결·파일·스트림을 직접 빌리고 닫는 곳에서 오류가 나도 반납되는지(finally, with) 확인해 고쳐 줘.
조회 결과가 없을 때(null·None) 바로 속성에 접근하는 곳, 수량·금액을 범위 검사 없이 계산하는 곳을 찾아 고쳐 줘.
```

- 없는 id로 요청하면 500 대신 404가 나와야 한다.
- 수량에 `-1`이나 아주 큰 수를 넣으면 400이 나와야 한다.

## 점검 항목

- 직접 빌린 DB 연결·파일은 `finally`·`with`로 오류가 나도 반납해야 한다.
- 반납하거나 닫은 연결·스트림을 다시 쓰지 않아야 한다.
- 조회 결과가 없을 수 있으면 속성에 접근하기 전에 404 등으로 처리해야 한다.
- 수량·금액은 정수 여부와 최소·최대 범위를 서버에서 검사해야 한다.
- 금액은 부동소수점 대신 정수(원 단위)나 `Decimal`로 계산해야 한다.
- 권한 같은 상태 변수는 처음에 거부 값으로 초기화해야 한다.

## 탐지 규칙

- **찾는 곳**: `pool.connect(`, `client.release(`, `getConnection(`, `(int) $request->input(`, 그 밖에 open·fopen, findOne·first()·get() 결과 바로 쓰기, parseFloat 로 한 금액 계산.
- **검사 1 — 자원 반납**: 지적할 때: `pool.connect()`·`getConnection()`·`open()`·`fopen()`으로 빌린 자원을 오류 경로에서 반납하지 않는 경우(`finally`·`with` 없음). 반납 뒤 같은 객체를 다시 쓰는 경우. / 지적하지 않을 때: `pool.query()`처럼 라이브러리가 빌리고 돌려주는 API를 쓰는 경우, `with`·`finally`로 닫는 경우.
- **검사 2 — 없는 값 접근**: 지적할 때: `findOne`·`get`·`first()` 결과를 확인 없이 속성에 접근해 500이 나는 경우. / 지적하지 않을 때: `findOrFail`·`get_object_or_404`나 명시적 None·null 확인을 쓰는 경우.
- **검사 3 — 숫자 범위**: 지적할 때: 요청의 수량·금액을 범위 검사 없이 곱하거나 저장하는 경우, 금액을 `parseFloat`·float로 계산하는 경우. / 지적하지 않을 때: 정수·최소·최대 검증 뒤 정수 원 단위나 Decimal로 계산하는 경우.
- **등급**: 위험: 금액 계산에 음수·범위 검사 없음. / 주의: 오류 경로의 연결 누수, 없는 값 접근으로 생기는 500. / 참고: float 금액 계산, 거부 값으로 초기화하지 않은 상태 변수.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[주의] WS-ERR-002 src/transfer.js:14 — pool.connect() 로 빌린 연결을 오류 때 release 하지 않음 → try/finally 로 client.release()`
- **고칠 방법**: 고친 곳마다 오류 경로 테스트(없는 id, 잘못된 수량, DB 오류)를 하나씩 추가해야 한다.

## 코드 예시

### express

✗ 취약 코드

```js
app.post("/transfer", async (req, res) => {
  const client = await pool.connect();
  await client.query("BEGIN");
  await client.query("UPDATE accounts SET balance = balance - $1 WHERE id = $2", [req.body.amount, req.user.id]);
  await client.query("COMMIT");   // 중간에 오류가 나면 연결을 반납하지 않는다 → 연결 고갈
  client.release();
  res.json({ ok: true });
});
```

✓ 안전 코드

```js
app.post("/transfer", async (req, res) => {
  const amount = Number(req.body.amount);
  if (!Number.isSafeInteger(amount) || amount < 1 || amount > 10_000_000) return res.sendStatus(400); // 원 단위 정수·범위
  const client = await pool.connect();
  try {
    await client.query("BEGIN");
    const r = await client.query(
      "UPDATE accounts SET balance = balance - $1 WHERE id = $2 AND balance >= $1", [amount, req.user.id]);
    if (r.rowCount !== 1) { await client.query("ROLLBACK"); return res.status(409).json({ error: "잔액 부족" }); }
    await client.query("COMMIT");
    res.json({ ok: true });
  } catch (e) {
    await client.query("ROLLBACK").catch(() => {});
    throw e;                       // Express 5 는 async 오류를 오류 처리기로 넘긴다
  } finally {
    client.release();              // 성공·실패 모두 반납
  }
});
```

### laravel

✗ 취약 코드

```php
public function order(Request $request)
{
    $qty = (int) $request->input('qty');                       // -5 도, 아주 큰 수도 그대로
    $total = $qty * Product::findOrFail($request->input('product_id'))->price;
    return Order::create(['user_id' => $request->user()->id, 'qty' => $qty, 'total' => $total]);
}
```

✓ 안전 코드

```php
public function order(Request $request)
{
    $data = $request->validate([
        'product_id' => ['required', 'integer', 'exists:products,id'],
        'qty' => ['required', 'integer', 'min:1', 'max:100'],    // 정수·범위
    ]);
    $product = Product::findOrFail($data['product_id']);
    $total = $data['qty'] * $product->price;                   // 가격은 서버 값, 원 단위 정수
    return Order::create(['user_id' => $request->user()->id, 'qty' => $data['qty'], 'total' => $total]);
}
```

### fastapi

✗ 취약 코드

```python
@app.get("/orders/{order_id}")
def get_order(order_id: int, user=Depends(current_user), db=Depends(get_db)):
    order = db.get(Order, order_id)
    if order.user_id != user.id:          # 없는 id 면 None 의 속성 접근 → 500
        raise HTTPException(403)
    return order
```

✓ 안전 코드

```python
@app.get("/orders/{order_id}", response_model=OrderOut)
def get_order(order_id: int, user=Depends(current_user), db=Depends(get_db)):
    order = db.get(Order, order_id)
    if order is None or order.user_id != user.id:   # 없음과 남의 것을 같은 404 로
        raise HTTPException(404)
    return order
```
