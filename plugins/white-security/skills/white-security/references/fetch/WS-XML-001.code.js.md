<!-- generated from white-security issues; do not edit -->
# WS-XML-001 코드 예시 — JavaScript

✗ 취약 코드 (vulnerable example — do not use)

```js
import express from "express";
import libxmljs from "libxmljs2";

const app = express();
app.post("/api/import", express.text({ type: "application/xml" }), (req, res) => {
  const doc = libxmljs.parseXml(req.body, { noent: true }); // 개체를 치환하며 외부 개체도 읽는다
  res.json({ name: doc.get("/order/customer/name")?.text() ?? null });
});
```

✓ 안전 코드 (safe example)

```js
import express from "express";
import { XMLParser } from "fast-xml-parser";

const app = express();
const parser = new XMLParser({ processEntities: false }); // 줄임말을 펼치지 않는다

app.post("/api/import", express.text({ type: "application/xml", limit: "1mb" }), (req, res) => {
  if (typeof req.body !== "string" || /<!DOCTYPE/i.test(req.body)) {
    return res.status(400).json({ error: "DTD가 있는 XML은 받지 않습니다" });
  }
  let data;
  try {
    data = parser.parse(req.body);
  } catch {
    return res.status(400).json({ error: "XML 형식이 올바르지 않습니다" }); // 파서 오류 원문은 숨긴다
  }
  res.json({ name: data?.order?.customer?.name ?? null });
});
```
