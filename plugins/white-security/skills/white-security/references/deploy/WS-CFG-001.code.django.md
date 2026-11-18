<!-- generated from white-security issues; do not edit -->
# WS-CFG-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
# settings.py — startproject 기본값 그대로 운영에 올림
DEBUG = True
ALLOWED_HOSTS = ["*"]

# Dockerfile: CMD ["python", "manage.py", "runserver", "0.0.0.0:8000"]
```

✓ 안전 코드 (safe example)

```python
# settings.py
import os

DEBUG = os.environ.get("DJANGO_DEBUG") == "1"        # 값이 없으면 꺼짐
ALLOWED_HOSTS = [h for h in os.environ.get("DJANGO_ALLOWED_HOSTS", "").split(",") if h]
# 예: DJANGO_ALLOWED_HOSTS=example.com,www.example.com

# 배포 전 점검: python manage.py check --deploy
# Dockerfile: CMD ["gunicorn", "mysite.wsgi", "-b", "0.0.0.0:8000"]  (runserver 는 개발 전용)
```
