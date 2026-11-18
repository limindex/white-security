---
id: WS-ENC-001
title: Leftover Debug Code·Data Exposure (남은 디버그 코드·잘못된 데이터 노출)
slug: debug-code-data-exposure
group: web
severity: medium
site_only: false
depth: brief
languages: [express, fastapi, laravel]
maps:
  owasp2025: [A02, A01]
  api2023: [API3]
  llm2026: []
  agentic2026: []
  asvs5: [V13.4.2, V15.2.3, V15.3.1]
  cwe: [CWE-489, CWE-488, CWE-495, CWE-496, CWE-615, CWE-200]
  kr_guide: ["캡슐화-1", "캡슐화-2", "캡슐화-3", "캡슐화-4", "보안기능-13"]
  kr_ciip: [IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Express·Node.js, FastAPI, Laravel(Octane 포함), 프로세스가 오래 사는 웹 서버]
severity_reason: "영향: 다른 사용자의 개인정보 노출, 비밀번호 해시·내부 주소 유출 · 악용: 동시에 요청하거나 남은 디버그 주소를 여는 것만으로 가능"
sources:
  - title: "CWE-488: Exposure of Data Element to Wrong Session"
    url: https://cwe.mitre.org/data/definitions/488.html
    year: 2025
  - title: "CWE-489: Active Debug Code"
    url: https://cwe.mitre.org/data/definitions/489.html
    year: 2025
  - title: "OWASP API Security Top 10 2023 - API3 Broken Object Property Level Authorization"
    url: https://owasp.org/API-Security/editions/2023/en/0xa3-broken-object-property-level-authorization/
    year: 2023
  - title: "FastAPI Docs - Response Model"
    url: https://fastapi.tiangolo.com/tutorial/response-model/
    year: 2026
---

## 요약

배포 코드에 남은 디버그 라우트·`dd()`·`console.log`, 여러 요청이 함께 쓰는 전역 변수에 넣은 사용자 정보, 비밀번호 해시까지 담긴 모델을 그대로 돌려주는 응답 때문에 남의 데이터나 내부 정보가 보이는 문제(진단가이드 이름: 제거되지 않고 남은 디버그 코드·잘못된 세션에 의한 데이터 정보 노출).

## 대응 방법

```text
배포 코드에 남은 디버그 라우트(/debug, /test, phpinfo)·dd()·dump()·토큰이나 개인정보를 찍는 console.log 를 찾아 지워 줘.
요청마다 다른 사용자 정보를 모듈 전역 변수·싱글턴에 담는 곳을 찾아 요청 객체로 옮겨 줘.
API 응답은 모델 전체 대신 필요한 필드만 고른 응답 형식(response_model·API Resource·DTO)으로 바꿔 줘.
```

- 두 계정으로 동시에 내 정보 화면을 여러 번 열어도 서로의 정보가 섞이지 않아야 한다.
- 회원 API 응답에 `password`·`token` 같은 필드가 없어야 한다.

## 점검 항목

- 디버그 라우트·`phpinfo()`·`dd()`·테스트 계정 코드는 배포 전에 지워야 한다.
- 화면 HTML·JS 주석에 내부 주소·계정·키를 남기지 않아야 한다.
- 요청별 사용자 정보는 전역 변수가 아니라 요청 객체·세션에 둬야 한다.
- 응답은 허용 필드만 골라 보내야 한다(비밀번호 해시·토큰·내부 메모 제외).
- 전역 설정·캐시 객체는 그대로 돌려주거나 고치지 말고 복사본을 써야 한다.

## 탐지 규칙

- **찾는 곳**: `phpinfo(`, `dd(`, `response_model`, `$hidden`, 그 밖에 /debug·/test 라우트, var_dump·console.log 로 찍는 토큰, 모듈 전역 currentUser.
- **검사 1 — 남은 디버그 코드**: 지적할 때: 운영 라우트에 `/debug`·`/test`·`phpinfo()`·`dd()`·`dump()`가 있거나, 토큰·비밀번호·개인정보를 `console.log`·`print`로 찍는 경우. / 지적하지 않을 때: 개발 환경에서만 켜지는 코드(`app()->isLocal()`, `NODE_ENV !== "production"`)이고 운영 설정에서 꺼진 것이 확인되는 경우.
- **검사 2 — 요청 사이 공유 상태**: 지적할 때: 모듈 전역·클래스 정적 변수·싱글턴에 현재 사용자·장바구니·토큰을 넣어 다른 요청이 읽을 수 있는 경우(Node·FastAPI·Laravel Octane처럼 프로세스가 오래 사는 서버). / 지적하지 않을 때: `req`·`request.state`·세션·`AsyncLocalStorage`·`contextvars`에 두는 경우.
- **검사 3 — 모델 통째 응답**: 지적할 때: ORM 모델·DB 행을 그대로 JSON으로 돌려줘 비밀번호 해시·토큰·내부 필드가 나가는 경우. / 지적하지 않을 때: `response_model`·API Resource·`$hidden`·필드 선택으로 보낼 값을 고른 경우.
- **등급**: 위험: 다른 사용자 정보가 섞이는 공유 상태, 비밀번호 해시·토큰이 응답에 나가는 경우. / 주의: 운영에서 열리는 디버그 라우트, 개인정보를 찍는 로그. / 참고: 주석 속 내부 정보, 전역 객체를 그대로 돌려주는 코드.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-ENC-001 src/auth.js:5 — 로그인 사용자를 모듈 전역 currentUser 에 저장, 동시 요청에서 다른 사람 정보가 보임 → req.user 에 저장`
- **고칠 방법**: 디버그 코드는 지우는 것을 기본으로 하고, 남겨야 하면 환경 설정으로 운영에서 꺼야 한다.

## 코드 예시

### express

✗ 취약 코드

```js
let currentUser = null;                       // 모든 요청이 함께 쓰는 변수

app.use(async (req, res, next) => {
  currentUser = await findUserBySession(req.cookies.sid);
  next();
});

app.get("/me", async (req, res) => {
  await loadSomething();                      // 기다리는 사이 다른 요청이 currentUser 를 바꾼다
  res.json(currentUser);                      // 남의 정보, 비밀번호 해시까지
});
app.get("/debug/env", (req, res) => res.json(process.env)); // 남은 디버그 라우트
```

✓ 안전 코드

```js
app.use(async (req, res, next) => {
  req.user = await findUserBySession(req.cookies.sid);   // 요청 객체에 둔다
  next();
});

app.get("/me", async (req, res) => {
  if (!req.user) return res.sendStatus(401);
  const { id, name, email } = req.user;                  // 보낼 필드만 고른다
  res.json({ id, name, email });
});
// /debug 라우트는 지운다
```

### fastapi

✗ 취약 코드

```python
@app.get("/me")
def read_me(user: User = Depends(current_user)):
    return user          # User 가 SQLModel 테이블이면 password_hash·reset_token 까지 나간다
```

✓ 안전 코드

```python
from pydantic import BaseModel, ConfigDict

class UserOut(BaseModel):
    model_config = ConfigDict(from_attributes=True)
    id: int
    name: str
    email: str

@app.get("/me", response_model=UserOut)      # 여기 적은 필드만 나간다
def read_me(user: User = Depends(current_user)):
    return user
```

### laravel

✗ 취약 코드

```php
// routes/web.php
Route::get('/debug', fn () => phpinfo());              // 서버 경로·환경 변수·모듈 목록 노출

// UserController
public function me(Request $request)
{
    return $request->user();                           // $hidden 에 없는 열(전화번호·내부 메모)이 모두 나간다
}
```

✓ 안전 코드

```php
// routes/web.php 의 /debug 라우트는 지운다

// app/Http/Resources/MeResource.php
class MeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email]; // 보낼 필드만
    }
}

// UserController
public function me(Request $request)
{
    return new MeResource($request->user());
}
```
