<!-- generated from white-security issues; do not edit -->
# mobile · 모바일 앱

- `EXPO_PUBLIC_` `sk-proj-` `api.openai.com` `api.anthropic.com` `String.fromEnvironment(` `--dart-define` `react-native-config` `buildConfigField` → WS-MOB-001: 유료·관리용 비밀키가 앱 안에
- `AsyncStorage.setItem(` `SharedPreferences.getInstance(` `getSharedPreferences(` `UserDefaults.standard` `react-native-mmkv` `android:allowBackup="true"` `CryptoJS.AES.encrypt(` `EncryptedSharedPreferences` → WS-MOB-002: 토큰·비밀번호를 평문 저장소에 저장
- `Linking.getInitialURL(` `Linking.addEventListener(` `uriLinkStream` `onOpenURL` `<intent-filter` `android:autoVerify` `apple-app-site-association` `assetlinks.json` → WS-MOB-003: 링크로 토큰 전달, 링크만으로 송금·삭제
- `addJavascriptInterface(` `setAllowFileAccess(true)` `allowUniversalAccessFromFileURLs` `window.ReactNativeWebView` `onMessage=` `originWhitelist` `injectedJavaScript` `addJavaScriptChannel(` → WS-MOB-004: 임의 주소 WebView에 토큰·결제 브리지
- `PurchaseStatus.purchased` `requestPurchase(` `isJailBroken(` `jail-monkey` `serverVerificationData` `purchaseToken` `subscriptionsv2` `getTransactionInfo(` → WS-MOB-005: 서버 확인 없이 유료·관리 기능 제공
- `android:usesCleartextTraffic="true"` `cleartextTrafficPermitted="true"` `NSAllowsArbitraryLoads` `NSExceptionAllowsInsecureHTTPLoads` `badCertificateCallback` `onReceivedSslError` `X509TrustManager` `hostnameVerifier` → WS-MOB-006: 인증서 검증 끔, 로그인·토큰이 http로
