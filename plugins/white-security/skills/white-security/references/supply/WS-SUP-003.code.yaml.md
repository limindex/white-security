<!-- generated from white-security issues; do not edit -->
# WS-SUP-003 코드 예시 — YAML

✗ 취약 코드 (vulnerable example — do not use)

```yaml
# pnpm-workspace.yaml — 모든 의존성의 빌드 스크립트를 자동 실행
dangerouslyAllowAllBuilds: true
```

✓ 안전 코드 (safe example)

```yaml
# pnpm-workspace.yaml (pnpm 11 이상) — 검토한 패키지만 빌드 허용, 목록 밖은 설치 실패로 알림
allowBuilds:
  esbuild: true
  core-js: false
strictDepBuilds: true      # pnpm 11 기본값도 true, 끄지 않음을 드러내려고 적음
minimumReleaseAge: 4320   # 공개 후 3일(단위: 분) 지난 버전만
# pnpm 10 은 onlyBuiltDependencies: [esbuild]
```
