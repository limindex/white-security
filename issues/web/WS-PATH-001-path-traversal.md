---
id: WS-PATH-001
title: Path Traversal (경로 조작)
slug: path-traversal
group: web
severity: high
site_only: false
depth: brief
languages: [express, fastapi, laravel]
maps:
  owasp2025: [A01]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V5.3.2, V5.4.1, V1.1.1]
  cwe: [CWE-22, CWE-23, CWE-73, CWE-99]
  kr_guide: ["입력데이터 검증 및 표현-3"]
  kr_ciip: [FD]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Express·JavaScript, FastAPI·Python, Laravel·PHP, 한국어 Windows 서버, 파일을 내려주거나 읽는 모든 기능]
severity_reason: "영향: 설정 파일·비밀키·소스 코드 노출, 쓰기 기능이면 서버 장악 · 악용: 파일 이름 칸에 ../ 몇 개로 가능"
sources:
  - title: "OWASP - Path Traversal"
    url: https://owasp.org/www-community/attacks/Path_Traversal
    year: 2026
  - title: "CWE-22: Improper Limitation of a Pathname to a Restricted Directory"
    url: https://cwe.mitre.org/data/definitions/22.html
    year: 2025
  - title: "Microsoft, Windows Best Fit 매핑 표 - 코드페이지 949 (0x20A9 → 0x5C)"
    url: https://www.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WindowsBestFit/bestfit949.txt
    year: 2006
  - title: "DEVCORE, WorstFit: Unveiling Hidden Transformers in Windows ANSI"
    url: https://devco.re/blog/2025/01/09/worstfit-unveiling-hidden-transformers-in-windows-ansi/
    year: 2025
  - title: KISA 2026 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드 (FD 파일 다운로드)
    url: https://www.kisa.or.kr/2060204/form?postSeq=22
    year: 2025
---

## 요약

내려받기·이미지 보기처럼 파일 이름을 받아 여는 기능에서 `../`가 든 이름으로 정해 둔 폴더 밖의 파일(설정·키·소스)을 읽거나 덮어쓰는 문제(진단가이드 이름: 경로 조작 및 자원 삽입). 한국어 Windows 서버에서는 원화 기호 `₩`(U+20A9)가 `\`로 바뀌어 검사를 지나치는 경우도 있음.

## 대응 방법

```text
요청 값으로 파일 경로를 만드는 곳(내려받기, 이미지 보기, 첨부, 템플릿·언어 파일 불러오기)을 모두 찾아 줘.
가능하면 파일을 DB id로 찾아 서버가 저장한 경로를 쓰게 바꾸고, 이름을 받아야 하면 정규화한 뒤 기준 폴더 안인지 확인하게 해 줘.
```

- `?name=../../.env`로 요청하면 404나 400이 나와야 한다.

## 점검 항목

- 파일은 이름 대신 DB id로 찾고, 실제 경로는 서버가 저장한 값을 써야 한다.
- 이름을 받아야 하면 허용 문자(`^[A-Za-z0-9._-]+$`)만 받고 `.`으로 시작하는 이름을 막아야 한다.
- 경로는 정규화(`realpath`·`resolve`)한 뒤 기준 폴더 안인지 다시 확인해야 한다.
- 검사는 URL 디코딩·유니코드 정규화를 모두 끝낸 값에 해야 한다.
- ANSI(CP949) 변환을 거치면 `₩`가 `\`로 바뀌므로 차단 목록이 아닌 허용 문자로 검사해야 한다.

## 탐지 규칙

- **찾는 곳**: `res.sendFile(`, `FileResponse(`, `send_file(`, `->download(`, 그 밖에 요청 값이 들어가는 path.join·open·readFile·file_get_contents·fopen·include.
- **검사 1 — 입력으로 경로 만들기**: 지적할 때: 요청·DB·압축 속 이름 값이 경로에 이어지고 기준 폴더 안인지 확인이 없는 경우. / 지적하지 않을 때: DB id로 찾은 서버 경로를 쓰는 경우. `realpath`·`resolve` 뒤 기준 폴더(구분자 포함)로 시작하는지 확인하는 경우. 허용 문자 정규식을 통과한 이름만 쓰는 경우.
- **검사 2 — 허술한 확인**: 지적할 때: `../`를 한 번만 지우는 치환, 정규화 전에 하는 검사, 구분자 없이 `startsWith(base)`로 비교하는 경우(`/data/files2`도 통과). / 지적하지 않을 때: 정규화한 경로가 `base + 구분자`로 시작하는지 비교하는 경우.
- **검사 3 — include·템플릿 경로**: 지적할 때: 요청 값으로 `include`·`require`·템플릿·언어 파일 경로를 고르는 경우. / 지적하지 않을 때: 허용 목록 매핑으로만 고르는 경우.
- **등급**: 위험: 요청 값으로 만든 경로로 파일을 읽거나 쓰면서 범위 확인이 없는 경우, `include`에 요청 값. / 주의: 한 번 치환·정규화 전 검사·구분자 없는 비교. / 참고: Windows 서버인데 `₩`를 거르지 않는 차단 목록 검사.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PATH-001 routes/files.js:7 — ?name= 값을 path.join 으로 붙여 sendFile → DB id 로 찾거나 resolve 후 기준 폴더 안인지 확인`
- **고칠 방법**: 파일 찾는 방식을 id로 바꿀 때 기존 링크가 깨지지 않게 이름→id 매핑을 두고, 범위 확인에 실패하면 404로 처리해야 한다.

## 코드 예시

### express

✗ 취약 코드

```js
app.get("/files", (req, res) => {
  res.sendFile(path.join(__dirname, "files", req.query.name)); // name=../.env 도 열린다
});
```

✓ 안전 코드

```js
import path from "node:path";

const BASE = path.resolve("/srv/app/files");
const NAME = /^[A-Za-z0-9._-]{1,100}$/;   // 허용 문자만(₩·\ 는 통과 못 함)

app.get("/files", (req, res) => {
  const name = String(req.query.name ?? "");
  if (!NAME.test(name) || name.startsWith(".")) return res.sendStatus(400);
  const full = path.resolve(BASE, name);
  if (!full.startsWith(BASE + path.sep)) return res.sendStatus(404);   // 정규화 뒤 기준 폴더 안인지
  res.sendFile(full, { dotfiles: "deny" }, (err) => { if (err && !res.headersSent) res.sendStatus(404); });
});
```

### fastapi

✗ 취약 코드

```python
@app.get("/files/{name:path}")
def get_file(name: str):
    return FileResponse(f"/srv/app/files/{name}")   # ../ 가 든 경로도 열린다
```

✓ 안전 코드

```python
from pathlib import Path

BASE = Path("/srv/app/files").resolve()

@app.get("/files/{name}")
def get_file(name: str):
    full = (BASE / name).resolve()               # 정규화(심볼릭 링크 포함)
    if not full.is_relative_to(BASE) or full == BASE or not full.is_file():
        raise HTTPException(404)                 # 기준 폴더 안의 파일만
    return FileResponse(full, filename=full.name)
```

### laravel

✗ 취약 코드

```php
public function download(Request $request)
{
    $path = storage_path('app/files/' . $request->query('name'));   // ../../.env 도 그대로
    return response()->download($path);
}
```

✓ 안전 코드

```php
// Route::get('/attachments/{attachment}/download', ...) — 경로 대신 DB id 로 찾는다
public function download(Request $request, Attachment $attachment)
{
    $this->authorize('view', $attachment);                           // 내 파일인지 확인
    return Storage::disk('local')->download($attachment->path, $attachment->original_name); // 경로는 서버가 저장한 값
}
```
