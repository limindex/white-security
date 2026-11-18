<!-- generated from white-security issues; do not edit -->
# WS-FILE-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
from PIL import Image

Image.MAX_IMAGE_PIXELS = None  # 픽셀 폭탄 보호를 끈다

def make_thumbnail(src: str, dst: str) -> None:
    im = Image.open(src)
    im.thumbnail((1024, 1024))
    im.save(dst, exif=im.info.get("exif", b""))  # 위치 정보까지 그대로 공개된다
```

✓ 안전 코드 (safe example)

```python
import os
from PIL import Image, ImageOps

Image.MAX_IMAGE_PIXELS = 25_000_000  # 이 값의 2배를 넘으면 열 때 DecompressionBombError
ALLOWED = ["JPEG", "PNG", "WEBP"]

def make_thumbnail(src: str, dst: str) -> None:
    if os.path.getsize(src) > 10 * 1024 * 1024:
        raise ValueError("10MB 넘는 파일은 받지 않습니다")
    with Image.open(src, formats=ALLOWED) as im:  # 받기로 한 형식의 해석기만 쓴다(머리 부분만 읽은 상태)
        if im.width * im.height > Image.MAX_IMAGE_PIXELS:
            raise ValueError("받지 않는 이미지입니다")
        im = ImageOps.exif_transpose(im)  # 회전 정보만 반영한다
        im.thumbnail((1024, 1024))
        im.convert("RGB").save(dst, "JPEG", quality=85)  # exif 를 넘기지 않으니 위치 정보가 빠진다
```
