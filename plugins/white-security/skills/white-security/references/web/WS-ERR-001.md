<!-- generated from white-security issues; do not edit -->
# WS-ERR-001 Improper Error Handling (오류 메시지 노출·부적절한 예외 처리)

## 탐지 규칙

- **찾는 곳**: `except Exception`, `getMessage()`, `err.stack`, `getTraceAsString(`, `traceback.format_exc(`, 그 밖에 except: pass·빈 catch, str(e)를 응답에 넣는 코드.
- **검사 1 — 오류 원문 노출**: 지적할 때: 예외 메시지·스택(`e.message`, `err.stack`, `getMessage()`, `str(e)`, `traceback`)을 응답 본문에 넣는 경우. / 지적하지 않을 때: 사용자가 고칠 수 있는 검증 오류(필드 이름·형식 안내)만 돌려주는 경우. 원문은 로그에만 쓰는 경우.
- **검사 2 — 실패를 통과로 처리(fail-open)**: 지적할 때: 토큰 검증·권한 확인·결제 검증·서명 검사를 감싼 `try`에서 예외를 무시하고 다음 처리로 넘어가는 경우. 실패 반환값(`false`, `null`)을 확인하지 않는 경우. / 지적하지 않을 때: 예외에서 401·403·400을 돌려주거나 다시 던지는 경우.
- **검사 3 — 삼킨 예외**: 지적할 때: 빈 `catch`·`except: pass`로 오류를 버려 저장 실패·부분 처리를 성공으로 알리는 경우. / 지적하지 않을 때: 정리 작업처럼 실패해도 되는 곳에서 이유를 주석·로그로 남긴 경우.
- **등급**: 위험: 인증·권한·결제 검사의 fail-open. / 주의: 스택·SQL·경로가 응답에 나오는 경우, 저장 실패를 삼키는 경우. / 참고: 넓은 예외 잡기, 마지막 처리기 없음.
- **보고 예**: `[위험] WS-ERR-001 middleware/auth.js:8 — 토큰 검증 예외를 무시하고 next() 호출 → catch 에서 401 응답 후 종료`
- **고칠 방법**: 고정 문구에 로그와 짝지을 오류 번호(요청 id)를 함께 돌려줘야 하고, 운영 설정의 디버그 모드가 꺼져 있는지도 확인해야 한다.

## 점검 항목

- 응답에는 고정 문구와 오류 번호만 넣고, 원문·스택은 서버 로그에만 남겨야 한다.
- 인증·권한·결제 검사에서 예외가 나면 거절(fail closed)로 끝나야 한다.
- 빈 `catch`·`except: pass`로 예외를 삼키지 않아야 한다.
- 넓은 `catch (Exception)` 대신 예상한 예외만 잡아야 한다.
- 모든 예외를 받는 마지막 처리기를 둬야 한다.
- `preg_match`·`json_decode`처럼 실패를 `false`·`null`로 알리는 함수는 반환값을 확인해야 한다.

Code examples (read only the file for the stack you are writing): `WS-ERR-001.code.laravel.md` (Laravel) · `WS-ERR-001.code.express.md` (Express) · `WS-ERR-001.code.fastapi.md` (FastAPI)

기본 심각도: 주의
기준: OWASP Top 10:2025 A10(예외 상황 처리 미흡) · OWASP API 2023 API8(보안 설정 오류) · ASVS 5.0 V16.5.1 · ASVS 5.0 V16.5.2 · ASVS 5.0 V16.5.3 · ASVS 5.0 V16.5.4 · CWE-209 · CWE-390 · CWE-754 · CWE-755 · CWE-636 · 국내 보안약점 49 에러처리-1(오류 메시지 정보노출) · 국내 보안약점 49 에러처리-2(오류상황 대응 부재) · 국내 보안약점 49 에러처리-3(부적절한 예외 처리) · 기반시설 웹 21 EP(에러 페이지 미흡)
