<!-- generated from white-security issues; do not edit -->
# WS-LOG-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
# views.py
def login_view(request):
    user = authenticate(request, username=request.POST["username"], password=request.POST["password"])
    logger.info("login attempt %s", request.POST)          # 비밀번호까지 기록
    if user is None:
        return render(request, "login.html", {"error": True})   # 실패는 기록 없음
    login(request, user)
    return redirect("/")
```

✓ 안전 코드 (safe example)

```python
# accounts/signals.py — apps.py 의 ready() 에서 import
import logging
from django.contrib.auth.signals import user_logged_in, user_login_failed
from django.dispatch import receiver

security = logging.getLogger("security")

def client_ip(request):
    return request.META.get("REMOTE_ADDR") if request else None   # 프록시 뒤면 신뢰하는 프록시가 넣은 값만

@receiver(user_login_failed)
def on_failed(sender, credentials, request=None, **kwargs):
    # credentials 는 비밀번호가 가려져 오지만, 아이디만 골라 남긴다
    # 기본 포맷터는 extra 를 찍지 않으므로 메시지 인자로 남긴다. %r 은 줄바꿈을 이스케이프(로그 위조 방지)
    security.warning("auth.failed username=%r ip=%s", credentials.get("username"), client_ip(request))

@receiver(user_logged_in)
def on_login(sender, request, user, **kwargs):
    security.info("auth.login user_id=%s ip=%s", user.pk, client_ip(request))

# settings.py LOGGING 의 "security" 로거는 별도 수집 서버로 보내고 1년 이상 보관, 실패 급증에 알림
```
