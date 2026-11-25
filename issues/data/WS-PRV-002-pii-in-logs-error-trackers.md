---
id: WS-PRV-002
title: Sensitive Data in Logs · Error Trackers (로그·오류 추적 도구에 남는 개인정보)
slug: pii-in-logs-error-trackers
group: data
severity: medium
site_only: false
depth: brief
languages: [fastapi, nextjs]
maps:
  owasp2025: [A09, A02]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V16.2.5, V14.2.1, V14.2.3, V16.4.2]
  cwe: [CWE-532, CWE-359, CWE-200]
  kr_guide: []
  kr_ciip: [IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [FastAPI·Python, Next.js·JavaScript, 오류 추적·세션 리플레이·로그 수집 서비스를 쓰는 모든 앱]
severity_reason: "영향: 로그·외부 오류 추적 서비스에 회원 개인정보·토큰이 쌓여 그 서비스 계정 하나로 대량 유출 · 악용: 로그 열람 권한이나 외부 서비스 계정만 있으면 가능"
sources:
  - title: "OWASP ASVS 5.0 - V14 Data Protection, V16 Security Logging and Error Handling"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: OWASP Logging Cheat Sheet (Data to exclude)
    url: https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html
    year: 2026
  - title: "Sentry Docs (Python) - Data Collected"
    url: https://docs.sentry.io/platforms/python/data-management/data-collected/
    year: 2026
  - title: "Sentry Docs (Next.js) - Session Replay Privacy"
    url: https://docs.sentry.io/platforms/javascript/guides/nextjs/session-replay/privacy/
    year: 2026
  - title: "pino - Redaction"
    url: https://getpino.io/#/docs/redaction
    year: 2026
  - title: "CWE-532: Insertion of Sensitive Information into Log File"
    url: https://cwe.mitre.org/data/definitions/532.html
    year: 2025
---

## 요약

요청 본문 전체를 찍는 로그, 오류 추적 도구(Sentry 등)가 자동으로 모으는 요청 본문·지역 변수, 입력값 가림을 끈 세션 리플레이, URL 쿼리에 넣은 전화번호·이메일 때문에 개인정보와 토큰이 로그 파일과 외부 서비스에 쌓이는 문제. 이런 도구의 기본 가림은 비밀번호·쿠키 같은 정해진 이름 위주라 주민등록번호·전화번호 값은 그대로 남을 수 있음.

## 대응 방법

```text
로그 호출(logger·console.log·Log::)과 Sentry 같은 오류 추적·세션 리플레이 설정을 찾아, 개인정보·토큰이 남을 수 있는 곳을 표로 보여 줘.
요청 본문·사용자 객체 전체를 찍는 로그는 사용자 id·요청 id만 남기게 바꾸고, 로거에 가림(redact) 설정을 넣어 줘.
오류 추적 도구에는 보내기 전 가림 함수(before_send)를 넣어 요청 본문·쿠키·지역 변수를 빼고, 전화번호·이메일을 URL 쿼리로 받는 API는 POST 본문으로 바꿔 줘.
```

- 일부러 전화번호를 넣은 요청으로 오류를 낸 뒤 로그와 오류 추적 화면에 그 번호가 보이지 않아야 한다.
- 접근 로그(웹 서버 로그)에 전화번호·이메일이 든 URL이 없어야 한다.

## 점검 항목

- 요청 본문·사용자 객체 전체를 로그에 남기지 않아야 한다.
- 로거에는 개인정보·토큰 필드를 가리는 설정을 둬야 한다.
- 오류 추적 도구는 보내기 전 요청 본문·쿠키·지역 변수를 빼야 한다.
- 개인정보 자동 수집 옵션(`send_default_pii`)을 켜지 않아야 한다.
- 전화번호·이메일 같은 개인정보는 URL 쿼리에 넣지 않아야 한다.
- 외부 로그·오류 도구의 보관 기간과 열람 권한을 최소로 정해야 한다.

## 탐지 규칙

- **찾는 곳**: `sentry_sdk.init(`, `Sentry.init(`, `send_default_pii`, `sendDefaultPii`, `maskAllText`, `before_send`, `beforeSend`, `redact:`, 그 밖에 요청 본문·헤더·사용자 객체를 통째로 넘기는 로그 호출, 개인정보를 쿼리 문자열로 받는 GET 라우트, 분석 이벤트 속성, AI 호출 추적 도구 설정.
- **검사 1 — 도구가 자동으로 모으는 값**: 지적할 때: `send_default_pii=True`·`sendDefaultPii: true`인 경우, 세션 리플레이의 입력값 가림을 끈 경우(`maskAllText: false`), 회원가입·결제처럼 개인정보를 받는 앱에서 오류 추적 도구를 쓰면서 보내기 전 가림 함수가 없는 경우. / 지적하지 않을 때: `before_send`·`beforeSend`에서 요청 본문·쿠키·지역 변수를 빼거나 가리는 경우, 리플레이 기본 가림을 그대로 둔 경우.
- **검사 2 — 직접 남기는 로그**: 지적할 때: 요청 본문 전체·헤더 전체·사용자 객체를 로그·`console.log`·분석 이벤트에 넣는 경우, 전화번호·이메일·주민등록번호를 URL 쿼리로 받아 접근 로그와 Referer에 남는 경우. / 지적하지 않을 때: 사용자 id·요청 id 같은 식별자만 남기거나 로거 가림 설정(`redact`)으로 가린 경우.
- **등급**: 위험: 주민등록번호·카드번호·비밀번호·토큰이 로그나 외부 도구로 가는 경우. / 주의: `send_default_pii` 켬, 요청 본문 전체 로그, URL 쿼리 속 전화번호·이메일, 리플레이 가림 해제. / 참고: 외부 도구 보관 기간·열람 권한을 저장소에서 확인할 수 없는 경우("확인 필요").
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[주의] WS-PRV-002 app/main.py:9 — sentry_sdk.init(send_default_pii=True) 로 회원가입 요청 본문·IP가 외부로 전송 → 옵션을 빼고 before_send 로 요청 본문·지역 변수 제거`
- **검토 주의**: 어떤 보안 사건을 남길지는 WS-LOG-001, 접속기록 법정 항목은 WS-PRV-003, AI API로 보내는 개인정보는 WS-LLM-003에서 다룬다. 개인정보를 외부(특히 국외) 오류 추적 서비스로 보내면 처리 위탁·국외 이전에 해당할 수 있으므로, 가림이 어려운 값을 보내야 한다면 처리방침 기재 여부를 사용자에게 확인하도록 안내해야 한다.
- **고칠 방법**: 로그에는 식별자만 남기고, 로거 가림 설정과 오류 추적 도구의 보내기 전 가림 함수를 한곳에 둬야 하며, 이미 쌓인 로그 속 개인정보는 보관 기간 설정과 삭제로 정리해야 한다.

## 코드 예시

### fastapi

✗ 취약 코드

```python
import logging, os, sentry_sdk
from fastapi import FastAPI, Request

sentry_sdk.init(dsn=os.environ["SENTRY_DSN"], send_default_pii=True)   # IP·사용자 정보까지 자동 수집
app = FastAPI()
log = logging.getLogger("app")

@app.post("/signup")
async def signup(req: Request):
    body = await req.json()
    log.info("signup %s", body)          # 이름·전화번호·생년월일이 로그 파일에
    return create_user(body)             # 여기서 오류가 나면 지역 변수 body 도 오류 보고에 실림
```

✓ 안전 코드

```python
import logging, os, re, sentry_sdk
from fastapi import FastAPI

PII = re.compile(r"\d{6}-?[1-8]\d{6}|01[016789]-?\d{3,4}-?\d{4}|[\w.+-]+@[\w-]+\.[\w.]+")

def scrub(event, hint):
    req = event.get("request", {})
    for key in ("data", "cookies", "query_string"):
        req.pop(key, None)                                     # 요청 본문·쿠키·쿼리는 보내지 않음
    for exc in (event.get("exception") or {}).get("values", []):
        exc["value"] = PII.sub("[가림]", exc.get("value") or "")  # 오류 메시지 속 번호·메일 가림
        for frame in (exc.get("stacktrace") or {}).get("frames", []):
            frame.pop("vars", None)                            # 지역 변수 값 제거
    for crumb in (event.get("breadcrumbs") or {}).get("values", []):
        crumb["message"] = PII.sub("[가림]", crumb.get("message") or "")
        crumb.pop("data", None)                                # 로그 인자·extra 값
    log = event.get("logentry") or {}
    for key in ("message", "formatted"):
        if isinstance(log.get(key), str):
            log[key] = PII.sub("[가림]", log[key])             # 로그 메시지 가림
    log.pop("params", None)                                    # 포맷 인자(원문 값)는 빼기
    event.pop("extra", None)                                   # logging extra 등 임의 값
    return event

# send_default_pii 는 켜지 않는다(기본 꺼짐)
sentry_sdk.init(dsn=os.environ["SENTRY_DSN"], before_send=scrub)
app = FastAPI()
log = logging.getLogger("app")

@app.post("/signup")
async def signup(body: SignupIn):
    user = create_user(body)
    log.info("signup user_id=%s", user.id)                     # 식별자만
    return {"id": user.id}
```

### nextjs

✗ 취약 코드

```ts
// app/api/orders/route.ts — /api/orders?phone=010...&orderNo=... : 번호가 접근 로그·Referer 에 남음
export async function GET(req: Request) {
  const q = new URL(req.url).searchParams;
  console.log("order lookup", { phone: q.get("phone"), headers: Object.fromEntries(req.headers) }); // 쿠키까지
  return Response.json(await findGuestOrder(q.get("orderNo"), q.get("phone")));
}

// instrumentation-client.ts
Sentry.init({ dsn: process.env.NEXT_PUBLIC_SENTRY_DSN, sendDefaultPii: true,
  integrations: [Sentry.replayIntegration({ maskAllText: false })] });   // 화면 입력값까지 녹화
```

✓ 안전 코드

```ts
// lib/log.ts
import pino from "pino";
export const log = pino({
  redact: { paths: ["phone", "email", "*.phone", "*.email", "req.headers.cookie", "req.headers.authorization"], censor: "[가림]" },
});

// app/api/orders/lookup/route.ts — 개인정보는 POST 본문으로 받고 로그엔 식별자만
export async function POST(req: Request) {
  const { orderNo, phone } = await req.json();
  const order = await findGuestOrder(orderNo, phone);
  log.info({ orderId: order?.id ?? null }, "guest order lookup");
  return order ? Response.json({ id: order.id, status: order.status }) : new Response(null, { status: 404 });
}

// instrumentation-client.ts — sendDefaultPii 는 켜지 않고(기본 꺼짐), 리플레이 가림은 기본값 유지
Sentry.init({ dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  integrations: [Sentry.replayIntegration({ maskAllText: true, blockAllMedia: true })] });
```
