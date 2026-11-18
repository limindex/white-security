<!-- generated from white-security issues; do not edit -->
# WS-SSRF-001 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/preview")
def preview():
    return requests.get(request.args["url"], timeout=10).text  # 내부 주소도 대신 열어 준다
```

✓ 안전 코드 (safe example)

```python
import http.client
import ipaddress
import socket
import ssl
from urllib.parse import urlsplit

def safe_fetch(url: str, limit: int = 2_000_000) -> bytes:
    u = urlsplit(url)
    if u.scheme != "https" or not u.hostname or u.port not in (None, 443) or u.username:
        raise ValueError("https 기본 포트 주소만 받습니다")
    ip = ipaddress.IPv4Address(socket.getaddrinfo(u.hostname, 443, socket.AF_INET, socket.SOCK_STREAM)[0][4][0])
    if not ip.is_global or ip.is_reserved or ip.is_multicast:  # IPv4 만 받는다
        raise ValueError("내부 주소로는 요청하지 않습니다")  # 127.*, 10.*, 169.254.169.254 등
    sock = socket.create_connection((str(ip), 443), timeout=5)  # 검사한 IP 로만 접속한다
    conn = http.client.HTTPSConnection(u.hostname, 443, timeout=5)
    conn.sock = ssl.create_default_context().wrap_socket(sock, server_hostname=u.hostname)
    conn.request("GET", (u.path or "/") + (f"?{u.query}" if u.query else ""))
    return conn.getresponse().read(limit)  # 리다이렉트는 따라가지 않는다
```
