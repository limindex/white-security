<!-- generated from white-security issues; do not edit -->
# WS-SUP-003 코드 예시 — Shell

✗ 취약 코드 (vulnerable example — do not use)

```sh
# CI: 배포 토큰이 있는 상태로 모든 의존성의 설치 스크립트 실행
export NPM_TOKEN AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY
npm install
pip install -r requirements.txt     # 소스 배포판이면 setup 코드도 실행
```

✓ 안전 코드 (safe example)

```sh
set -euo pipefail
# 1) 잠금 파일대로, 설치 스크립트 없이(비밀값 없는 단계에서)
npm ci --ignore-scripts
# 2) 빌드가 꼭 필요한 패키지만 골라 다시 빌드(허용 목록)
npm rebuild esbuild
# 3) 파이썬은 휠만 받아 소스 빌드 코드가 돌지 않게, 해시 고정
pip install --only-binary :all: --require-hashes -r requirements.txt
# 새 버전은 공개 후 며칠 지나 받기: .npmrc 에 min-release-age=3 (단위: 일)
```
