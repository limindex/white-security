<!-- generated from white-security issues; do not edit -->
# WS-FILE-001 코드 예시 — PHP

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
// 업로드 원본을 그대로 공개 폴더로 옮긴다: 크기 확인도, 위치 정보 제거도 없다
move_uploaded_file($_FILES['photo']['tmp_name'], "public/photos/{$userId}.jpg");
```

✓ 안전 코드 (safe example)

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
