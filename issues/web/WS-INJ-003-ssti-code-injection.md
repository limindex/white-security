---
id: WS-INJ-003
title: SSTI · Code Injection (서버 템플릿 인젝션·코드 인젝션)
slug: ssti-code-injection
group: web
severity: high
site_only: false
depth: brief
languages: [flask, python, express, laravel]
maps:
  owasp2025: [A05]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.3.7, V1.3.2, V1.3.10]
  cwe: [CWE-1336, CWE-94, CWE-95, CWE-134]
  kr_guide: ["입력데이터 검증 및 표현-2", "입력데이터 검증 및 표현-17"]
  kr_ciip: [CI]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Flask(Jinja2), Python, Express·Node.js, Laravel(Blade), 문구·계산식을 사용자에게 받는 기능]
severity_reason: "영향: 서버에서 임의 코드 실행·환경 변수와 비밀키 노출 · 악용: 이름·문구 칸에 넣는 글자만으로 가능"
sources:
  - title: "PortSwigger Web Security Academy - Server-side template injection"
    url: https://portswigger.net/web-security/server-side-template-injection
    year: 2026
  - title: "OWASP WSTG - Testing for Server-side Template Injection"
    url: https://owasp.org/www-project-web-security-testing-guide/latest/4-Web_Application_Security_Testing/07-Input_Validation_Testing/18-Testing_for_Server-side_Template_Injection
    year: 2026
  - title: "Armin Ronacher, Be Careful with Python's New-Style String Format"
    url: https://lucumr.pocoo.org/2016/12/29/careful-with-str-format/
    year: 2016
  - title: KISA Python 시큐어코딩 가이드 (2023년 개정본, 포맷 스트링 삽입)
    url: https://www.kisa.or.kr/2060204/form?postSeq=13
    year: 2023
---

## 요약

사용자가 준 글을 템플릿 원문이나 코드로 취급해 `render_template_string`·`eval`·`new Function`에 넘기면, `{{ }}` 같은 문법이 서버에서 실행되는 문제(진단가이드 이름: 코드 삽입·포맷 스트링 삽입). 안내 문구 꾸미기·계산식 입력·알림 템플릿 기능에서 흔함.

## 대응 방법

```text
사용자 입력이 템플릿 원문(render_template_string, jinja2.Template, Blade::render, ejs.render)이나 eval·exec·new Function, str.format 의 서식으로 쓰이는 곳을 찾아 줘.
템플릿은 코드에 고정하고 사용자 값은 변수로만 넘기게 바꿔 줘. 계산식은 eval 없이 직접 파싱해 줘.
사용자가 문구를 꾸며야 하면 정해진 자리({name} 등)만 바꾸는 단순 치환으로 바꿔 줘.
```

- 이름 칸에 `{{7*7}}`을 넣으면 화면에 `49`가 아니라 `{{7*7}}`이 그대로 보여야 한다.

## 점검 항목

- 템플릿 원문은 코드·파일에 고정하고, 사용자 값은 변수로만 넘겨야 한다.
- `eval`·`exec`·`new Function`·`vm` 실행에 외부 값을 넘기지 않아야 한다.
- 사용자 문구 꾸미기는 정해진 자리 치환만 허용해야 한다.
- 사용자 문자열을 `str.format`·`format_map`의 서식 문자열로 쓰지 않아야 한다.
- 샌드박스 템플릿(`SandboxedEnvironment`)도 완전한 방어로 믿지 않아야 한다.

## 탐지 규칙

- **찾는 곳**: `render_template_string(`, `Blade::render(`, `new Function(`, `ejs.render(`, 그 밖에 jinja2 Template·eval·exec·vm.runInContext·Handlebars.compile·str.format·format_map.
- **검사 1 — 입력이 템플릿·코드가 됨**: 지적할 때: 요청·DB·AI 출력 값이 템플릿 원문이나 `eval`·`exec`·`new Function`·`vm` 코드로 들어가는 경우(f-string으로 템플릿을 만든 뒤 렌더링하는 경우 포함). / 지적하지 않을 때: 템플릿이 코드 상수·파일이고 값은 렌더링 인자로만 넘기는 경우.
- **검사 2 — 서식 문자열**: 지적할 때: 사용자 문자열에 `.format(...)`·`format_map`을 호출하면서 객체(user, request, settings)를 넘기는 경우. / 지적하지 않을 때: `string.Template.safe_substitute`나 정해진 키만 바꾸는 직접 치환인 경우.
- **등급**: 위험: 외부 값이 템플릿 원문이나 `eval`류로 들어가는 경우. / 주의: 사용자 서식 문자열에 객체를 넘기는 경우, 샌드박스 템플릿에 외부 원문을 넣는 경우. / 참고: 관리자만 쓰는 템플릿 편집 기능(계정을 뺏기면 서버까지 넘어가는 경로).
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-INJ-003 app/views.py:12 — 이름을 f-string 으로 템플릿에 넣고 render_template_string 실행 → 고정 템플릿에 name 변수로 전달`
- **고칠 방법**: 꾸미기 기능은 허용 키 목록과 단순 치환으로 바꿔야 하고, 이미 저장된 템플릿에 `{{`·`{%`·`@php`가 남아 있는지 확인해야 한다.

## 코드 예시

### flask

✗ 취약 코드

```python
@app.get("/hello")
def hello():
    name = request.args.get("name", "")
    return render_template_string(f"<h1>안녕하세요 {name}님</h1>")  # name 이 템플릿 문법으로 실행된다
```

✓ 안전 코드

```python
@app.get("/hello")
def hello():
    name = request.args.get("name", "")
    return render_template_string("<h1>안녕하세요 {{ name }}님</h1>", name=name)  # 템플릿은 고정, 값은 변수
```

### python

✗ 취약 코드

```python
# 관리자가 정한 알림 문구 예: "{user.name}님, 주문이 접수되었습니다"
def render_notice(template: str, user) -> str:
    return template.format(user=user)  # 서식 안에서 객체 속성을 따라가 설정·비밀값까지 꺼낼 수 있다
```

✓ 안전 코드

```python
from string import Template

def render_notice(template: str, user, order) -> str:
    values = {"name": str(user.name), "order_no": str(order.no)}  # 쓸 수 있는 값만 문자열로
    return Template(template).safe_substitute(values)               # $name·$order_no 만 바뀐다, 속성 접근 없음
```

### express

✗ 취약 코드

```js
// 계산기: "1+2*3" 을 받아 계산
app.get("/calc", (req, res) => {
  res.json({ result: eval(req.query.expr) }); // 식 대신 코드도 실행된다
});
```

✓ 안전 코드

```js
// 숫자와 + - * / ( ) 만 읽는 작은 계산기. eval·new Function 을 쓰지 않는다
function calc(src) {
  if (typeof src !== "string" || src.length > 100) throw new Error("식이 너무 깁니다");
  const t = src.match(/\d+(?:\.\d+)?|\S/g) ?? [];
  let i = 0;
  const num = () => {
    const x = t[i++];
    if (x === "(") { const v = expr(); if (t[i++] !== ")") throw new Error("괄호"); return v; }
    if (x === "-") return -num();
    if (!/^\d/.test(x ?? "")) throw new Error("허용하지 않는 글자");
    return Number(x);
  };
  const term = () => { let v = num(); while (t[i] === "*" || t[i] === "/") v = t[i++] === "*" ? v * num() : v / num(); return v; };
  const expr = () => { let v = term(); while (t[i] === "+" || t[i] === "-") v = t[i++] === "+" ? v + term() : v - term(); return v; };
  const v = expr();
  if (i !== t.length) throw new Error("허용하지 않는 글자");
  return v;
}

app.get("/calc", (req, res) => {
  try { res.json({ result: calc(req.query.expr) }); } catch { res.status(400).json({ error: "계산할 수 없는 식" }); }
});
```

### laravel

✗ 취약 코드

```php
// 사용자가 쓴 안내 문구를 Blade 로 렌더링
public function preview(Request $request)
{
    return Blade::render($request->input('template'), ['user' => $request->user()]); // @php 등도 실행된다
}
```

✓ 안전 코드

```php
public function preview(Request $request)
{
    $data = $request->validate(['template' => ['required', 'string', 'max:2000']]);
    $values = [':name' => $request->user()->name, ':date' => now()->toDateString()]; // 정해진 자리만
    $text = strtr($data['template'], $values);                      // 코드로 해석하지 않는 단순 치환
    return view('notice.preview', ['text' => $text]);                // 화면은 {{ $text }} 로 이스케이프 출력
}
```
