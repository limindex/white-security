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
import { APIError, APIException, AppStoreServerAPIClient, Environment, SignedDataVerifier } from "@apple/app-store-server-library";

const play = google.androidpublisher({ version: "v3",
  auth: new google.auth.GoogleAuth({ scopes: ["https://www.googleapis.com/auth/androidpublisher"] }) });
// 운영 → 샌드박스 순서로 조회(App Store 심사는 샌드박스 구매로 함)
const apple = [Environment.PRODUCTION, Environment.SANDBOX].map((env) => ({
  api: new AppStoreServerAPIClient(APPLE_KEY, APPLE_KEY_ID, APPLE_ISSUER_ID, BUNDLE_ID, env),
  verifier: new SignedDataVerifier(APPLE_ROOT_CAS, true, env, BUNDLE_ID, APP_APPLE_ID),
}));

async function appleTransaction(transactionId) {
  for (const { api, verifier } of apple) {
    try {
      const { signedTransactionInfo } = await api.getTransactionInfo(transactionId);
      return await verifier.verifyAndDecodeTransaction(signedTransactionInfo);        // Apple 서명 확인
    } catch (e) {
      if (e instanceof APIException && e.apiError === APIError.TRANSACTION_ID_NOT_FOUND) continue;
      throw e;
    }
  }
  return null;
}

// 스토어에 확인한 구매 → { key, expiresAt } (key: Android purchaseToken · iOS originalTransactionId)
async function verifyStore(platform, productId, token, user) {
  if (platform === "android") {
    const { data } = await play.purchases.subscriptionsv2.get({ packageName: PACKAGE, token });
    const item = data.lineItems?.find((l) => l.productId === productId);
    const active = ["SUBSCRIPTION_STATE_ACTIVE", "SUBSCRIPTION_STATE_IN_GRACE_PERIOD"].includes(data.subscriptionState);
    if (!item || !active || data.externalAccountIdentifiers?.obfuscatedExternalAccountId !== user.billingId) return null;
    if (data.acknowledgementState !== "ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED")
      await play.purchases.subscriptions.acknowledge({ packageName: PACKAGE, subscriptionId: productId, token });
    return { key: `android:${token}`, expiresAt: Date.parse(item.expiryTime) };
  }
  const tx = await appleTransaction(token);
  // appAccountToken 은 UUID 여야 함: 결제 요청 때 앱이 서버가 준 user.billingId(UUID)를 넘김
  if (!tx || tx.productId !== productId || tx.revocationDate || tx.appAccountToken !== user.billingId) return null;
  return { key: `ios:${tx.originalTransactionId}`, expiresAt: tx.expiresDate ?? 0 };
}

app.post("/iap/verify", requireLogin, async (req, res) => {
  const { platform, productId, token } = req.body;
  if (!["android", "ios"].includes(platform) || productId !== "pro_monthly" || typeof token !== "string") return res.sendStatus(400);
  const v = await verifyStore(platform, productId, token, req.user);
  if (!v || v.expiresAt <= Date.now()) return res.sendStatus(402);
  const owner = await db.purchases.ownerOf(v.key);                                    // key 열은 UNIQUE
  // 다른 계정의 구매는 보통 위 계정 연결 값 확인에서 402. 여기는 연결 값이 같은데 DB 소유자가 다른 드문 경우(데이터 이전·계정 병합 등)
  if (owner && owner !== req.user.id) return res.sendStatus(409);
  // 같은 계정의 갱신·복원은 같은 key 로 다시 와도 스토어 확인 뒤 만료 시각만 갱신
  await db.purchases.upsert({ key: v.key, userId: req.user.id, productId, expiresAt: v.expiresAt });
  res.json({ pro: true, expiresAt: v.expiresAt });
});
// 갱신·환불·만료는 Play 실시간 개발자 알림(RTDN)·App Store Server Notifications V2 처리기에서
// 같은 key 로 expiresAt 을 갱신하거나 권한을 거둠(여기서는 생략) — 첫 구매 때 값으로 굳히지 않음

app.get("/lessons/premium/:id", requireLogin, async (req, res) => {
  if (!(await db.purchases.activeFor(req.user.id, "pro_monthly"))) return res.sendStatus(403); // 요청마다 서버 확인
  res.json(await db.lessons.get(req.params.id));
});
```
