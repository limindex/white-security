<!-- generated from white-security issues; do not edit -->
# WS-INJ-003 코드 예시 — Flask

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.get("/hello")
def hello():
    name = request.args.get("name", "")
    return render_template_string(f"<h1>안녕하세요 {name}님</h1>")  # name 이 템플릿 문법으로 실행된다
```

✓ 안전 코드 (safe example)

```python
@app.get("/hello")
def hello():
    name = request.args.get("name", "")
    return render_template_string("<h1>안녕하세요 {{ name }}님</h1>", name=name)  # 템플릿은 고정, 값은 변수
```
