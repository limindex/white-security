<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-INJ-001 SQL 인젝션 (SQLi)

위험 · OWASP A05 · ASVS V1.2 · CWE-89 · 진단가이드 입력데이터 검증 및 표현-1 · 기반시설 SI

## 점검 항목

- 쿼리에 들어가는 값은 모두 바인딩(`?`, `%s`, `:name`, `$1`)으로 넘겨야 한다. 문자열 연결·f-string·템플릿 리터럴·`sprintf`로 값을 넣지 않아야 하고, `IN (...)` 목록은 값 개수만큼 자리표시자를 만들며, `LIKE` 검색도 값은 바인딩하고 필요하면 `%`·`_`를 이스케이프해야 한다.
- 이스케이프 함수(`addslashes`, 직접 만든 `sql_escape`, `mysqli_real_escape_string`)를 주된 방어로 쓰지 않아야 한다. 참고로 "EUC-KR·CP949에서는 `addslashes`가 멀티바이트 문자로 우회된다"는 설명은 한국어 문자셋에 해당하지 않음. 이 우회는 앞 바이트와 `\`(0x5C)가 한 글자로 묶이는 GBK·Big5·Shift_JIS·CP932에서 생기고, EUC-KR·CP949에는 0x5C를 둘째 바이트로 쓰는 글자가 없음(Python 표준 코덱으로 0x81~0xFE 뒤에 0x5C를 붙여 확인). 그래도 이어 붙이는 쿼리는 인코딩과 상관없이 SQL 인젝션 항목.
- 테이블·컬럼 이름, `ORDER BY` 방향처럼 바인딩할 수 없는 자리는 코드에 정해 둔 허용 목록에서만 골라야 한다.
- ORM의 날 SQL 기능(SQLAlchemy `text()`, Django `raw()`·`extra()`, `knex.raw`, `sequelize.query`, Laravel `DB::raw`·`whereRaw`)에도 같은 규칙을 적용해야 한다. Java MyBatis라면 `${}` 대신 `#{}`를 써야 한다.
- DB 연결 문자셋을 DSN에 적어야 하고(`charset=utf8mb4`), PDO는 `PDO::ATTR_EMULATE_PREPARES => false`로 서버 쪽 준비문을 써야 한다.
- 앱이 쓰는 DB 계정은 필요한 권한만 가져야 하고, DB 오류 원문을 화면에 보여 주지 않아야 한다.

## 탐지 규칙

**찾는 곳**: 쿼리 실행부와 그 인자(`execute(`, `executemany(`, `cursor.`, `query(`, `raw(`, `text(`, `->query(`, `->exec(`, `->prepare(`, `mysqli_query(`, `pg_query(`, `pool.query(`, `sequelize.query(`, `knex.raw(`, `whereRaw(`).

**검사 1. 값 이어 붙이기**
- 지적할 때: 요청·파일·외부 API·LLM 출력에서 온 값이 `+`, `.`, f-string, 템플릿 리터럴, `%` 포맷, `format()`, `sprintf`로 쿼리 문자열에 합쳐지는 경우(함수 몇 단계를 거쳐 와도 출처가 외부면 같음).
- 지적하지 않을 때: 합치는 값이 코드 상수나 허용 목록에서 고른 식별자이거나, 자리표시자 문자열만 조립하는 경우(`", ".join(["%s"] * len(ids))`).

**검사 2. 식별자 자리**
- 지적할 때: `ORDER BY`, 테이블 이름, 컬럼 이름에 요청 값이 그대로 들어가는 경우.
- 지적하지 않을 때: `SORT_COLUMNS[sort]`처럼 허용 목록 매핑을 거치고, 목록에 없으면 기본값을 쓰는 경우.

**검사 3. 이스케이프에 기댄 코드**
- 지적할 때: `addslashes`나 자체 이스케이프 뒤에 문자열을 이어 붙이는 경우(SQL 인젝션으로 보고 바인딩 권고).
- 지적하지 않을 때: "EUC-KR 멀티바이트 우회"는 이유로 들지 않음. 연결 문자셋이 GBK·Big5·Shift_JIS·CP932일 때만 그 우회를 근거로 함께 적음.

**등급**
- 위험: 외부 값이 쿼리 문자열에 그대로 들어가는 경우.
- 주의: 값의 출처를 끝까지 따라가지 못한 경우("확인 필요" 표시).
- 참고: 정수 변환(`int()`·`(int)`) 뒤 연결, DSN 문자셋 미지정, DB 오류 원문 노출.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-INJ-001 app/search.py:18 — 검색어를 f-string으로 SQL에 넣음 → cur.execute("... LIKE %s", (f"%{q}%",)) 로 바인딩`

**고칠 방법**: 쿼리 모양은 그대로 두고 값만 자리표시자로 바꿔야 하고, 기존 테스트로 결과가 같은지 확인한 뒤 따옴표가 든 입력 테스트를 하나 추가해야 한다.

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
