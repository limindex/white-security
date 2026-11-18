<!-- generated from white-security issues; do not edit -->
# WS-SUP-001 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
import subprocess

def add_dependencies(suggested: list[str]) -> None:
    # AI가 제안한 이름을 확인 없이 설치한다 (설치 중 패키지 코드가 실행될 수 있다)
    for name in suggested:
        subprocess.run(["pip", "install", name], check=True)
```

✓ 안전 코드 (safe example)

```python
import json, re, urllib.error, urllib.request
from datetime import datetime, timedelta, timezone

def check_pypi(name: str, min_age_days: int = 90) -> str:
    """'있음'이 아니면 설치하지 말고 사람에게 묻는다."""
    if not re.fullmatch(r"[A-Za-z0-9][A-Za-z0-9._-]{0,99}", name):
        return "이름 형식이 아님"
    try:
        with urllib.request.urlopen(f"https://pypi.org/pypi/{name}/json", timeout=10) as r:
            info = json.load(r)
    except urllib.error.HTTPError as e:
        return "없음" if e.code == 404 else f"확인 못 함(HTTP {e.code})"
    except OSError:
        return "확인 못 함(네트워크)"
    uploads = [f["upload_time_iso_8601"] for files in info["releases"].values() for f in files]
    if not uploads:
        return "올린 파일 없음"
    first = min(datetime.fromisoformat(t.replace("Z", "+00:00")) for t in uploads)
    if datetime.now(timezone.utc) - first < timedelta(days=min_age_days):
        return f"새 패키지({first.date()} 처음 등록)"
    return "있음"

# '있음'이고 사람이 이름·버전을 확인한 뒤에만 requirements.txt 에 버전과 해시를 고정한다.
# 설치: pip install --require-hashes -r requirements.txt
```
