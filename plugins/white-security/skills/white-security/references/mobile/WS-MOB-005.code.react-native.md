<!-- generated from white-security issues; do not edit -->
# WS-MOB-005 코드 예시 — React Native

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
