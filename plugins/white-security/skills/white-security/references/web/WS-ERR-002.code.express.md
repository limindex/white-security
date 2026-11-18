<!-- generated from white-security issues; do not edit -->
# WS-ERR-002 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
