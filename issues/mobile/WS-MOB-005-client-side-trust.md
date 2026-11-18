---
id: WS-MOB-005
title: Client-side Trust in Mobile Apps (앱에서만 하는 결제·권한·탈옥 판단)
slug: client-side-trust
group: mobile
severity: high
site_only: false
depth: brief
languages: [react-native, flutter, express]
maps:
  owasp2025: [A01, A06]
  api2023: [API5, API6]
  llm2026: []
  agentic2026: []
  owasp_mobile2024: [M3, M8, M7]
  asvs5: [V8.3.1, V2.2.2, V2.3.1]
  cwe: [CWE-602, CWE-862, CWE-807, CWE-345]
  kr_guide: ["입력데이터 검증 및 표현-15", "보안기능-2"]
  kr_ciip: [IN, PV]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [React Native·Expo, Flutter, 인앱 결제·구독, 유료 콘텐츠·횟수 제한, 관리자 메뉴가 숨겨진 앱, 루팅·탈옥 검사]
severity_reason: "영향: 결제 없이 유료 기능 사용·남의 구매로 잠금 해제·관리자 기능 호출 · 악용: 기기 저장값 수정, 앱 변조, 서버 API 직접 호출"
sources:
  - title: "OWASP Mobile Top 10 2024 - M3: Insecure Authentication/Authorization"
    url: https://owasp.org/www-project-mobile-top-10/2023-risks/m3-insecure-authentication-authorization
    year: 2024
  - title: "Android Developers - Fight fraud and abuse (Play Billing: verify purchases before granting entitlements)"
    url: https://developer.android.com/google/play/billing/security
    year: 2026
  - title: "Google Play Developer API - purchases.subscriptionsv2.get"
    url: https://developers.google.com/android-publisher/api-ref/rest/v3/purchases.subscriptionsv2/get
    year: 2026
  - title: "Apple Developer - App Store Server API: Get Transaction Info"
    url: https://developer.apple.com/documentation/appstoreserverapi/get-transaction-info
    year: 2026
  - title: "Apple Developer - appAccountToken (UUID로 구매와 계정 연결)"
    url: https://developer.apple.com/documentation/appstoreserverapi/appaccounttoken
    year: 2026
  - title: "Apple Developer - App Store Server Notifications"
    url: https://developer.apple.com/documentation/appstoreservernotifications
    year: 2026
  - title: "Android Developers - Real-time developer notifications reference"
    url: https://developer.android.com/google/play/billing/rtdn-reference
    year: 2026
  - title: "Apple App Store Server Library (Node.js) 3.1"
    url: https://github.com/apple/app-store-server-library-node
    year: 2026
  - title: "OWASP MASVS v2.1 - MASVS-AUTH"
    url: https://mas.owasp.org/MASVS/07-MASVS-AUTH/
    year: 2026
---

## 요약

유료 기능 잠금 해제·구독 여부·관리자 메뉴·루팅(탈옥) 검사를 앱 안에서만 판단해, 저장값을 바꾸거나 앱을 변조하거나 서버 API를 직접 부르면 그대로 뚫리는 문제. 구매는 서버가 Google Play Developer API·App Store Server API로 확인하고, 유료 콘텐츠와 권한은 요청마다 서버가 확인해야 함.

## 대응 방법

```text
앱에서 isPremium·구독·관리자·횟수 제한·루팅 검사로 기능을 여닫는 곳과, 그 기능이 부르는 서버 API를 짝지어 표로 보여 줘.
결제가 끝나면 앱은 구매 토큰(Android purchaseToken·iOS transactionId)만 서버로 보내고, 서버가 스토어 API로 확인한 뒤 권한을 저장하게 고쳐 줘.
유료 콘텐츠·관리 기능 API는 요청마다 서버에 저장된 권한을 확인하게 하고, 앱의 값은 화면 표시용으로만 써 줘.
```

- 앱 저장값을 `isPremium=true`로 바꾸거나 API를 직접 불러도 유료 콘텐츠가 내려오지 않아야 한다.
- 같은 구매 토큰을 다른 계정으로 다시 보내면 거절되어야 한다.

## 점검 항목

- 유료 기능·구독 여부는 서버가 스토어 API로 확인한 구매 기록으로만 정해야 한다.
- 구매(Android purchaseToken·iOS originalTransactionId)는 한 계정에만 묶고, 다른 계정이 같은 구매를 보내면 거절해야 한다.
- 유료 콘텐츠·관리 기능 API는 요청마다 서버가 권한을 확인해야 한다.
- 갱신·환불·만료 알림(Play 실시간 개발자 알림·App Store Server Notifications V2)을 받아 만료 시각을 갱신하거나 권한을 거둬야 한다.
- 루팅·탈옥 검사·앱 변조 탐지는 보조 수단으로만 쓰고, 이것만으로 보안 판단을 하지 않아야 한다.
- 앱에 숨긴 관리자 메뉴·디버그 스위치도 서버 권한 확인 없이는 동작하지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `PurchaseStatus.purchased`, `requestPurchase(`, `isJailBroken(`, `jail-monkey`, `serverVerificationData`, `purchaseToken`, `subscriptionsv2`, `getTransactionInfo(`, 그 밖에 isPremium·isPro·isAdmin을 AsyncStorage·SharedPreferences에 두는 코드, 영수증 검증 API, 유료 콘텐츠 API, 루팅·탈옥 검사 패키지.
- **검사 1 — 앱 안의 결제 판단**: 지적할 때: 결제 라이브러리의 성공 콜백만 보고 기기 저장값으로 잠금을 푸는 경우, 앱이 `isPremium: true`를 서버에 보내 저장하게 하는 경우, 서버가 영수증·토큰을 스토어 API로 확인하지 않는 경우. / 지적하지 않을 때: 서버가 Google Play Developer API·App Store Server API로 확인하고 권한을 저장하는 경우.
- **검사 2 — 서버의 권한 확인**: 지적할 때: 유료 콘텐츠·관리 기능 API가 로그인만 보고 구독·역할을 확인하지 않는 경우, 같은 구매를 다른 계정이 쓰는 것을 막지 않는 경우, 첫 구매 때 만료 시각을 굳혀 갱신·환불·만료를 반영하지 않는 경우, iOS 운영 환경만 조회해 심사용 샌드박스 구매가 실패하는 경우. / 지적하지 않을 때: 요청마다 서버 저장 권한을 확인하고, 구매를 계정에 묶되 같은 계정의 갱신·복원은 다시 확인해 받아들이는 경우.
- **검사 3 — 기기 검사만 믿기**: 지적할 때: 루팅·탈옥 검사나 앱 변조 탐지를 통과하면 서버 확인을 건너뛰는 경우, 숨긴 메뉴로만 관리자 기능을 막는 경우. / 지적하지 않을 때: 기기 검사·Play Integrity·App Attest 결과를 서버가 확인해 위험 신호로만 쓰는 경우.
- **등급**: 위험: 서버 확인 없이 유료·관리 기능 제공, 앱이 보낸 결제 결과를 그대로 저장. / 주의: 다른 계정의 구매 재사용·계정 불일치 확인 없음, 갱신·환불·만료 알림 미반영. / 참고: 루팅·탈옥 검사를 보안 판단의 중심으로 씀.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-MOB-005 src/paywall.tsx:40 — 결제 성공 콜백에서 AsyncStorage isPremium=true 로 잠금 해제, 서버 /lessons/premium 은 구독 확인 없음 → 서버가 subscriptionsv2.get 으로 확인 후 권한 저장, API 에서 권한 확인`
- **검토 주의**: Google은 구매 토큰을 백엔드로 보내 Play Developer API로 확인하고, 이전 토큰과 겹치지 않는지 본 뒤 권한을 주라고 안내한다(Play Billing 문서). 다만 구독 갱신·복원은 같은 purchaseToken(iOS는 같은 originalTransactionId)으로 다시 오므로, 같은 계정이면 다시 확인해 만료 시각을 갱신하고 다른 계정일 때만 거절해야 한다. App Store 심사는 샌드박스에서 구매하므로 운영 조회가 `TransactionIdNotFound`면 샌드박스로 다시 조회한다. 루팅·탈옥 검사는 기기를 가진 사람이 우회할 수 있다. 웹 쪽의 클라이언트 판단 신뢰는 WS-AUTH-005, 결제 금액 변조·웹훅은 WS-PAY-001, 역할 필드 덮어쓰기는 WS-AUTH-006에서 다룬다.
- **고칠 방법**: 구매는 서버가 스토어 API로 확인해 계정에 묶어 저장하고, 유료·관리 API는 요청마다 서버 권한을 확인해야 한다.

## 코드 예시

### react-native

✗ 취약 코드

```tsx
import AsyncStorage from "@react-native-async-storage/async-storage";
import JailMonkey from "jail-monkey";

// 결제 라이브러리의 성공 콜백만 보고 잠금 해제
export async function onPurchaseSuccess() {
  await AsyncStorage.setItem("isPremium", "true");                  // 기기에서 바꿀 수 있는 값
  await api.patch("/me", { isPremium: true });                      // 서버는 앱이 보낸 값을 저장
}

export async function openPremiumLesson(id: string) {
  const premium = (await AsyncStorage.getItem("isPremium")) === "true";
  if (!premium || JailMonkey.isJailBroken()) return showPaywall();  // 앱 안에서만 판단
  return api.get(`/lessons/${id}`);                                  // 서버는 로그인만 확인
}
```

✓ 안전 코드

```tsx
import { Platform } from "react-native";

// 결제 요청 때 서버가 준 billingId 를 넘김(Android obfuscatedAccountId · iOS appAccountToken)
export async function onPurchaseSuccess(p: { productId: string; purchaseToken?: string; transactionId?: string }) {
  const token = Platform.OS === "android" ? p.purchaseToken : p.transactionId;
  const r = await api.post("/iap/verify", { platform: Platform.OS, productId: p.productId, token });
  setPro(r.pro);                                                     // 화면 표시용 상태일 뿐
  // 서버 확인이 끝난 뒤 결제 라이브러리의 finishTransaction 호출(라이브러리 문서 순서대로)
}

export async function openPremiumLesson(id: string) {
  const res = await api.raw(`/lessons/premium/${id}`);              // 서버가 구독을 확인
  if (res.status === 403) return showPaywall();
  return res.json();
}
```

### flutter

✗ 취약 코드

```dart
void onPurchaseUpdated(List<PurchaseDetails> list) async {
  final prefs = await SharedPreferences.getInstance();
  for (final p in list) {
    if (p.status == PurchaseStatus.purchased) {
      await prefs.setBool('isPremium', true);                  // 앱 안에서만 잠금 해제
      await InAppPurchase.instance.completePurchase(p);
    }
  }
}

bool canOpenPremium(SharedPreferences prefs) => prefs.getBool('isPremium') ?? false;
```

✓ 안전 코드

```dart
void onPurchaseUpdated(List<PurchaseDetails> list) async {
  for (final p in list) {
    if (p.status != PurchaseStatus.purchased && p.status != PurchaseStatus.restored) continue;
    final token = Platform.isAndroid ? p.verificationData.serverVerificationData // purchaseToken
                                     : p.purchaseID;                             // transactionId
    final res = await api.post('/iap/verify', {'platform': Platform.operatingSystem, 'productId': p.productID, 'token': token});
    if (res.statusCode == 200 && p.pendingCompletePurchase) {
      await InAppPurchase.instance.completePurchase(p);        // 서버가 확인한 뒤에만 완료
    }
  }
}

// 유료 화면은 서버 응답으로 판단: GET /lessons/premium/:id → 403 이면 결제 화면
```

### express

✗ 취약 코드

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

✓ 안전 코드

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
