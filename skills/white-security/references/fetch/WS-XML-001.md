<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-XML-001 XML 외부 개체 참조·엔티티 폭탄

위험 · OWASP A02 · ASVS V1.5 · CWE-770 · 진단가이드 입력데이터 검증 및 표현-8

## 체크 항목

- 외부에서 온 XML에 DTD(`<!DOCTYPE`)가 있으면 거절한다. DTD가 꼭 필요하면 외부 개체·외부 DTD 로딩을 끄고 개체 확장 한도를 둔다.
- Python: 외부 XML은 표준 `xml.*` 대신 `defusedxml`로 읽는다(`forbid_dtd=True`). lxml을 쓰면 `resolve_entities=False`, `no_network=True`, `load_dtd=False`로 둔다. 표준 모듈을 써야 하면 `pyexpat.EXPAT_VERSION`이 2.7.2 이상인지 확인한다.
- PHP: 외부 XML에 `LIBXML_NOENT`, `LIBXML_DTDLOAD`, `LIBXML_DTDVALID`, `LIBXML_PARSEHUGE`를 쓰지 않는다. PHP 8.4 이상이고 libxml 2.13 이상이면 `LIBXML_NO_XXE`를 쓸 수 있다. `libxml_disable_entity_loader()`는 PHP 8.0부터 폐기됐으니 이것에 기대지 않는다.
- Node: `libxmljs`에서 `noent: true`를 쓰지 않는다. `fast-xml-parser`는 `processEntities: false`로 둔다.
- 요청 본문, 업로드 파일, 압축을 푼 뒤의 크기에 각각 상한을 둔다.
- XInclude와 XSLT의 외부 문서 불러오기를 끈다.
- 파서 오류 원문을 응답에 넣지 않는다. 읽어 온 파일 내용이 오류 문장에 섞여 나올 수 있다.
- SVG, 오피스 파일, RSS, SAML 응답처럼 XML인 줄 모르고 다루는 형식도 같은 파서 설정을 거친다.

## 스킬이 잡는 방식

**찾는 곳**: XML 파싱 호출과 그 입력의 출처. Python `xml.etree`, `xml.dom.minidom`, `xml.sax`, `lxml.etree.XMLParser(`, `etree.fromstring(`, `etree.parse(`. PHP `loadXML(`, `DOMDocument::load(`, `simplexml_load_string(`, `simplexml_load_file(`, `XMLReader`, `LIBXML_NOENT`. Node `libxmljs`, `parseXml(`, `xml2js`, `fast-xml-parser`, `@xmldom/xmldom`.

**검사 1. 외부 개체를 따라가는 설정**
- 지적할 때: 외부 입력 XML을 외부 개체 해석이 켜진 설정으로 파싱한다. lxml `resolve_entities=True`나 `no_network=False`와 `load_dtd=True`의 조합, PHP `LIBXML_NOENT`·`LIBXML_DTDLOAD`, libxmljs `noent: true`, `xml.sax`의 `feature_external_ges` 켬.
- 지적하지 않을 때: 입력이 저장소 안의 고정 파일이나 빌드 산출물이라 외부에서 바꿀 수 없다. 외부 개체가 기본으로 꺼진 라이브러리를 기본 설정 그대로 쓴다.

**검사 2. 엔티티 폭탄**
- 지적할 때: 외부 입력을 Python 표준 `xml.*`로 파싱하면서 `defusedxml`도 Expat 버전 확인도 없다. Expat 버전에 따라 다르므로 주의로 적고 "확인 필요"를 붙인다. PHP `LIBXML_PARSEHUGE`를 외부 입력에 쓴다.
- 지적하지 않을 때: DTD를 거절한다. `defusedxml`을 쓴다. `fast-xml-parser`를 `processEntities: false`로 쓴다.

**검사 3. 크기 상한·오류 노출**
- 지적할 때: 외부 XML을 크기 제한 없이 메모리에 올린다. 파서 오류 문장을 그대로 응답에 넣는다.

**등급**
- 위험: 외부 입력에 외부 개체 해석이 켜져 있다.
- 주의: 외부 입력인데 엔티티 확장 한도가 있는지 알 수 없다. `fast-xml-parser`를 `processEntities` 기본값(켜짐)으로 외부 입력에 쓴다.
- 참고: 크기 상한이 없다. 오류 원문이 응답에 나간다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <한 줄 이유> → <고칠 방법>`

예: `[위험] WS-XML-001 api/import.py:27 — 업로드한 XML을 resolve_entities=True 인 lxml 파서로 읽음(XXE) → defusedxml.ElementTree.fromstring(data, forbid_dtd=True) 로 바꾼다`

**고칠 때**
- 정상 문서에 DTD가 정말 필요한지 먼저 확인한다. 대부분 필요 없으므로 DTD 거절을 기본으로 둔다.
- CWE 번호는 XXE가 CWE-611, 엔티티 폭탄이 CWE-776이다. 보고에 함께 적어도 된다.

## 코드

### python

✗ 뚫리는 코드

```python
from lxml import etree

def import_order(xml_bytes: bytes):
    parser = etree.XMLParser(resolve_entities=True, load_dtd=True, no_network=False)
    root = etree.fromstring(xml_bytes, parser)   # 문서 속 DTD가 서버 파일·내부 주소를 불러온다
    return root.findtext("customer/name")
```

✓ 막는 코드

```python
from defusedxml import DefusedXmlException
from defusedxml.ElementTree import fromstring

MAX_XML = 1_000_000  # 1MB

def import_order(xml_bytes: bytes):
    if len(xml_bytes) > MAX_XML:
        raise ValueError("파일이 너무 큽니다")
    try:
        root = fromstring(xml_bytes, forbid_dtd=True)  # DTD·개체 선언·외부 참조를 거절
    except DefusedXmlException:
        raise ValueError("DTD가 있는 XML은 받지 않습니다")
    return root.findtext("customer/name")
```

### js

✗ 뚫리는 코드

```js
import express from "express";
import libxmljs from "libxmljs2";

const app = express();
app.post("/api/import", express.text({ type: "application/xml" }), (req, res) => {
  const doc = libxmljs.parseXml(req.body, { noent: true }); // 개체를 치환하며 외부 개체도 읽는다
  res.json({ name: doc.get("/order/customer/name")?.text() ?? null });
});
```

✓ 막는 코드

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

### php

✗ 뚫리는 코드

```php
<?php
$xml = file_get_contents('php://input');
$doc = new DOMDocument();
$doc->loadXML($xml, LIBXML_NOENT | LIBXML_DTDLOAD);   // 개체 치환과 외부 DTD 로딩을 켠다
header('Content-Type: application/json');
echo json_encode(['name' => $doc->getElementsByTagName('name')->item(0)?->textContent]);
```

✓ 막는 코드

```php
<?php
$xml = file_get_contents('php://input', false, null, 0, 1_000_001);
if ($xml === false || strlen($xml) > 1_000_000) { http_response_code(413); exit; }
// UTF-16 처럼 NUL 바이트가 섞인 문서로 DOCTYPE 검사를 피하지 못하게 함께 거절한다
if (stripos($xml, '<!DOCTYPE') !== false || str_contains($xml, "\0")) {
    http_response_code(400);
    exit('DTD가 있는 XML은 받지 않습니다');
}
libxml_use_internal_errors(true);                      // 파서 오류를 화면에 내지 않는다
$doc = new DOMDocument();
// NOENT·DTDLOAD·DTDVALID·PARSEHUGE 를 켜지 않고, 네트워크 접근도 막는다
if (!$doc->loadXML($xml, LIBXML_NONET) || $doc->doctype !== null) {
    http_response_code(400);
    exit('XML 형식이 올바르지 않습니다');
}
header('Content-Type: application/json');
echo json_encode(['name' => $doc->getElementsByTagName('name')->item(0)?->textContent]);
```
