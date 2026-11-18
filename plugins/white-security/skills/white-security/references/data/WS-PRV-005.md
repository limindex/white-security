<!-- generated from white-security issues; do not edit -->
# WS-PRV-005 Excessive Data Exposure (API 응답 과다 노출)

## 탐지 규칙

- **찾는 곳**: `.select('*')`, `select("*")`, `include: { author: true }`, `include: { user: true }`, `->with('user')`, `return User::`, `->toArray()`, `"__all__"`, 그 밖에 phone·email·address·birth 필드가 있는 사용자 모델·관계를 통째로 돌려주는 목록 API, 서버 컴포넌트가 클라이언트 컴포넌트에 넘기는 사용자 객체, GraphQL 사용자 타입, 프런트엔드의 가림 함수.
- **검사 1 — 화면보다 많은 필드**: 지적할 때: 목록·상세 응답이 DB 행·모델 전체나 관계 전체(`include: { author: true }`·`with('buyer')`)를 돌려주는데 화면은 일부만 쓰는 경우, 특히 다른 회원의 전화번호·이메일·주소·생년월일이 응답에 있는 경우, 서버 컴포넌트가 사용자 객체 전체를 클라이언트 컴포넌트 props로 넘기는 경우. / 지적하지 않을 때: `select`·응답 형식(Resource·DTO·response_model)으로 필드를 고른 경우, 본인 정보 화면이 본인 정보를 돌려주는 경우.
- **검사 2 — 프런트에서만 가림**: 지적할 때: API는 원문을 주고 화면 코드에서 가림 함수·CSS로 가리는 경우, 숨긴 입력 필드에 다른 회원 개인정보를 두는 경우. / 지적하지 않을 때: 서버에서 가린 값만 보내는 경우.
- **검사 3 — 클라이언트가 고르는 열**: 지적할 때: Supabase·GraphQL에서 클라이언트가 아무 열이나 고를 수 있고 개인정보 열을 막는 열 권한·필드 권한이 없는 경우. / 지적하지 않을 때: 열 단위 권한(`grant select (…)`)·뷰·개인정보 열 분리 테이블·필드 수준 권한 검사가 있는 경우.
- **등급**: 위험: 다른 회원의 연락처·주소·고유식별정보가 응답에 원문으로 있음. / 주의: 화면에 안 쓰는 다른 회원 필드, 프런트에서만 가림, 열 제한 없는 공개 프로필. / 참고: 관리자 화면 항목 최소화를 확인할 필요가 있는 경우.
- **보고 예**: `[위험] WS-PRV-005 app/api/posts/route.ts:4 — findMany({ include: { author: true } }) 로 작성자 이메일·전화번호가 목록 응답에 포함 → author: { select: { nickname: true } } 로 필요한 필드만`
- **고칠 방법**: 엔드포인트마다 응답 필드를 허용 목록으로 정하고, 다른 회원 정보는 서버에서 가려 보내야 한다.

## 점검 항목

- 응답은 화면에 필요한 필드만 골라 보내야 한다(행·모델 통째 금지).
- 다른 회원의 개인정보는 서버에서 가린 값으로 보내야 한다.
- 가리기·숨기기를 프런트엔드 코드나 CSS에만 맡기지 않아야 한다.
- 서버 컴포넌트가 클라이언트 컴포넌트에 넘기는 값도 필요한 것만 넘겨야 한다.
- 클라이언트가 열을 고르는 API(Supabase·GraphQL)는 열 단위로 막아야 한다.
- 관리자 화면도 업무에 필요한 항목만 보여 줘야 한다.

## 검토 주의

비밀번호 해시·토큰·디버그 정보가 응답에 나가는 경우는 WS-ENC-001, 요청으로 필드를 덮어쓰는 쓰기 쪽 문제는 WS-AUTH-006, 남의 객체 자체에 접근하는 문제는 WS-AUTH-001, Supabase 행 정책은 WS-BAAS-001에서 다룬다. 같은 줄을 여러 이슈로 중복 보고하지 않아야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-PRV-005.code.nextjs.md` (Next.js) · `WS-PRV-005.code.laravel.md` (Laravel) · `WS-PRV-005.code.sql.md` (SQL)

기본 심각도: 위험 · CWE-213 · CWE-359 · CWE-200
