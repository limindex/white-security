<!-- generated from white-security issues; do not edit -->
# WS-RED-001 Open Redirect (외부 주소로 자동 이동)

## 탐지 규칙

- **찾는 곳**: `->input('next'`, `request.GET.get("next"`, `searchParams.get("next"`, `formData.get("next"`, `window.location`, 그 밖에 returnUrl·redirect_uri 값으로 부르는 redirect()·->away()·Location 헤더.
- **검사 1 — 검사 없는 이동**: 지적할 때: 요청·DB·OAuth state 값이 `redirect()`·`Location`·`window.location`으로 그대로 가는 경우. / 지적하지 않을 때: 상대 경로 검사 함수, Django `url_has_allowed_host_and_scheme`, 허용 도메인 목록을 거친 경우. 이동 대상이 코드 상수인 경우.
- **검사 2 — 허술한 검사**: 지적할 때: `startsWith("/")`만 보는 경우(`//`·`/\` 통과), `includes("mysite.com")`·정규식 부분 일치, 검사 뒤 다시 디코딩하는 경우. / 지적하지 않을 때: `new URL(value, base)`로 해석해 origin을 비교하고, 결과 경로가 `//`로 시작하지 않는지까지 확인하는 경우.
- **등급**: 위험: 로그인·OAuth 콜백 뒤 이동에 검사가 없는 경우. / 주의: 그 밖의 검사 없는 이동, 허술한 문자열 검사. / 참고: 외부 이동에 안내 화면이 없는 경우.
- **보고 예**: `[위험] WS-RED-001 app/login/actions.ts:21 — 로그인 뒤 폼의 next 값으로 그대로 redirect → 같은 origin 의 상대 경로만 허용하는 safeNext() 사용`
- **고칠 방법**: 검사 함수 하나를 만들어 모든 이동에 써야 하고, 검사에 실패하면 오류 대신 첫 화면으로 보내야 한다.

## 점검 항목

- 이동 주소는 `/`로 시작하는 내 사이트 상대 경로만 허용해야 한다.
- `//`·`/\`로 시작하는 값과 제어 문자는 막아야 한다(브라우저가 다른 도메인으로 읽음).
- 검사는 브라우저와 같은 방식(`new URL`)으로 해석한 결과에 해야 한다.
- 외부 이동은 허용 도메인 목록이나 id→주소 매핑으로만 해야 한다.
- 요청 값으로 정해지는 외부 이동은 이동 안내 화면을 거치게 해야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-RED-001.code.nextjs.md` (Next.js) · `WS-RED-001.code.laravel.md` (Laravel) · `WS-RED-001.code.django.md` (Django)

기본 심각도: 주의 · CWE-601
