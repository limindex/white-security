<!-- generated from white-security issues; do not edit -->
# WS-UPL-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.post("/upload")
async def upload(file: UploadFile):
    with open(f"static/uploads/{file.filename}", "wb") as f:   # 이름 그대로, 형식·크기 검사 없음
        f.write(await file.read())
    return {"url": f"/static/uploads/{file.filename}"}
```

✓ 안전 코드 (safe example)

```python
import io, uuid
from pathlib import Path
from PIL import Image

UPLOADS = Path("/srv/app/uploads")            # 정적 폴더 밖
MAX = 5 * 1024 * 1024                         # 본문 크기 상한은 프록시에도 둔다
FORMATS = {"JPEG": "jpg", "PNG": "png", "WEBP": "webp"}

@app.post("/upload")
async def upload(file: UploadFile):
    data = await file.read(MAX + 1)
    if len(data) > MAX:
        raise HTTPException(413, "5MB 이하만 받습니다")
    try:
        with Image.open(io.BytesIO(data)) as im:
            ext = FORMATS.get(im.format)        # 내용으로 형식 판단
            if not ext:
                raise HTTPException(415, "허용하지 않는 형식")
            name = f"{uuid.uuid4().hex}.{ext}"
            im.save(UPLOADS / name, format=im.format)   # 다시 인코딩해 숨은 데이터 제거
    except (OSError, Image.DecompressionBombError):
        raise HTTPException(415, "이미지가 아닙니다")
    return {"id": name}
```
