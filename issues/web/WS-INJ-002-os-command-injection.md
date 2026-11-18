---
id: WS-INJ-002
title: OS Command Injection (운영체제 명령어 삽입)
slug: os-command-injection
group: web
severity: high
site_only: false
depth: brief
languages: [fastapi, express, laravel]
maps:
  owasp2025: [A05]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.2.5]
  cwe: [CWE-78, CWE-77, CWE-88]
  kr_guide: ["입력데이터 검증 및 표현-5"]
  kr_ciip: [CI]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [FastAPI·Python, Express·Node.js, Laravel·PHP, 외부 프로그램을 부르는 모든 서버]
severity_reason: "영향: 서버에서 임의 명령 실행·파일 탈취·서버 장악 · 악용: 파일 이름·주소 칸에 넣는 글자만으로 가능"
sources:
  - title: OWASP OS Command Injection Defense Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/OS_Command_Injection_Defense_Cheat_Sheet.html
    year: 2026
  - title: "CISA·FBI Secure by Design Alert - Eliminating OS Command Injection Vulnerabilities"
    url: https://www.cisa.gov/resources-tools/resources/secure-design-alert-eliminating-os-command-injection-vulnerabilities
    year: 2024
  - title: "Node.js Docs - Child process (shell 옵션과 execFile)"
    url: https://nodejs.org/api/child_process.html
    year: 2026
  - title: "Python Docs - subprocess: Security Considerations"
    url: https://docs.python.org/3/library/subprocess.html#security-considerations
    year: 2026
---

## 요약

파일 이름·주소 같은 입력을 셸 명령 문자열에 이어 붙여 `;`·`|`·`$( )`가 새 명령으로 실행되는 문제(진단가이드 이름: 운영체제 명령어 삽입). 이미지 변환·압축·PDF 생성·핑 검사처럼 외부 프로그램을 부르는 기능에서 흔함.

## 대응 방법

```text
셸 명령을 실행하는 코드(subprocess, os.system, exec, shell_exec, Process)를 모두 찾아 입력값이 들어가는 곳을 알려 줘.
가능하면 언어 라이브러리로 바꾸고, 꼭 실행해야 하면 셸 없이 프로그램 이름과 인자를 목록으로 넘기게 바꿔 줘.
인자 값은 허용 목록이나 형식 검사를 거치게 하고, 파일은 서버가 만든 이름의 절대 경로로 넘겨 줘.
```

- 파일 이름에 `a.png; echo banana`를 넣으면 banana가 실행되지 않고 오류가 나와야 한다.

## 점검 항목

- 명령은 셸 문자열이 아니라 프로그램 이름과 인자 목록으로 실행해야 한다.
- 이미지·압축·PDF 처리는 외부 명령보다 언어 라이브러리를 먼저 써야 한다.
- 인자 값은 허용 목록이나 형식(정규식·숫자) 검사를 거쳐야 한다.
- `-`로 시작하는 값이 옵션으로 읽히지 않게 막아야 한다(`--` 지원 여부 확인).
- `escapeshellarg`·`shlex.quote` 같은 이스케이프를 주된 방어로 쓰지 않아야 한다.
- 명령을 실행하는 계정은 최소 권한으로 둬야 한다.

## 탐지 규칙

- **찾는 곳**: `shell=True`, `os.system(`, `child_process`, `shell_exec(`, `Process::run`, 그 밖에 exec·execSync·popen·passthru·proc_open·백틱, Symfony Process 의 fromShellCommandline.
- **검사 1 — 셸 문자열에 입력**: 지적할 때: 요청·파일 이름·DB·AI 출력 값이 셸 명령 문자열에 이어 붙는 경우(`shell=True`, `os.system`, `exec`, `execSync`, `shell_exec`, 백틱, 문자열 `Process::run`). / 지적하지 않을 때: 셸 없이 인자 목록으로 실행하는 경우(`subprocess.run([...])`, `execFile`, 기본값 `spawn`, 배열 `Process::run([...])`). 명령 문자열이 전부 코드 상수인 경우.
- **검사 2 — 인자 주입**: 지적할 때: 목록 실행이어도 입력이 `-`로 시작할 수 있고 형식 검사가 없는 경우(`git`·`curl`·`tar`처럼 옵션으로 다른 일을 하는 프로그램). / 지적하지 않을 때: 값이 허용 목록·정규식을 통과했거나 서버가 만든 절대 경로인 경우, `--` 뒤에 오는 경우.
- **등급**: 위험: 외부 값이 셸 문자열에 들어가는 경우. / 주의: 목록 실행이지만 인자 검사가 없는 경우, 이스케이프 함수에만 기대는 경우. / 참고: 명령이 root처럼 넓은 권한 계정으로 도는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-INJ-002 app/thumbs.py:21 — 업로드 파일 이름을 f-string 으로 넣어 subprocess.run(shell=True) 실행 → Pillow 로 처리하거나 인자 목록 실행, 이름은 서버가 만든 값만`
- **고칠 방법**: 셸 문자열을 인자 목록으로 바꿔야 하고, 파이프·리다이렉트가 필요하면 셸 대신 코드에서 두 프로그램을 이어야 한다.

## 코드 예시

### fastapi

✗ 취약 코드

```python
@app.post("/thumb")
def thumb(name: str):
    # name = "a.png; echo banana" 이면 뒤 명령도 실행된다
    subprocess.run(f"convert uploads/{name} -resize 200x200 thumbs/{name}", shell=True, check=True)
    return {"ok": True}
```

✓ 안전 코드

```python
import re
from pathlib import Path
from PIL import Image

UPLOADS, THUMBS = Path("/srv/app/uploads"), Path("/srv/app/thumbs")
NAME = re.compile(r"[0-9a-f]{32}\.(png|jpg)")          # 서버가 저장할 때 만든 이름 형식만

@app.post("/thumb")
def thumb(name: str):
    if not NAME.fullmatch(name):
        raise HTTPException(400, "잘못된 파일 이름")
    with Image.open(UPLOADS / name) as im:              # 외부 명령 대신 라이브러리
        im.thumbnail((200, 200))
        im.save(THUMBS / name)
    # 외부 프로그램이 꼭 필요하면: subprocess.run(["prog", str(UPLOADS / name)], check=True, timeout=30)
    return {"ok": True}
```

### express

✗ 취약 코드

```js
import { exec } from "node:child_process";

app.post("/ping", (req, res) => {
  exec(`ping -c 1 ${req.body.host}`, (err, out) => res.send(out)); // host 에 "; echo banana"
});
```

✓ 안전 코드

```js
import { execFile } from "node:child_process";
import net from "node:net";

const HOST = /^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i; // '-' 로 시작 불가

app.post("/ping", (req, res) => {
  const host = String(req.body.host ?? "");
  if (!net.isIPv4(host) && !HOST.test(host)) return res.status(400).send("잘못된 주소");
  execFile("ping", ["-c", "1", host], { timeout: 5000 }, (err, out) => { // 셸 없이 인자 목록으로
    if (err) return res.status(502).send("실패");
    res.type("text/plain").send(out);
  });
});
```

### laravel

✗ 취약 코드

```php
public function zip(Request $request)
{
    $dir = $request->input('folder');
    shell_exec("cd storage/app/exports && zip -r out.zip $dir");   // 입력이 셸 문자열에 들어간다
    return response()->download(storage_path('app/exports/out.zip'));
}
```

✓ 안전 코드

```php
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

public function zip(Request $request)
{
    $data = $request->validate(['folder' => ['required', 'string', 'regex:/^[a-z0-9_]{1,40}$/']]); // 형식 검사
    $base = storage_path('app/exports/' . $request->user()->id);           // 본인 폴더 안에서만 고른다
    abort_unless(is_dir("$base/{$data['folder']}"), 404);
    $out = storage_path('app/tmp/' . Str::uuid() . '.zip');                 // 요청마다 새 파일 이름
    $result = Process::path($base)->timeout(60)
        ->run(['zip', '-r', $out, $data['folder']]);                         // 배열 = 셸 없이 인자 목록
    abort_unless($result->successful(), 500);
    return response()->download($out, 'export.zip')->deleteFileAfterSend(); // 보낸 뒤 지운다
}
```
