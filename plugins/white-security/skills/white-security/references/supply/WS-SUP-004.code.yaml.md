<!-- generated from white-security issues; do not edit -->
# WS-SUP-004 코드 예시 — YAML

✗ 취약 코드 (vulnerable example — do not use)

```yaml
# .github/workflows/ci.yml
steps:
  - uses: actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6.1.0
  - run: npm install                       # 잠금 파일과 다르면 새 버전을 풀고 잠금 파일을 고침
  - run: pip install -r requirements.txt   # 버전·해시 고정 없음
  - run: composer update --no-interaction  # 잠금 파일을 무시하고 새로 계산
```

✓ 안전 코드 (safe example)

```yaml
# .github/workflows/ci.yml
permissions:
  contents: read
steps:
  - uses: actions/checkout@d23441a48e516b6c34aea4fa41551a30e30af803 # v6.1.0
  - run: npm ci --ignore-scripts             # 잠금 파일과 package.json 이 다르면 실패, integrity 확인
  - run: npx --yes lockfile-lint@5.1.0 --path package-lock.json --type npm --allowed-hosts npm --validate-https --validate-integrity
  - run: pip install --require-hashes -r requirements.txt   # 해시는 uv pip compile --generate-hashes 로 생성
  - run: composer validate && composer install --no-interaction   # validate: 잠금 파일이 최신이 아니면 실패
```
