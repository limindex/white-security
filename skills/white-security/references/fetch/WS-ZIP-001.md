<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-ZIP-001 Zip Slip · 압축 폭탄(Zip Bomb)

위험 · OWASP A01 · ASVS V5.2.3 · ASVS V5.3.3 · ASVS V5.2.5 · CWE-22 · CWE-409 · 진단가이드 입력데이터 검증 및 표현-3

## 체크 항목

- 압축 속 파일 이름을 믿지 않는다. 풀 경로를 절대 경로로 바꿔 대상 폴더 안인지 확인하고, `..`·`/`로 시작하는 이름·`\`가 든 이름은 거절한다.
- 파일 개수 상한을 보고, 풀면서 실제로 쓴 바이트를 세어 상한을 넘으면 멈춘다. 압축에 적힌 크기는 거짓일 수 있고, 46MB가 4.5PB로 풀리는 예도 공개돼 있다(Fifield 2019).
- 링크(심볼릭 링크) 항목은 풀지 않는다.
- 파이썬 `tarfile`은 `extractall(..., filter="data")`로 푼다(3.14부터 기본값). 이 거름이 없어 생긴 문제가 CVE-2007-4559다.
- 웹에서 바로 열리는 폴더에 풀지 않는다. 이미지·문서 파일 자체의 폭탄은 WS-FILE-001에서 본다.

## 스킬이 잡는 방식

**찾는 곳**: `zipfile`, `tarfile`, `shutil.unpack_archive`, `ZipArchive`, `PharData`, `adm-zip`, `yauzl`, `unzipper`, `tar`(npm), `unzip`·`tar -x` 셸 호출.

**검사 1. 경로를 검사하지 않는다(Zip Slip)**
- 지적할 때: 항목 이름을 대상 폴더와 이어 붙여 바로 쓴다. `tarfile.extractall`을 `filter` 없이 쓴다(3.13 이하). 링크 항목을 그대로 푼다.
- 지적하지 않을 때: 정규화한 경로가 대상 폴더 안인지 확인한다. 또는 파이썬 `zipfile.extract`·`extractall`을 쓴다(공식 문서상 `..`와 절대 경로를 정리한다. 이때도 검사 2는 본다).

**검사 2. 크기 상한이 없다(Zip Bomb)**
- 지적할 때: 남이 준 압축을 풀면서 개수·실제 크기 상한이 없다. 항목을 통째로 메모리에 읽는다(`read()`, `getFromIndex`).
- 지적하지 않을 때: 개수와 실제로 푼 바이트에 상한이 있다.

**등급**
- 위험: 남이 올린 압축을 경로 검사 없이 푼다.
- 주의: 경로 검사는 있지만 크기·개수 상한이 없다.
- 참고: 웹에서 바로 열리는 폴더에 푼다. 실패 때 정리가 없다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-ZIP-001 upload/import.php:28 — getNameIndex 이름을 "uploads/$name" 으로 바로 씀 → 이름 검사로 .. · 절대 경로 거절, 개수·실제 크기 상한`

## 코드

### python

✗ 뚫리는 코드

```python
with zipfile.ZipFile(upload_path) as zf:
    for info in zf.infolist():
        with open(os.path.join(dest, info.filename), "wb") as f:  # ../ 이름이면 폴더 밖에 쓴다
            f.write(zf.read(info))  # 풀린 크기 제한이 없다
```

✓ 막는 코드

```python
import zipfile
from pathlib import Path

MAX_FILES, MAX_TOTAL = 1000, 200 * 1024 * 1024

def safe_unzip(src: str, dest: str) -> None:  # 실패하면 호출한 쪽에서 dest 를 통째로 지운다
    root = Path(dest).resolve()
    with zipfile.ZipFile(src) as zf:
        infos = [i for i in zf.infolist() if not i.is_dir()]
        if len(infos) > MAX_FILES:
            raise ValueError("파일이 너무 많습니다")
        targets = [(i, (root / i.filename).resolve()) for i in infos]
        if any(not t.is_relative_to(root) for _, t in targets):
            raise ValueError("압축 밖으로 나가는 경로가 있습니다")
        written = 0
        for info, target in targets:
            target.parent.mkdir(parents=True, exist_ok=True)
            with zf.open(info) as s, open(target, "wb") as d:
                while chunk := s.read(65536):
                    written += len(chunk)  # 적힌 크기가 아니라 실제로 푼 양을 센다
                    if written > MAX_TOTAL:
                        raise ValueError("풀면 너무 큽니다")
                    d.write(chunk)
```

### php

✗ 뚫리는 코드

```php
<?php
$zip = new ZipArchive();
$zip->open($_FILES['file']['tmp_name']);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    file_put_contents("uploads/$name", $zip->getFromIndex($i)); // ../ 로 밖에 쓰고, 통째로 메모리에 올린다
}
```

✓ 막는 코드

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
