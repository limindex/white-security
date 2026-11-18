<!-- generated from white-security issues; do not edit -->
# WS-ZIP-001 Zip Slip · Zip Bomb (경로 탈출·압축 폭탄)

심각도: 위험
기준: OWASP Top 10:2025 A01(접근 통제 실패) · ASVS 5.0 V5.2.3 · ASVS 5.0 V5.3.3 · ASVS 5.0 V5.2.5 · CWE-22(경로 조작) · CWE-409 · 국내 보안약점 49 입력데이터 검증 및 표현-3(경로 조작 및 자원 삽입)

## 점검 항목

- 압축 속 파일 이름은 믿지 않아야 한다. 풀 경로를 절대 경로로 바꿔 대상 폴더 안인지 확인하고, `..`·`/`로 시작하는 이름·`\`가 든 이름은 거절해야 한다.
- 파일 개수 상한을 확인하고, 풀면서 실제로 쓴 바이트를 세어 상한을 넘으면 멈추어야 한다. 압축에 적힌 크기는 거짓일 수 있고, 46MB가 4.5PB로 풀리는 예도 공개됨(Fifield 2019).
- 링크(심볼릭 링크) 항목은 풀지 않아야 한다.
- 파이썬 `tarfile`은 패치된 파이썬(3.9.23·3.10.18·3.11.13·3.12.11·3.13.4 이상)에서 `extractall(..., filter="data")`로 풀어야 한다(3.14부터 기본값). 거름이 없어 생긴 문제가 CVE-2007-4559이고, 2025년에는 이 거름을 피해 가는 CVE-2025-4517 등이 나와 위 버전에서 수정됨.
- 웹에서 바로 열리는 폴더에는 풀지 않아야 한다. 이미지·문서 파일 자체의 폭탄은 WS-FILE-001 범위.

## 탐지 규칙

- **찾는 곳**: `zipfile`, `tarfile.open(`, `shutil.unpack_archive`, `ZipArchive`, `PharData`, `adm-zip`, `yauzl`, `unzipper`, tar(npm, `tar.x(`·`tar.extract(`), `unzip`·`tar -x` 셸 호출.
- **검사 1 — 경로 검사 없음(Zip Slip)**: 지적할 때: 항목 이름을 대상 폴더와 이어 붙여 바로 쓰는 경우. `tarfile.extractall`을 `filter` 없이 쓰는 경우(3.13 이하). 링크 항목을 그대로 푸는 경우. `filter="data"`를 쓰지만 런타임이 위 패치 버전보다 낮은 경우(버전을 알 수 없으면 참고). / 지적하지 않을 때: 정규화한 경로가 대상 폴더 안인지 확인하는 경우. 파이썬 `zipfile.extract`·`extractall`을 쓰는 경우(공식 문서상 `..`와 절대 경로를 정리함. 검사 2는 별도 진행).
- **검사 2 — 크기 상한 없음(Zip Bomb)**: 지적할 때: 남이 준 압축을 풀면서 개수·실제 크기 상한이 없는 경우. 항목을 통째로 메모리에 읽는 경우(`read()`, `getFromIndex`). / 지적하지 않을 때: 개수와 실제로 푼 바이트에 상한이 있는 경우.
- **등급**: 위험: 남이 올린 압축을 경로 검사 없이 푸는 경우. / 주의: 경로 검사는 있지만 크기·개수 상한이 없는 경우. / 참고: 웹에서 바로 열리는 폴더에 푸는 경우. 실패 때 정리가 없는 경우.
- **보고 예**: `[위험] WS-ZIP-001 upload/import.php:28 — getNameIndex 이름을 "uploads/$name" 으로 바로 씀 → 이름 검사로 .. · 절대 경로 거절, 개수·실제 크기 상한`

Code examples (read only the file for the language you are writing): `WS-ZIP-001.code.python.md` · `WS-ZIP-001.code.php.md`
