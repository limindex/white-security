---
id: WS-AUTH-002
title: Missing Authentication (중요 기능 인증 누락)
slug: missing-authentication
group: auth
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs, fastapi]
maps:
  owasp2025: [A07, A01]
  api2023: [API2, API5]
  llm2026: []
  agentic2026: []
  asvs5: [V6.3.4, V7.2.1, V8.3.1]
  cwe: [CWE-306, CWE-288, CWE-862]
  kr_guide: ["보안기능-1"]
  kr_ciip: [IA]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js(Server Actions·Route Handlers), FastAPI, 로그인 기능이 있는 모든 웹 애플리케이션]
severity_reason: "영향: 로그인 없이 개인정보 조회·데이터 변경·발송·정산 실행 · 악용: 주소나 요청 하나를 직접 보내는 것만으로 가능"
sources:
  - title: "OWASP Top 10:2025 - A07 Authentication Failures"
    url: https://top10.owasp.org/2025/A07_2025-Authentication_Failures
    year: 2025
  - title: "GHSA-f82v-jwr5-mffw (CVE-2025-29927) Authorization Bypass in Next.js Middleware"
    url: https://github.com/vercel/next.js/security/advisories/GHSA-f82v-jwr5-mffw
    year: 2025
  - title: "Next.js Docs - Data Security (Server Actions 은 직접 POST 로 부를 수 있음)"
    url: https://nextjs.org/docs/app/guides/data-security
    year: 2026
  - title: "OWASP API Security Top 10:2023 - API2 Broken Authentication"
    url: https://owasp.org/API-Security/editions/2023/en/0xa2-broken-authentication/
    year: 2023
  - title: "FastAPI - Global Dependencies"
    url: https://fastapi.tiangolo.com/tutorial/dependencies/global-dependencies/
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

로그인 없이 부를 수 있는 API·Server Action·내부 경로로 개인정보 조회·데이터 변경·발송 같은 중요 기능이 열려 있는 문제(진단가이드 이름: 적절한 인증 없는 중요 기능 허용). 화면에서 버튼만 숨기거나 미들웨어 한 곳에만 로그인 확인을 둔 구조가 흔한 원인.

## 대응 방법

```text
라우트·API·Server Action·크론 경로를 모두 표로 정리하고, 각각 로그인 확인이 어디서 이뤄지는지 적어 줘.
확인이 없는 중요 기능은 라우트 묶음 단위로 기본 잠금을 걸고, 공개 경로만 따로 모아 줘.
Server Action과 Route Handler는 함수 안에서도 세션을 다시 확인하게 해 줘.
```

- 로그아웃 상태에서 중요 API를 curl로 직접 부르면 401이나 403이 나와야 한다.
- 화면에서 숨긴 기능의 요청을 브라우저 개발자 도구로 다시 보내도 로그인 없이는 실패해야 한다.

## 점검 항목

- 데이터를 바꾸거나 개인정보를 내주는 모든 핸들러에서 서버가 로그인을 확인해야 한다.
- 라우트는 묶음 단위로 기본 잠금을 걸고, 공개 경로만 따로 열어야 한다.
- Server Action·Route Handler는 공개 API로 보고 함수 안에서 세션을 다시 확인해야 한다.
- Next.js 미들웨어(16부터 Proxy) 확인은 1차 거름망으로만 써야 한다.
- 크론·내부·웹훅 경로는 웹에 열지 않거나 서명·비밀 토큰으로 확인해야 한다.

## 탐지 규칙

- **찾는 곳**: Server Action(`use server`), Route Handler(`function POST(`, 그 밖에 PUT·PATCH·DELETE), `withoutMiddleware(`, `middleware.ts`, `proxy.ts`, `routes/api.php`, `/cron`, `/internal`, 그 밖에 FastAPI 라우터, /debug·웹훅 경로, 세션·토큰 확인 없이 DB를 바꾸는 핸들러.
- **검사 1 — 인증 없는 중요 기능**: 지적할 때: 데이터 변경·개인정보 조회·내보내기·관리 작업·문자와 메일 발송·결제 처리 핸들러에 로그인(세션·토큰) 확인이 없는 경우. Server Action·Route Handler 안에 확인이 없는 경우. / 지적하지 않을 때: 라우트 그룹 미들웨어·라우터 의존성이 확인하는 것을 실제 설정에서 찾은 경우. 로그인·가입·공개 글 조회처럼 누구나 써도 되는 기능인 경우.
- **검사 2 — 미들웨어·화면에만 기댐**: 지적할 때: Next.js `middleware.ts`(16부터 `proxy.ts`)의 matcher나 화면 숨김으로만 보호하고 핸들러·데이터 접근 함수에서는 다시 확인하지 않는 경우. `withoutMiddleware(`로 인증 미들웨어를 뺀 경우. / 지적하지 않을 때: 핸들러나 데이터 접근 계층(DAL)이 세션을 다시 확인하는 경우.
- **검사 3 — 숨긴 내부 경로**: 지적할 때: `/cron`·`/internal`·`/debug`·웹훅 경로가 서명·비밀 토큰·IP 제한 없이 열려 있는 경우. / 지적하지 않을 때: 서명 검증(WS-PAY-001)이나 `hash_equals`·`timingSafeEqual`로 비밀 토큰을 비교하는 경우. 웹 경로 대신 스케줄러로 도는 경우.
- **등급**: 위험: 로그인 없이 개인정보 조회·데이터 변경·결제·발송을 부를 수 있는 경우. / 주의: 미들웨어에만 기대는 경우, 내부 경로의 비밀 토큰을 일반 비교(`==`)로 확인하는 경우. / 참고: 공개 경로와 보호 경로가 섞여 있어 기본 잠금이 아닌 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-AUTH-002 app/actions/post.ts:4 — "use server" 함수 deletePost 가 세션을 확인하지 않음 → 함수 첫 줄에서 auth() 로 확인하고 작성자 조건으로 삭제`
- **검토 주의**: Next.js 미들웨어는 2025년 3월 우회 취약점(CVE-2025-29927, 15.2.3·14.2.25·13.5.9·12.3.5에서 수정)이 있었으므로 버전을 확인하고, 업데이트가 어려우면 앞단(프록시·CDN)에서 외부 요청의 `x-middleware-subrequest` 헤더를 지워야 한다. 버전과 별개로 핸들러 안 확인이 있는지도 봐야 한다.
- **고칠 방법**: 보호를 기본값으로 바꿔야 한다(라우트 묶음·라우터 단위 잠금). 공개 경로는 목록으로 따로 두고, 각 핸들러는 세션에서 꺼낸 사용자로만 일하게 해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// routes/api.php
Route::get('/orders/export', [OrderExportController::class, 'download']);  // 로그인 확인 없음
Route::post('/coupons', [CouponController::class, 'store']);
Route::post('/cron/settle', [SettleController::class, 'run']);             // "내부용"이라 그냥 열어 둠
```

✓ 안전 코드

```php
// routes/api.php — 묶음 전체를 기본 잠금
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders/export', [OrderExportController::class, 'download']);
    Route::post('/coupons', [CouponController::class, 'store'])->middleware('can:manage-coupons');
});

// routes/console.php — 정산은 웹 경로 대신 스케줄러로
use Illuminate\Support\Facades\Schedule;
Schedule::command('orders:settle')->dailyAt('03:00');
```

### nextjs

✗ 취약 코드

```ts
// app/actions/post.ts
"use server";
import { db } from "@/lib/db";

// 화면에서 삭제 버튼을 숨겨도 Server Action 은 POST 요청으로 직접 부를 수 있다
export async function deletePost(id: string) {
  await db.post.deleteMany({ where: { id } });
}
// middleware.ts 의 matcher 에만 로그인 확인이 있다
```

✓ 안전 코드

```ts
// app/actions/post.ts
"use server";
import { auth } from "@/auth";
import { db } from "@/lib/db";

export async function deletePost(id: string) {
  const session = await auth();                         // 함수 안에서 직접 확인
  if (!session?.user?.id) throw new Error("Unauthorized");
  await db.post.deleteMany({ where: { id, authorId: session.user.id } });  // 내 글만
}

// app/api/reports/route.ts — Route Handler 도 같은 방식
export async function GET() {
  const session = await auth();
  if (!session?.user) return new Response(null, { status: 401 });
  return Response.json(await buildReport(session.user.id));
}
```

### fastapi

✗ 취약 코드

```python
router = APIRouter(prefix="/api")

@router.get("/users/{user_id}/export")
def export_user(user_id: int, db: Session = Depends(get_db)):  # 인증 의존성 없음
    return build_export(db, user_id)

@router.post("/internal/resend-sms")                           # 아무나 문자 발송을 부를 수 있음
def resend(phone: str):
    send_sms(phone, "인증번호를 다시 보냈습니다")
```

✓ 안전 코드

```python
# 라우터 전체를 기본 잠금, 공개 경로는 public 라우터에만
router = APIRouter(prefix="/api", dependencies=[Depends(get_current_user)])
public = APIRouter(prefix="/api/public")

@router.get("/me/export")
def export_me(user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    return build_export(db, user.id)        # 대상은 요청 값이 아니라 로그인한 사용자

# 내부 발송 기능은 웹 경로로 열지 않고, 서버 작업 큐(예: Celery 작업)에서만 부른다
```
