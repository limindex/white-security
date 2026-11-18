<!-- generated from white-security issues; do not edit -->
# WS-CFG-001 Debug Mode in Production (디버그 모드 켠 채 배포)

심각도: 위험
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP API 2023 API8(보안 설정 오류) · ASVS 5.0 V13.4.2 · ASVS 5.0 V16.5.1 · CWE-489 · CWE-215 · CWE-209 · 국내 보안약점 49 캡슐화-2(제거되지 않고 남은 디버그 코드) · 국내 보안약점 49 에러처리-1(오류 메시지 정보노출) · 기반시설 웹 21 EP(에러 페이지 미흡) · 기반시설 웹 21 IL(정보 누출)

## 점검 항목

- 디버그 모드는 환경 변수로만 켜고, 값이 없으면 꺼진 상태여야 한다.
- 운영 `.env`는 `APP_DEBUG=false`로 두고, `.env.example`을 그대로 복사하지 않아야 한다.
- Flask·Werkzeug 디버거와 개발 서버는 운영에서 쓰지 않아야 한다.
- Django는 `DEBUG = False`와 함께 `ALLOWED_HOSTS`에 실제 도메인만 적어야 한다.
- 운영 설치는 개발용 패키지 없이(`composer install --no-dev` 등) 해야 한다.

## 탐지 규칙

- **찾는 곳**: `APP_DEBUG=true`, `DEBUG = True`, `debug=True`, `FLASK_DEBUG`, `manage.py runserver`, `flask run`, 그 밖에 운영용 .env·settings 파일, Dockerfile CMD·배포 스크립트의 실행 명령, ALLOWED_HOSTS, uvicorn --reload.
- **검사 1 — 운영에서 켜지는 디버그**: 지적할 때: 운영에 쓰이는 설정 파일·`.env.production`·배포용 Docker·CI 설정에서 디버그가 켜진 경우. 환경 변수가 없을 때 디버그가 켜지는 기본값(`os.getenv("DEBUG", "True")`, `env('APP_DEBUG', true)`)인 경우. / 지적하지 않을 때: `.env.example`·로컬 전용 설정(`settings/local.py`)·테스트 설정에만 켜져 있고 운영 설정은 꺼짐이 기본인 경우.
- **검사 2 — 개발 서버로 운영**: 지적할 때: Dockerfile `CMD`나 배포 스크립트가 `flask run --debug`, `python app.py`(안에 `app.run(debug=True)`), `manage.py runserver`, `uvicorn --reload`로 서비스를 띄우는 경우. / 지적하지 않을 때: `app.run(debug=True)`가 `if __name__ == "__main__":` 아래 로컬 실행 전용이고 운영은 gunicorn·uvicorn(리로드 없음)으로 띄우는 경우(참고로만 표시).
- **등급**: 위험: 운영 설정에서 디버그가 켜진 경우, Werkzeug 디버거가 운영에서 도는 경우. / 주의: 값이 없으면 켜지는 기본값, 운영 실행 명령이 개발 서버인 경우. / 참고: `ALLOWED_HOSTS = ["*"]`, 운영 설치에 개발용 패키지 포함.
- **보고 예**: `[위험] WS-CFG-001 Dockerfile:14 — CMD ["python", "app.py"] 가 app.run(debug=True) 를 실행해 운영 오류 화면에서 코드 실행 가능 → gunicorn 으로 실행하고 debug 인자 삭제`
- **검토 주의**: 코드가 오류 내용을 직접 응답에 넣는 문제는 WS-ERR-001, 남은 디버그 라우트·`dd()`·`phpinfo()`는 WS-ENC-001에서 다루므로 여기서는 설정과 실행 방식만 봐야 한다.
- **고칠 방법**: 디버그 값은 환경 변수에서 읽고 기본값을 꺼짐으로 바꿔야 하며, 운영 환경 변수를 고친 뒤 설정 캐시(`php artisan config:cache`)와 서비스를 다시 올려야 한다.

Code examples (read only the file for the stack you are writing): `WS-CFG-001.code.laravel.md` (Laravel) · `WS-CFG-001.code.django.md` (Django) · `WS-CFG-001.code.flask.md` (Flask)
