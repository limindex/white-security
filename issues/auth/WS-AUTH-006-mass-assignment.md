---
id: WS-AUTH-006
title: Mass Assignment (대량 할당·속성 수준 권한)
slug: mass-assignment
group: auth
severity: high
grade_hint: "요청으로 역할·잔액·소유자 변경 가능"
site_only: false
depth: brief
languages: [laravel, nextjs, django]
maps:
  owasp2025: [A01]
  api2023: [API3]
  llm2026: []
  agentic2026: []
  asvs5: [V8.2.3, V15.3.3]
  cwe: [CWE-915, CWE-863]
  kr_guide: ["입력데이터 검증 및 표현-15"]
  kr_ciip: [IN]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel(Eloquent), Next.js·Prisma, Django REST framework, 요청 JSON으로 DB를 고치는 모든 API]
severity_reason: "영향: 일반 회원이 관리자 권한·포인트·잔액·소유자 변경 · 악용: 요청 본문에 필드 하나를 더 넣는 것만으로 가능"
sources:
  - title: "OWASP API Security Top 10:2023 - API3 Broken Object Property Level Authorization"
    url: https://owasp.org/API-Security/editions/2023/en/0xa3-broken-object-property-level-authorization/
    year: 2023
  - title: OWASP Mass Assignment Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Mass_Assignment_Cheat_Sheet.html
    year: 2026
  - title: "CWE-915 Improperly Controlled Modification of Dynamically-Determined Object Attributes"
    url: https://cwe.mitre.org/data/definitions/915.html
    year: 2026
  - title: "Laravel 12.x Eloquent - Mass Assignment"
    url: https://laravel.com/docs/12.x/eloquent#mass-assignment
    year: 2026
  - title: "Django REST framework - Serializers (ModelSerializer fields·read_only_fields)"
    url: https://www.django-rest-framework.org/api-guide/serializers/
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

회원정보 수정·가입 API가 요청 본문을 통째로 모델에 넣어, 본문에 `role`·`is_admin`·`points`·`owner_id` 같은 필드를 끼워 보내면 바꾸면 안 되는 값까지 바뀌는 문제(진단가이드 이름: 보안기능 결정에 사용되는 부적절한 입력값).

## 위험 배경

- OWASP API Security 2023은 필요 이상 필드를 응답으로 내주는 문제와 함께 BOPLA(Broken Object Property Level Authorization, 속성 수준 권한 검증 실패)로 분류.

## 대응 방법

```text
요청 본문을 통째로 모델·ORM에 넘기는 코드($request->all(), $guarded = [], fields = "__all__", data: body, **payload)를 모두 찾아 줘.
API마다 사용자가 바꿔도 되는 필드만 허용 목록으로 받게 바꾸고, 역할·포인트·소유자·상태 필드는 서버만 정하게 해 줘.
응답도 화면에 필요한 필드만 골라 돌려주게 해 줘.
```

- 회원정보 수정 요청 본문에 `"role": "admin"`을 끼워 보내도 역할이 바뀌지 않아야 한다.
- 가입 요청에 `"points": 999999`를 넣어도 기본값으로 저장돼야 한다.

## 점검 항목

- 생성·수정 API는 바꿔도 되는 필드만 허용 목록(검증 규칙·DTO·serializer `fields`)으로 받아야 한다.
- 역할·권한·포인트·잔액·소유자·결제 상태는 요청 값으로 바꾸지 않아야 한다.
- Laravel은 `$guarded = []`·`Model::unguard()`·요청 값을 넣은 `forceFill()`을 쓰지 않아야 한다.
- 관리자용 수정 API와 일반 사용자용 수정 API는 허용 필드를 따로 둬야 한다.
- 응답은 필요한 필드만 골라 보내야 한다(비밀번호 해시·내부 메모 제외).

## 탐지 규칙

- **찾는 곳**: `$guarded = []`, `$request->all()`, `forceFill(`, `"__all__"`, `data: body`, `**payload`, 그 밖에 ...body 펼치기, model_dump() 결과를 그대로 넘기는 갱신, setattr 반복, Object.assign.
- **검사 1 — 본문 통째 저장**: 지적할 때: 요청 본문 전체를 생성·수정에 넘기고(`$request->all()`, `data: req.body`, `**body.model_dump()`, serializer `fields = "__all__"`), 그 모델에 역할·포인트·소유자 같은 민감 필드가 있는 경우. / 지적하지 않을 때: `$request->validated()`·`safe()->only([...])`·DTO·zod `.strict()`·`read_only_fields`처럼 허용 필드만 넘기는 경우. 모델에 민감 필드가 전혀 없는 경우.
- **검사 2 — 보호 장치 해제**: 지적할 때: `$guarded = []`, `Model::unguard()`, `forceFill(`·`setattr`에 요청 값이 들어가는 경우. / 지적하지 않을 때: `forceFill(`에 서버가 정한 값만 넣는 경우.
- **등급**: 위험: 요청으로 역할·권한·포인트·잔액·소유자를 바꿀 수 있는 경우. / 주의: 통째 저장이지만 지금 모델에는 민감 필드가 없는 경우(필드가 늘면 바로 위험). / 참고: 관리자와 일반 사용자가 같은 허용 목록을 쓰는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-AUTH-006 app/api/me/route.ts:9 — 요청 JSON 을 그대로 prisma.user.update 의 data 로 넘겨 role 변경 가능 → zod strict 스키마로 nickname·marketingOptIn 만 받기`
- **고칠 방법**: 허용 목록 방식으로 바꿔야 한다(막을 필드 목록이 아니라 받을 필드 목록). 응답도 `select`·응답 스키마로 필드를 골라야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
class User extends Authenticatable
{
    protected $guarded = [];                       // 모든 필드 대량 할당 허용
}

public function update(Request $request)
{
    $request->user()->update($request->all());     // 본문에 "role":"admin" 을 넣으면 그대로 저장
    return $request->user();                       // 모델 전체 응답
}
```

✓ 안전 코드

```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'nickname', 'marketing_opt_in'];  // role·points 는 목록에 없다
    protected $hidden = ['password', 'remember_token'];
}

public function update(Request $request)
{
    $data = $request->validate([                   // 규칙에 있는 키만 돌려준다
        'nickname' => 'sometimes|string|max:30',
        'marketing_opt_in' => 'sometimes|boolean',
    ]);
    $request->user()->update($data);
    return $request->user()->only(['id', 'nickname', 'marketing_opt_in']);
}
```

### nextjs

✗ 취약 코드

```ts
// app/api/me/route.ts
export async function PATCH(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return new Response(null, { status: 401 });
  const body = await req.json();
  const user = await db.user.update({ where: { id: session.user.id }, data: body });  // role·points 도 바뀐다
  return Response.json(user);                                                        // passwordHash 까지 응답
}
```

✓ 안전 코드

```ts
import { z } from "zod";

const ProfilePatch = z.object({
  nickname: z.string().min(1).max(30).optional(),
  marketingOptIn: z.boolean().optional(),
}).strict();                                       // 목록에 없는 필드가 오면 거절

export async function PATCH(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return new Response(null, { status: 401 });
  const parsed = ProfilePatch.safeParse(await req.json());
  if (!parsed.success) return Response.json({ error: "허용되지 않은 값" }, { status: 400 });
  const user = await db.user.update({
    where: { id: session.user.id },
    data: parsed.data,
    select: { id: true, nickname: true, marketingOptIn: true },  // 응답 필드도 고른다
  });
  return Response.json(user);
}
```

### django

✗ 취약 코드

```python
class UserSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = "__all__"        # is_staff·is_superuser·points·password 까지 읽고 쓸 수 있다

class MeView(generics.RetrieveUpdateAPIView):
    serializer_class = UserSerializer
    permission_classes = [IsAuthenticated]

    def get_object(self):
        return self.request.user
```

✓ 안전 코드

```python
class ProfileSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = ["id", "nickname", "marketing_opt_in", "points"]  # 받을·보일 필드만
        read_only_fields = ["id", "points"]                         # 보여 주기만, 수정 불가

class MeView(generics.RetrieveUpdateAPIView):
    serializer_class = ProfileSerializer
    permission_classes = [IsAuthenticated]

    def get_object(self):
        return self.request.user
```
