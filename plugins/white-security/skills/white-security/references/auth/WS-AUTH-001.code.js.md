<!-- generated from white-security issues; do not edit -->
# WS-AUTH-001 코드 예시 — js

✗ 취약 코드 (vulnerable example — do not use)

```js
// Express + Prisma
app.get("/api/orders/:id", requireLogin, async (req, res) => {
  const order = await prisma.order.findUnique({ where: { id: req.params.id } });
  if (!order) return res.sendStatus(404);
  res.json(order); // 누구 주문인지 확인하지 않는다
});

app.patch("/api/me", requireLogin, async (req, res) => {
  const user = await prisma.user.update({
    where: { id: req.body.userId }, // 대상도 요청 값
    data: req.body,                 // role 같은 필드까지 바뀐다
  });
  res.json(user);
});
```

✓ 안전 코드 (safe example)

```js
app.get("/api/orders/:id", requireLogin, async (req, res) => {
  const order = await prisma.order.findFirst({
    where: { id: req.params.id, userId: req.user.id }, // 소유자 조건
  });
  if (!order) return res.sendStatus(404);
  res.json(order);
});

app.patch("/api/me", requireLogin, async (req, res) => {
  const data = {};                                      // 바꿔도 되는 필드만
  if (typeof req.body.nickname === "string") data.nickname = req.body.nickname.slice(0, 30);
  if (typeof req.body.marketingOptIn === "boolean") data.marketingOptIn = req.body.marketingOptIn;
  const user = await prisma.user.update({
    where: { id: req.user.id },                         // 대상은 세션 사용자
    data,
    select: { id: true, nickname: true, marketingOptIn: true },
  });
  res.json(user);
});
```
