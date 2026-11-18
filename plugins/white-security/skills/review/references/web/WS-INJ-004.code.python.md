<!-- generated from white-security issues; do not edit -->
# WS-INJ-004 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
from ldap3 import Connection

def find_user(conn: Connection, username: str):
    # * ( ) 가 섞이면 필터 조건이 바뀐다
    conn.search("ou=people,dc=example,dc=com", f"(uid={username})", attributes=["cn", "mail"])
    return conn.entries
```

✓ 안전 코드 (safe example)

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
