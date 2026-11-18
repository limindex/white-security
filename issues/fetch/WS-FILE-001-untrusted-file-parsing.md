---
id: WS-FILE-001
title: Decompression Bomb · 파서 취약점 (남이 준 이미지·PDF·오피스 파일 처리)
slug: untrusted-file-parsing
group: fetch
severity: medium
site_only: false
depth: brief
languages: [python, php]
maps:
  owasp2025: []
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V5.2.1, V5.2.6, V14.2.8]
  cwe: [CWE-400, CWE-770, CWE-212]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, PHP, 업로드 파일을 처리하는 서비스]
severity_reason: "영향: 메모리 고갈로 인한 장애·위치 정보 노출 · 악용: 파일 하나로 가능하나 대부분 서비스 중단에 그침"
sources:
  - title: OWASP File Upload Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
    year: 2026
  - title: "Pillow Docs - Image.open (DecompressionBombWarning / MAX_IMAGE_PIXELS)"
    url: https://pillow.readthedocs.io/en/stable/reference/Image.html
    year: 2026
  - title: "ImageMagick - Security Policy"
    url: https://imagemagick.org/security-policy/
    year: 2026
  - title: "NVD CVE-2016-3714 (ImageMagick, ImageTragick)"
    url: https://nvd.nist.gov/vuln/detail/CVE-2016-3714
    year: 2016
---

## 요약

남이 올린 이미지·PDF·오피스 파일로 썸네일·미리보기·텍스트 추출을 할 때, 작은 파일 하나가 메모리를 다 쓰게 하거나(Decompression Bomb, 픽셀 폭탄) 파서 프로그램의 취약점을 건드리는 문제. 사진 속 위치 정보(EXIF)를 지우지 않고 다시 공개하는 사고도 같은 범위.

## 위험 배경

- 2016년 ImageTragick(CVE-2016-3714), 조작한 이미지 하나로 서버 명령을 실행한 사례.

## 대응 방법

```text
업로드된 이미지·PDF·오피스 파일을 여는 코드(썸네일, 미리보기, 텍스트 추출)를 모두 찾아 줘.
열기 전에 파일 크기와 이미지 픽셀 수에 상한을 넣고, 공개용 이미지는 다시 저장해 EXIF·위치 정보를 지워 줘.
ImageMagick 이나 문서 변환기를 쓰면 시간 제한이 있는 별도 프로세스로 돌리고 쓰지 않는 형식은 막아 줘.
```

- 휴대폰으로 찍은 사진을 올린 뒤 공개된 이미지를 EXIF 보기 도구로 열었을 때, 위치 정보가 없어야 한다.

## 점검 항목

- 열기 전에 파일 크기, 이미지 가로×세로 픽셀, 문서 쪽수에 상한을 두고, Pillow `Image.MAX_IMAGE_PIXELS`를 `None`으로 끄지 않아야 한다.
- 확장자가 아니라 실제 형식으로 판단하고, 받기로 한 형식만 처리해야 한다.
- ImageMagick은 보안 정책 파일(`policy.xml`)로 쓰지 않는 코더와 자원 사용량을 막아야 한다.
- 변환·추출은 시간 제한이 있는 별도 프로세스(가능하면 네트워크 없는 컨테이너)에서 돌리고, 파서 라이브러리를 최신으로 유지해야 한다.
- 공개할 이미지는 다시 그려 저장해 EXIF·GPS 같은 메타데이터를 지워야 한다(사용자가 원해서 남기는 경우만 예외).
- 압축 파일은 WS-ZIP-001, XML·오피스 문서 속 XML은 WS-XML-001, 뽑은 글을 AI에 넣는 경우는 WS-PI-001도 함께 확인해야 한다.

## 탐지 규칙

- **찾는 곳**: `PIL.Image.open`, `Image.MAX_IMAGE_PIXELS`, PHP `Imagick`·`imagecreatefrom*`·`getimagesize`, `subprocess.`로 부르는 convert·`magick`·Ghostscript(`gs -`)·`libreoffice`·`pdftotext`, `wand.image`, `pdf.js`·`pdf-parse`·`sharp(`.
- **검사 1 — 크기 상한 없음**: 지적할 때: 남이 올린 파일을 크기·픽셀 확인 없이 여는 경우. `Image.MAX_IMAGE_PIXELS = None`이 있는 경우. / 지적하지 않을 때: 파일 크기·픽셀 상한을 열기 전에 확인하는 경우. 라이브러리 기본 상한(Pillow 기본값, `sharp`의 `limitInputPixels` 기본값)을 그대로 쓰는 경우.
- **검사 2 — 넓게 열린 변환기**: 지적할 때: ImageMagick·Ghostscript·문서 변환기를 남이 준 파일에 돌리는데 시간 제한이 없는 경우. 저장소·Dockerfile에 있는 `policy.xml`이 넓게 열려 있는 경우(`open` 정책, 코더 제한 없음). / 지적하지 않을 때: 정책 파일로 형식을 좁히고 시간 제한이 있는 별도 프로세스·컨테이너에서 도는 경우. 정책 파일이 저장소 밖(서버 설정)에 있어 볼 수 없으면 "확인 필요"로만 적음.
- **검사 3 — 그대로 공개되는 메타데이터**: 지적할 때: 업로드 원본을 그대로 공개 URL로 내보내는 경우(프로필 사진, 게시판 첨부 이미지). / 지적하지 않을 때: 다시 그려 저장한 파일을 공개하는 경우.
- **등급**: 위험: 남이 준 파일을 ImageMagick·Ghostscript로 처리하는데, 정책이 없거나 넓게 열려 있음을 확인한 경우(저장소·이미지 안 설정). / 주의: 정책 파일을 볼 수 없어 "확인 필요"인 경우. 크기·픽셀 상한이 없는 경우. 공개 이미지에 위치 정보가 남는 경우. / 참고: 변환에 시간 제한이 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[주의] WS-FILE-001 media/thumbs.py:9 — Image.MAX_IMAGE_PIXELS = None 으로 픽셀 폭탄 보호를 끔 → 기본값으로 되돌리고 파일 크기 상한 추가`

## 코드 예시

### python

✗ 취약 코드

```python
from PIL import Image

Image.MAX_IMAGE_PIXELS = None  # 픽셀 폭탄 보호를 끈다

def make_thumbnail(src: str, dst: str) -> None:
    im = Image.open(src)
    im.thumbnail((1024, 1024))
    im.save(dst, exif=im.info.get("exif", b""))  # 위치 정보까지 그대로 공개된다
```

✓ 안전 코드

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

### php

✗ 취약 코드

```php
<?php
// 업로드 원본을 그대로 공개 폴더로 옮긴다: 크기 확인도, 위치 정보 제거도 없다
move_uploaded_file($_FILES['photo']['tmp_name'], "public/photos/{$userId}.jpg");
```

✓ 안전 코드

```php
<?php
const MAX_BYTES = 10 * 1024 * 1024, MAX_PIXELS = 25_000_000;

function make_thumbnail(string $src, string $dst): void
{
    if (filesize($src) > MAX_BYTES) throw new RuntimeException('10MB 넘는 파일은 받지 않습니다');
    $info = getimagesize($src); // 머리 부분만 읽어 형식·크기를 본다
    if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) || $info[0] * $info[1] > MAX_PIXELS) {
        throw new RuntimeException('받지 않는 이미지입니다');
    }
    $img = $info[2] === IMAGETYPE_JPEG ? imagecreatefromjpeg($src) : imagecreatefrompng($src);
    if ($img === false) throw new RuntimeException('이미지를 읽지 못했습니다');
    imagejpeg(imagescale($img, min(1024, $info[0])), $dst, 85); // 새로 그린 그림이라 EXIF·위치 정보가 없다
}
```
