---
id: WS-ZIP-001
title: Zip Slip · Zip Bomb (경로 탈출·압축 폭탄)
slug: zip-slip-zip-bomb
group: fetch
severity: high
site_only: false
depth: brief
languages: [python, php]
maps:
  owasp2025: [A01]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V5.2.3, V5.3.3, V5.2.5]
  cwe: [CWE-22, CWE-409]
  kr_guide: ["입력데이터 검증 및 표현-3"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, PHP, 압축 파일을 푸는 모든 서비스]
severity_reason: "영향: 정해 둔 폴더 밖 파일 덮어쓰기(코드 실행으로 이어짐)·디스크 고갈 · 악용: 조작한 압축 파일 하나로 가능"
sources:
  - title: "Snyk - Zip Slip Vulnerability"
    url: https://security.snyk.io/research/zip-slip-vulnerability
    year: 2018
  - title: "David Fifield - A better zip bomb"
    url: https://www.bamsoftware.com/hacks/zipbomb/
    year: 2019
  - title: "Python Docs - tarfile (Extraction filters)"
    url: https://docs.python.org/3/library/tarfile.html
    year: 2026
  - title: "Python Docs - zipfile (Decompression pitfalls)"
    url: https://docs.python.org/3/library/zipfile.html
    year: 2026
  - title: "NVD CVE-2007-4559 (Python tarfile 경로 탈출)"
    url: https://nvd.nist.gov/vuln/detail/CVE-2007-4559
    year: 2007
  - title: "NVD CVE-2025-4517 (tarfile filter=\"data\" 우회로 대상 폴더 밖 쓰기)"
    url: https://nvd.nist.gov/vuln/detail/CVE-2025-4517
    year: 2025
  - title: "Python 3.13.4 릴리스 노트 (gh-135034: tarfile 추출 필터 우회 수정)"
    url: https://www.python.org/downloads/release/python-3134/
    year: 2025
---

## 요약

압축 파일 속 이름에 `../`가 들어 있어 풀 때 정해 둔 폴더 밖에 파일이 써지는 Zip Slip, 작은 압축 하나가 풀리면서 디스크와 메모리를 다 채우는 압축 폭탄(Zip Bomb)에 대한 이슈(진단가이드 이름: 경로 조작 및 자원 삽입).

## 대응 방법

```text
업로드·다운로드한 압축(zip, tar, gz)을 푸는 코드를 모두 찾아 줘.
풀기 전에 모든 파일 이름이 대상 폴더 안에 머무는지 검사하고, 파일 개수와 실제로 푼 크기에 상한을 넣어 줘.
링크 항목은 풀지 말고, 실패하면 풀던 폴더를 통째로 지우게 해 줘.
```

- 이름이 `../test.txt`인 항목이 든 테스트 압축을 올렸을 때, 거절되고 대상 폴더 밖에 파일이 생기지 않아야 한다.

## 점검 항목

- 압축 속 파일 이름은 믿지 않아야 한다. 풀 경로를 절대 경로로 바꿔 대상 폴더 안인지 확인하고, `..`·`/`로 시작하는 이름·`\`가 든 이름은 거절해야 한다.
- 파일 개수 상한을 확인하고, 풀면서 실제로 쓴 바이트를 세어 상한을 넘으면 멈추어야 한다. 압축에 적힌 크기는 거짓일 수 있고, 46MB가 4.5PB로 풀리는 예도 공개됨(Fifield 2019).
- 링크(심볼릭 링크) 항목은 풀지 않아야 한다.
- 파이썬 `tarfile`은 패치된 파이썬(3.9.23·3.10.18·3.11.13·3.12.11·3.13.4 이상)에서 `extractall(..., filter="data")`로 풀어야 한다(3.14부터 기본값). 거름이 없어 생긴 문제가 CVE-2007-4559이고, 2025년에는 이 거름을 피해 가는 CVE-2025-4517 등이 나와 위 버전에서 수정됨.
- 웹에서 바로 열리는 폴더에는 풀지 않아야 한다. 이미지·문서 파일 자체의 폭탄은 WS-FILE-001 범위.

## 탐지 규칙

**찾는 곳**: `zipfile`, `tarfile`, `shutil.unpack_archive`, `ZipArchive`, `PharData`, `adm-zip`, `yauzl`, `unzipper`, `tar`(npm), `unzip`·`tar -x` 셸 호출.

**검사 1. 경로 검사 없음(Zip Slip)**
- 지적할 때: 항목 이름을 대상 폴더와 이어 붙여 바로 쓰는 경우. `tarfile.extractall`을 `filter` 없이 쓰는 경우(3.13 이하). 링크 항목을 그대로 푸는 경우. `filter="data"`를 쓰지만 런타임이 위 패치 버전보다 낮은 경우(버전을 알 수 없으면 참고).
- 지적하지 않을 때: 정규화한 경로가 대상 폴더 안인지 확인하는 경우. 파이썬 `zipfile.extract`·`extractall`을 쓰는 경우(공식 문서상 `..`와 절대 경로를 정리함. 검사 2는 별도 진행).

**검사 2. 크기 상한 없음(Zip Bomb)**
- 지적할 때: 남이 준 압축을 풀면서 개수·실제 크기 상한이 없는 경우. 항목을 통째로 메모리에 읽는 경우(`read()`, `getFromIndex`).
- 지적하지 않을 때: 개수와 실제로 푼 바이트에 상한이 있는 경우.

**등급**
- 위험: 남이 올린 압축을 경로 검사 없이 푸는 경우.
- 주의: 경로 검사는 있지만 크기·개수 상한이 없는 경우.
- 참고: 웹에서 바로 열리는 폴더에 푸는 경우. 실패 때 정리가 없는 경우.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-ZIP-001 upload/import.php:28 — getNameIndex 이름을 "uploads/$name" 으로 바로 씀 → 이름 검사로 .. · 절대 경로 거절, 개수·실제 크기 상한`

## 코드 예시

### python

✗ 취약 코드

```python
with zipfile.ZipFile(upload_path) as zf:
    for info in zf.infolist():
        with open(os.path.join(dest, info.filename), "wb") as f:  # ../ 이름이면 폴더 밖에 쓴다
            f.write(zf.read(info))  # 풀린 크기 제한이 없다
```

✓ 안전 코드

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
        if any("\\" in i.filename or not t.is_relative_to(root) for i, t in targets):
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

✗ 취약 코드

```php
<?php
$zip = new ZipArchive();
$zip->open($_FILES['file']['tmp_name']);
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    file_put_contents("uploads/$name", $zip->getFromIndex($i)); // ../ 로 밖에 쓰고, 통째로 메모리에 올린다
}
```

✓ 안전 코드

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
