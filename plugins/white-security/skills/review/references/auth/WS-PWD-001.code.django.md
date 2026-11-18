<!-- generated from white-security issues; do not edit -->
# WS-PWD-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
# settings.py — 테스트 속도용 설정이 운영까지 넘어옴
PASSWORD_HASHERS = ["django.contrib.auth.hashers.MD5PasswordHasher"]
AUTH_PASSWORD_VALIDATORS = []

# views.py
user = User.objects.create_user(username=username, password=pw)  # 규칙 확인 없이 저장
```

✓ 안전 코드 (safe example)

```python
# settings.py — 첫 번째 해셔로 저장, 나머지는 옛 해시 확인용(로그인 때 자동으로 새 방식으로 바뀜)
PASSWORD_HASHERS = [
    "django.contrib.auth.hashers.Argon2PasswordHasher",   # pip install "django[argon2]"
    "django.contrib.auth.hashers.PBKDF2PasswordHasher",
    # 옛 MD5 해시가 남아 있으면 이전 기간에만 맨 끝에 MD5PasswordHasher 를 두어 로그인 때 새 방식으로 바꾸고,
    # 로그인하지 않는 계정은 Django 문서의 감싼 해셔(wrapped hasher) 방식으로 한 번에 옮긴 뒤 MD5 를 뺀다
]
AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator"},
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator",
     "OPTIONS": {"min_length": 15}},                     # 2단계 인증과 함께 쓰면 8자까지 허용
    {"NAME": "django.contrib.auth.password_validation.CommonPasswordValidator"},  # 흔한 비밀번호 목록
]

# views.py
validate_password(pw, user=User(username=username))   # 위 규칙 적용(ValidationError)
user = User.objects.create_user(username=username, password=pw)  # set_password 로 해시
```
