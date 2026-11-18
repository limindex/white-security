<!-- generated from white-security issues; do not edit -->
# WS-ERR-001 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
function auth(req, res, next) {
  try {
    req.user = jwt.verify(req.cookies.token, process.env.JWT_SECRET);
  } catch (e) {
    console.log(e);                         // 실패를 삼키고
  }
  next();                                   // 검증 실패여도 다음으로(fail-open)
}
app.use((err, req, res, next) => res.status(500).json({ error: err.message, stack: err.stack })); // 원문 노출
```

✓ 안전 코드 (safe example)

```js
function auth(req, res, next) {
  try {
    req.user = jwt.verify(req.cookies.token, process.env.JWT_SECRET, { algorithms: ["HS256"] });
  } catch {
    return res.status(401).json({ error: "다시 로그인해 주세요" }); // 실패하면 거절로 끝낸다
  }
  next();
}
app.use((err, req, res, next) => {          // 마지막 처리기: 원문은 로그, 응답은 고정 문구
  const ref = crypto.randomUUID();
  console.error(ref, err);
  if (res.headersSent) return next(err);
  res.status(500).json({ error: "처리하지 못했습니다", ref });
});
```
