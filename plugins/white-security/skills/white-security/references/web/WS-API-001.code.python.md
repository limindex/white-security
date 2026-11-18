<!-- generated from white-security issues; do not edit -->
# WS-API-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
import tempfile

def export_csv(rows):
    path = tempfile.mktemp(suffix=".csv")   # 이름만 정하고 파일은 나중에: 그 사이 다른 프로세스가 선점
    with open(path, "w") as f:
        f.writelines(rows)
    return path

def check_token(given: str, expected: str) -> bool:
    return given == expected                # 비교 시간이 값에 따라 달라진다
```

✓ 안전 코드 (safe example)

```python
import hmac
import tempfile

def export_csv(rows):
    with tempfile.NamedTemporaryFile("w", suffix=".csv", delete=False) as f:  # 만들면서 바로 연다(권한 0600)
        f.writelines(rows)
        return f.name

def check_token(given: str, expected: str) -> bool:
    return hmac.compare_digest(given.encode(), expected.encode())           # 일정 시간 비교
```
