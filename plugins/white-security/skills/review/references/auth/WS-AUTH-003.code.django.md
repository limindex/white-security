<!-- generated from white-security issues; do not edit -->
# WS-AUTH-003 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
def login_view(request):
    user = authenticate(username=request.POST["username"],   # request 를 넘기지 않아 제한 도구도 못 본다
                        password=request.POST["password"])
    if user is None:
        return render(request, "login.html", {"error": "로그인 실패"})
    login(request, user)
    return redirect("home")
# 시도 제한 설정 없음
```

✓ 안전 코드 (safe example)

```python
# settings.py — django-axes: 실패 횟수를 DB·캐시에 남기고 넘으면 잠시 막는다
from datetime import timedelta
INSTALLED_APPS += ["axes"]
MIDDLEWARE += ["axes.middleware.AxesMiddleware"]          # 목록 맨 끝
AUTHENTICATION_BACKENDS = ["axes.backends.AxesStandaloneBackend",
                           "django.contrib.auth.backends.ModelBackend"]
AXES_FAILURE_LIMIT = 10
AXES_COOLOFF_TIME = timedelta(minutes=15)                 # 영구 잠금 대신 15분 뒤 자동 해제
AXES_LOCKOUT_PARAMETERS = ["username"]                    # 계정 단위: IP를 바꿔 가며 시도해도 센다
# (한도가 하나라 계정·IP 조합을 더해도 결과가 같음. 15분 뒤 풀려 남이 잠가도 피해가 짧다)
AXES_IPWARE_PROXY_COUNT = 1                               # 프록시 한 대 뒤라면 실제 IP 위치를 맞춘다

# views.py — authenticate 에 request 를 넘겨야 axes 가 센다
user = authenticate(request, username=request.POST["username"], password=request.POST["password"])
```
