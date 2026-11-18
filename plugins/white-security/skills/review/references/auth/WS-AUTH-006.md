<!-- generated from white-security issues; do not edit -->
# WS-AUTH-006 Mass Assignment (대량 할당·속성 수준 권한)

## 탐지 규칙

- **찾는 곳**: `$guarded = []`, `$request->all()`, `forceFill(`, `"__all__"`, `data: body`, `**payload`, 그 밖에 ...body 펼치기, model_dump() 결과를 그대로 넘기는 갱신, setattr 반복, Object.assign.
- **검사 1 — 본문 통째 저장**: 지적할 때: 요청 본문 전체를 생성·수정에 넘기고(`$request->all()`, `data: req.body`, `**body.model_dump()`, serializer `fields = "__all__"`), 그 모델에 역할·포인트·소유자 같은 민감 필드가 있는 경우. / 지적하지 않을 때: `$request->validated()`·`safe()->only([...])`·DTO·zod `.strict()`·`read_only_fields`처럼 허용 필드만 넘기는 경우. 모델에 민감 필드가 전혀 없는 경우.
- **검사 2 — 보호 장치 해제**: 지적할 때: `$guarded = []`, `Model::unguard()`, `forceFill(`·`setattr`에 요청 값이 들어가는 경우. / 지적하지 않을 때: `forceFill(`에 서버가 정한 값만 넣는 경우.
- **등급**: 위험: 요청으로 역할·권한·포인트·잔액·소유자를 바꿀 수 있는 경우. / 주의: 통째 저장이지만 지금 모델에는 민감 필드가 없는 경우(필드가 늘면 바로 위험). / 참고: 관리자와 일반 사용자가 같은 허용 목록을 쓰는 경우.
- **보고 예**: `[위험] WS-AUTH-006 app/api/me/route.ts:9 — 요청 JSON 을 그대로 prisma.user.update 의 data 로 넘겨 role 변경 가능 → zod strict 스키마로 nickname·marketingOptIn 만 받기`
- **고칠 방법**: 허용 목록 방식으로 바꿔야 한다(막을 필드 목록이 아니라 받을 필드 목록). 응답도 `select`·응답 스키마로 필드를 골라야 한다.

## 점검 항목

- 생성·수정 API는 바꿔도 되는 필드만 허용 목록(검증 규칙·DTO·serializer `fields`)으로 받아야 한다.
- 역할·권한·포인트·잔액·소유자·결제 상태는 요청 값으로 바꾸지 않아야 한다.
- Laravel은 `$guarded = []`·`Model::unguard()`·요청 값을 넣은 `forceFill()`을 쓰지 않아야 한다.
- 관리자용 수정 API와 일반 사용자용 수정 API는 허용 필드를 따로 둬야 한다.
- 응답은 필요한 필드만 골라 보내야 한다(비밀번호 해시·내부 메모 제외).

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-AUTH-006.code.laravel.md` (Laravel) · `WS-AUTH-006.code.nextjs.md` (Next.js) · `WS-AUTH-006.code.django.md` (Django)

기본 심각도: 위험 · CWE-915 · CWE-863
