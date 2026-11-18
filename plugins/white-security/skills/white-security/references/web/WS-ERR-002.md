<!-- generated from white-security issues; do not edit -->
# WS-ERR-002 Code Errors (Null 역참조·자원 누수·정수 오버플로)

심각도: 주의
기준: OWASP Top 10:2025 A10(예외 상황 처리 미흡) · ASVS 5.0 V1.4.2 · ASVS 5.0 V1.4.3 · ASVS 5.0 V15.3.5 · CWE-476(널 포인터 역참조) · CWE-404 · CWE-672 · CWE-457 · CWE-190 · CWE-20(입력값 검증 미흡) · 국내 보안약점 49 코드오류-1(Null Pointer 역참조) · 국내 보안약점 49 코드오류-2(부적절한 자원 해제) · 국내 보안약점 49 코드오류-3(해제된 자원 사용) · 국내 보안약점 49 코드오류-4(초기화되지 않은 변수 사용) · 국내 보안약점 49 입력데이터 검증 및 표현-14(정수형 오버플로우)

## 점검 항목

- 직접 빌린 DB 연결·파일은 `finally`·`with`로 오류가 나도 반납해야 한다.
- 반납하거나 닫은 연결·스트림을 다시 쓰지 않아야 한다.
- 조회 결과가 없을 수 있으면 속성에 접근하기 전에 404 등으로 처리해야 한다.
- 수량·금액은 정수 여부와 최소·최대 범위를 서버에서 검사해야 한다.
- 금액은 부동소수점 대신 정수(원 단위)나 `Decimal`로 계산해야 한다.
- 권한 같은 상태 변수는 처음에 거부 값으로 초기화해야 한다.

## 탐지 규칙

- **찾는 곳**: `pool.connect(`, `client.release(`, `parseFloat(`, `fopen(`, 그 밖에 getConnection·open·findOne·first()·get() 결과 바로 쓰기, 요청의 수량·금액 계산.
- **검사 1 — 자원 반납**: 지적할 때: `pool.connect()`·`getConnection()`·`open()`·`fopen()`으로 빌린 자원을 오류 경로에서 반납하지 않는 경우(`finally`·`with` 없음). 반납 뒤 같은 객체를 다시 쓰는 경우. / 지적하지 않을 때: `pool.query()`처럼 라이브러리가 빌리고 돌려주는 API를 쓰는 경우, `with`·`finally`로 닫는 경우.
- **검사 2 — 없는 값 접근**: 지적할 때: `findOne`·`get`·`first()` 결과를 확인 없이 속성에 접근해 500이 나는 경우. / 지적하지 않을 때: `findOrFail`·`get_object_or_404`나 명시적 None·null 확인을 쓰는 경우.
- **검사 3 — 숫자 범위**: 지적할 때: 요청의 수량·금액을 범위 검사 없이 곱하거나 저장하는 경우, 금액을 `parseFloat`·float로 계산하는 경우. / 지적하지 않을 때: 정수·최소·최대 검증 뒤 정수 원 단위나 Decimal로 계산하는 경우.
- **등급**: 위험: 금액 계산에 음수·범위 검사 없음. / 주의: 오류 경로의 연결 누수, 없는 값 접근으로 생기는 500. / 참고: float 금액 계산, 거부 값으로 초기화하지 않은 상태 변수.
- **보고 예**: `[주의] WS-ERR-002 src/transfer.js:14 — pool.connect() 로 빌린 연결을 오류 때 release 하지 않음 → try/finally 로 client.release()`
- **고칠 방법**: 고친 곳마다 오류 경로 테스트(없는 id, 잘못된 수량, DB 오류)를 하나씩 추가해야 한다.

Code examples (read only the file for the stack you are writing): `WS-ERR-002.code.express.md` (Express) · `WS-ERR-002.code.laravel.md` (Laravel) · `WS-ERR-002.code.fastapi.md` (FastAPI)
