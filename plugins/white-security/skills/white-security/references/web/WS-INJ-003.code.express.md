<!-- generated from white-security issues; do not edit -->
# WS-INJ-003 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
// 계산기: "1+2*3" 을 받아 계산
app.get("/calc", (req, res) => {
  res.json({ result: eval(req.query.expr) }); // 식 대신 코드도 실행된다
});
```

✓ 안전 코드 (safe example)

```js
// 숫자와 + - * / ( ) 만 읽는 작은 계산기. eval·new Function 을 쓰지 않는다
function calc(src) {
  if (typeof src !== "string" || src.length > 100) throw new Error("식이 너무 깁니다");
  const t = src.match(/\d+(?:\.\d+)?|\S/g) ?? [];
  let i = 0;
  const num = () => {
    const x = t[i++];
    if (x === "(") { const v = expr(); if (t[i++] !== ")") throw new Error("괄호"); return v; }
    if (x === "-") return -num();
    if (!/^\d/.test(x ?? "")) throw new Error("허용하지 않는 글자");
    return Number(x);
  };
  const term = () => { let v = num(); while (t[i] === "*" || t[i] === "/") v = t[i++] === "*" ? v * num() : v / num(); return v; };
  const expr = () => { let v = term(); while (t[i] === "+" || t[i] === "-") v = t[i++] === "+" ? v + term() : v - term(); return v; };
  const v = expr();
  if (i !== t.length) throw new Error("허용하지 않는 글자");
  return v;
}

app.get("/calc", (req, res) => {
  try { res.json({ result: calc(req.query.expr) }); } catch { res.status(400).json({ error: "계산할 수 없는 식" }); }
});
```
