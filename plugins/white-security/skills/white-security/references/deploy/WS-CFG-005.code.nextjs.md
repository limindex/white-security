<!-- generated from white-security issues; do not edit -->
# WS-CFG-005 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```js
// next.config.js — 운영 빌드에서 소스맵 공개(원본 코드·주석이 그대로 보임)
module.exports = {
  productionBrowserSourceMaps: true,
};
// public/ 에 둔 db-backup.sql·.env.local.bak 도 https://도메인/db-backup.sql 로 내려받아진다
```

✓ 안전 코드 (safe example)

```js
// next.config.js — 기본값(false) 그대로 둔다
module.exports = {
  productionBrowserSourceMaps: false,
};
// public/ 에는 공개해도 되는 정적 파일만 둔다
// 백업·덤프는 저장소와 배포물 밖에 보관하고 .gitignore 에 *.sql·*.bak·*.zip 을 넣는다
// 오류 추적용 소스맵이 필요하면 빌드 뒤 추적 도구에 올리고 배포물에서는 지운다
```
