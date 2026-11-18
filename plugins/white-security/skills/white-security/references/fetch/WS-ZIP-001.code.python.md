<!-- generated from white-security issues; do not edit -->
# WS-ZIP-001 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
with zipfile.ZipFile(upload_path) as zf:
    for info in zf.infolist():
        with open(os.path.join(dest, info.filename), "wb") as f:  # ../ 이름이면 폴더 밖에 쓴다
            f.write(zf.read(info))  # 풀린 크기 제한이 없다
```

✓ 안전 코드 (safe example)

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
