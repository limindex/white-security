---
id: WS-CFG-001
title: Debug Mode in Production (디버그 모드 켠 채 배포)
slug: debug-mode-production
group: deploy
severity: high
site_only: false
depth: brief
languages: [laravel, django, flask]
maps:
  owasp2025: [A02]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  asvs5: [V13.4.2, V16.5.1]
  cwe: [CWE-489, CWE-215, CWE-209]
  kr_guide: ["캡슐화-2", "에러처리-1"]
  kr_ciip: [EP, IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Django, Flask, FastAPI, 운영 서버에 올리는 모든 웹 앱]
severity_reason: "영향: 오류 화면에 환경 변수·설정값·소스 노출, Flask 디버거는 브라우저에서 코드 실행 · 악용: 오류를 일으키는 요청 하나로 가능"
sources:
  - title: "Laravel 12.x - Deployment (Debug Mode)"
    url: https://laravel.com/docs/12.x/deployment
    year: 2026
  - title: "Django 6.0 - Settings (DEBUG, ALLOWED_HOSTS)·Deployment checklist"
    url: https://docs.djangoproject.com/en/6.0/howto/deployment/checklist/
    year: 2026
  - title: "Flask - Debugging Application Errors (In Production)"
    url: https://flask.palletsprojects.com/en/stable/debugging/
    year: 2026
  - title: "OWASP ASVS 5.0 - V13.4 Unintended Information Leakage (13.4.2)"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "OWASP Top 10:2025 - A02 Security Misconfiguration"
    url: https://top10.owasp.org/2025/A02_2025-Security_Misconfiguration
    year: 2025
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

운영 서버에서 `APP_DEBUG=true`·`DEBUG = True`·`app.run(debug=True)` 같은 개발용 디버그 모드를 켜 둬, 오류 한 번에 스택 트레이스·환경 변수·설정값이 화면에 그대로 나오는 문제(진단가이드 이름: 제거되지 않고 남은 디버그 코드). Flask(Werkzeug) 디버거는 오류 화면에서 파이썬 코드를 실행하게 해 주므로 가장 위험.

## 대응 방법

```text
운영 배포에 쓰는 설정·.env·Dockerfile·배포 스크립트에서 디버그 모드(APP_DEBUG·DEBUG·FLASK_DEBUG·debug=True)가 켜질 수 있는 곳을 모두 찾아 줘.
디버그 값은 환경 변수에서 읽고 값이 없으면 꺼지게 바꾸고, 운영 .env 는 APP_ENV=production·APP_DEBUG=false 로 맞춰 줘.
Flask·Django 개발 서버(flask run --debug, manage.py runserver)로 운영을 띄우는 곳은 gunicorn 같은 운영 서버로 바꿔 줘.
```

- 운영 주소에서 없는 경로를 열거나 오류를 일으켜도 스택 트레이스·설정값 없이 짧은 오류 화면만 나와야 한다.
- Django는 `python manage.py check --deploy`에 DEBUG 경고가 없어야 한다.

## 점검 항목

- 디버그 모드는 환경 변수로만 켜고, 값이 없으면 꺼진 상태여야 한다.
- 운영 `.env`는 `APP_ENV=production`·`APP_DEBUG=false`로 두고, `.env.example`을 그대로 복사해 쓰지 않아야 한다.
- Flask·Werkzeug 디버거와 개발 서버는 운영에서 쓰지 않아야 한다.
- Django는 `DEBUG = False`와 함께 `ALLOWED_HOSTS`에 실제 도메인만 적어야 한다.
- 운영 설치는 개발용 패키지 없이(`composer install --no-dev` 등) 해야 한다.

## 탐지 규칙

- **찾는 곳**: `APP_DEBUG=true`, `DEBUG = True`, `debug=True`, `FLASK_DEBUG`, `manage.py runserver`, `flask run`, 그 밖에 운영용 .env·settings 파일, Dockerfile CMD·배포 스크립트의 실행 명령, ALLOWED_HOSTS, uvicorn --reload.
- **검사 1 — 운영에서 켜지는 디버그**: 지적할 때: 운영에 쓰이는 설정 파일·`.env.production`·배포용 Docker·CI 설정에서 디버그가 켜진 경우. 환경 변수가 없을 때 디버그가 켜지는 기본값(`os.getenv("DEBUG", "True")`, `env('APP_DEBUG', true)`)인 경우. / 지적하지 않을 때: `.env.example`·로컬 전용 설정(`settings/local.py`)·테스트 설정에만 켜져 있고 운영 설정은 꺼짐이 기본인 경우.
- **검사 2 — 개발 서버로 운영**: 지적할 때: Dockerfile `CMD`나 배포 스크립트가 `flask run --debug`, `python app.py`(안에 `app.run(debug=True)`), `manage.py runserver`, `uvicorn --reload`로 서비스를 띄우는 경우. / 지적하지 않을 때: `app.run(debug=True)`가 `if __name__ == "__main__":` 아래 로컬 실행 전용이고 운영은 gunicorn·uvicorn(리로드 없음)으로 띄우는 경우(참고로만 표시).
- **등급**: 위험: 운영 설정에서 디버그가 켜진 경우, Werkzeug 디버거가 운영에서 도는 경우. / 주의: 값이 없으면 켜지는 기본값, 운영 실행 명령이 개발 서버인 경우. / 참고: `ALLOWED_HOSTS = ["*"]`, 운영 설치에 개발용 패키지 포함.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CFG-001 Dockerfile:14 — CMD ["python", "app.py"] 가 app.run(debug=True) 를 실행해 운영 오류 화면에서 코드 실행 가능 → gunicorn 으로 실행하고 debug 인자 삭제`
- **검토 주의**: 코드가 오류 내용을 직접 응답에 넣는 문제는 WS-ERR-001, 남은 디버그 라우트·`dd()`·`phpinfo()`는 WS-ENC-001에서 다루므로 여기서는 설정과 실행 방식만 봐야 한다.
- **고칠 방법**: 디버그 값은 환경 변수에서 읽고 기본값을 꺼짐으로 바꿔야 하며, 운영 환경 변수를 고친 뒤 설정 캐시(`php artisan config:cache`)와 서비스를 다시 올려야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// config/app.php — 값이 없으면 켜지게 기본값을 바꿔 둠
'debug' => (bool) env('APP_DEBUG', true),

// 운영 서버 .env — 개발용 .env.example 을 그대로 복사해 씀
// APP_ENV=local
// APP_DEBUG=true
```

✓ 안전 코드

```php
// config/app.php — 프레임워크 기본값 그대로(값이 없으면 꺼짐)
'debug' => (bool) env('APP_DEBUG', false),

// 운영 서버 .env
// APP_ENV=production
// APP_DEBUG=false

// 배포 명령(.env 를 바꾼 뒤에는 설정 캐시를 다시 만든다)
// composer install --no-dev --optimize-autoloader
// php artisan config:cache
```

### django

✗ 취약 코드

```python
# settings.py — startproject 기본값 그대로 운영에 올림
DEBUG = True
ALLOWED_HOSTS = ["*"]

# Dockerfile: CMD ["python", "manage.py", "runserver", "0.0.0.0:8000"]
```

✓ 안전 코드

```python
# settings.py
import os

DEBUG = os.environ.get("DJANGO_DEBUG") == "1"        # 값이 없으면 꺼짐
ALLOWED_HOSTS = [h for h in os.environ.get("DJANGO_ALLOWED_HOSTS", "").split(",") if h]
# 예: DJANGO_ALLOWED_HOSTS=example.com,www.example.com

# 배포 전 점검: python manage.py check --deploy
# Dockerfile: CMD ["gunicorn", "mysite.wsgi", "-b", "0.0.0.0:8000"]  (runserver 는 개발 전용)
```

### flask

✗ 취약 코드

```python
# app.py — Dockerfile 의 CMD ["python", "app.py"] 로 운영에서도 이 줄이 실행됨
from flask import Flask

app = Flask(__name__)

if __name__ == "__main__":
    app.run(host="0.0.0.0", debug=True)   # 오류 화면에서 파이썬 코드 실행 가능
```

✓ 안전 코드

```python
# app.py — 디버그는 로컬에서 flask --app app run --debug 로만 켠다
from flask import Flask

app = Flask(__name__)

if __name__ == "__main__":
    app.run(host="127.0.0.1")              # 로컬 실행, debug 없음

# Dockerfile: CMD ["gunicorn", "-b", "0.0.0.0:8000", "app:app"]
# 운영 환경 변수에 FLASK_DEBUG 를 두지 않는다
```
