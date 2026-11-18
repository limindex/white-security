---
id: WS-SUP-007
title: Unsafe Consumption of APIs (외부 API 응답 무조건 신뢰)
slug: unsafe-consumption-of-apis
group: supply
severity: medium
site_only: false
depth: brief
languages: [fastapi, nextjs]
maps:
  owasp2025: [A08]
  api2023: [API10]
  llm2026: []
  agentic2026: []
  asvs5: [V2.2.1, V15.3.2, V12.3.2, V16.5.2]
  cwe: [CWE-20, CWE-345, CWE-400]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [FastAPI·Python(requests·httpx), Next.js·Node.js(fetch), 결제·지도·환율·제휴사·외부 데이터 API를 서버에서 부르는 기능]
severity_reason: "영향: 외부 서비스가 뚫리거나 응답이 바뀌면 내 DB·화면·결제 판단이 그대로 오염 · 악용: 외부 API 탈취, 응답 위조, 느린 응답으로 서버 자원 고갈"
sources:
  - title: "OWASP API Security Top 10 2023 - API10 Unsafe Consumption of APIs"
    url: https://owasp.org/API-Security/editions/2023/en/0xaa-unsafe-consumption-of-apis/
    year: 2023
  - title: "OWASP ASVS 5.0 - V2.2.1, V15.3.2, V12.3.2, V16.5.2"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "HTTPX Docs - Timeouts, Redirects"
    url: https://www.python-httpx.org/advanced/timeouts/
    year: 2026
  - title: "MDN - RequestInit redirect, AbortSignal.timeout()"
    url: https://developer.mozilla.org/en-US/docs/Web/API/RequestInit
    year: 2026
---

## 요약

결제·지도·환율·제휴사 같은 외부 API 응답을 "믿을 만한 서버가 준 값"으로 여겨 형식 검사 없이 DB 쿼리·화면·금액과 권한 판단에 쓰거나, 리다이렉트를 따라가고 시간 제한 없이 기다리는 문제(OWASP API10:2023). 외부 서비스가 뚫리거나 응답 형식이 바뀌면 그대로 내 서비스의 취약점이 됨.

## 대응 방법

```text
서버에서 외부 API를 부르는 코드(fetch·axios·requests·httpx·Http::)를 찾아, 응답을 검사 없이 DB·화면·금액·권한 판단에 쓰는 곳과 시간 제한·리다이렉트 설정을 표로 보여 줘.
응답은 스키마(zod·pydantic)로 필요한 필드만 검사해 쓰고, 그 값도 SQL·HTML에 넣을 때는 사용자 입력처럼 바인딩·이스케이프하게 고쳐 줘.
https·인증서 검증·시간 제한을 두고 리다이렉트는 따라가지 않게, 실패하면 결제·권한을 허용하지 않고 거절하게 바꿔 줘.
```

- 외부 API가 엉뚱한 형식이나 느린 응답을 주면 정해진 시간 안에 오류로 처리되어야 한다.
- 외부 API 장애 때 결제 승인·권한 허용으로 넘어가는 경로가 없어야 한다.

## 점검 항목

- 외부 API 응답은 스키마로 검사하고 필요한 필드만 써야 한다.
- 외부 응답 값도 SQL·HTML·명령에 넣을 때는 사용자 입력처럼 다뤄야 한다.
- 외부 호출은 https·인증서 검증·시간 제한을 둬야 한다.
- 리다이렉트는 따라가지 않거나 허용한 주소로만 따라가야 한다.
- 응답 크기와 재시도 횟수에 상한을 둬야 한다.
- 외부 API가 실패하면 허용이 아니라 거절·보류로 처리해야 한다.

## 탐지 규칙

- **찾는 곳**: `requests.get(`, `requests.post(`, `httpx.`, `axios.get(`, `Http::get(`, `allow_redirects`, `follow_redirects=True`, `AbortSignal.timeout(`, 그 밖에 서버 코드의 fetch 응답을 그대로 쓰는 곳, 외부 응답을 저장·표시·금액 계산에 쓰는 코드, 제휴사·지도·환율 API 클라이언트.
- **검사 1 — 검사 없는 응답 사용**: 지적할 때: 외부 응답 값을 형식 검사 없이 SQL 문자열·HTML·셸 명령·파일 경로에 넣는 경우, 금액·권한·상태 판단에 그대로 쓰는 경우, 응답 전체를 DB에 저장하거나 클라이언트에 그대로 넘기는 경우. / 지적하지 않을 때: 스키마로 검사한 필드만 쓰고, 쿼리·출력은 일반 입력과 같은 방어를 거치는 경우.
- **검사 2 — 연결 설정**: 지적할 때: http 주소, 시간 제한 없음(`requests`는 기본값이 없음), 인증 헤더를 붙인 채 리다이렉트를 자동으로 따라가는 서버 쪽 호출, 크기 제한 없는 다운로드. / 지적하지 않을 때: https·시간 제한·리다이렉트 끔(또는 허용 목록)이 보이는 경우.
- **검사 3 — 실패 처리**: 지적할 때: 외부 API 오류·시간 초과 때 결제 성공·권한 허용·검증 통과로 넘어가는 경우(fail-open), 기본값(환율 1 등)으로 계속 진행하는 경우, 횟수 제한 없는 재시도. / 지적하지 않을 때: 거절·보류 처리, 재시도 횟수·간격 제한.
- **등급**: 위험: 외부 응답이 SQL·HTML·명령에 그대로 들어감, 외부 API 실패 때 결제·권한 허용. / 주의: 스키마 검사 없음, 시간 제한 없음, 인증 헤더를 단 채 리다이렉트를 따라감. / 참고: 응답 크기·재시도 상한 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SUP-007 app/services/partner.py:14 — 제휴사 API 응답의 name 을 f-string SQL 에 넣음 → 응답을 pydantic 으로 검사하고 매개변수 바인딩`
- **검토 주의**: 사용자가 준 URL로 서버가 요청하는 문제는 WS-SSRF-001, 웹훅 서명 확인은 WS-PAY-001, AI 모델 출력은 WS-LLM-001, 인증서 검증 끔 자체는 WS-CRY-001에서 다룬다. 이 이슈는 내가 골라 연동한 외부 서비스의 응답을 믿는 문제만 본다.
- **고칠 방법**: 외부 응답은 스키마로 검사한 필드만 쓰고 일반 입력과 같은 방어를 거치게 하며, 호출에는 https·시간 제한·리다이렉트 끔·실패 시 거절을 함께 둬야 한다.

## 코드 예시

### fastapi

✗ 취약 코드

```python
import requests

@app.get("/stores/{store_id}")
def store(store_id: int, db: Session = Depends(get_db)):
    # 시간 제한 없음(requests 기본값 없음), 리다이렉트 자동 추적
    data = requests.get(f"https://partner.example.com/stores/{store_id}").json()
    db.execute(text(f"UPDATE stores SET name = '{data['name']}' WHERE id = {store_id}"))  # 외부 값이 SQL 로
    return data                                                                         # 응답 전체를 그대로
```

✓ 안전 코드

```python
import httpx
from pydantic import BaseModel, Field

class PartnerStore(BaseModel):          # 쓰는 필드만, 모르는 필드는 버림
    name: str = Field(max_length=100)
    open: bool

client = httpx.Client(base_url="https://partner.example.com", timeout=5.0, follow_redirects=False)
MAX_BYTES = 100_000

def get_json_capped(path: str) -> bytes:
    with client.stream("GET", path) as r:
        r.raise_for_status()                                 # 3xx·4xx·5xx 는 예외
        body = b""
        for chunk in r.iter_bytes():
            body += chunk
            if len(body) > MAX_BYTES:                        # 응답 크기 상한
                raise ValueError("response too large")
        return body

@app.get("/stores/{store_id}")
def store(store_id: int, db: Session = Depends(get_db)):
    try:
        s = PartnerStore.model_validate_json(get_json_capped(f"/stores/{store_id}"))  # 형식·길이가 다르면 예외
    except (httpx.HTTPError, ValueError):                    # ValidationError 도 ValueError
        raise HTTPException(status_code=502, detail="partner unavailable")   # 실패하면 거절
    db.execute(text("UPDATE stores SET name = :n WHERE id = :id"), {"n": s.name, "id": store_id})
    return {"name": s.name, "open": s.open}
```

### nextjs

✗ 취약 코드

```ts
// lib/rate.ts — 결제 금액 계산에 쓰는 환율
export async function getRate(cur: string) {
  const res = await fetch(`http://rates.example.com/v1/${cur}`);   // http, 시간 제한 없음, 리다이렉트 추적
  return res.json().catch(() => ({ rate: 1 }));                    // 실패하면 환율 1 로 결제 진행
}
```

✓ 안전 코드

```ts
// lib/rate.ts
import { z } from "zod";

const Rate = z.object({ currency: z.string().regex(/^[A-Z]{3}$/), rate: z.number().positive().finite() });

export async function getRate(cur: string) {
  const res = await fetch(`https://rates.example.com/v1/${encodeURIComponent(cur)}`, {
    signal: AbortSignal.timeout(5000),   // 5초 시간 제한
    redirect: "error",                   // 리다이렉트는 따라가지 않음
    cache: "no-store",
  });
  if (!res.ok || !res.body) throw new Error("rate unavailable");   // 실패하면 결제를 멈춤
  const reader = res.body.getReader();
  const chunks: Uint8Array[] = [];
  let size = 0;
  for (let r = await reader.read(); !r.done; r = await reader.read()) {
    size += r.value.length;
    if (size > 10_000) { await reader.cancel(); throw new Error("response too large"); } // 응답 크기 상한
    chunks.push(r.value);
  }
  return Rate.parse(JSON.parse(Buffer.concat(chunks).toString("utf8")));   // 형식이 다르면 예외
}
```
