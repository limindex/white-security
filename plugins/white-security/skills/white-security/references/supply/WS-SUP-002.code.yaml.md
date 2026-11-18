<!-- generated from white-security issues; do not edit -->
# WS-SUP-002 코드 예시 — YAML

✗ 취약 코드 (vulnerable example — do not use)

```yaml
# .github/workflows/deploy.yml — 검사 없이 배포, 업데이트 체계(Dependabot) 없음
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v6
      - run: npm install && npm audit fix --force   # 검토 없이 메이저 버전까지 바뀜
      - run: npm run build && ./deploy.sh
```

✓ 안전 코드 (safe example)

```yaml
# .github/workflows/ci.yml — 높음 이상 취약점이 있으면 실패
on: [pull_request, push]
permissions:
  contents: read
jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6.1.0
      - run: npm ci --ignore-scripts
      - run: npm audit --audit-level=high
---
# .github/dependabot.yml — 의존성·액션 업데이트 PR 을 매주 받음
version: 2
updates:
  - package-ecosystem: "npm"
    directory: "/"
    schedule:
      interval: "weekly"
  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
```
