<!-- generated from white-security issues; do not edit -->
# WS-MOB-005 코드 예시 — Flutter

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
