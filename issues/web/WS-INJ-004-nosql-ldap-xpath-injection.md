---
id: WS-INJ-004
title: NoSQL · LDAP · XPath Injection (NoSQL·LDAP·XPath 인젝션)
slug: nosql-ldap-xpath-injection
group: web
severity: high
site_only: false
depth: brief
languages: [express, fastapi, python]
maps:
  owasp2025: [A05]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.2.4, V1.2.6, V1.2.7, V15.3.5]
  cwe: [CWE-943, CWE-90, CWE-643, CWE-20]
  kr_guide: ["입력데이터 검증 및 표현-9", "입력데이터 검증 및 표현-10"]
  kr_ciip: [CI]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Express·Mongoose, FastAPI·PyMongo, Python ldap3, MongoDB·LDAP·XML 데이터를 조회하는 서비스]
severity_reason: "영향: 비밀번호 없이 로그인·다른 사용자 데이터 조회 · 악용: JSON 요청 본문이나 검색어 한 줄로 가능"
sources:
  - title: "OWASP WSTG - Testing for NoSQL Injection"
    url: https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/07-Input_Validation_Testing/05.6-Testing_for_NoSQL_Injection
    year: 2026
  - title: OWASP LDAP Injection Prevention Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/LDAP_Injection_Prevention_Cheat_Sheet.html
    year: 2026
  - title: "Mongoose Docs - sanitizeFilter"
    url: https://mongoosejs.com/docs/api/mongoose.html#Mongoose.prototype.sanitizeFilter()
    year: 2026
  - title: "CWE-943: Improper Neutralization of Special Elements in Data Query Logic"
    url: https://cwe.mitre.org/data/definitions/943.html
    year: 2025
---

## 요약

MongoDB 같은 NoSQL 조회에 요청 JSON을 그대로 넣어 `$`로 시작하는 연산자가 조건을 바꾸거나, LDAP 필터·XPath 식에 입력을 이어 붙여 검색 조건이 바뀌는 문제(진단가이드 이름: LDAP 삽입·XML 삽입). 로그인 조회에서 생기면 비밀번호 확인이 무력해짐.

## 대응 방법

```text
MongoDB 조회(find·findOne·aggregate·updateOne)에 req.body·req.query 값이 그대로 들어가는 곳과, LDAP 필터·XPath 식을 문자열로 만드는 곳을 찾아 줘.
조회 값은 문자열·숫자로 형을 확인하고, $where·$function 처럼 서버에서 JS를 돌리는 연산자는 빼 줘.
LDAP 은 escape_filter_chars 같은 이스케이프, XPath 는 변수 바인딩이나 허용 목록 검사로 바꿔 줘.
```

- 로그인 요청에서 비밀번호 칸을 문자열 대신 객체로 보내면 400이나 로그인 실패가 나와야 한다.

## 점검 항목

- NoSQL 조회 값은 스키마 검증이나 `String()`으로 기대한 형인지 확인해야 한다.
- 요청 본문을 조회 조건 객체로 통째로 넘기지 않아야 한다.
- `$where`·`$function`·`mapReduce`처럼 서버에서 JS를 돌리는 기능은 쓰지 않아야 한다.
- Mongoose는 `sanitizeFilter` 옵션을 켜 둬야 한다.
- LDAP 필터 값은 `escape_filter_chars`로, DN 값은 DN용 이스케이프로 처리해야 한다.
- XPath 식에 입력을 이어 붙이지 말고 변수 바인딩이나 허용 목록 검사를 거쳐야 한다.

## 탐지 규칙

- **찾는 곳**: `findOne(req.body`, `find(req.query`, `$where`, `ldap_search(`, `xpath(`, 그 밖에 req.body·req.query 를 받는 findOne·updateOne·aggregate, ldap3 search 필터, 문자열로 만든 LDAP 필터.
- **검사 1 — 요청 객체가 조건**: 지적할 때: `req.body`·`req.query`·JSON 값이 형 확인 없이 `find`·`findOne`·`updateOne` 조건에 들어가는 경우(Express 4의 기본 쿼리 파서는 대괄호 이름도 객체로 만듦). / 지적하지 않을 때: `String()`·`Number()` 변환이나 zod·Pydantic 스키마로 형을 확인한 경우, Mongoose `sanitizeFilter`가 켜진 경우.
- **검사 2 — 서버 JS 실행 연산자**: 지적할 때: `$where`·`$function`·`$accumulator`·`mapReduce`에 외부 값이 들어가는 경우. / 지적하지 않을 때: 코드에 고정된 식만 쓰는 경우.
- **검사 3 — LDAP·XPath 문자열**: 지적할 때: LDAP 필터(`(uid=...)`)나 XPath 식에 입력을 이어 붙이는 경우. / 지적하지 않을 때: `escape_filter_chars`·`ldap_escape()` 같은 이스케이프나 XPath 변수 바인딩을 거친 경우.
- **등급**: 위험: 로그인·권한 조회 조건에 형 확인 없는 요청 값, `$where`에 입력. / 주의: 일반 조회 조건의 형 확인 없음, LDAP·XPath 이어 붙이기. / 참고: `$` 키 검사를 직접 만든 경우(빠진 경로 확인 필요).
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-INJ-004 routes/auth.js:9 — req.body.password 를 형 확인 없이 findOne 조건에 넣음 → 문자열 확인 후 해시 비교, mongoose sanitizeFilter 켜기`
- **고칠 방법**: 조회 직전에 형 확인을 넣고, 같은 모델을 쓰는 다른 라우트에도 같은 검사가 있는지 확인해야 한다.

## 코드 예시

### express

✗ 취약 코드

```js
app.post("/login", async (req, res) => {
  // 값 자리에 문자열 대신 연산자 객체가 들어오면 조건이 바뀐다
  const user = await User.findOne({ email: req.body.email, password: req.body.password });
  if (!user) return res.status(401).end();
  req.session.userId = user.id;
  res.json({ ok: true });
});
```

✓ 안전 코드

```js
mongoose.set("sanitizeFilter", true); // 값 자리의 연산자 객체를 $eq 로 감싸 값으로 비교

app.post("/login", async (req, res) => {
  const { email, password } = req.body ?? {};
  if (typeof email !== "string" || typeof password !== "string") return res.status(400).end(); // 형 확인
  const user = await User.findOne({ email });
  if (!user || !(await bcrypt.compare(password, user.passwordHash))) return res.status(401).end();
  req.session.regenerate((err) => {
    if (err) return res.sendStatus(500);
    req.session.userId = user.id;
    res.json({ ok: true });
  });
});
```

### fastapi

✗ 취약 코드

```python
@app.get("/products")
def products(max_price: str):
    # 입력을 서버에서 실행되는 JS 식에 이어 붙인다
    return list(db.products.find({"$where": f"this.price <= {max_price}"}, {"_id": 0}))
```

✓ 안전 코드

```python
from fastapi import Query

@app.get("/products")
def products(max_price: int = Query(ge=0, le=10_000_000)):   # 숫자로만 받는다
    return list(db.products.find({"price": {"$lte": max_price}}, {"_id": 0}).limit(100))
```

### python

✗ 취약 코드

```python
from ldap3 import Connection

def find_user(conn: Connection, username: str):
    # * ( ) 가 섞이면 필터 조건이 바뀐다
    conn.search("ou=people,dc=example,dc=com", f"(uid={username})", attributes=["cn", "mail"])
    return conn.entries
```

✓ 안전 코드

```python
from ldap3 import Connection
from ldap3.utils.conv import escape_filter_chars

def find_user(conn: Connection, username: str):
    if not username or len(username) > 64:
        raise ValueError("잘못된 사용자 이름")
    flt = f"(uid={escape_filter_chars(username)})"   # * ( ) \ NUL 을 이스케이프
    conn.search("ou=people,dc=example,dc=com", flt, attributes=["cn", "mail"], size_limit=10)
    return conn.entries
```
