---
id: WS-INJ-001
title: SQL 삽입
slug: sql-injection
group: web
severity: high
site_only: false
depth: full
languages: [python, js, php]
maps:
  owasp2025: [A05]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.2]
  cwe: [CWE-89]
  kr_guide: ["입력데이터 검증 및 표현-1"]
  kr_ciip: [SI]
checked: 2026-11-18
sources:
  - title: OWASP SQL Injection Prevention Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
    year: 2026
  - title: "OWASP Top 10:2025 - A05 Injection"
    url: https://top10.owasp.org/2025/A05_2025-Injection
    year: 2025
  - title: "CWE-89: Improper Neutralization of Special Elements used in an SQL Command"
    url: https://cwe.mitre.org/data/definitions/89.html
    year: 2025
  - title: 2025 CWE Top 25 Most Dangerous Software Weaknesses
    url: https://cwe.mitre.org/top25/archive/2025/2025_cwe_top25.html
    year: 2025
  - title: "CISA 권고 AA23-158A, CL0P Ransomware Gang Exploits CVE-2023-34362 MOVEit Vulnerability"
    url: https://www.cisa.gov/news-events/cybersecurity-advisories/aa23-158a
    year: 2023
  - title: "OWASP ASVS 5.0 - V1.2 Injection Prevention"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: 행정기관 및 공공기관 정보시스템 구축·운영 지침 (행정안전부고시 제2025-1호, 별표 3)
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%ED%96%89%EC%A0%95%EA%B8%B0%EA%B4%80%EB%B0%8F%EA%B3%B5%EA%B3%B5%EA%B8%B0%EA%B4%80%EC%A0%95%EB%B3%B4%EC%8B%9C%EC%8A%A4%ED%85%9C%EA%B5%AC%EC%B6%95%E3%86%8D%EC%9A%B4%EC%98%81%EC%A7%80%EC%B9%A8
    year: 2025
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
  - title: "Chris Shiflett, addslashes() Versus mysql_real_escape_string()"
    url: https://shiflett.org/blog/2006/addslashes-versus-mysql-real-escape-string
    year: 2006
---

## 요약

검색창이나 로그인 칸에 넣은 글자가 DB(데이터를 쌓아 두는 창고)에 보내는 명령문(SQL)에 그대로 끼워지면, 그 글자가 명령이 되어 남의 데이터를 꺼내거나 지운다. 진단가이드 이름은 'SQL 삽입'이다.

## 발생 상황

- 카페 주문서 빈칸에 "아메리카노 한 잔"이 아니라 "아메리카노 한 잔, 그리고 금고도 열어 주세요"라고 썼는데 직원이 그대로 따르는 것과 같다. 빈칸에 쓴 내용과 가게의 지시를 구분하지 않아서 생기는 일이다.
- AI에게 "상품 검색 기능 만들어 줘"라고 했다. 잘 돌아간다. 그런데 검색창에 따옴표(`'`) 하나를 넣으니 DB 오류 문장이 화면에 나온다. 내가 넣은 글자가 명령문에 그대로 붙는다는 신호다.
- 로그인, 게시판 검색, 정렬 기준(`?sort=price`), 엑셀 내려받기 조건처럼 입력한 값이 DB로 가는 곳은 모두 해당한다.
- 평소에는 화면이 멀쩡하게 돌아가서 문제를 알아채기 어렵다.

## 위험 배경

- 오래된 문제지만 지금도 큰 사고를 낸다. 2023년 파일 전송 제품 MOVEit Transfer에서 SQL 삽입 취약점(CVE-2023-34362)이 발견됐다. 미국 CISA(사이버보안 기관)에 따르면 랜섬웨어 조직이 이 취약점으로 여러 기관의 데이터를 빼냈다.
- 소프트웨어 약점 목록 CWE를 운영하는 MITRE가 2025년에 낸 '가장 위험한 약점 25개'에서 SQL 삽입은 2위다.
- 국내 공공 소프트웨어 개발보안 기준(행정안전부 고시 별표 3, 49개 보안약점)에서도 첫 번째 항목이 'SQL 삽입'이다.
- 막는 방법은 정해져 있다. 바인딩(명령문과 입력값을 따로 보내는 방법)을 쓰면 된다. 주문서에 '여기 적힌 것은 메뉴 이름으로만 읽으세요'라고 칸을 나눠 두는 방식이다.

## 대응 방법

**1. AI에게 이렇게 말한다**

```text
DB에 보내는 쿼리를 모두 찾아서, 사용자 입력이 SQL 문장에 직접 이어 붙는 곳이 있는지 알려 줘.
그런 곳은 매개변수 바인딩(prepared statement)으로 바꿔 줘.
정렬 기준·컬럼 이름처럼 바인딩이 안 되는 자리는 정해 둔 목록에서만 고르게 해 줘.
```

**2. 결과를 이렇게 확인한다**

- 바뀐 코드의 SQL 문장 안에 `+`, `.`, `${...}`, `f"...{...}"`로 입력값이 끼워진 곳이 없는지 본다. 대신 `?`나 `%s` 같은 빈칸 표시가 있고 값은 따로 넘기면 된다.
- 검색창에 따옴표(`'`)를 넣어 본다. 오류 문장 없이 "결과 없음"이 나오면 된다.

**3. 코드 칸 보는 법**

- ✗ 코드는 검색어를 명령문 글자에 직접 이어 붙인다. 검색어 속 따옴표 하나로 명령문의 모양이 바뀐다.
- ✓ 코드는 명령문에 빈칸(`?`, `%s`)만 두고 검색어는 따로 보낸다. DB는 검색어를 끝까지 '값'으로만 읽는다.

**4.** 화이트 보안 스킬을 깔아 두면 AI가 쿼리를 쓸 때 입력값을 이어 붙이는지 같이 살펴보고 바인딩으로 바꾸자고 알려 준다.

## 점검 항목

- 쿼리에 들어가는 값은 모두 바인딩(`?`, `%s`, `:name`, `$1`)으로 넘긴다. 문자열 연결, f-string, 템플릿 리터럴, `sprintf`로 값을 넣지 않는다.
- 이스케이프 함수(`addslashes`, 직접 만든 `sql_escape`, `mysqli_real_escape_string`)를 주된 방어로 쓰지 않는다.
- 테이블·컬럼 이름, `ORDER BY` 방향처럼 바인딩할 수 없는 자리는 코드에 정해 둔 허용 목록에서만 고른다.
- `IN (...)` 목록은 값 개수만큼 자리표시자를 만들어 바인딩한다.
- `LIKE` 검색도 값은 바인딩하고, 필요하면 `%`·`_`를 이스케이프한다.
- ORM의 날 SQL 기능(SQLAlchemy `text()`, Django `raw()`·`extra()`, `knex.raw`, `sequelize.query`, Laravel `DB::raw`·`whereRaw`)에도 같은 규칙을 적용한다. Java MyBatis를 쓰는 프로젝트라면 `${}` 대신 `#{}`를 쓴다.
- DB 연결 문자셋을 DSN에 적는다(`charset=utf8mb4`). PDO는 `PDO::ATTR_EMULATE_PREPARES => false`로 서버 쪽 준비문을 쓴다.
- 앱이 쓰는 DB 계정은 필요한 권한만 가진다.
- DB 오류 원문을 화면에 보여 주지 않는다.
- 참고(국내 레거시): "EUC-KR·CP949에서는 `addslashes`가 멀티바이트 문자로 우회된다"는 설명이 돌지만 한국어 문자셋에는 해당하지 않는다. 이 우회는 앞 바이트와 `\`(0x5C)가 한 글자로 묶이는 GBK·Big5·Shift_JIS·CP932에서 생기는데, EUC-KR·CP949에는 0x5C를 둘째 바이트로 쓰는 글자가 없다(Python 표준 코덱으로 0x81~0xFE 뒤에 0x5C를 붙여 확인). 그래도 `addslashes`에 문자열을 이어 붙이는 쿼리는 인코딩과 상관없이 SQL 삽입 항목에 걸린다. 바인딩으로 바꾸고 연결 문자셋을 DSN에 적는다.

## 탐지 규칙

**찾는 곳**: 쿼리 실행부와 그 인자. `execute(`, `executemany(`, `cursor.`, `query(`, `raw(`, `text(`, `->query(`, `->exec(`, `->prepare(`, `mysqli_query(`, `pg_query(`, `db.query(`, `pool.query(`, `sequelize.query(`, `knex.raw(`, `whereRaw(`.

**검사 1. 값 이어 붙이기**
- 지적할 때: 쿼리 문자열에 요청·파일·외부 API·LLM 출력에서 온 값이 `+`, `.`, f-string, 템플릿 리터럴, `%` 포맷, `format()`, `sprintf`로 합쳐진다. 값이 함수 몇 단계를 거쳐 와도 출처가 외부면 같다.
- 지적하지 않을 때: 합쳐지는 값이 코드 상수이거나 허용 목록에서 고른 식별자다. 값은 자리표시자로 넘기고 자리표시자 문자열만 조립한다(`", ".join(["%s"] * len(ids))`). `int()`·`(int)` 변환으로 정수만 들어가는 경우는 위험이 아니라 참고로 바인딩을 권한다.

**검사 2. 식별자 자리**
- 지적할 때: `ORDER BY`, 테이블 이름, 컬럼 이름에 요청 값이 그대로 들어간다.
- 지적하지 않을 때: `SORT_COLUMNS[sort]`처럼 허용 목록 매핑을 거치고, 목록에 없으면 기본값을 쓴다.

**검사 3. 이스케이프에 기댄 코드**
- 지적할 때: `addslashes`나 자체 이스케이프 뒤에 문자열을 이어 붙인다. SQL 삽입으로 보고 바인딩을 권한다.
- 이유로 "EUC-KR 멀티바이트 우회"를 들지 않는다. 연결 문자셋이 GBK·Big5·Shift_JIS·CP932일 때만 그 우회를 근거로 함께 적는다.

**등급**
- 위험: 외부 값이 쿼리 문자열에 그대로 들어간다.
- 주의: 값의 출처를 끝까지 따라가지 못했다(이때 "확인 필요"를 붙인다).
- 참고: 정수 변환 뒤 연결. DSN 문자셋 미지정. DB 오류 원문 노출.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <한 줄 이유> → <고칠 방법>`

예: `[위험] WS-INJ-001 app/search.py:18 — 검색어를 f-string으로 SQL에 넣음 → cur.execute("... LIKE %s", (f"%{q}%",)) 로 바인딩`

**고칠 때**
- 쿼리 모양은 그대로 두고 값만 자리표시자로 바꾼다.
- 바꾼 뒤 기존 테스트로 결과가 같은지 확인하고, 따옴표가 든 입력 테스트를 하나 추가한다.

## 코드 예시

### python

✗ 취약 코드

```python
def search_products(conn, q: str, sort: str):
    sql = f"SELECT id, name, price FROM products WHERE name LIKE '%{q}%' ORDER BY {sort}"
    with conn.cursor() as cur:
        cur.execute(sql)              # 검색어와 정렬 값이 명령문 글자가 된다
        return cur.fetchall()
```

✓ 안전 코드

```python
SORT_COLUMNS = {"name": "name", "price": "price", "new": "created_at DESC"}

def search_products(conn, q: str, sort: str):
    order_by = SORT_COLUMNS.get(sort, "name")   # 식별자는 허용 목록에서 고른다
    sql = f"SELECT id, name, price FROM products WHERE name LIKE %s ORDER BY {order_by}"
    with conn.cursor() as cur:                   # psycopg / PyMySQL
        cur.execute(sql, (f"%{q}%",))            # 값은 바인딩으로 따로 보낸다
        return cur.fetchall()
```

### js

✗ 취약 코드

```js
// Express + mysql2
app.post("/login", async (req, res) => {
  const { email, password } = req.body;
  const [rows] = await pool.query(
    `SELECT id, password_hash FROM users WHERE email = '${email}'` // 입력이 명령문에 끼워진다
  );
  const user = rows[0];
  if (!user || !(await bcrypt.compare(password, user.password_hash))) {
    return res.status(401).json({ error: "이메일 또는 비밀번호가 맞지 않습니다" });
  }
  req.session.userId = user.id;
  res.json({ ok: true });
});
```

✓ 안전 코드

```js
app.post("/login", async (req, res) => {
  const email = String(req.body.email ?? "");
  const password = String(req.body.password ?? "");
  const [rows] = await pool.execute(
    "SELECT id, password_hash FROM users WHERE email = ?", // 빈칸만 둔다
    [email]                                                // 값은 따로
  );
  const user = rows[0];
  if (!user || !(await bcrypt.compare(password, user.password_hash))) {
    return res.status(401).json({ error: "이메일 또는 비밀번호가 맞지 않습니다" });
  }
  req.session.regenerate((err) => {
    if (err) return res.sendStatus(500);
    req.session.userId = user.id;
    res.json({ ok: true });
  });
});
```

### php

✗ 취약 코드

```php
<?php
$q = addslashes($_GET['q'] ?? '');               // 이스케이프 + 이어 붙이기
$sql = "SELECT id, title FROM board WHERE title LIKE '%$q%' ORDER BY " . ($_GET['sort'] ?? 'id');
$rows = $pdo->query($sql)->fetchAll();
```

✓ 안전 코드

```php
<?php
$pdo = new PDO('mysql:host=db;dbname=app;charset=utf8mb4', $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,         // 서버 쪽 준비문
]);

$sorts = ['id' => 'id DESC', 'title' => 'title ASC'];
$sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : '';
$orderBy = $sorts[$sort] ?? 'id DESC';           // 식별자는 허용 목록에서
$q = is_string($_GET['q'] ?? null) ? $_GET['q'] : '';

$st = $pdo->prepare("SELECT id, title FROM board WHERE title LIKE ? ORDER BY $orderBy");
$st->execute(['%' . $q . '%']);                   // 값은 바인딩
$rows = $st->fetchAll();
```
