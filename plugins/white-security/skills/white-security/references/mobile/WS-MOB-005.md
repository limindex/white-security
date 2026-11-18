<!-- generated from white-security issues; do not edit -->
# WS-MOB-005 Client-side Trust in Mobile Apps (앱에서만 하는 결제·권한·탈옥 판단)

심각도: 위험
기준: OWASP Top 10:2025 A01(접근 통제 실패) · OWASP Top 10:2025 A06(안전하지 않은 설계) · OWASP API 2023 API5(기능 수준 권한 검증 실패) · OWASP API 2023 API6(민감한 업무 흐름 접근 무제한) · OWASP Mobile 2024 M3(취약한 인증·인가) · OWASP Mobile 2024 M8(보안 설정 오류) · OWASP Mobile 2024 M7(바이너리 보호 부족) · ASVS 5.0 V8.3.1 · ASVS 5.0 V2.2.2 · ASVS 5.0 V2.3.1 · CWE-602 · CWE-862(권한 검사 누락) · CWE-807 · CWE-345 · 국내 보안약점 49 입력데이터 검증 및 표현-15(보안기능 결정에 사용되는 부적절한 입력값) · 국내 보안약점 49 보안기능-2(부적절한 인가) · 기반시설 웹 21 IN(불충분한 권한 검증) · 기반시설 웹 21 PV(프로세스 검증 누락)

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
- **보고 예**: `[위험] WS-MOB-005 src/paywall.tsx:40 — 결제 성공 콜백에서 AsyncStorage isPremium=true 로 잠금 해제, 서버 /lessons/premium 은 구독 확인 없음 → 서버가 subscriptionsv2.get 으로 확인 후 권한 저장, API 에서 권한 확인`
- **검토 주의**: Google은 구매 토큰을 백엔드로 보내 Play Developer API로 확인하고, 이전 토큰과 겹치지 않는지 본 뒤 권한을 주라고 안내한다(Play Billing 문서). 다만 구독 갱신·복원은 같은 purchaseToken(iOS는 같은 originalTransactionId)으로 다시 오므로, 같은 계정이면 다시 확인해 만료 시각을 갱신하고 다른 계정일 때만 거절해야 한다. App Store 심사는 샌드박스에서 구매하므로 운영 조회가 `TransactionIdNotFound`면 샌드박스로 다시 조회한다. 루팅·탈옥 검사는 기기를 가진 사람이 우회할 수 있다. 웹 쪽의 클라이언트 판단 신뢰는 WS-AUTH-005, 결제 금액 변조·웹훅은 WS-PAY-001, 역할 필드 덮어쓰기는 WS-AUTH-006에서 다룬다.
- **고칠 방법**: 구매는 서버가 스토어 API로 확인해 계정에 묶어 저장하고, 유료·관리 API는 요청마다 서버 권한을 확인해야 한다.

Code examples (read only the file for the stack you are writing): `WS-MOB-005.code.react-native.md` (React Native) · `WS-MOB-005.code.flutter.md` (Flutter) · `WS-MOB-005.code.express.md` (Express)
