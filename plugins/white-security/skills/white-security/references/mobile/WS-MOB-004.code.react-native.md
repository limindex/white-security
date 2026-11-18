<!-- generated from white-security issues; do not edit -->
# WS-MOB-004 코드 예시 — React Native

✗ 취약 코드 (vulnerable example — do not use)

```tsx
import { Linking } from "react-native";
import { WebView } from "react-native-webview";

export function WebScreen({ route }: { route: { params: { url: string } } }) {
  return (
    <WebView
      source={{ uri: route.params.url }}                 // 딥링크로 받은 아무 주소
      originWhitelist={["*"]}
      allowFileAccess
      allowUniversalAccessFromFileURLs
      injectedJavaScriptBeforeContentLoaded={`window.__TOKEN__ = "${accessToken}";`} // 페이지에 토큰 주입
      onMessage={(e) => {
        const msg = JSON.parse(e.nativeEvent.data);       // 누가 보냈는지 확인 안 함
        if (msg.type === "pay") payNow(msg.amount);
        if (msg.type === "open") Linking.openURL(msg.url);
      }}
    />
  );
}
```

✓ 안전 코드 (safe example)

```tsx
import { Linking } from "react-native";
import { WebView } from "react-native-webview";

const ORIGIN = "https://help.example.com";
const isOurs = (u: string) => u === ORIGIN || u.startsWith(ORIGIN + "/"); // .example.com.evil.example 같은 주소는 불일치

export function HelpScreen({ onClose }: { onClose: () => void }) {
  return (
    <WebView
      source={{ uri: `${ORIGIN}/faq` }}                   // 고정 주소만
      originWhitelist={[ORIGIN]}
      onShouldStartLoadWithRequest={(req) => {
        if (isOurs(req.url)) return true;
        if (req.url.startsWith("https://")) Linking.openURL(req.url); // 밖 링크는 외부 브라우저로
        return false;
      }}
      onMessage={(e) => {
        // Android 최신 WebView 는 보낸 프레임의 출처, 오래된 WebView(WEB_MESSAGE_LISTENER 없음)는 최상위 주소라 iframe 구별 불가
        if (!isOurs(e.nativeEvent.url)) return;           // 우리 페이지가 보낸 것만 — 그래서 이 페이지엔 외부 iframe 금지(CSP frame-src)
        if (e.nativeEvent.data === "close") onClose();    // 정해 둔 메시지만, 토큰·결제는 브리지로 처리하지 않음
      }}
    />
  );
}
```
