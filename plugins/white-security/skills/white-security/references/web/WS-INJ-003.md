<!-- generated from white-security issues; do not edit -->
# WS-INJ-003 SSTI·Code Injection (서버 템플릿 삽입·코드 삽입)

## 탐지 규칙

- **찾는 곳**: `render_template_string(`, `Blade::render(`, `new Function(`, `ejs.render(`, 그 밖에 jinja2 Template·eval·exec·vm.runInContext·Handlebars.compile·str.format·format_map.
- **검사 1 — 입력이 템플릿·코드가 됨**: 지적할 때: 요청·DB·AI 출력 값이 템플릿 원문이나 `eval`·`exec`·`new Function`·`vm` 코드로 들어가는 경우(f-string으로 템플릿을 만든 뒤 렌더링하는 경우 포함). / 지적하지 않을 때: 템플릿이 코드 상수·파일이고 값은 렌더링 인자로만 넘기는 경우.
- **검사 2 — 서식 문자열**: 지적할 때: 사용자 문자열에 `.format(...)`·`format_map`을 호출하면서 객체(user, request, settings)를 넘기는 경우. / 지적하지 않을 때: `string.Template.safe_substitute`나 정해진 키만 바꾸는 직접 치환인 경우.
- **등급**: 위험: 외부 값이 템플릿 원문이나 `eval`류로 들어가는 경우. / 주의: 사용자 서식 문자열에 객체를 넘기는 경우, 샌드박스 템플릿에 외부 원문을 넣는 경우. / 참고: 관리자만 쓰는 템플릿 편집 기능(계정을 뺏기면 서버까지 넘어가는 경로).
- **보고 예**: `[위험] WS-INJ-003 app/views.py:12 — 이름을 f-string 으로 템플릿에 넣고 render_template_string 실행 → 고정 템플릿에 name 변수로 전달`
- **고칠 방법**: 꾸미기 기능은 허용 키 목록과 단순 치환으로 바꿔야 하고, 이미 저장된 템플릿에 `{{`·`{%`·`@php`가 남아 있는지 확인해야 한다.

## 점검 항목

- 템플릿 원문은 코드·파일에 고정하고, 사용자 값은 변수로만 넘겨야 한다.
- `eval`·`exec`·`new Function`·`vm` 실행에 외부 값을 넘기지 않아야 한다.
- 사용자 문구 꾸미기는 정해진 자리 치환만 허용해야 한다.
- 사용자 문자열을 `str.format`·`format_map`의 서식 문자열로 쓰지 않아야 한다.
- 샌드박스 템플릿(`SandboxedEnvironment`)도 완전한 방어로 믿지 않아야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-INJ-003.code.flask.md` (Flask) · `WS-INJ-003.code.python.md` (Python) · `WS-INJ-003.code.express.md` (Express) · `WS-INJ-003.code.laravel.md` (Laravel)

기본 심각도: 위험 · CWE-1336 · CWE-94 · CWE-95 · CWE-134
