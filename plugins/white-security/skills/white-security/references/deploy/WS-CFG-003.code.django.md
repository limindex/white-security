<!-- generated from white-security issues; do not edit -->
# WS-CFG-003 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
# settings.py — SecurityMiddleware 를 빼고, HSTS·CSP 설정 없음
MIDDLEWARE = [
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
]
```

✓ 안전 코드 (safe example)

```python
# settings.py (Django 6.0 이상)
from django.utils.csp import CSP

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",          # HSTS·nosniff·Referrer-Policy·COOP
    "django.middleware.csp.ContentSecurityPolicyMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware", # X-Frame-Options: DENY(기본값)
]
SECURE_HSTS_SECONDS = 31536000        # 기본값 0(꺼짐). HTTPS 가 되는 것을 확인한 뒤 켠다
SECURE_HSTS_INCLUDE_SUBDOMAINS = True
SECURE_SSL_REDIRECT = True            # 프록시 뒤라면 SECURE_PROXY_SSL_HEADER 도 맞춘다
SECURE_CSP = {
    "default-src": [CSP.SELF],
    "object-src": [CSP.NONE],
    "base-uri": [CSP.SELF],
    "frame-ancestors": [CSP.NONE],
}
# nosniff(True)·Referrer-Policy(same-origin)는 기본값이 이미 켜져 있다
```
