<!-- generated from white-security issues; do not edit -->
# WS-AUTH-004 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
# urls.py — 누구나 아는 주소, 아이디·비밀번호만으로 로그인
urlpatterns = [
    path("admin/", admin.site.urls),
]

# settings.py
SESSION_COOKIE_AGE = 60 * 60 * 24 * 14   # 관리자 세션도 2주 유지
```

✓ 안전 코드 (safe example)

```python
# settings.py — 관리자 전용 배포(사내 관리 서버) 기준
INSTALLED_APPS += ["django_otp", "django_otp.plugins.otp_totp"]
MIDDLEWARE += ["django_otp.middleware.OTPMiddleware"]   # AuthenticationMiddleware 뒤에
SESSION_COOKIE_AGE = 60 * 30                            # 30분
SESSION_SAVE_EVERY_REQUEST = True                       # 요청마다 연장 → 30분 비활동 시 만료

# urls.py
from django_otp.admin import OTPAdminSite
admin.site.__class__ = OTPAdminSite                     # 관리자 로그인에 OTP(2단계 인증) 요구
urlpatterns = [path(settings.ADMIN_PATH, admin.site.urls)]  # 주소 바꾸기는 보조 수단
# 접속 자체는 웹 서버·방화벽에서 사무실·VPN IP 로 제한
```
