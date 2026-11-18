<!-- generated from white-security issues; do not edit -->
# WS-RED-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
def login_view(request):
    user = authenticate(request, username=request.POST["username"], password=request.POST["password"])
    if user is None:
        return render(request, "login.html", status=401)
    login(request, user)
    return redirect(request.GET.get("next", "/"))    # 외부 주소도 따라간다
```

✓ 안전 코드 (safe example)

```python
from django.utils.http import url_has_allowed_host_and_scheme

def login_view(request):
    user = authenticate(request, username=request.POST["username"], password=request.POST["password"])
    if user is None:
        return render(request, "login.html", status=401)
    login(request, user)
    nxt = request.POST.get("next") or request.GET.get("next", "")
    if not url_has_allowed_host_and_scheme(nxt, allowed_hosts={request.get_host()}, require_https=request.is_secure()):
        nxt = "/"                                      # 허용 호스트가 아니면 첫 화면
    return redirect(nxt)
```
