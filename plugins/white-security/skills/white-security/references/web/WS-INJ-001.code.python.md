<!-- generated from white-security issues; do not edit -->
# WS-INJ-001 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
def search_products(conn, q: str, sort: str):
    sql = f"SELECT id, name, price FROM products WHERE name LIKE '%{q}%' ORDER BY {sort}"
    with conn.cursor() as cur:
        cur.execute(sql)              # 검색어와 정렬 값이 명령문 글자가 된다
        return cur.fetchall()
```

✓ 안전 코드 (safe example)

```python
SORT_COLUMNS = {"name": "name", "price": "price", "new": "created_at DESC"}

def search_products(conn, q: str, sort: str):
    order_by = SORT_COLUMNS.get(sort, "name")   # 식별자는 허용 목록에서 고른다
    sql = f"SELECT id, name, price FROM products WHERE name LIKE %s ORDER BY {order_by}"
    with conn.cursor() as cur:                   # psycopg / PyMySQL
        cur.execute(sql, (f"%{q}%",))            # 값은 바인딩으로 따로 보낸다
        return cur.fetchall()
```
