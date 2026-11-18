<!-- generated from white-security issues; do not edit -->
# WS-RACE-001 Race Condition (경쟁 조건: 검사 시점과 사용 시점)

심각도: 위험
기준: OWASP Top 10:2025 A06(안전하지 않은 설계) · ASVS 5.0 V15.4.2 · ASVS 5.0 V2.3.3 · ASVS 5.0 V2.3.4 · CWE-362 · CWE-367 · 국내 보안약점 49 시간 및 상태-1(경쟁조건: 검사 시점과 사용 시점)

## 점검 항목

- 확인과 변경은 조건부 `UPDATE ... WHERE` 한 문장이나 행 잠금 트랜잭션으로 묶어야 한다.
- 성공 여부는 바뀐 행 수로 판단해야 한다.
- 한 번만 생겨야 하는 기록에는 DB 고유 제약(unique)을 걸어야 한다.
- 결제·지급 요청은 멱등 키로 두 번째 처리를 막아야 한다.
- 잠금은 메모리 변수가 아니라 DB·Redis처럼 여러 서버가 함께 보는 곳에 둬야 한다.

## 탐지 규칙

- **찾는 곳**: `lockForUpdate(`, `select_for_update(`, `$transaction(`, 그 밖에 쿠폰·포인트·잔액·재고 값을 읽고 따로 저장하는 코드, increment, 결제 완료 콜백·웹훅 처리.
- **검사 1 — 확인 뒤 따로 변경**: 지적할 때: 쿠폰 사용 여부·잔액·재고를 읽어(`find`, `get`, `findUnique`) 조건을 확인한 뒤 별도 문장으로 저장(`save`, `update`)하고, 잠금·조건부 UPDATE가 없는 경우. / 지적하지 않을 때: `UPDATE ... WHERE used_at IS NULL`처럼 조건을 UPDATE 안에 넣고 바뀐 행 수를 확인하는 경우. 트랜잭션 안에서 `lockForUpdate`·`select_for_update`로 읽는 경우.
- **검사 2 — 중복 처리 방지 없음**: 지적할 때: 결제 완료 콜백·웹훅·보상 지급이 같은 주문으로 여러 번 처리될 수 있는 경우(고유 제약·멱등 키 없음). / 지적하지 않을 때: 주문 id 고유 제약이나 처리 기록 테이블로 두 번째 처리를 막는 경우.
- **등급**: 위험: 돈·포인트·쿠폰·재고에 확인 뒤 변경 패턴. / 주의: 가입 보상·투표·좋아요처럼 1회 제한 기능의 같은 패턴. / 참고: 메모리 변수 잠금(서버가 여러 대면 무효).
- **보고 예**: `[위험] WS-RACE-001 app/Http/Controllers/CouponController.php:22 — 쿠폰 used_at 을 읽고 따로 update, 동시 요청에 중복 사용 → whereNull('used_at')->update() 후 바뀐 행 수 확인`
- **고칠 방법**: 고친 뒤 같은 요청을 동시에 여러 번 보내는 테스트(스레드·`Promise.all`)로 한 번만 성공하는지 확인해야 한다.

Code examples (read only the file for the stack you are writing): `WS-RACE-001.code.laravel.md` (Laravel) · `WS-RACE-001.code.django.md` (Django) · `WS-RACE-001.code.nextjs.md` (Next.js)
