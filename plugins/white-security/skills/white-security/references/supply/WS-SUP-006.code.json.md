<!-- generated from white-security issues; do not edit -->
# WS-SUP-006 코드 예시 — JSON

✗ 취약 코드 (vulnerable example — do not use)

```json
{
  "dependencies": {
    "chart.js": "github:example/Chart.js#my-fix",
    "lodash": "4.17.15"
  }
}
```

✓ 안전 코드 (safe example)

```json
{
  "dependencies": {
    "chart.js": "4.5.1",
    "lodash": "4.18.1"
  },
  "overrides": {
    "lodash": "4.18.1"
  }
}
```
