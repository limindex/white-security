<!-- generated from white-security issues; do not edit -->
# mobile · 모바일 앱

When: React Native·Expo·Flutter 등 모바일 앱의 키·저장·딥링크·WebView·결제 확인·통신
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.
Stacks: Next.js · Express · React Native · Expo · Flutter · Kotlin

- `EXPO_PUBLIC_` `sk-proj-` `api.openai.com` `api.anthropic.com` `String.fromEnvironment(` `--dart-define` `react-native-config` `buildConfigField` → WS-MOB-001 Hardcoded Secrets in App Bundle
- `AsyncStorage.setItem(` `SharedPreferences.getInstance(` `getSharedPreferences(` `UserDefaults.standard` `react-native-mmkv` `android:allowBackup="true"` `CryptoJS.AES.encrypt(` `EncryptedSharedPreferences` → WS-MOB-002 Insecure Data Storage
- `Linking.getInitialURL(` `Linking.addEventListener(` `uriLinkStream` `onOpenURL` `<intent-filter` `android:autoVerify` `apple-app-site-association` `assetlinks.json` → WS-MOB-003 Deep Link Hijacking · Unvalidated Deep Links
- `addJavascriptInterface(` `setAllowFileAccess(true)` `allowUniversalAccessFromFileURLs` `window.ReactNativeWebView` `onMessage=` `originWhitelist` `injectedJavaScript` `addJavaScriptChannel(` → WS-MOB-004 Insecure WebView · JavaScript Bridge
- `PurchaseStatus.purchased` `requestPurchase(` `isJailBroken(` `jail-monkey` `serverVerificationData` `purchaseToken` `subscriptionsv2` `getTransactionInfo(` → WS-MOB-005 Client-side Trust in Mobile Apps
- `android:usesCleartextTraffic="true"` `cleartextTrafficPermitted="true"` `NSAllowsArbitraryLoads` `NSExceptionAllowsInsecureHTTPLoads` `badCertificateCallback` `onReceivedSslError` `X509TrustManager` `hostnameVerifier` → WS-MOB-006 Cleartext Traffic · Certificate Validation Bypass
