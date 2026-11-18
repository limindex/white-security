<!-- generated from white-security issues; do not edit -->
# WS-CSRF-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
from django.views.decorators.csrf import csrf_exempt

@csrf_exempt                      # 폼 오류가 나서 보호를 끔
@login_required
def change_email(request):
    request.user.email = request.POST["email"]
    request.user.save()
    return redirect("/account")
```

✓ 안전 코드 (safe example)

```python
from django.views.decorators.http import require_POST

@require_POST                     # 상태 변경은 POST 로만, CSRF 미들웨어가 토큰 검사
@login_required
def change_email(request):
    form = EmailForm(request.POST)
    if not form.is_valid():
        return render(request, "account.html", {"form": form}, status=400)
    request.user.email = form.cleaned_data["email"]
    request.user.save(update_fields=["email"])
    return redirect("/account")
# 템플릿 폼에는 {% csrf_token %}, fetch 는 X-CSRFToken 헤더
```
