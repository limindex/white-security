---
id: WS-ERR-001
title: Improper Error Handling (오류 메시지 노출·부적절한 예외 처리)
slug: error-handling
group: web
severity: medium
site_only: false
depth: brief
languages: [laravel, express, fastapi]
maps:
  owasp2025: [A10]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  asvs5: [V16.5.1, V16.5.2, V16.5.3, V16.5.4]
  cwe: [CWE-209, CWE-390, CWE-754, CWE-755, CWE-636]
  kr_guide: ["에러처리-1", "에러처리-2", "에러처리-3"]
  kr_ciip: [EP]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Express·Node.js, FastAPI, 모든 웹 애플리케이션·API]
severity_reason: "영향: 오류 문장으로 DB 구조·경로·키 노출, 검사 실패를 성공으로 처리해 인증·결제 우회 · 악용: 잘못된 값 하나로 오류를 일으키는 것만으로 가능"
sources:
  - title: OWASP Error Handling Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Error_Handling_Cheat_Sheet.html
    year: 2026
  - title: "OWASP Top 10:2025 - A10 Mishandling of Exceptional Conditions"
    url: https://top10.owasp.org/2025/A10_2025-Mishandling_of_Exceptional_Conditions
    year: 2025
  - title: "OWASP API Security Top 10 2023 - API8 Security Misconfiguration (스택 트레이스 노출)"
    url: https://owasp.org/API-Security/editions/2023/en/0xa8-security-misconfiguration/
    year: 2023
  - title: "CWE-209: Generation of Error Message Containing Sensitive Information"
    url: https://cwe.mitre.org/data/definitions/209.html
    year: 2025
---

## 요약

오류가 나면 스택 트레이스·SQL 문장을 화면에 그대로 보여 주거나, 예외를 삼켜(`catch {}`) 검사가 실패했는데도 다음 단계로 넘어가는 문제(진단가이드 이름: 오류 메시지 정보노출·오류상황 대응 부재·부적절한 예외 처리). 인증·결제 검사의 실패를 통과로 처리하는 fail-open이 가장 위험.

## 대응 방법

```text
catch·except 블록을 모두 찾아, 예외를 무시하거나(pass, 빈 catch) 오류 원문(e.message, stack, getMessage)을 응답에 넣는 곳을 알려 줘.
사용자에게는 고정 문구와 오류 번호만 보내고, 원문은 서버 로그에 남기게 바꿔 줘.
인증·권한·결제·입력 검사에서 예외가 나면 거절로 끝나게 바꾸고, 모든 예외를 받는 마지막 처리기를 하나 둬 줘.
```

- 일부러 DB 오류를 내도 화면에는 고정 문구와 오류 번호만 보여야 한다.

## 점검 항목

- 응답에는 고정 문구와 오류 번호만 넣고, 원문·스택은 서버 로그에만 남겨야 한다.
- 인증·권한·결제 검사에서 예외가 나면 거절(fail closed)로 끝나야 한다.
- 빈 `catch`·`except: pass`로 예외를 삼키지 않아야 한다.
- 넓은 `catch (Exception)` 대신 예상한 예외만 잡아야 한다.
- 모든 예외를 받는 마지막 처리기를 둬야 한다.
- `preg_match`·`json_decode`처럼 실패를 `false`·`null`로 알리는 함수는 반환값을 확인해야 한다.

## 탐지 규칙

- **찾는 곳**: `except Exception`, `catch (e)`, `getMessage()`, `err.stack`, 그 밖에 except: pass·빈 catch·getTraceAsString·traceback·str(e)를 응답에 넣는 코드.
- **검사 1 — 오류 원문 노출**: 지적할 때: 예외 메시지·스택(`e.message`, `err.stack`, `getMessage()`, `str(e)`, `traceback`)을 응답 본문에 넣는 경우. / 지적하지 않을 때: 사용자가 고칠 수 있는 검증 오류(필드 이름·형식 안내)만 돌려주는 경우. 원문은 로그에만 쓰는 경우.
- **검사 2 — 실패를 통과로 처리(fail-open)**: 지적할 때: 토큰 검증·권한 확인·결제 검증·서명 검사를 감싼 `try`에서 예외를 무시하고 다음 처리로 넘어가는 경우. 실패 반환값(`false`, `null`)을 확인하지 않는 경우. / 지적하지 않을 때: 예외에서 401·403·400을 돌려주거나 다시 던지는 경우.
- **검사 3 — 삼킨 예외**: 지적할 때: 빈 `catch`·`except: pass`로 오류를 버려 저장 실패·부분 처리를 성공으로 알리는 경우. / 지적하지 않을 때: 정리 작업처럼 실패해도 되는 곳에서 이유를 주석·로그로 남긴 경우.
- **등급**: 위험: 인증·권한·결제 검사의 fail-open. / 주의: 스택·SQL·경로가 응답에 나오는 경우, 저장 실패를 삼키는 경우. / 참고: 넓은 예외 잡기, 마지막 처리기 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-ERR-001 middleware/auth.js:8 — 토큰 검증 예외를 무시하고 next() 호출 → catch 에서 401 응답 후 종료`
- **고칠 방법**: 고정 문구에 로그와 짝지을 오류 번호(요청 id)를 함께 돌려줘야 하고, 운영 설정의 디버그 모드가 꺼져 있는지도 확인해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
public function show(int $id)
{
    try {
        return Order::findOrFail($id);
    } catch (\Throwable $e) {
        return response($e->getMessage() . "\n" . $e->getTraceAsString(), 500); // SQL·경로가 그대로 나간다
    }
}
```

✓ 안전 코드

```php
public function show(Request $request, int $id)
{
    $order = Order::whereKey($id)->where('user_id', $request->user()->id)->first();
    abort_if(! $order, 404);                  // 예상한 상황은 상태 코드로
    return $order;
}
// 예상 못 한 예외는 잡지 않고 Laravel 처리기에 맡긴다.
// APP_DEBUG=false 면 화면은 고정 문구, 원문은 storage/logs 에만 남는다.
```

### express

✗ 취약 코드

```js
function auth(req, res, next) {
  try {
    req.user = jwt.verify(req.cookies.token, process.env.JWT_SECRET);
  } catch (e) {
    console.log(e);                         // 실패를 삼키고
  }
  next();                                   // 검증 실패여도 다음으로(fail-open)
}
app.use((err, req, res, next) => res.status(500).json({ error: err.message, stack: err.stack })); // 원문 노출
```

✓ 안전 코드

```js
function auth(req, res, next) {
  try {
    req.user = jwt.verify(req.cookies.token, process.env.JWT_SECRET, { algorithms: ["HS256"] });
  } catch {
    return res.status(401).json({ error: "다시 로그인해 주세요" }); // 실패하면 거절로 끝낸다
  }
  next();
}
app.use((err, req, res, next) => {          // 마지막 처리기: 원문은 로그, 응답은 고정 문구
  const ref = crypto.randomUUID();
  console.error(ref, err);
  if (res.headersSent) return next(err);
  res.status(500).json({ error: "처리하지 못했습니다", ref });
});
```

### fastapi

✗ 취약 코드

```python
@app.post("/orders")
def create_order(body: OrderIn):
    try:
        verify_payment(body.payment_id, body.amount)   # 결제 검증
    except Exception:
        pass                                          # 검증 실패를 무시하고 주문 생성
    return save_order(body)
```

✓ 안전 코드

```python
import logging, uuid
log = logging.getLogger(__name__)

@app.post("/orders")
def create_order(body: OrderIn):
    try:
        verify_payment(body.payment_id, body.amount)
    except PaymentError:                              # 예상한 실패만 잡아 거절
        raise HTTPException(402, "결제를 확인하지 못했습니다")
    return save_order(body)                           # 그 밖의 예외는 아래 처리기로 → 주문 생성 안 됨

@app.exception_handler(Exception)                    # 마지막 처리기
async def unhandled(request: Request, exc: Exception):
    ref = uuid.uuid4().hex
    log.exception("unhandled %s", ref)                # 원문·스택은 로그에만
    return JSONResponse({"error": "처리하지 못했습니다", "ref": ref}, status_code=500)
```
