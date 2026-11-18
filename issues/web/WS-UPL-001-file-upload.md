---
id: WS-UPL-001
title: Unrestricted File Upload (위험한 형식 파일 업로드)
slug: file-upload
group: web
severity: high
grade_hint: "형식 검사 없이 웹 공개 경로에 저장"
always_on: "`multer(` `UploadFile` → 업로드는 확장자·형식을 허용 목록으로 검사하고 크기를 제한해야 한다."
site_only: false
depth: brief
languages: [laravel, express, fastapi]
maps:
  owasp2025: [A06]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V5.2.1, V5.2.2, V5.2.4, V5.3.2]
  cwe: [CWE-434, CWE-770]
  kr_guide: ["입력데이터 검증 및 표현-6"]
  kr_ciip: [FU]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Express(multer), FastAPI, 파일을 올려받는 모든 웹 애플리케이션]
severity_reason: "영향: 웹셸 실행·다른 사용자에게 악성 파일 배포·저장 공간 고갈 · 악용: 업로드 폼 하나로 가능"
sources:
  - title: OWASP File Upload Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html
    year: 2026
  - title: "CWE-434: Unrestricted Upload of File with Dangerous Type"
    url: https://cwe.mitre.org/data/definitions/434.html
    year: 2025
  - title: "Laravel 12.x Validation - File rules (mimes, extensions, max)"
    url: https://laravel.com/docs/12.x/validation#validating-files
    year: 2026
  - title: "expressjs/multer - limits"
    url: https://github.com/expressjs/multer#limits
    year: 2026
---

## 요약

업로드 기능이 파일 형식·크기·이름을 확인하지 않아 스크립트·HTML·SVG·아주 큰 파일이 그대로 저장되는 문제(진단가이드 이름: 위험한 형식 파일 업로드). 실행되면 웹셸(WS-SHELL-001), 같은 도메인에서 열리면 XSS, 크기 제한이 없으면 저장 공간 고갈로 이어짐.

## 대응 방법

```text
업로드를 받는 곳(폼, API, 에디터 이미지, 프로필 사진)을 모두 찾아 허용 확장자·내용 형식·크기·개수 제한이 있는지 표로 보여 줘.
확장자와 실제 내용(매직 바이트)을 허용 목록으로 함께 검사하고, 저장 이름은 서버가 만든 무작위 이름으로 바꿔 줘.
내려줄 때는 Content-Disposition: attachment 와 X-Content-Type-Options: nosniff 를 붙여 줘.
```

- `.html`·`.svg` 파일과 이름만 `.jpg`로 바꾼 텍스트 파일을 올리면 둘 다 거절되어야 한다.

## 점검 항목

- 확장자는 차단 목록이 아니라 허용 목록으로 검사해야 한다.
- 확장자와 실제 내용(매직 바이트) 형식이 모두 허용 목록에 맞아야 한다.
- 파일 크기·개수·사용자별 총량 제한을 서버에서 걸어야 한다.
- 저장 이름은 서버가 만들고, 원래 이름은 DB에만 둬야 한다.
- HTML·SVG는 받지 않거나 다른 도메인에서 내려받기로만 제공해야 한다.
- 이미지는 다시 인코딩해 숨은 데이터와 메타데이터를 지워야 한다.

## 탐지 규칙

- **찾는 곳**: `multer(`, `UploadFile`, `->store(`, `request.FILES`, 그 밖에 getClientOriginalName·originalname·file.filename·move_uploaded_file.
- **검사 1 — 형식 검사 없음·차단 목록**: 지적할 때: 확장자·형식 검사가 없거나 `.php`만 막는 차단 목록인 경우. 클라이언트가 보낸 `Content-Type`(`mimetype`, `content_type`)만 믿는 경우. / 지적하지 않을 때: 허용 확장자 목록과 내용 검사(`finfo`, `file-type`, Pillow, Laravel `mimes:`)를 함께 하는 경우.
- **검사 2 — 원래 이름으로 저장·크기 제한 없음**: 지적할 때: `originalname`·`getClientOriginalName()`·`file.filename`으로 저장 경로를 만드는 경우. 크기 제한(`limits.fileSize`, `max:`, 본문 크기 상한)이 없는 경우. / 지적하지 않을 때: 무작위 이름(`hashName()`, UUID)으로 저장하고 크기 상한이 있는 경우.
- **등급**: 위험: 형식 검사 없이 웹에서 열리는 곳에 저장하는 경우. / 주의: 차단 목록·`Content-Type`만 검사, 원래 이름으로 저장, 크기 제한 없음. / 참고: HTML·SVG 허용, 이미지 재인코딩 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[주의] WS-UPL-001 routes/upload.js:5 — multer 가 originalname 으로 저장하고 크기 제한 없음 → 무작위 이름·limits.fileSize·내용 형식 검사`
- **고칠 방법**: 검사에 실패한 파일은 저장 전에 지워야 하고, 이미 올라온 파일 중 허용 목록 밖 형식이 있는지 확인해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
public function store(Request $request)
{
    $file = $request->file('photo');
    $file->move(public_path('uploads'), $file->getClientOriginalName()); // 형식·크기 검사 없음, 이름 그대로
    return back();
}
```

✓ 안전 코드

```php
public function store(Request $request)
{
    $request->validate([
        'photo' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp'], // 내용·확장자, 5MB
    ]);
    $file = $request->file('photo');
    $path = $file->store('photos', 'local');                    // 무작위 이름(hashName), 웹 루트 밖
    $request->user()->photos()->create([
        'path' => $path,
        'original_name' => $file->getClientOriginalName(),     // 원래 이름은 DB 에만
    ]);
    return back();
}
```

### express

✗ 취약 코드

```js
const upload = multer({
  storage: multer.diskStorage({
    destination: "public/uploads",                               // 웹에서 바로 열리는 곳
    filename: (req, file, cb) => cb(null, file.originalname),    // 이름 그대로, 크기 제한 없음
  }),
});
app.post("/upload", upload.single("photo"), (req, res) => res.json({ url: `/uploads/${req.file.originalname}` }));
```

✓ 안전 코드

```js
import crypto from "node:crypto";
import fs from "node:fs/promises";
import multer from "multer";
import { fileTypeFromFile } from "file-type";

const ALLOWED = new Map([["image/jpeg", "jpg"], ["image/png", "png"], ["image/webp", "webp"]]);
const upload = multer({ dest: "/srv/app/tmp", limits: { fileSize: 5 * 1024 * 1024, files: 1 } }); // 웹 루트 밖, 5MB

app.post("/upload", upload.single("photo"), async (req, res) => {
  if (!req.file) return res.sendStatus(400);
  const type = await fileTypeFromFile(req.file.path);           // 매직 바이트로 판단
  const ext = type && ALLOWED.get(type.mime);
  if (!ext) { await fs.unlink(req.file.path); return res.status(415).send("허용하지 않는 형식"); }
  const name = `${crypto.randomUUID()}.${ext}`;                  // 서버가 정한 이름
  await fs.rename(req.file.path, `/srv/app/uploads/${name}`);
  res.json({ id: name });
});
```

### fastapi

✗ 취약 코드

```python
@app.post("/upload")
async def upload(file: UploadFile):
    with open(f"static/uploads/{file.filename}", "wb") as f:   # 이름 그대로, 형식·크기 검사 없음
        f.write(await file.read())
    return {"url": f"/static/uploads/{file.filename}"}
```

✓ 안전 코드

```python
import io, uuid
from pathlib import Path
from PIL import Image

UPLOADS = Path("/srv/app/uploads")            # 정적 폴더 밖
MAX = 5 * 1024 * 1024                         # 본문 크기 상한은 프록시에도 둔다
FORMATS = {"JPEG": "jpg", "PNG": "png", "WEBP": "webp"}

@app.post("/upload")
async def upload(file: UploadFile):
    data = await file.read(MAX + 1)
    if len(data) > MAX:
        raise HTTPException(413, "5MB 이하만 받습니다")
    try:
        with Image.open(io.BytesIO(data)) as im:
            ext = FORMATS.get(im.format)        # 내용으로 형식 판단
            if not ext:
                raise HTTPException(415, "허용하지 않는 형식")
            name = f"{uuid.uuid4().hex}.{ext}"
            im.save(UPLOADS / name, format=im.format)   # 다시 인코딩해 숨은 데이터 제거
    except (OSError, Image.DecompressionBombError):
        raise HTTPException(415, "이미지가 아닙니다")
    return {"id": name}
```
