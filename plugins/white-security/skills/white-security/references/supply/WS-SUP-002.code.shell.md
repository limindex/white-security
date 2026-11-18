<!-- generated from white-security issues; do not edit -->
# WS-SUP-002 코드 예시 — Shell

✗ 취약 코드 (vulnerable example — do not use)

```sh
# 배포 스크립트: 취약점 검사 없이 설치만(npm 경고도 끔)
npm install --no-audit
pip install -r requirements.txt
composer install --no-dev
```

✓ 안전 코드 (safe example)

```sh
set -euo pipefail
# Node: 잠금 파일대로 설치 후 높음 이상이면 실패
npm ci --ignore-scripts && npm audit --audit-level=high
# Python: 고정한 요구 사항 파일 검사(PyPA pip-audit)
pip-audit -r requirements.txt
# PHP: 잠금 파일 기준 검사(취약점이 있으면 종료 코드 1)
composer audit --locked
# 여러 언어를 한 번에: OSV-Scanner
osv-scanner scan source -r .
# 고칠 때: 고친 버전으로 올리고 잠금 파일을 함께 커밋(예: npm install next@15.2.3 --save-exact)
```
