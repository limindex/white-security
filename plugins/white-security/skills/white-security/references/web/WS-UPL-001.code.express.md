<!-- generated from white-security issues; do not edit -->
# WS-UPL-001 코드 예시 — Express

✗ 취약 코드 (vulnerable example — do not use)

```js
const upload = multer({
  storage: multer.diskStorage({
    destination: "public/uploads",                               // 웹에서 바로 열리는 곳
    filename: (req, file, cb) => cb(null, file.originalname),    // 이름 그대로, 크기 제한 없음
  }),
});
app.post("/upload", upload.single("photo"), (req, res) => res.json({ url: `/uploads/${req.file.originalname}` }));
```

✓ 안전 코드 (safe example)

```js
import crypto from "node:crypto";
import fs from "node:fs/promises";
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
