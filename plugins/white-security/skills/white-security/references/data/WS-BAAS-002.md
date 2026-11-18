<!-- generated from white-security issues; do not edit -->
# WS-BAAS-002 Insecure Security Rules (Firebase 보안 규칙 개방)

심각도: 위험
기준: OWASP Top 10:2025 A01(접근 통제 실패) · OWASP Top 10:2025 A02(보안 설정 오류) · OWASP API 2023 API1(객체 수준 권한 검증 실패) · OWASP API 2023 API8(보안 설정 오류) · ASVS 5.0 V8.2.2 · ASVS 5.0 V8.3.1 · ASVS 5.0 V13.3.2 · CWE-284(접근 통제 미흡) · CWE-732 · CWE-862(권한 검사 누락) · CWE-798 · 국내 보안약점 49 보안기능-3(중요한 자원에 대한 잘못된 권한 설정) · 국내 보안약점 49 보안기능-2(부적절한 인가) · 국내 보안약점 49 보안기능-6(하드코드된 중요정보) · 기반시설 웹 21 IN(불충분한 권한 검증)

## 점검 항목

- Firestore·Realtime Database·Storage 규칙 어디에도 `if true`가 없어야 한다.
- 테스트 모드 규칙(`request.time <` 기한)은 출시 전에 실제 규칙으로 바꿔야 한다.
- `request.auth != null`만 보지 말고 `request.auth.uid`를 소유자 필드와 비교해야 한다.
- 쓰기 규칙은 소유자·역할 필드를 바꾸지 못하게 `request.resource.data`를 검사해야 한다.
- Realtime Database는 상위 경로에 `.read: true`를 주지 않아야 한다(하위 규칙으로 못 막음).
- 서비스 계정 키는 서버에만 둬야 한다. `firebaseConfig`의 `apiKey`는 공개 값이라 괜찮음.

## 탐지 규칙

- **찾는 곳**: `firestore.rules`, `storage.rules`, `database.rules.json`, `if true`, `request.time <`, `firebase-admin`, `service-account`·`serviceAccountKey` JSON 파일, `cert(`, `".read": true`.
- **검사 1 — 열린 규칙**: 지적할 때: `allow read, write: if true`, `match /{document=**}`에 넓은 허용, `request.time <` 기한부 허용, Realtime Database 루트의 `".read": true`·`".write": true`가 있는 경우. / 지적하지 않을 때: 공지·상품 목록처럼 공개용 컬렉션의 `read`만 `true`이고 쓰기는 막힌 경우.
- **검사 2 — 로그인만 확인**: 지적할 때: 개인 데이터에 `request.auth != null`만 확인해 로그인한 누구나 남의 문서를 읽고 쓰는 경우. 쓰기 규칙이 소유자·역할 필드 변경을 막지 않는 경우. / 지적하지 않을 때: `request.auth.uid == resource.data.ownerId`처럼 소유자와 비교하고, 쓰기에서 `request.resource.data`의 소유자 필드를 고정하는 경우.
- **검사 3 — Admin SDK 키·우회**: 지적할 때: 서비스 계정 JSON이 저장소·클라이언트 번들에 있는 경우. Admin SDK로 요청 id만 보고 읽고 쓰며 ID 토큰 확인이 없는 경우. / 지적하지 않을 때: `apiKey` 등 `firebaseConfig` 값이 클라이언트에 있는 경우(공개 값). Admin SDK가 서버에서 `verifyIdToken` 뒤 소유자를 확인하는 경우.
- **등급**: 위험: 쓰기를 여는 `if true`·기한부 규칙, 서비스 계정 키 노출. / 주의: 로그인만 확인하는 규칙, 소유자 필드를 바꿀 수 있는 쓰기 규칙. / 참고: 규칙 파일이 저장소에 없어 콘솔 확인이 필요한 경우.
- **보고 예**: `[위험] WS-BAAS-002 firestore.rules:5 — 모든 문서에 allow read, write: if true → 컬렉션별로 request.auth.uid 와 ownerId 를 비교하는 규칙으로 교체`
- **고칠 방법**: 규칙을 바꾸기 전에 Emulator 규칙 테스트로 본인 허용·남 거부를 확인해야 하고, `firebase deploy --only firestore:rules`처럼 규칙만 따로 배포해야 한다.

Code examples (read only the file for the stack you are writing): `WS-BAAS-002.code.firebase.md` (Firebase) · `WS-BAAS-002.code.nextjs.md` (Next.js)
