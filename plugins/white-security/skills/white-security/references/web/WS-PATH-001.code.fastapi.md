<!-- generated from white-security issues; do not edit -->
# WS-PATH-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/files/{name:path}")
def get_file(name: str):
    return FileResponse(f"/srv/app/files/{name}")   # ../ 가 든 경로도 열린다
```

✓ 안전 코드 (safe example)

```python
from pathlib import Path

BASE = Path("/srv/app/files").resolve()

@app.get("/files/{name}")
def get_file(name: str):
    full = (BASE / name).resolve()               # 정규화(심볼릭 링크 포함)
    if not full.is_relative_to(BASE) or full == BASE or not full.is_file():
        raise HTTPException(404)                 # 기준 폴더 안의 파일만
    return FileResponse(full, filename=full.name)
```
