---
id: WS-INJ-001
title: SQL 인젝션 (SQLi)
title_en: SQL Injection (SQLi)
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
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 탐지 규칙 찾는 곳 신호 정리(DB::raw 추가, 스킬 INDEX 크기 맞춤)
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, JavaScript, PHP, SQL DB를 쓰는 모든 웹 애플리케이션]
severity_reason: "영향: DB 전체 조회·변조·삭제 · 악용: 입력 칸 하나에 넣는 글자만으로 가능, 자동화 도구 흔함"
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

SQL 인젝션(SQLi)은 검색창·로그인 칸에 넣은 글자가 DB(데이터를 쌓아 두는 저장소)에 보내는 명령문(SQL)에 그대로 끼워져, 입력이 명령으로 실행되는 취약점에 대한 이슈(진단가이드 이름: SQL 삽입). 남의 데이터 조회·변조·삭제로 이어지는 위험.

## 발생 상황

- AI에 맡겨 만든 상품 검색 기능에서 검색창에 따옴표(`'`) 하나를 넣으면 DB 오류 문장이 화면에 나오는 경우. 입력이 명령문에 그대로 붙는다는 신호.
- 로그인, 게시판 검색, 정렬 기준(`?sort=price`), 엑셀 내려받기 조건처럼 입력값이 DB로 가는 모든 기능.
- 평소에는 화면이 정상으로 돌아가 문제를 알아채기 어려운 구조.

## 위험 배경

- 2023년 파일 전송 제품 MOVEit Transfer의 SQL 인젝션 취약점(CVE-2023-34362)으로 랜섬웨어 조직이 여러 기관의 데이터를 빼낸 사고(미국 CISA 권고).
- MITRE의 2025 CWE Top 25(가장 위험한 소프트웨어 약점 25개) 2위. 국내 공공 소프트웨어 개발보안 기준(행정안전부 고시 별표 3, 보안약점 49개)의 첫 번째 항목도 'SQL 삽입'.
- 막는 방법은 이미 정해진 상태. 바인딩(명령문과 입력값을 따로 보내는 방식)으로 입력을 끝까지 '값'으로만 읽게 하는 것.

## 대응 방법

```text
DB에 보내는 쿼리를 모두 찾아, 사용자 입력이 SQL 문장에 직접 이어 붙는 곳을 알려 줘.
그런 곳은 매개변수 바인딩(prepared statement)으로 바꿔 줘.
정렬 기준·컬럼 이름처럼 바인딩이 안 되는 자리는 정해 둔 목록에서만 고르게 해 줘.
```

- 바뀐 코드의 SQL 문장에 `+`, `.`, `${...}`, `f"...{...}"`로 끼운 입력값이 없어야 하고, 값은 `?`·`%s` 같은 자리표시자로 따로 넘겨야 한다.
- 검색창에 따옴표(`'`)를 넣었을 때 오류 문장 없이 "결과 없음"이 나와야 한다.

## 점검 항목

- 쿼리 값은 모두 바인딩(`?`, `%s`, `:name`, `$1`)으로 넘겨야 한다. 문자열 연결·f-string·템플릿 리터럴·`sprintf`로 값을 넣지 않아야 하고, `IN (...)` 목록은 값 개수만큼 자리표시자를 만들며, `LIKE` 검색도 값은 바인딩하고 필요하면 `%`·`_`를 이스케이프해야 한다.
- 이스케이프 함수(`addslashes`, 직접 만든 `sql_escape`, `mysqli_real_escape_string`)를 주된 방어로 쓰지 않아야 한다.
- 테이블·컬럼 이름, `ORDER BY` 방향처럼 바인딩할 수 없는 자리는 코드에 정해 둔 허용 목록에서만 골라야 한다.
- ORM의 날 SQL 기능(SQLAlchemy `text()`, Django `raw()`·`extra()`, `knex.raw`, `sequelize.query`, Laravel `DB::raw`·`whereRaw`)에도 같은 규칙을 적용해야 한다. Java MyBatis라면 `${}` 대신 `#{}`를 써야 한다.
- DB 연결 문자셋을 DSN에 적어야 하고(`charset=utf8mb4`), PDO는 `PDO::ATTR_EMULATE_PREPARES => false`로 서버 쪽 준비문을 써야 한다.
- 앱이 쓰는 DB 계정은 필요한 권한만 가져야 하고, DB 오류 원문을 화면에 보여 주지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: 쿼리 실행부와 그 인자(`cursor.execute(`, `->query(`, `pool.query(`, `whereRaw(`, `DB::raw(`, `knex.raw(`, 그 밖에 mysqli_query·db.query·sequelize.query·executemany, Django raw, SQLAlchemy text, PDO exec·prepare, pg_query).
- **검사 1 — 값 이어 붙이기**: 지적할 때: 요청·파일·외부 API·LLM 출력에서 온 값이 `+`, `.`, f-string, 템플릿 리터럴, `%` 포맷, `format()`, `sprintf`로 쿼리 문자열에 합쳐지는 경우(함수 몇 단계를 거쳐 와도 출처가 외부면 같음). / 지적하지 않을 때: 합치는 값이 코드 상수나 허용 목록에서 고른 식별자이거나, 자리표시자 문자열만 조립하는 경우(`", ".join(["%s"] * len(ids))`).
- **검사 2 — 식별자 자리**: 지적할 때: `ORDER BY`, 테이블 이름, 컬럼 이름에 요청 값이 그대로 들어가는 경우. / 지적하지 않을 때: `SORT_COLUMNS[sort]`처럼 허용 목록 매핑을 거치고, 목록에 없으면 기본값을 쓰는 경우.
- **검사 3 — 이스케이프에 기댄 코드**: 지적할 때: `addslashes`나 자체 이스케이프 뒤에 문자열을 이어 붙이는 경우(SQL 인젝션으로 보고 바인딩 권고). / 지적하지 않을 때: 이스케이프 결과가 쿼리 문자열이 아닌 곳(화면 출력, 파일 등)에만 쓰이는 경우.
- **등급**: 위험: 외부 값이 쿼리 문자열에 그대로 들어가는 경우. / 주의: 값의 출처를 끝까지 따라가지 못한 경우("확인 필요" 표시). / 참고: 정수 변환(`int()`·`(int)`) 뒤 연결, DSN 문자셋 미지정, DB 오류 원문 노출.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-INJ-001 app/search.py:18 — 검색어를 f-string으로 SQL에 넣음 → cur.execute("... LIKE %s", (f"%{q}%",)) 로 바인딩`
- **검토 주의**: 이스케이프 코드를 지적할 때 이유로 "EUC-KR 멀티바이트 우회"를 들지 않아야 하고, 연결 문자셋이 GBK·Big5·Shift_JIS·CP932일 때만 그 우회를 근거로 함께 적어야 한다(EUC-KR이어도 이어 붙이는 쿼리 자체는 지적 대상). 이 우회는 앞 바이트와 `\`(0x5C)가 한 글자로 묶이는 GBK·Big5·Shift_JIS·CP932에서 생기고, EUC-KR·CP949에는 0x5C를 둘째 바이트로 쓰는 글자가 없다(Python 표준 코덱으로 0x81~0xFE 뒤에 0x5C를 붙여 확인).
- **고칠 방법**: 쿼리 모양은 그대로 두고 값만 자리표시자로 바꿔야 하고, 기존 테스트로 결과가 같은지 확인한 뒤 따옴표가 든 입력 테스트를 하나 추가해야 한다.

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
  const [rows] = await pool.execute(
    "SELECT id, password_hash FROM users WHERE email = ?", // 빈칸만 둔다
    [email]                                                // 값은 따로
  );
  const user = rows[0];
  if (!user || !(await bcrypt.compare(String(req.body.password ?? ""), user.password_hash))) {
    return res.status(401).json({ error: "이메일 또는 비밀번호가 맞지 않습니다" });
  }
  req.session.regenerate((err) => {              // 로그인 뒤 세션 ID 를 새로 발급한다
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
