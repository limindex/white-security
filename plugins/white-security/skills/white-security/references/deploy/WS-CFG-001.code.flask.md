<!-- generated from white-security issues; do not edit -->
# WS-CFG-001 코드 예시 — Flask

✗ 취약 코드 (vulnerable example — do not use)

```python
# app.py — Dockerfile 의 CMD ["python", "app.py"] 로 운영에서도 이 줄이 실행됨
from flask import Flask

app = Flask(__name__)

if __name__ == "__main__":
    app.run(host="0.0.0.0", debug=True)   # 오류 화면에서 파이썬 코드 실행 가능
```

✓ 안전 코드 (safe example)

```python
# app.py — 디버그는 로컬에서 flask --app app run --debug 로만 켠다
from flask import Flask

app = Flask(__name__)

if __name__ == "__main__":
    app.run(host="127.0.0.1")              # 로컬 실행, debug 없음

# Dockerfile: CMD ["gunicorn", "-b", "0.0.0.0:8000", "app:app"]
# 운영 환경 변수에 FLASK_DEBUG 를 두지 않는다
```
