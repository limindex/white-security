<!-- generated from white-security issues; do not edit -->
# WS-HDR-001 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
app.get("/download/:id", async (req, res) => {
  const file = await File.findById(req.params.id);
  // 따옴표·세미콜론이 든 이름은 헤더 값을 바꾸고, 한글 이름은 오류(ERR_INVALID_CHAR)를 낸다
  res.setHeader("Content-Disposition", `attachment; filename="${file.name}"`);
  res.sendFile(file.path);
});
```

✓ 안전 코드 (safe example)

```js
app.get("/download/:id", async (req, res) => {
  const file = await File.findById(req.params.id);
  if (!file || String(file.ownerId) !== String(req.user?.id)) return res.sendStatus(404);
  res.download(file.path, file.name); // filename*=UTF-8'' 형식으로 인코딩해 준다
});
```
