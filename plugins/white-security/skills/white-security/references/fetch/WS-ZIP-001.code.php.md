<!-- generated from white-security issues; do not edit -->
# WS-ZIP-001 코드 예시 — php

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
$zip = new ZipArchive();
$zip->open($_FILES['file']['tmp_name']);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    file_put_contents("uploads/$name", $zip->getFromIndex($i)); // ../ 로 밖에 쓰고, 통째로 메모리에 올린다
}
```

✓ 안전 코드 (safe example)

```php
<?php
const MAX_FILES = 1000, MAX_TOTAL = 200 * 1024 * 1024;

function safe_unzip(string $src, string $dest): void // 실패하면 호출한 쪽에서 $dest 를 통째로 지운다
{
    $zip = new ZipArchive();
    if ($zip->open($src) !== true) throw new RuntimeException('압축 파일을 열 수 없습니다');
    if ($zip->numFiles > MAX_FILES) throw new RuntimeException('파일이 너무 많습니다');
    for ($i = 0; $i < $zip->numFiles; $i++) { // 먼저 이름을 모두 검사한다
        $name = $zip->getNameIndex($i);
        if ($name === false || str_contains($name, '\\') || str_starts_with($name, '/') || in_array('..', explode('/', $name), true)) {
            throw new RuntimeException('압축 밖으로 나가는 경로가 있습니다');
        }
    }
    $written = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (str_ends_with($name, '/')) continue;
        is_dir(dirname("$dest/$name")) || mkdir(dirname("$dest/$name"), 0755, true);
        [$in, $out] = [$zip->getStreamIndex($i), fopen("$dest/$name", 'wb')];
        while (!feof($in) && ($chunk = fread($in, 65536)) !== false) {
            $written += strlen($chunk); // 적힌 크기가 아니라 실제로 푼 양을 센다
            if ($written > MAX_TOTAL) throw new RuntimeException('풀면 너무 큽니다');
            fwrite($out, $chunk);
        }
        fclose($in);
        fclose($out);
    }
}
```
