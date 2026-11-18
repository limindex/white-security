---
id: WS-MOB-002
title: Insecure Data Storage (토큰·개인정보 저장 위치)
slug: insecure-data-storage
group: mobile
severity: high
site_only: false
depth: brief
languages: [expo, flutter, kotlin]
maps:
  owasp2025: [A04]
  api2023: []
  llm2026: []
  agentic2026: []
  owasp_mobile2024: [M9, M6, M10]
  asvs5: [V14.2.4, V14.3.1, V14.3.3, V11.1.1]
  cwe: [CWE-312, CWE-922, CWE-532, CWE-321]
  kr_guide: ["보안기능-5", "보안기능-6"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [React Native·Expo, Flutter, Android(Kotlin), 로그인 토큰·개인정보를 기기에 저장하는 앱]
severity_reason: "영향: 기기 백업·루팅 기기·분실 기기에서 로그인 토큰과 개인정보를 그대로 읽어 계정 탈취 · 악용: 저장소 파일이 평문이라 꺼내기만 하면 됨"
sources:
  - title: "OWASP Mobile Top 10 2024 - M9: Insecure Data Storage"
    url: https://owasp.org/www-project-mobile-top-10/2023-risks/m9-insecure-data-storage
    year: 2024
  - title: "OWASP MASVS v2.1 - MASVS-STORAGE"
    url: https://mas.owasp.org/MASVS/05-MASVS-STORAGE/
    year: 2026
  - title: "React Native Docs - Security (Storing Sensitive Info: Async Storage is unencrypted)"
    url: https://reactnative.dev/docs/security
    year: 2026
  - title: "Expo Docs - SecureStore (SDK 58)"
    url: https://docs.expo.dev/versions/latest/sdk/securestore/
    year: 2026
  - title: "flutter_secure_storage 11.2 (pub.dev)"
    url: https://pub.dev/packages/flutter_secure_storage
    year: 2026
  - title: "Android Developers - Android Keystore system"
    url: https://developer.android.com/privacy-and-security/keystore
    year: 2026
  - title: "Android Developers - Jetpack Security release notes (security-crypto 1.1.0: 모든 API 지원 중단)"
    url: https://developer.android.com/jetpack/androidx/releases/security
    year: 2026
---

## 요약

로그인 토큰·개인정보를 AsyncStorage, SharedPreferences, UserDefaults, MMKV처럼 암호화되지 않는 저장소에 두어, 기기 백업이나 루팅·탈옥된 기기에서 그대로 읽히는 문제. 토큰은 iOS Keychain·Android Keystore를 쓰는 `expo-secure-store`·`flutter_secure_storage`에 두고, 앱 코드에 박은 키로 직접 암호화하는 방식은 쓰지 않아야 함.

## 대응 방법

```text
AsyncStorage, SharedPreferences, UserDefaults, MMKV, SQLite, 파일에 저장하는 값을 찾아, 토큰·비밀번호·개인정보가 있는지 표로 보여 줘.
토큰·비밀값은 expo-secure-store(또는 react-native-keychain), flutter_secure_storage, Android Keystore 로 옮기고, 기존 평문 값은 앱 업데이트 때 지워 줘.
개인정보는 꼭 필요한 것만 기기에 남기고, 로그·백업 대상에서 빼 줘.
```

- 루팅된 에뮬레이터나 기기 백업에서 앱 데이터 폴더를 열어도 토큰·개인정보가 평문으로 보이지 않아야 한다.
- 로그아웃하면 기기에 저장한 토큰이 지워져야 한다.

## 점검 항목

- 로그인 토큰·리프레시 토큰·비밀번호는 Keychain·Keystore 기반 저장소에만 두어야 한다.
- AsyncStorage, SharedPreferences, UserDefaults, MMKV에는 화면 설정 같은 민감하지 않은 값만 두어야 한다.
- 앱 코드에 박은 키로 직접 암호화하지 않고, 키는 Keystore·Keychain이 만들고 보관하게 해야 한다.
- 로그(`console.log`·`print`·`Log.d`)에 토큰·개인정보를 남기지 않아야 한다.
- 민감한 저장소는 기기 백업(`android:allowBackup`·백업 규칙)에서 빼야 한다.
- 로그아웃·탈퇴 때 기기에 저장한 토큰과 개인정보를 지워야 한다.

## 탐지 규칙

- **찾는 곳**: `AsyncStorage.setItem(`, `SharedPreferences.getInstance(`, `getSharedPreferences(`, `UserDefaults.standard`, `react-native-mmkv`, `android:allowBackup="true"`, `CryptoJS.AES.encrypt(`, `EncryptedSharedPreferences`, 그 밖에 토큰·비밀번호·주민등록번호를 저장하는 SQLite·파일 쓰기, 토큰을 찍는 로그.
- **검사 1 — 평문 저장소의 민감값**: 지적할 때: 토큰·비밀번호·카드·신분증 번호를 AsyncStorage, SharedPreferences, UserDefaults, MMKV·일반 파일·SQLite에 평문으로 저장하는 경우, Expo 웹 빌드에서 토큰을 localStorage에 두는 경우. / 지적하지 않을 때: `expo-secure-store`·`react-native-keychain`·`flutter_secure_storage`·Keychain·Keystore를 쓰는 경우, 테마·언어 같은 민감하지 않은 값.
- **검사 2 — 앱 안의 키로 직접 암호화**: 지적할 때: 앱 코드에 들어 있는 고정 키로 `CryptoJS.AES.encrypt`·직접 만든 암호화를 해서 저장하는 경우, ECB·고정 IV. / 지적하지 않을 때: Keystore·Keychain이 만든 꺼낼 수 없는 키로 AES-GCM 암호화하는 경우.
- **검사 3 — 로그·백업으로 새는 값**: 지적할 때: 토큰·개인정보를 로그로 찍는 경우, 민감한 저장소가 백업 대상에 포함된 경우. / 지적하지 않을 때: 개발 빌드에서만 켜지는 로그에 가린 값만 찍는 경우, 백업 규칙에서 해당 파일을 뺀 경우.
- **등급**: 위험: 로그인 토큰·비밀번호·고유식별정보를 평문 저장소에 저장, 앱 코드 속 키로 암호화. / 주의: 운영 로그에 토큰·개인정보, 민감 저장소가 백업 대상, 지원 중단된 `EncryptedSharedPreferences`를 새로 도입. / 참고: 로그아웃 때 저장값을 지우지 않음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-MOB-002 src/auth/session.ts:18 — AsyncStorage.setItem("refresh_token", …) 로 리프레시 토큰을 평문 저장 → SecureStore.setItemAsync 로 옮기고 기존 값 삭제`
- **검토 주의**: `expo-secure-store`는 iOS에서 Keychain, Android에서 Keystore로 암호화한 SharedPreferences에 저장하고(Expo 문서), 기본 접근 시점은 기기 잠금 해제 상태다. 큰 값을 넣을 때 플랫폼 오류가 날 수 있으므로 토큰 같은 작은 값만 넣는다. Jetpack Security(`EncryptedSharedPreferences`)는 1.1.0에서 모든 API가 지원 중단돼 새 코드에는 Keystore를 직접 쓴다. Keychain·Keystore도 루팅·탈옥 기기에서 앱이 실행 중일 때는 읽힐 수 있으므로 토큰 수명을 짧게 하고 서버에서 폐기할 수 있어야 한다(WS-SESS-001).
- **고칠 방법**: 토큰·비밀값을 Keychain·Keystore 기반 저장소로 옮기고 기존 평문 값을 지우며, 로그·백업에서 빼야 한다.

## 코드 예시

### expo

✗ 취약 코드

```ts
import AsyncStorage from "@react-native-async-storage/async-storage";

export async function onLogin(session: { accessToken: string; refreshToken: string; user: { phone: string } }) {
  await AsyncStorage.setItem("access_token", session.accessToken);   // 암호화 없는 저장소
  await AsyncStorage.setItem("refresh_token", session.refreshToken);
  await AsyncStorage.setItem("user", JSON.stringify(session.user));  // 전화번호까지 평문
  console.log("login ok", session);                                  // 토큰이 기기 로그에 남음
}
```

✓ 안전 코드

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

### flutter

✗ 취약 코드

```dart
import 'package:shared_preferences/shared_preferences.dart';

Future<void> onLogin(String accessToken, String refreshToken) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString('access_token', accessToken);   // Android XML·iOS plist 에 평문
  await prefs.setString('refresh_token', refreshToken);
  print('refresh=$refreshToken');                        // 로그에 토큰
}
```

✓ 안전 코드

```dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

const storage = FlutterSecureStorage(
  iOptions: IOSOptions(accessibility: KeychainAccessibility.unlocked_this_device), // Keychain
  // Android 기본값: Keystore 로 감싼 키 + AES-GCM (11.x)
);

Future<void> onLogin(String accessToken, String refreshToken) async {
  await storage.write(key: 'access_token', value: accessToken);
  await storage.write(key: 'refresh_token', value: refreshToken);
  final prefs = await SharedPreferences.getInstance();
  await prefs.remove('access_token');                    // 예전 평문 값 정리
  await prefs.remove('refresh_token');
}

Future<void> onLogout() => storage.deleteAll();
// AndroidManifest.xml: <application android:allowBackup="false"> — 백업 복원 시 키 오류도 막음(패키지 문서)
```

### kotlin

✗ 취약 코드

```kotlin
// 앱 안에 박힌 키로 직접 암호화 — APK 를 풀면 키도 함께 나옴
private const val APP_KEY = "0123456789abcdef"

fun saveToken(ctx: Context, token: String) {
    val cipher = Cipher.getInstance("AES/ECB/PKCS5Padding")            // ECB, 고정 키
    cipher.init(Cipher.ENCRYPT_MODE, SecretKeySpec(APP_KEY.toByteArray(), "AES"))
    val enc = Base64.encodeToString(cipher.doFinal(token.toByteArray()), Base64.NO_WRAP)
    ctx.getSharedPreferences("auth", Context.MODE_PRIVATE).edit().putString("token", enc).apply()
}
```

✓ 안전 코드

```kotlin
private const val ALIAS = "auth_token_key"

// 키는 Android Keystore 가 만들고 보관(앱 밖으로 꺼낼 수 없음)
private fun key(): SecretKey {
    val ks = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
    (ks.getKey(ALIAS, null) as? SecretKey)?.let { return it }
    val gen = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore")
    gen.init(KeyGenParameterSpec.Builder(ALIAS, KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
        .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
        .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
        .setKeySize(256)
        .build())
    return gen.generateKey()
}

fun saveToken(ctx: Context, token: String) {
    val c = Cipher.getInstance("AES/GCM/NoPadding").apply { init(Cipher.ENCRYPT_MODE, key()) } // IV 는 매번 새로(12바이트)
    val blob = c.iv + c.doFinal(token.toByteArray())
    ctx.getSharedPreferences("auth", Context.MODE_PRIVATE).edit()
        .putString("token", Base64.encodeToString(blob, Base64.NO_WRAP)).apply()
}

fun loadToken(ctx: Context): String? {
    val s = ctx.getSharedPreferences("auth", Context.MODE_PRIVATE).getString("token", null) ?: return null
    val blob = Base64.decode(s, Base64.NO_WRAP)
    val c = Cipher.getInstance("AES/GCM/NoPadding")
        .apply { init(Cipher.DECRYPT_MODE, key(), GCMParameterSpec(128, blob, 0, 12)) }
    return String(c.doFinal(blob, 12, blob.size - 12))                  // 변조되면 AEADBadTagException
}
```
