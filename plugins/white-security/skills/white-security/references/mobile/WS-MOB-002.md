<!-- generated from white-security issues; do not edit -->
# WS-MOB-002 Insecure Data Storage (토큰·개인정보 저장 위치)

심각도: 위험
기준: OWASP Top 10:2025 A04(암호화 실패) · OWASP Mobile 2024 M9(안전하지 않은 데이터 저장) · OWASP Mobile 2024 M6(개인정보 보호 통제 미흡) · OWASP Mobile 2024 M10(암호화 부족) · ASVS 5.0 V14.2.4 · ASVS 5.0 V14.3.1 · ASVS 5.0 V14.3.3 · ASVS 5.0 V11.1.1 · CWE-312 · CWE-922 · CWE-532 · CWE-321 · 국내 보안약점 49 보안기능-5(암호화되지 않은 중요정보) · 국내 보안약점 49 보안기능-6(하드코드된 중요정보)

## 점검 항목

- 로그인 토큰·리프레시 토큰·비밀번호는 Keychain·Keystore 기반 저장소에만 두어야 한다.
- AsyncStorage·SharedPreferences·UserDefaults·MMKV에는 화면 설정 같은 민감하지 않은 값만 두어야 한다.
- 앱 코드에 박은 키로 직접 암호화하지 않고, 키는 Keystore·Keychain이 만들고 보관하게 해야 한다.
- 로그(`console.log`·`print`·`Log.d`)에 토큰·개인정보를 남기지 않아야 한다.
- 민감한 저장소는 기기 백업(`android:allowBackup`·백업 규칙)에서 빼야 한다.
- 로그아웃·탈퇴 때 기기에 저장한 토큰과 개인정보를 지워야 한다.

## 탐지 규칙

- **찾는 곳**: `AsyncStorage.setItem(`, `SharedPreferences.getInstance(`, `getSharedPreferences(`, `UserDefaults.standard`, `react-native-mmkv`, `android:allowBackup="true"`, `CryptoJS.AES.encrypt(`, `EncryptedSharedPreferences`, 그 밖에 토큰·비밀번호·주민등록번호를 저장하는 SQLite·파일 쓰기, 토큰을 찍는 로그.
- **검사 1 — 평문 저장소의 민감값**: 지적할 때: 토큰·비밀번호·카드·신분증 번호를 AsyncStorage·SharedPreferences·UserDefaults·MMKV·일반 파일·SQLite에 평문으로 저장하는 경우, Expo 웹 빌드에서 토큰을 localStorage에 두는 경우. / 지적하지 않을 때: `expo-secure-store`·`react-native-keychain`·`flutter_secure_storage`·Keychain·Keystore를 쓰는 경우, 테마·언어 같은 민감하지 않은 값.
- **검사 2 — 앱 안의 키로 직접 암호화**: 지적할 때: 앱 코드에 들어 있는 고정 키로 `CryptoJS.AES.encrypt`·직접 만든 암호화를 해서 저장하는 경우, ECB·고정 IV. / 지적하지 않을 때: Keystore·Keychain이 만든 꺼낼 수 없는 키로 AES-GCM 암호화하는 경우.
- **검사 3 — 로그·백업으로 새는 값**: 지적할 때: 토큰·개인정보를 로그로 찍는 경우, 민감한 저장소가 백업 대상에 포함된 경우. / 지적하지 않을 때: 개발 빌드에서만 켜지는 로그에 가린 값만 찍는 경우, 백업 규칙에서 해당 파일을 뺀 경우.
- **등급**: 위험: 로그인 토큰·비밀번호·고유식별정보를 평문 저장소에 저장, 앱 코드 속 키로 암호화. / 주의: 운영 로그에 토큰·개인정보, 민감 저장소가 백업 대상, 지원 중단된 `EncryptedSharedPreferences`를 새로 도입. / 참고: 로그아웃 때 저장값을 지우지 않음.
- **보고 예**: `[위험] WS-MOB-002 src/auth/session.ts:18 — AsyncStorage.setItem("refresh_token", …) 로 리프레시 토큰을 평문 저장 → SecureStore.setItemAsync 로 옮기고 기존 값 삭제`
- **검토 주의**: `expo-secure-store`는 iOS에서 Keychain, Android에서 Keystore로 암호화한 SharedPreferences에 저장하고(Expo 문서), 기본 접근 시점은 기기 잠금 해제 상태다. 큰 값을 넣을 때 플랫폼 오류가 날 수 있으므로 토큰 같은 작은 값만 넣는다. Jetpack Security(`EncryptedSharedPreferences`)는 1.1.0에서 모든 API가 지원 중단돼 새 코드에는 Keystore를 직접 쓴다. Keychain·Keystore도 루팅·탈옥 기기에서 앱이 실행 중일 때는 읽힐 수 있으므로 토큰 수명을 짧게 하고 서버에서 폐기할 수 있어야 한다(WS-SESS-001).
- **고칠 방법**: 토큰·비밀값을 Keychain·Keystore 기반 저장소로 옮기고 기존 평문 값을 지우며, 로그·백업에서 빼야 한다.

Code examples (read only the file for the stack you are writing): `WS-MOB-002.code.expo.md` (Expo) · `WS-MOB-002.code.flutter.md` (Flutter) · `WS-MOB-002.code.kotlin.md` (Kotlin)
