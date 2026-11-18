<!-- generated from white-security issues; do not edit -->
# WS-MOB-005 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
// 앱이 보낸 결과를 그대로 믿음
app.patch("/me", requireLogin, async (req, res) => {
  await db.users.update(req.user.id, { isPremium: req.body.isPremium });
  res.sendStatus(204);
});

app.get("/lessons/premium/:id", requireLogin, async (req, res) => {
  res.json(await db.lessons.get(req.params.id));               // 구독 확인 없음
});
```

✓ 안전 코드 (safe example)

```js
import { google } from "googleapis";
import { AppStoreServerAPIClient, SignedDataVerifier, Environment } from "@apple/app-store-server-library";

const play = google.androidpublisher({ version: "v3",
  auth: new google.auth.GoogleAuth({ scopes: ["https://www.googleapis.com/auth/androidpublisher"] }) });
const apple = new AppStoreServerAPIClient(APPLE_KEY, APPLE_KEY_ID, APPLE_ISSUER_ID, BUNDLE_ID, Environment.PRODUCTION);
const verifier = new SignedDataVerifier(APPLE_ROOT_CAS, true, Environment.PRODUCTION, BUNDLE_ID, APP_APPLE_ID);

app.post("/iap/verify", requireLogin, async (req, res) => {
  const { platform, productId, token } = req.body;
  if (productId !== "pro_monthly" || typeof token !== "string") return res.sendStatus(400);
  if (await db.purchases.findByToken(token)) return res.sendStatus(409);          // 같은 구매 재사용 막기
  let expiresAt = 0;
  if (platform === "android") {
    const { data } = await play.purchases.subscriptionsv2.get({ packageName: PACKAGE, token });
    const item = data.lineItems?.find((l) => l.productId === productId);
    const active = ["SUBSCRIPTION_STATE_ACTIVE", "SUBSCRIPTION_STATE_IN_GRACE_PERIOD"].includes(data.subscriptionState);
    if (item && active && data.externalAccountIdentifiers?.obfuscatedExternalAccountId === req.user.billingId) {
      expiresAt = Date.parse(item.expiryTime);
      if (data.acknowledgementState !== "ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED")
        await play.purchases.subscriptions.acknowledge({ packageName: PACKAGE, subscriptionId: productId, token });
    }
  } else if (platform === "ios") {
    const { signedTransactionInfo } = await apple.getTransactionInfo(token);
    const tx = await verifier.verifyAndDecodeTransaction(signedTransactionInfo);   // Apple 서명 확인
    if (tx.productId === productId && !tx.revocationDate && tx.appAccountToken === req.user.billingId) expiresAt = tx.expiresDate ?? 0;
  }
  if (expiresAt <= Date.now()) return res.sendStatus(402);
  await db.purchases.insert({ token, userId: req.user.id, productId, expiresAt });  // token 열은 UNIQUE
  res.json({ pro: true, expiresAt });
});

app.get("/lessons/premium/:id", requireLogin, async (req, res) => {
  if (!(await db.purchases.activeFor(req.user.id, "pro_monthly"))) return res.sendStatus(403); // 요청마다 서버 확인
  res.json(await db.lessons.get(req.params.id));
});
```
