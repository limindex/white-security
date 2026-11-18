---
id: WS-PRV-005
title: Excessive Data Exposure (API 응답 과다 노출)
slug: excessive-data-exposure
group: data
severity: high
site_only: false
depth: brief
languages: [nextjs, laravel, sql]
maps:
  owasp2025: [A01]
  api2023: [API3]
  llm2026: []
  agentic2026: []
  asvs5: [V15.3.1, V14.2.6]
  cwe: [CWE-213, CWE-359, CWE-200]
  kr_guide: []
  kr_ciip: [IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Next.js(Route Handler·Server Component), Laravel, Supabase·PostgreSQL, 게시판·주문·예약 목록처럼 다른 회원 정보가 함께 나오는 화면]
severity_reason: "영향: 화면엔 안 보이는 다른 회원의 연락처·주소가 응답마다 새어 대량 수집 가능 · 악용: 브라우저 개발자 도구로 응답을 열어 보는 것만으로 가능"
sources:
  - title: "OWASP API Security Top 10 2023 - API3 Broken Object Property Level Authorization"
    url: https://owasp.org/API-Security/editions/2023/en/0xa3-broken-object-property-level-authorization/
    year: 2023
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제6조 ③·제12조 ①
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: "OWASP ASVS 5.0 - V15.3.1, V14.2.6"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "Supabase Docs - Column Level Security"
    url: https://supabase.com/docs/guides/database/postgres/column-level-security
    year: 2026
  - title: "Prisma Docs - Select fields"
    url: https://www.prisma.io/docs/orm/prisma-client/queries/select-fields
    year: 2026
---

## 요약

화면에는 닉네임만 보이지만 API 응답 JSON에는 작성자의 전화번호·이메일·주소까지 담기는 것처럼, 필요보다 많은 개인정보를 응답에 넣고 숨기는 일은 프런트엔드에 맡기는 문제(OWASP API3:2023의 과다 노출). 「개인정보의 안전성 확보조치 기준」 제12조 ①은 화면표시를 포함한 출력 때 용도에 따라 출력 항목을 최소화하도록 정함.

## 대응 방법

```text
API·Server Action·서버 컴포넌트가 내려보내는 값 중 화면에 쓰지 않는 개인정보 필드(전화번호·이메일·주소·생년월일 등)를 엔드포인트별로 표로 보여 줘.
모델·행 전체를 돌려주는 곳은 화면에 필요한 필드만 고르는 select·응답 형식(API Resource·DTO)으로 바꿔 줘.
다른 회원 정보는 서버에서 가려서(홍*동, 010-****-1234) 보내고, 프런트에서만 가리던 곳은 서버로 옮겨 줘.
```

- 브라우저 개발자 도구 Network 탭의 응답에 화면에 없는 개인정보 필드가 없어야 한다.
- 다른 회원 정보가 나오는 목록 응답은 이미 가려진 값이어야 한다.

## 점검 항목

- 응답은 화면에 필요한 필드만 골라 보내야 한다(행·모델 통째 금지).
- 다른 회원의 개인정보는 서버에서 가린 값으로 보내야 한다.
- 가리기·숨기기를 프런트엔드 코드나 CSS에만 맡기지 않아야 한다.
- 서버 컴포넌트가 클라이언트 컴포넌트에 넘기는 값도 필요한 것만 넘겨야 한다.
- 클라이언트가 열을 고르는 API(Supabase·GraphQL)는 열 단위로 막아야 한다.
- 관리자 화면도 업무에 필요한 항목만 보여 줘야 한다.

## 탐지 규칙

- **찾는 곳**: `.select('*')`, `select("*")`, `include: { author: true }`, `include: { user: true }`, `->with('user')`, `return User::`, `->toArray()`, `"__all__"`, 그 밖에 phone·email·address·birth 필드가 있는 사용자 모델·관계를 통째로 돌려주는 목록 API, 서버 컴포넌트가 클라이언트 컴포넌트에 넘기는 사용자 객체, GraphQL 사용자 타입, 프런트엔드의 가림 함수.
- **검사 1 — 화면보다 많은 필드**: 지적할 때: 목록·상세 응답이 DB 행·모델 전체나 관계 전체(`include: { author: true }`·`with('buyer')`)를 돌려주는데 화면은 일부만 쓰는 경우, 특히 다른 회원의 전화번호·이메일·주소·생년월일이 응답에 있는 경우, 서버 컴포넌트가 사용자 객체 전체를 클라이언트 컴포넌트 props로 넘기는 경우. / 지적하지 않을 때: `select`·응답 형식(Resource·DTO·response_model)으로 필드를 고른 경우, 본인 정보 화면이 본인 정보를 돌려주는 경우.
- **검사 2 — 프런트에서만 가림**: 지적할 때: API는 원문을 주고 화면 코드에서 가림 함수·CSS로 가리는 경우, 숨긴 입력 필드에 다른 회원 개인정보를 두는 경우. / 지적하지 않을 때: 서버에서 가린 값만 보내는 경우.
- **검사 3 — 클라이언트가 고르는 열**: 지적할 때: Supabase·GraphQL에서 클라이언트가 아무 열이나 고를 수 있고 개인정보 열을 막는 열 권한·필드 권한이 없는 경우. / 지적하지 않을 때: 열 단위 권한(`grant select (…)`)·뷰·개인정보 열 분리 테이블·필드 수준 권한 검사가 있는 경우.
- **등급**: 위험: 다른 회원의 연락처·주소·고유식별정보가 응답에 원문으로 있음. / 주의: 화면에 안 쓰는 다른 회원 필드, 프런트에서만 가림, 열 제한 없는 공개 프로필. / 참고: 관리자 화면 항목 최소화를 확인할 필요가 있는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PRV-005 app/api/posts/route.ts:4 — findMany({ include: { author: true } }) 로 작성자 이메일·전화번호가 목록 응답에 포함 → author: { select: { nickname: true } } 로 필요한 필드만`
- **검토 주의**: 비밀번호 해시·토큰·디버그 정보가 응답에 나가는 경우는 WS-ENC-001, 요청으로 필드를 덮어쓰는 쓰기 쪽 문제는 WS-AUTH-006, 남의 객체 자체에 접근하는 문제는 WS-AUTH-001, Supabase 행 정책은 WS-BAAS-001에서 다룬다. 같은 줄을 여러 이슈로 중복 보고하지 않아야 한다.
- **고칠 방법**: 엔드포인트마다 응답 필드를 허용 목록으로 정하고, 다른 회원 정보는 서버에서 가려 보내야 한다.

## 코드 예시

### nextjs

✗ 취약 코드

```ts
// app/api/posts/route.ts — 게시판 목록: 화면은 제목·닉네임만 씀
export async function GET() {
  const posts = await prisma.post.findMany({ include: { author: true } }); // 작성자 이메일·전화번호·생년월일까지
  return Response.json(posts);
}

// app/me/page.tsx (서버 컴포넌트): <ProfileCard user={user} />  — user 전체가 브라우저로 직렬화
```

✓ 안전 코드

```ts
// app/api/posts/route.ts
export async function GET() {
  const posts = await prisma.post.findMany({
    select: { id: true, title: true, createdAt: true, author: { select: { nickname: true } } }, // 화면에 쓰는 것만
    orderBy: { createdAt: "desc" },
    take: 20,
  });
  return Response.json(posts);
}

// app/me/page.tsx: <ProfileCard user={{ nickname: user.nickname, avatarUrl: user.avatarUrl }} />
```

### laravel

✗ 취약 코드

```php
// 판매자 주문 목록: 구매자 모델 전체(전화번호·주소·이메일)가 JSON 으로
public function index(Request $request)
{
    return Order::with('buyer')->where('seller_id', $request->user()->id)->paginate(20);
}
```

✓ 안전 코드

```php
class SellerOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->buyer->name;
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'buyer_name'  => Str::mask($name, '*', 1, max(1, mb_strlen($name) - 2)),   // 홍*동
            'buyer_phone' => self::maskPhone($this->buyer->phone),                     // 010-****-1234
        ];
    }

    private static function maskPhone(?string $p): ?string
    {
        return $p && preg_match('/^(\d{3})-?\d{3,4}-?(\d{4})$/', $p, $m) ? "{$m[1]}-****-{$m[2]}" : null; // 형식이 다르면 빼기
    }
}

public function index(Request $request)
{
    return SellerOrderResource::collection(
        Order::with('buyer:id,name,phone')->where('seller_id', $request->user()->id)->paginate(20)
    );
}
// 배송에 실제 연락처가 필요하면 배송 처리 화면에서만, 그 주문 1건 단위로 보여 주고 접속기록(WS-PRV-003)을 남김
```

### sql

✗ 취약 코드

```sql
-- Supabase: 로그인한 누구나 profiles 행을 읽음 → 클라이언트가 select('*') 로 전화번호·주소 열까지
create policy "profiles_read" on public.profiles
  for select to authenticated using (true);
```

✓ 안전 코드

```sql
-- 남에게 보여 줄 열만 열 단위로 허용(이제 select('*') 는 권한 오류)
revoke select on table public.profiles from anon, authenticated;
grant select (id, nickname, avatar_url) on table public.profiles to authenticated;
create policy "profiles_read" on public.profiles
  for select to authenticated using (true);

-- 전화번호·주소는 본인만 읽는 별도 테이블로 분리
create table public.profile_private (
  user_id uuid primary key references auth.users (id),
  phone text,
  address text
);
alter table public.profile_private enable row level security;
create policy "own_private" on public.profile_private
  for select to authenticated using ((select auth.uid()) = user_id);
```
