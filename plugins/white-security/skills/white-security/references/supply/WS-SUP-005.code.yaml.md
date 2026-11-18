<!-- generated from white-security issues; do not edit -->
# WS-SUP-005 코드 예시 — YAML

✗ 취약 코드 (vulnerable example — do not use)

```yaml
# .github/workflows/preview.yml
on: pull_request_target          # 포크 PR 에도 비밀값·쓰기 토큰이 있는 상태로 실행
jobs:
  preview:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v6
        with:
          ref: ${{ github.event.pull_request.head.sha }}   # 남의 PR 코드를 체크아웃
      - run: npm install && npm run build                  # PR 의 설치·빌드 스크립트가 비밀값과 함께 실행
      - run: echo "${{ github.event.pull_request.title }}"   # PR 제목이 그대로 셸 코드가 됨
      - uses: example-org/deploy-preview@main              # 브랜치 고정: 바뀐 코드를 그대로 실행
        with:
          token: ${{ secrets.DEPLOY_TOKEN }}
```

✓ 안전 코드 (safe example)

```yaml
# .github/workflows/ci.yml — 포크 PR 은 비밀값 없이, 읽기 토큰으로
on: pull_request
permissions:
  contents: read
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6.1.0
        with:
          persist-credentials: false
      - run: npm ci --ignore-scripts && npm run build
      - env:
          TITLE: ${{ github.event.pull_request.title }}    # 남이 정하는 값은 환경 변수로만
        run: echo "$TITLE"
# 비밀값이 필요한 배포는 main 에 합친 뒤(push) 별도 워크플로에서, 그 job 에만 필요한 권한을 줌
```
