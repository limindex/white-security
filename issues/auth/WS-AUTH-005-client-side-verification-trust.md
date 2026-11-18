---
id: WS-AUTH-005
title: Authentication Bypass (본인확인 결과·클라이언트 판단 신뢰)
slug: client-side-verification-trust
group: auth
severity: high
grade_hint: "값 하나 바꿔 본인확인·재설정 건너뜀"
always_on: "`phoneVerified` `verifyIdToken(` → 본인확인 결과는 클라이언트 값이 아니라 서버가 기관 모듈·ID 토큰으로 직접 검증해야 한다."
site_only: false
depth: brief
languages: [laravel, nextjs, fastapi]
maps:
  owasp2025: [A07, A06]
  api2023: [API2]
  llm2026: []
  agentic2026: []
  asvs5: [V2.2.2, V8.3.1, V6.6.2]
  cwe: [CWE-602, CWE-603, CWE-807, CWE-294]
  kr_guide: ["입력데이터 검증 및 표현-15", "보안기능-1"]
  kr_ciip: [IA, CC, PV]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js·Firebase 전화 인증, FastAPI, 휴대폰 본인확인·성인 확인·앱 인증을 쓰는 모든 서비스]
severity_reason: "영향: 남의 명의로 가입·비밀번호 재설정, 미성년자 성인 인증 통과 · 악용: 요청 값 하나를 바꾸는 것만으로 가능"
sources:
  - title: KISA 2026 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드 (불충분한 인증 절차·쿠키 변조·프로세스 검증 누락)
    url: https://www.kisa.or.kr/2060204/form?postSeq=22
    year: 2025
  - title: 정보통신망 이용촉진 및 정보보호 등에 관한 법률 제23조의2~제23조의6 (주민등록번호 사용 제한·본인확인기관·연계정보)
    url: https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EC%A0%95%EB%B3%B4%ED%86%B5%EC%8B%A0%EB%A7%9D%EC%9D%B4%EC%9A%A9%EC%B4%89%EC%A7%84%EB%B0%8F%EC%A0%95%EB%B3%B4%EB%B3%B4%ED%98%B8%EB%93%B1%EC%97%90%EA%B4%80%ED%95%9C%EB%B2%95%EB%A5%A0
    year: 2026
  - title: 정보통신망 이용촉진 및 정보보호 등에 관한 법률 시행령 (대통령령 제36728호) 제13조 본인확인기관의 물리적·기술적·관리적 조치 등
    url: https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EC%A0%95%EB%B3%B4%ED%86%B5%EC%8B%A0%EB%A7%9D%EC%9D%B4%EC%9A%A9%EC%B4%89%EC%A7%84%EB%B0%8F%EC%A0%95%EB%B3%B4%EB%B3%B4%ED%98%B8%EB%93%B1%EC%97%90%EA%B4%80%ED%95%9C%EB%B2%95%EB%A5%A0%EC%8B%9C%ED%96%89%EB%A0%B9
    year: 2026
  - title: "CWE-602 Client-Side Enforcement of Server-Side Security"
    url: https://cwe.mitre.org/data/definitions/602.html
    year: 2026
  - title: "Firebase Docs - Verify ID Tokens"
    url: https://firebase.google.com/docs/auth/admin/verify-id-tokens
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

휴대폰 본인확인·전화번호 인증·성인 확인의 결과를 서버가 직접 검증하지 않고, 브라우저나 앱이 보낸 `verified=true`·`cert_ok=Y`·이름·생년월일 값을 그대로 믿어 값만 바꾸면 인증을 건너뛰는 문제(진단가이드 이름: 보안기능 결정에 사용되는 부적절한 입력값). 서버 응답의 `"success": false`를 `true`로 바꿔 앱 화면만 넘기는 응답 변조도 같은 범위.

## 대응 방법

```text
본인확인·휴대폰 인증·성인 확인 결과를 받는 곳과, 그 결과로 가입·비밀번호 재설정·결제를 허용하는 곳을 찾아 줘.
결과는 서버가 본인확인기관 서버 모듈이나 ID 토큰으로 직접 검증하고, 인증 완료 여부와 인증된 이름·번호·생년월일은 서버 세션에만 두게 바꿔 줘.
요청 본문·쿠키·로컬 저장소의 인증 플래그와 다시 입력받은 이름·번호는 쓰지 않게 해 줘.
```

- 인증 뒤 가입 요청의 이름·휴대폰 번호를 다른 값으로 바꿔 보내도 인증된 값으로 저장돼야 한다.
- 한 번 쓴 인증 결과로 다른 계정을 하나 더 만들면 실패해야 한다.

## 점검 항목

- 본인확인·휴대폰 인증 결과는 서버가 기관 모듈이나 ID 토큰으로 직접 검증해야 한다.
- 인증 완료 여부는 서버 세션·DB에 두고, 쿠키·요청 값의 플래그를 믿지 않아야 한다.
- 이름·생년월일·휴대폰 번호는 인증 결과에서 서버가 채워야 한다.
- 인증 결과는 요청번호와 대조하고, 짧은 유효시간 안에 한 번만 써야 한다.
- 나이·실명 판단은 검증된 생년월일로 서버에서 계산해야 한다.
- CI(연계정보)는 암호화해 주민등록번호와 분리 보관하고, CI·DI(중복가입확인정보)는 로그·URL·브라우저에 남기지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `phoneVerified`, `is_adult`, `cert_ok`, `enc_data`, `verifyIdToken(`, `confirmationResult.confirm(`, 그 밖에 isVerified 같은 인증 플래그, 본인확인 콜백·가입 폼의 hidden 필드, 앱의 인증 성공 분기.
- **검사 1 — 클라이언트가 보낸 인증 결과**: 지적할 때: 요청 본문·쿼리·쿠키·로컬 저장소의 `verified`·`cert_ok=Y`·`is_adult` 같은 값으로 가입·비밀번호 재설정·성인 확인·결제 단계를 통과시키는 경우. 브라우저의 Firebase 전화 인증 뒤 서버가 ID 토큰 없이 번호만 받는 경우. / 지적하지 않을 때: 서버가 본인확인 결과·ID 토큰(`verifyIdToken(`)을 직접 검증해 세션·DB에 저장한 값만 쓰는 경우.
- **검사 2 — 인증된 값 바꿔치기·재사용**: 지적할 때: 인증 뒤 이름·생년월일·휴대폰 번호를 폼에서 다시 받아 저장하는 경우. 결과를 요청번호·유효시간·1회 사용으로 묶지 않는 경우. / 지적하지 않을 때: 인증 결과 값을 서버가 채우고, 요청번호 대조·시간 제한·사용 후 삭제가 있는 경우.
- **등급**: 위험: 값 하나 바꿔 본인확인·성인 확인·비밀번호 재설정을 건너뛸 수 있는 경우. / 주의: 인증 결과의 재사용·시간 제한이 없는 경우, CI를 평문으로 저장하거나 로그·URL·클라이언트에 남기는 경우. / 참고: DI를 로그·URL·클라이언트에 남기는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-AUTH-005 app/Http/Controllers/RegisterController.php:27 — cert_ok=Y 와 hidden 필드의 이름·번호를 그대로 믿고 가입 처리 → 콜백에서 서버가 결과를 복호화·검증해 세션에 두고, 가입은 세션 값만 사용`
- **검토 주의**: 본인확인기관마다 결과 복호화·검증 방법이 다르므로 기관이 준 서버 모듈과 문서대로 쓰는지 봐야 하고, 모듈 호출이 보이지 않으면 "확인 필요"로 표시해야 한다. CI는 연계정보 이용기관의 안전조치(정보통신망법 제23조의6 ②·시행령 제13조 ②: 주민등록번호와 분리 보관, 암호화 저장·전송)를 근거로 지적하고, DI는 법률·시행령에 규정이 없어 권고로만 보고해야 한다.
- **고칠 방법**: 인증 결과는 서버가 검증해 세션에 넣고, 다음 단계는 세션 값만 읽어 쓴 뒤 지워야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// 본인확인 팝업이 끝나면 결과 페이지가 이름·번호를 hidden 필드로 가입 폼에 넘긴다
public function register(Request $request)
{
    if ($request->input('cert_ok') !== 'Y') abort(403);   // 브라우저가 보낸 값
    User::create([
        'name'     => $request->input('name'),             // 다른 이름·번호로 바꿔 보내도 통과
        'phone'    => $request->input('phone'),
        'birth'    => $request->input('birth'),
        'password' => Hash::make($request->input('password')),
    ]);
}
```

✓ 안전 코드

```php
// 콜백: 요청 때 세션에 둔 요청번호와 대조, 결과는 서버 모듈로 복호화·검증
public function callback(Request $request, IdentityVerifier $verifier)  // 기관 서버 모듈을 감싼 클래스
{
    $r = $verifier->decryptAndVerify((string) $request->input('enc_data'));
    abort_unless($r && hash_equals((string) session()->pull('cert_req_no'), $r->requestNo), 403);
    session(['cert' => ['name' => $r->name, 'phone' => $r->phone, 'birth' => $r->birth,
                        'di' => $r->di, 'at' => now()->timestamp]]);
}

public function register(Request $request)
{
    $cert = session()->pull('cert');                                 // 한 번 쓰면 사라진다
    abort_if(! $cert || now()->timestamp - $cert['at'] > 600, 403);  // 10분 안에만
    abort_if(User::where('di', $cert['di'])->exists(), 409);        // 같은 사람 중복 가입 차단
    $data = $request->validate(['password' => 'required|string|min:15|max:128']);  // WS-PWD-001 기준
    User::create(['name' => $cert['name'], 'phone' => $cert['phone'], 'birth' => $cert['birth'],
                  'di' => $cert['di'], 'password' => Hash::make($data['password'])]);  // 값은 세션에서
}
```

### nextjs

✗ 취약 코드

```ts
// app/api/signup/route.ts — 전화 인증은 브라우저에서 끝내고 결과만 받는다
export async function POST(req: Request) {
  const { phone, phoneVerified, name } = await req.json();
  if (!phoneVerified) return new Response("인증 필요", { status: 400 });  // 보낸 true 를 그대로 믿음
  await db.user.create({ data: { phone, name, phoneVerified: true } });
  return Response.json({ ok: true });
}
```

✓ 안전 코드

```ts
import { getAuth } from "firebase-admin/auth";

export async function POST(req: Request) {
  const { idToken, name } = await req.json();
  // 서명·만료·폐기 여부를 서버에서 확인(브라우저의 confirmationResult.confirm 뒤 받은 ID 토큰)
  const t = await getAuth().verifyIdToken(String(idToken), true).catch(() => null);
  if (!t?.phone_number) return new Response("인증 필요", { status: 401 });
  if (Date.now() / 1000 - t.auth_time > 300) return new Response("다시 인증해 주세요", { status: 401 });
  await db.user.create({
    data: { phone: t.phone_number, firebaseUid: t.uid, name: String(name).slice(0, 30) },  // 번호는 토큰에서
  });                                                    // firebaseUid 는 unique 제약으로 재사용 차단
  return Response.json({ ok: true });
}
```

### fastapi

✗ 취약 코드

```python
class OrderIn(BaseModel):
    product_id: int
    is_adult: bool                 # 앱이 "성인 인증 완료"라고 보내는 값

@app.post("/orders")
def order(body: OrderIn, user: User = Depends(get_current_user)):
    product = get_product(body.product_id)
    if product.adult_only and not body.is_adult:  # 값 하나만 바꾸면 통과
        raise HTTPException(403)
    return create_order(user, product)
```

✓ 안전 코드

```python
class OrderIn(BaseModel):
    product_id: int

def age_on(birth: date, today: date) -> int:
    return today.year - birth.year - ((today.month, today.day) < (birth.month, birth.day))

@app.post("/orders")
def order(body: OrderIn, user: User = Depends(get_current_user)):
    product = get_product(body.product_id)
    # 생년월일은 서버가 본인확인 결과를 검증해 저장한 값만 쓴다(가입 폼 입력값 사용 금지)
    if product.adult_only and (user.verified_birth is None
                               or age_on(user.verified_birth, date.today()) < ADULT_AGE):  # 서비스 법령 기준
        raise HTTPException(403, "성인 인증이 필요합니다")
    return create_order(user, product)
```
