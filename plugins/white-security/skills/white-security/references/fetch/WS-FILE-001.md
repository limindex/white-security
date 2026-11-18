<!-- generated from white-security issues; do not edit -->
# WS-FILE-001 Decompression Bomb · 파서 취약점 (남이 준 이미지·PDF·오피스 파일 처리)

## 탐지 규칙

- **찾는 곳**: `PIL.Image.open`, `Image.MAX_IMAGE_PIXELS`, PHP `Imagick`·`imagecreatefrom*`·`getimagesize`, `subprocess.`로 부르는 convert·`magick`·Ghostscript(`gs -`)·`libreoffice`·`pdftotext`, `wand.image`, `pdf.js`·`pdf-parse`·`sharp(`.
- **검사 1 — 크기 상한 없음**: 지적할 때: 남이 올린 파일을 크기·픽셀 확인 없이 여는 경우. `Image.MAX_IMAGE_PIXELS = None`이 있는 경우. / 지적하지 않을 때: 파일 크기·픽셀 상한을 열기 전에 확인하는 경우. 라이브러리 기본 상한(Pillow 기본값, `sharp`의 `limitInputPixels` 기본값)을 그대로 쓰는 경우.
- **검사 2 — 넓게 열린 변환기**: 지적할 때: ImageMagick·Ghostscript·문서 변환기를 남이 준 파일에 돌리는데 시간 제한이 없는 경우. 저장소·Dockerfile에 있는 `policy.xml`이 넓게 열려 있는 경우(`open` 정책, 코더 제한 없음). / 지적하지 않을 때: 정책 파일로 형식을 좁히고 시간 제한이 있는 별도 프로세스·컨테이너에서 도는 경우. 정책 파일이 저장소 밖(서버 설정)에 있어 볼 수 없으면 "확인 필요"로만 적음.
- **검사 3 — 그대로 공개되는 메타데이터**: 지적할 때: 업로드 원본을 그대로 공개 URL로 내보내는 경우(프로필 사진, 게시판 첨부 이미지). / 지적하지 않을 때: 다시 그려 저장한 파일을 공개하는 경우.
- **등급**: 위험: 남이 준 파일을 ImageMagick·Ghostscript로 처리하는데, 정책이 없거나 넓게 열려 있음을 확인한 경우(저장소·이미지 안 설정). / 주의: 정책 파일을 볼 수 없어 "확인 필요"인 경우. 크기·픽셀 상한이 없는 경우. 공개 이미지에 위치 정보가 남는 경우. / 참고: 변환에 시간 제한이 없는 경우.
- **보고 예**: `[주의] WS-FILE-001 media/thumbs.py:9 — Image.MAX_IMAGE_PIXELS = None 으로 픽셀 폭탄 보호를 끔 → 기본값으로 되돌리고 파일 크기 상한 추가`

## 점검 항목

- 열기 전에 파일 크기, 이미지 가로×세로 픽셀, 문서 쪽수에 상한을 두어야 한다. Pillow의 `Image.MAX_IMAGE_PIXELS`는 `None`으로 끄지 않아야 한다.
- 확장자가 아니라 실제 형식으로 판단하고, 받기로 한 형식만 처리해야 한다.
- ImageMagick은 보안 정책 파일(`policy.xml`)로 쓰지 않는 코더와 자원 사용량을 막아야 한다. 2016년 ImageTragick(CVE-2016-3714)은 조작한 이미지 하나로 서버 명령을 실행한 사례.
- 변환·추출은 시간 제한이 있는 별도 프로세스(가능하면 네트워크 없는 컨테이너)에서 돌리고, 파서 라이브러리를 최신으로 유지해야 한다.
- 공개할 이미지는 다시 그려 저장해 EXIF·GPS 같은 메타데이터를 지워야 한다(사용자가 원해서 남기는 경우만 예외).
- 압축 파일은 WS-ZIP-001, XML·오피스 문서 속 XML은 WS-XML-001, 뽑은 글을 AI에 넣는 경우는 WS-PI-001도 함께 확인해야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-FILE-001.code.python.md` (Python) · `WS-FILE-001.code.php.md` (PHP)

기본 심각도: 주의 · CWE-400 · CWE-770 · CWE-212
