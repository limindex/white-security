<!-- generated from white-security issues; do not edit -->
# WS-INJ-002 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.post("/thumb")
def thumb(name: str):
    # name = "a.png; echo banana" 이면 뒤 명령도 실행된다
    subprocess.run(f"convert uploads/{name} -resize 200x200 thumbs/{name}", shell=True, check=True)
    return {"ok": True}
```

✓ 안전 코드 (safe example)

```python
import re
from pathlib import Path
from PIL import Image

UPLOADS, THUMBS = Path("/srv/app/uploads"), Path("/srv/app/thumbs")
NAME = re.compile(r"[0-9a-f]{32}\.(png|jpg)")          # 서버가 저장할 때 만든 이름 형식만

@app.post("/thumb")
def thumb(name: str):
    if not NAME.fullmatch(name):
        raise HTTPException(400, "잘못된 파일 이름")
    with Image.open(UPLOADS / name) as im:              # 외부 명령 대신 라이브러리
        im.thumbnail((200, 200))
        im.save(THUMBS / name)
    # 외부 프로그램이 꼭 필요하면: subprocess.run(["prog", str(UPLOADS / name)], check=True, timeout=30)
    return {"ok": True}
```
