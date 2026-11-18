<!-- generated from white-security issues; do not edit -->
# WS-AUTH-002 Missing Authentication (중요 기능 인증 누락)

## 탐지 규칙

- **찾는 곳**: `withoutMiddleware(`, `middleware.ts`, `proxy.ts`, `routes/api.php`, `/cron`, `/internal`, 그 밖에 "use server" 함수, Route Handler(export async function POST 등), FastAPI 라우터, /debug·웹훅 경로, 세션·토큰 확인 없이 DB를 바꾸는 핸들러.
- **검사 1 — 인증 없는 중요 기능**: 지적할 때: 데이터 변경·개인정보 조회·내보내기·관리 작업·문자와 메일 발송·결제 처리 핸들러에 로그인(세션·토큰) 확인이 없는 경우. Server Action·Route Handler 안에 확인이 없는 경우. / 지적하지 않을 때: 라우트 그룹 미들웨어·라우터 의존성이 확인하는 것을 실제 설정에서 찾은 경우. 로그인·가입·공개 글 조회처럼 누구나 써도 되는 기능인 경우.
- **검사 2 — 미들웨어·화면에만 기댐**: 지적할 때: Next.js `middleware.ts`(16부터 `proxy.ts`)의 matcher나 화면 숨김으로만 보호하고 핸들러·데이터 접근 함수에서는 다시 확인하지 않는 경우. `withoutMiddleware(`로 인증 미들웨어를 뺀 경우. / 지적하지 않을 때: 핸들러나 데이터 접근 계층(DAL)이 세션을 다시 확인하는 경우.
- **검사 3 — 숨긴 내부 경로**: 지적할 때: `/cron`·`/internal`·`/debug`·웹훅 경로가 서명·비밀 토큰·IP 제한 없이 열려 있는 경우. / 지적하지 않을 때: 서명 검증(WS-PAY-001)이나 `hash_equals`·`timingSafeEqual`로 비밀 토큰을 비교하는 경우. 웹 경로 대신 스케줄러로 도는 경우.
- **등급**: 위험: 로그인 없이 개인정보 조회·데이터 변경·결제·발송을 부를 수 있는 경우. / 주의: 미들웨어에만 기대는 경우, 내부 경로의 비밀 토큰을 일반 비교(`==`)로 확인하는 경우. / 참고: 공개 경로와 보호 경로가 섞여 있어 기본 잠금이 아닌 경우.
- **보고 예**: `[위험] WS-AUTH-002 app/actions/post.ts:4 — "use server" 함수 deletePost 가 세션을 확인하지 않음 → 함수 첫 줄에서 auth() 로 확인하고 작성자 조건으로 삭제`
- **고칠 방법**: 보호를 기본값으로 바꿔야 한다(라우트 묶음·라우터 단위 잠금). 공개 경로는 목록으로 따로 두고, 각 핸들러는 세션에서 꺼낸 사용자로만 일하게 해야 한다.

## 점검 항목

- 데이터를 바꾸거나 개인정보를 내주는 모든 핸들러에서 서버가 로그인을 확인해야 한다.
- 라우트는 묶음 단위로 기본 잠금을 걸고, 공개 경로만 따로 열어야 한다.
- Server Action·Route Handler는 공개 API로 보고 함수 안에서 세션을 다시 확인해야 한다.
- Next.js 미들웨어(16부터 Proxy) 확인은 1차 거름망으로만 써야 한다.
- 크론·내부·웹훅 경로는 웹에 열지 않거나 서명·비밀 토큰으로 확인해야 한다.

## 검토 주의

Next.js 미들웨어는 2025년 3월 우회 취약점(CVE-2025-29927, 15.2.3·14.2.25·13.5.9·12.3.5에서 수정)이 있었으므로 버전을 확인하고, 업데이트가 어려우면 앞단(프록시·CDN)에서 외부 요청의 `x-middleware-subrequest` 헤더를 지워야 한다. 버전과 별개로 핸들러 안 확인이 있는지도 봐야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-AUTH-002.code.laravel.md` (Laravel) · `WS-AUTH-002.code.nextjs.md` (Next.js) · `WS-AUTH-002.code.fastapi.md` (FastAPI)

기본 심각도: 위험 · CWE-306 · CWE-288 · CWE-862
