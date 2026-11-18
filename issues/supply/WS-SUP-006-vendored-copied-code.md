---
id: WS-SUP-006
title: Vendored · Copied Code (복사해 온 코드·공통 코드 패치 누락)
slug: vendored-copied-code
group: supply
severity: medium
grade_hint: "알려진 XSS·RCE 버전 복사본을 운영에서 사용"
site_only: false
depth: brief
languages: [laravel, nextjs, json]
maps:
  owasp2025: [A03]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V15.1.2, V15.2.1]
  cwe: [CWE-1104, CWE-1395, CWE-1041]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel(Blade·public 폴더), Next.js·React, PHP·JS로 만든 레거시 게시판·쇼핑몰, 복사한 에디터·차트·업로드 플러그인을 쓰는 사이트]
severity_reason: "영향: 원본에 이미 고친 XSS·업로드 취약점이 내 복사본에는 그대로 남음 · 악용: 공개된 취약 버전을 파일 이름·버전 주석으로 찾아 공격"
sources:
  - title: "OWASP Top 10:2025 - A03 Software Supply Chain Failures"
    url: https://top10.owasp.org/2025/A03_2025-Software_Supply_Chain_Failures
    year: 2025
  - title: "CWE-1104: Use of Unmaintained Third Party Components"
    url: https://cwe.mitre.org/data/definitions/1104.html
    year: 2025
  - title: "Retire.js - What you require you must also retire"
    url: https://retirejs.github.io/retire.js/
    year: 2026
  - title: "GitHub Advisory - jQuery XSS in htmlPrefilter (CVE-2020-11022)"
    url: https://github.com/advisories/GHSA-gxr4-xjj5-5px2
    year: 2020
  - title: "GitHub Advisory Database - lodash (CVE-2026-4800, 4.18.0에서 수정 · 4.18.1은 CVE-2026-2950까지 수정)"
    url: https://github.com/advisories/GHSA-r5fr-rjxr-66jc
    year: 2026
  - title: "npm Docs - package.json overrides"
    url: https://docs.npmjs.com/cli/v11/configuring-npm/package-json#overrides
    year: 2026
---

## 요약

jQuery·웹 에디터·차트 라이브러리 파일을 `public/js`에 복사해 두거나 남의 코드를 저장소에 붙여 넣어 패키지 관리자 밖에 두는 바람에, 원본에 보안 패치가 나와도 검사 도구와 업데이트 알림이 보지 못하는 문제. 같은 권한 확인·입력 검증 코드를 여러 파일에 복사해 한 곳만 고쳐지는 것도 같은 구조.

## 대응 방법

```text
public/·static/·assets/·third_party/ 에 복사해 넣은 외부 라이브러리 파일과 그 버전(파일 이름·머리 주석)을 표로 보여 주고, retire.js 로 알려진 취약점을 확인해 줘.
패키지로 받을 수 있는 것은 package.json·composer.json 의존성으로 옮기고 복사본은 지워 줘.
여러 파일에 복사된 권한 확인·입력 검증 코드는 공통 함수 하나로 모아 모든 곳이 그걸 쓰게 바꿔 줘.
```

- 저장소에 버전 주석이 붙은 외부 `*.min.js` 복사본이 남아 있지 않아야 한다(빌드 결과물 제외).
- 권한 확인 코드를 한 곳에서 고치면 같은 기능의 모든 경로에 반영되어야 한다.

## 점검 항목

- 외부 라이브러리는 복사하지 말고 패키지 관리자로 받아야 한다.
- 꼭 복사해 둬야 하면 출처·버전·라이선스를 기록하고 정기적으로 점검해야 한다.
- 복사본 파일도 retire.js·osv-scanner로 알려진 취약점을 검사해야 한다.
- 권한 확인·입력 검증·가림 같은 보안 코드는 한 곳에 두고 불러 써야 한다.
- 포크한 패키지는 원본의 보안 수정을 따라가거나 원본으로 돌아가야 한다.

## 탐지 규칙

- **찾는 곳**: `jquery-1.`, `jquery-2.`, `/*! jQuery v`, `public/vendor/`, `static/vendor/`, `third_party/`, `"github:`, `"overrides"`, 그 밖에 버전이 이름에 든 .min.js·.min.css, 에디터·업로드 플러그인 폴더, 여러 라우트에 복사된 권한 확인 코드, git 주소 의존성.
- **검사 1 — 관리 밖 라이브러리**: 지적할 때: 저장소에 커밋된 외부 라이브러리 파일(파일 이름·머리 주석의 버전)이 알려진 취약 버전인 경우(예: jQuery 3.5.0 미만의 XSS), 출처·버전 기록 없이 복사한 경우. / 지적하지 않을 때: 빌드 결과물(dist·build·.next), 패키지 관리자가 만든 `vendor/`(Composer)·`node_modules`, 출처·버전·점검 기록이 있는 복사본.
- **검사 2 — 복사된 보안 코드**: 지적할 때: 같은 권한 확인·입력 검증·가림 코드가 여러 파일에 복사되어 일부만 고쳐졌거나 서로 다른 경우. / 지적하지 않을 때: 공통 함수·미들웨어·정책 클래스를 부르는 경우.
- **검사 3 — 포크·git 의존성**: 지적할 때: `github:`·`git+https:` 의존성이나 포크한 패키지가 오래 갱신되지 않아 원본의 보안 수정을 받지 못하는 경우. / 지적하지 않을 때: 커밋을 고정하고 원본 추적 계획이 문서에 있는 경우, 간접 의존성 취약점을 `overrides`로 고친 경우.
- **등급**: 위험: 알려진 XSS·원격 코드 실행 취약 버전의 복사본이 운영 화면에서 쓰임. / 주의: 출처·버전을 모르는 복사본, 일부만 고쳐진 복사 보안 코드, 오래된 포크. / 참고: 기록이 있는 복사본.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SUP-006 public/js/jquery-1.12.4.min.js:1 — 패키지 관리 밖 jQuery 1.12.4(CVE-2020-11022 XSS) → npm 의존성 jquery(3.5.0 이상, 가능하면 최신)로 옮기고 복사본 삭제`
- **검토 주의**: Composer `vendor/`·`node_modules`는 패키지 관리자 결과라 이 이슈 대상이 아니다. 패키지 의존성의 취약 버전은 WS-SUP-002, CDN에서 불러오는 스크립트의 무결성(SRI)은 WS-SUP-005에서 다룬다. 권한 확인 자체가 빠진 경로는 WS-AUTH-001로 보고한다.
- **고칠 방법**: 복사본은 패키지 의존성으로 옮겨 검사·업데이트 알림 대상에 넣고, 복사된 보안 코드는 공통 함수로 합쳐야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
{{-- resources/views/layouts/app.blade.php — 몇 년 전 복사한 파일, 업데이트 알림·검사 대상 밖 --}}
<script src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('vendor/summernote-0.8.9/summernote-lite.min.js') }}"></script>
```

✓ 안전 코드

```php
{{-- npm install jquery summernote 로 package.json 에서 관리 → Dependabot·npm audit 대상 --}}
{{-- resources/js/app.js: import $ from "jquery"; window.$ = window.jQuery = $; import "summernote/dist/summernote-lite.js"; --}}
@vite('resources/js/app.js')
```

### nextjs

✗ 취약 코드

```ts
// 같은 관리자 확인을 라우트마다 복사: 한 곳만 고쳐지고 나머지는 옛 조건 그대로
// app/api/admin/users/route.ts — 나중에 고친 조건
export async function GET() {
  const s = await auth();
  if (s?.user?.role !== "admin" || !s.user.emailVerified) return new Response(null, { status: 403 });
  // ...
}

// app/api/admin/export/route.ts — 복사해 둔 옛 조건: 옛 필드라 권한을 뺀 계정도 통과
export async function GET() {
  const s = await auth();
  if (!s?.user?.isAdmin) return new Response(null, { status: 403 });
  // ...
}
```

✓ 안전 코드

```ts
// lib/require-admin.ts — 보안 판단은 한 곳에
import "server-only";
import { auth } from "@/auth";

export async function requireAdmin() {
  const user = (await auth())?.user;
  return user && user.role === "admin" && user.emailVerified && !user.suspended ? user : null;
}

// app/api/admin/export/route.ts — 모든 관리자 라우트가 같은 함수를 부름
export async function GET() {
  const admin = await requireAdmin();
  if (!admin) return new Response(null, { status: 403 });
  // ...
}
```

### json

✗ 취약 코드

```json
{
  "dependencies": {
    "chart.js": "github:example/Chart.js#my-fix",
    "lodash": "4.17.15"
  }
}
```

✓ 안전 코드

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
