<!-- generated from white-security issues; do not edit -->
# WS-MOB-002 코드 예시 — Expo

✗ 취약 코드 (vulnerable example — do not use)

```ts
import AsyncStorage from "@react-native-async-storage/async-storage";

export async function onLogin(session: { accessToken: string; refreshToken: string; user: { phone: string } }) {
  await AsyncStorage.setItem("access_token", session.accessToken);   // 암호화 없는 저장소
  await AsyncStorage.setItem("refresh_token", session.refreshToken);
  await AsyncStorage.setItem("user", JSON.stringify(session.user));  // 전화번호까지 평문
  console.log("login ok", session);                                  // 토큰이 기기 로그에 남음
}
```

✓ 안전 코드 (safe example)

```ts
import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from "expo-secure-store";

const opts = { keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY }; // 다른 기기로 옮겨지지 않음

export async function onLogin(session: { accessToken: string; refreshToken: string; user: { name: string } }) {
  await SecureStore.setItemAsync("access_token", session.accessToken, opts);    // iOS Keychain · Android Keystore
  await SecureStore.setItemAsync("refresh_token", session.refreshToken, opts);
  await AsyncStorage.setItem("display_name", session.user.name);               // 민감하지 않은 표시용 값만
  for (const k of ["access_token", "refresh_token", "user"]) await AsyncStorage.removeItem(k); // 예전 평문 값 정리
}

export async function onLogout() {
  await SecureStore.deleteItemAsync("access_token", opts);
  await SecureStore.deleteItemAsync("refresh_token", opts);
}
```
