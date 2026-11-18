<!-- generated from white-security issues; do not edit -->
# WS-CFG-006 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
# accounts/migrations/0002_create_admin.py — 모든 환경에서 고정 비밀번호 관리자 생성
from django.contrib.auth.hashers import make_password
from django.db import migrations

def create_admin(apps, schema_editor):
    User = apps.get_model("auth", "User")
    User.objects.create(username="admin", is_staff=True, is_superuser=True,
                        password=make_password("admin"))

class Migration(migrations.Migration):
    dependencies = [("accounts", "0001_initial")]
    operations = [migrations.RunPython(create_admin)]
```

✓ 안전 코드 (safe example)

```python
# 마이그레이션에서는 관리자를 만들지 않는다.
# 배포 뒤 서버에서 한 번, 대화형으로: python manage.py createsuperuser
# (--noinput + DJANGO_SUPERUSER_PASSWORD 방식은 아래 검사기를 거치지 않으므로 쓰지 않는다)

# settings.py — 짧거나 흔한 비밀번호를 막는 검사기(createsuperuser 대화형 입력에도 적용)
AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator",
     "OPTIONS": {"min_length": 15}},
    {"NAME": "django.contrib.auth.password_validation.CommonPasswordValidator"},
    {"NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator"},
]
```
