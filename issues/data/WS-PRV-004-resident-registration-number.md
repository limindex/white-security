---
id: WS-PRV-004
title: RRN Processing (주민등록번호 처리 제한·대체수단)
title_en: RRN (Korean Resident Registration Number) Processing
slug: resident-registration-number
group: data
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs]
maps:
  owasp2025: [A06, A04]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V14.1.1, V14.2.1, V14.2.6]
  cwe: [CWE-359, CWE-312, CWE-532]
  kr_guide: ["보안기능-5"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Next.js, 회원가입·본인확인·나이 확인·정산·지원서처럼 주민등록번호를 받는 화면과 API]
severity_reason: "영향: 평생 바뀌지 않는 번호가 새면 명의 도용·금융 사기로 이어지고 정보주체가 번호를 바꾸기도 어려움 · 악용: 평문 DB·로그·화면 응답 하나로 대량 유출"
sources:
  - title: 개인정보 보호법 (법률 제21445호) 제24조의2 주민등록번호 처리의 제한
    url: https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EB%B3%B4%ED%98%B8%EB%B2%95
    year: 2026
  - title: 정보통신망 이용촉진 및 정보보호 등에 관한 법률 (법률 제21988호) 제23조의2 주민등록번호의 사용 제한, 제23조의5 연계정보의 생성·처리 등, 제23조의6 연계정보의 안전조치 의무 등
    url: https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EC%A0%95%EB%B3%B4%ED%86%B5%EC%8B%A0%EB%A7%9D%EC%9D%B4%EC%9A%A9%EC%B4%89%EC%A7%84%EB%B0%8F%EC%A0%95%EB%B3%B4%EB%B3%B4%ED%98%B8%EB%93%B1%EC%97%90%EA%B4%80%ED%95%9C%EB%B2%95%EB%A5%A0
    year: 2026
  - title: 정보통신망 이용촉진 및 정보보호 등에 관한 법률 시행령 (대통령령 제36728호) 제13조 본인확인기관의 물리적·기술적·관리적 조치 등
    url: https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EC%A0%95%EB%B3%B4%ED%86%B5%EC%8B%A0%EB%A7%9D%EC%9D%B4%EC%9A%A9%EC%B4%89%EC%A7%84%EB%B0%8F%EC%A0%95%EB%B3%B4%EB%B3%B4%ED%98%B8%EB%93%B1%EC%97%90%EA%B4%80%ED%95%9C%EB%B2%95%EB%A5%A0%EC%8B%9C%ED%96%89%EB%A0%B9
    year: 2026
  - title: 개인정보 보호법 시행령 (대통령령 제36804호) 제21조의2 주민등록번호 암호화 적용 대상 등
    url: https://www.law.go.kr/%EB%B2%95%EB%A0%B9/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EB%B3%B4%ED%98%B8%EB%B2%95%EC%8B%9C%ED%96%89%EB%A0%B9
    year: 2026
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제7조
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
---

## 요약

법령 근거 없이 회원가입·나이 확인·본인 확인에 주민등록번호를 받거나, 받을 수 있는 경우에도 평문 저장·전체 표시·로그 기록을 하는 문제. 「개인정보 보호법」 제24조의2는 법률·대통령령 등이 구체적으로 요구·허용한 경우 등이 아니면 주민등록번호 처리를 금지하고(①), 암호화 보관(②)과 홈페이지 회원가입 단계에서 주민등록번호 없이 가입하는 방법 제공(③)을 정함.

## 대응 방법

```text
코드·폼·DB·픽스처에서 주민등록번호(외국인등록번호 포함)를 받거나 저장·표시·기록하는 곳을 모두 찾아 표로 보여 줘.
법령 근거가 확인되지 않은 곳은 생년월일이나 본인확인 서비스 결과로 바꾸고, 기존 컬럼과 데이터를 지우는 마이그레이션을 만들어 줘.
근거가 있어 꼭 받아야 하는 곳은 회원가입과 분리하고, 암호화 저장·뒷자리 가림·로그와 URL 제외로 고쳐 줘. 근거가 있는지는 나에게 먼저 확인해.
```

- 회원가입 화면에서 주민등록번호를 넣지 않고 가입을 끝낼 수 있어야 한다.
- DB·로그·오류 추적 도구 어디에서도 주민등록번호 원문이 보이지 않아야 한다.

## 점검 항목

- 주민등록번호는 법령 근거가 확인된 경우에만 받아야 한다.
- 나이는 생년월일로, 본인 확인은 본인확인기관 결과로 대신해야 한다.
- 회원가입 단계에서는 주민등록번호 없이 가입하는 방법을 둬야 한다.
- 받는 경우 암호화해 저장하고, 검색은 별도 키 HMAC 값으로 해야 한다.
- 화면·응답은 뒷자리를 가리고, 로그·URL·오류 보고에 남기지 않아야 한다.
- 테스트·예시 데이터에는 `000000-0000000` 같은 명백한 가짜 값을 써야 한다.

## 탐지 규칙

- **찾는 곳**: `jumin`, `resident_no`, `residentNumber`, `rrn_`, `foreigner_no`, `주민등록번호`, `\b\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])-?[1-8]\d{6}\b`, 그 밖에 ssn·rrn 이름의 컬럼과 입력 필드, 회원가입·본인확인·정산 폼, 픽스처·시드·문서 예시의 13자리 번호.
- **검사 1 — 근거 없는 수집**: 지적할 때: 회원가입·로그인·나이 확인·이벤트 응모·배송처럼 일반 기능이 주민등록번호를 받는 경우, 회원가입 폼에 주민등록번호 없이 가입하는 방법이 없는 경우. / 지적하지 않을 때: 생년월일이나 본인확인 서비스 결과만 받는 경우, 근거 법령이 코드 주석·문서에 적혀 있고 회원가입과 분리된 단계에서 받는 경우(근거 자체는 "확인 필요"로 보고).
- **검사 2 — 받는 경우의 보호**: 지적할 때: 평문 컬럼, 앞자리만 평문이고 뒷자리만 암호화한 경우, 키 없는 해시(경우의 수가 적어 대입하면 되돌릴 수 있음), 화면·API 응답의 전체 번호, 로그·URL 쿼리·오류 추적에 남는 원문. / 지적하지 않을 때: 암호화 저장, 별도 키 HMAC 검색값, 응답에 가린 값만 있는 경우.
- **검사 3 — 코드·데이터 속 실제 번호**: 지적할 때: 픽스처·시드·테스트·문서에 앞 6자리가 실제 날짜(YYMMDD)이고 7번째 자리가 1~8인 13자리 번호가 있는 경우. / 지적하지 않을 때: `000000-0000000`·`900101-1******`처럼 명백한 가짜·가린 값, 앞 6자리가 날짜가 아니거나 타임스탬프·주문번호·전화번호처럼 문맥이 다른 13자리 숫자.
- **등급**: 위험: 근거 없이 주민등록번호를 받음, 평문 저장·전체 표시, 로그에 원문. / 주의: 회원가입 단계에 대체 방법 없음, 키 없는 해시, 실제 같은 번호의 테스트 데이터, 연계정보(CI) 평문 저장·로그·URL 노출. / 참고: 근거 법령 표기가 없어 확인이 필요한 수집.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PRV-004 app/Http/Controllers/SignupController.php:18 — 나이 확인용으로 jumin 을 필수로 받아 평문 저장 → birth_date 로 바꾸고 jumin 컬럼·데이터 삭제 마이그레이션`
- **검토 주의**: 제24조의2 ①은 "제24조제1항에도 불구하고"로 시작해 별도 동의만으로는 주민등록번호 처리 근거가 되지 않으므로 "동의받았으니 괜찮다"는 설명을 그대로 받아들이지 않아야 하고, 근거 여부의 최종 판단은 사용자(필요하면 법률 전문가)에게 맡겨야 한다. 정보통신서비스 제공자는 정보통신망법 제23조의2에 따라 본인확인기관 등 예외가 아니면 이용자 주민등록번호를 수집·이용할 수 없다. 본인확인기관에서 받은 연계정보(CI, 주민등록번호로 만든 평생 고정 식별값)는 연계정보 이용기관에 해당하면 정보통신망법 제23조의6 ②·시행령 제13조 ② 제3·4호에 따라 주민등록번호와 분리해 보관하고 저장·전송 시 암호화해야 하므로, 평문 저장·로그·URL 노출을 지적해야 한다. 저장 암호화 방식은 WS-PRV-001, 본인확인 결과 위변조는 WS-AUTH-005.
- **고칠 방법**: 근거가 없으면 입력란·컬럼·기존 데이터(백업 포함)를 지워야 하고, 근거가 있으면 회원가입과 분리한 화면에서 받아 암호화·가림·로그 제외를 함께 적용해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// 회원가입: 나이 확인을 위해 주민등록번호를 필수로 받음
$data = $request->validate([
    'name'  => ['required', 'string'],
    'email' => ['required', 'email'],
    'jumin' => ['required', 'regex:/^\d{6}-?[1-8]\d{6}$/'],
]);
$user = User::create($data);          // 평문 저장
Log::info('signup', $data);           // 로그에도 원문
```

✓ 안전 코드

```php
// 회원가입: 주민등록번호 대신 생년월일(만 14세 미만은 법정대리인 동의 절차가 따로 필요해 여기선 받지 않음)
$data = $request->validate([
    'name'       => ['required', 'string', 'max:50'],
    'email'      => ['required', 'email'],
    'birth_date' => ['required', 'date', 'before_or_equal:' . now()->subYears(14)->toDateString()],
]);
$user = User::create($data);
Log::info('signup', ['user_id' => $user->id]);

// 법령 근거가 있어 꼭 받아야 하는 경우만: 회원가입과 분리된 화면, 암호화 저장·가림 표시
class TaxProfile extends Model
{
    protected $fillable = ['user_id', 'rrn', 'rrn_idx'];
    protected $hidden = ['rrn', 'rrn_idx'];               // JSON 응답에서 제외

    protected function casts(): array
    {
        return ['rrn' => 'encrypted'];                     // 하이픈 넣은 형식으로 저장
    }

    public function maskedRrn(): string
    {
        return substr($this->rrn, 0, 8) . '******';        // 900101-1******
    }
}
```

### nextjs

✗ 취약 코드

```ts
// app/api/signup/route.ts — 폼의 <input name="jumin"> 을 그대로 저장
export async function POST(req: Request) {
  const { name, email, jumin } = await req.json();
  const user = await db.user.create({ data: { name, email, jumin } });   // 평문
  return Response.redirect(new URL(`/welcome?jumin=${jumin}`, req.url)); // URL 에도 남음
}
```

✓ 안전 코드

```ts
// app/api/signup/route.ts — 주민등록번호는 받지 않음
import { z } from "zod";

const Signup = z.object({
  name: z.string().min(1).max(50),
  email: z.email(),
  birthDate: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
}).strict();                                 // 정의하지 않은 필드(jumin 등)가 오면 거절

export async function POST(req: Request) {
  const parsed = Signup.safeParse(await req.json());
  if (!parsed.success) return new Response(null, { status: 400 });
  const user = await db.user.create({ data: parsed.data });
  return Response.json({ id: user.id });
}
// 성인·본인 확인이 꼭 필요하면 본인확인 서비스 결과를 서버에서 검증해(WS-AUTH-005) 생년월일만 저장
```
