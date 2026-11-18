---
id: WS-XML-001
title: XXE · Billion Laughs
title_en: XXE (XML External Entity) · Billion Laughs
slug: billion-laughs-xxe
group: fetch
severity: high
grade_hint: "외부 XML에 외부 개체 해석 켜짐"
always_on: "`etree.fromstring(` `simplexml_load_string(` → 외부 XML은 DTD·외부 개체를 끈 파서로 읽어야 한다."
site_only: false
depth: full
languages: [python, js, php]
maps:
  owasp2025: [A02]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.5]
  cwe: [CWE-611, CWE-776]
  kr_guide: ["입력데이터 검증 및 표현-8"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, Node.js, PHP, XML을 읽는 모든 서비스]
severity_reason: "영향: 서버 파일 유출·메모리 고갈로 인한 서비스 중단 · 악용: 조작한 XML 파일 하나로 가능"
sources:
  - title: OWASP XML External Entity Prevention Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/XML_External_Entity_Prevention_Cheat_Sheet.html
    year: 2026
  - title: "OWASP Top 10:2025 - A02 Security Misconfiguration (CWE-611, CWE-776)"
    url: https://top10.owasp.org/2025/A02_2025-Security_Misconfiguration
    year: 2025
  - title: "CWE-611: Improper Restriction of XML External Entity Reference"
    url: https://cwe.mitre.org/data/definitions/611.html
    year: 2025
  - title: "CWE-776: Improper Restriction of Recursive Entity References in DTDs ('XML Entity Expansion')"
    url: https://cwe.mitre.org/data/definitions/776.html
    year: 2025
  - title: "Python 문서 - XML Processing Modules (XML security)"
    url: https://docs.python.org/3/library/xml.html
    year: 2026
  - title: "PHP 매뉴얼 - libxml_disable_entity_loader"
    url: https://www.php.net/manual/en/function.libxml-disable-entity-loader.php
    year: 2026
  - title: defusedxml (PyPI)
    url: https://pypi.org/project/defusedxml/
    year: 2021
  - title: "fast-xml-parser - XML parse options (processEntities)"
    url: https://github.com/NaturalIntelligence/fast-xml-parser/blob/master/docs/v4%2C%20v5/2.XMLparseOptions.md
    year: 2026
  - title: 행정기관 및 공공기관 정보시스템 구축·운영 지침 (행정안전부고시 제2025-1호, 별표 3)
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%ED%96%89%EC%A0%95%EA%B8%B0%EA%B4%80%EB%B0%8F%EA%B3%B5%EA%B3%B5%EA%B8%B0%EA%B4%80%EC%A0%95%EB%B3%B4%EC%8B%9C%EC%8A%A4%ED%85%9C%EA%B5%AC%EC%B6%95%E3%86%8D%EC%9A%B4%EC%98%81%EC%A7%80%EC%B9%A8
    year: 2025
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

XXE(XML External Entity, XML 외부 개체 참조)와 Billion Laughs(엔티티 폭탄)는 XML(태그로 내용을 감싸 적는 데이터 형식) 파서가 문서 속 선언을 그대로 따라 서버 파일을 읽어 오거나, 작은 파일 하나로 서버 메모리를 다 쓰게 되는 취약점에 대한 이슈(진단가이드 이름: 부적절한 XML 외부 개체 참조). 비밀 파일 유출과 서비스 중단 위험.

## 발생 상황

- 거래처 XML 주문서를 DB에 넣거나 블로그 RSS(새 글 목록 파일)를 긁어 요약하는 기능. SVG 그림 파일과 엑셀(xlsx) 파일도 속은 XML.
- XML 맨 위 DTD(`<!DOCTYPE ...>`, 문서 안 '줄임말 사전')의 엔티티(개체, 줄임말) 하나를 "서버의 이 파일 내용"으로 정해 두면, 파서(파일을 읽어 해석하는 프로그램)가 서버 파일을 열어 끼워 넣는 XXE.
- 줄임말이 다른 줄임말을 여러 번 부르게 해 크기를 불리는 Billion Laughs. 아래 두 단계 예시(해 없음)에서 `&b;`는 "ha" 10번, 20글자. 단계마다 10배라 10단계면 "ha" 10억 번, 글자만 약 2GB로 1KB도 안 되는 파일이 서버 메모리를 다 쓰는 구조.

```xml
<!DOCTYPE r [
  <!ENTITY a "ha">
  <!ENTITY b "&a;&a;&a;&a;&a;&a;&a;&a;&a;&a;">
]>
<r>&b;</r>
```

## 위험 배경

- 스크래퍼와 AI 에이전트가 사람이 고르지 않은 외부 파일(RSS, 사이트맵, SVG, 오피스 파일)을 확인 없이 파서로 바로 넘기는 일의 증가.
- 언어마다 다른 기본값, 옵션 하나로 다시 열리는 구조. PHP는 기본 차단이지만 `LIBXML_NOENT` 같은 옵션을 켜면 다시 열림(PHP 매뉴얼). Python 표준 모듈은 Expat이 2.7.2보다 오래되면 Billion Laughs에 취약할 수 있음(Python 문서).
- XXE를 막아도 Billion Laughs는 따로 막아야 함(OWASP 치트시트). OWASP Top 10:2025는 두 문제를 '보안 설정 오류'(A02)에 포함. 국내 공공 개발보안 기준 49개 항목에도 '부적절한 XML 외부 개체 참조' 포함.

## 대응 방법

```text
외부에서 받은 XML·SVG·RSS·오피스 파일을 읽는 코드를 모두 찾아 줘.
<!DOCTYPE 가 들어 있는 XML은 거절하고, 외부 개체(엔티티) 처리를 끈 안전한 파서로 바꿔 줘.
파일 크기 상한을 넣고, 파싱 오류 원문은 화면에 보여 주지 마.
```

- 위 두 단계 예시를 파일로 저장해 넣었을 때 "받지 않는 형식" 같은 거절 메시지가 나와야 한다. "hahaha…"가 펼쳐지면 줄임말 처리가 켜진 상태.
- 코드에 PHP `LIBXML_NOENT`, Node `noent: true`(문서 속 엔티티를 바깥 파일 것까지 풀어 넣는 옵션)가 없어야 하고, Python은 `defusedxml`을 써야 한다.

## 점검 항목

- 외부에서 온 XML에 DTD(`<!DOCTYPE`)가 있으면 거절해야 한다. DTD가 꼭 필요하면 외부 개체·외부 DTD 로딩을 끄고 개체 확장 한도를 둬야 하며, XInclude와 XSLT의 외부 문서 불러오기도 꺼야 한다.
- Python: 외부 XML은 표준 `xml.*` 대신 `defusedxml`(`forbid_dtd=True`)로 읽어야 한다. lxml은 `resolve_entities=False`, `no_network=True`, `load_dtd=False`로 둬야 하고, 표준 모듈을 써야 하면 `pyexpat.EXPAT_VERSION`이 2.7.2 이상인지 확인해야 한다.
- PHP: 외부 XML에 `LIBXML_NOENT`, `LIBXML_DTDLOAD`, `LIBXML_DTDVALID`, `LIBXML_PARSEHUGE`를 쓰지 않아야 한다. `libxml_disable_entity_loader()`는 PHP 8.0부터 폐기된 함수라 기대지 않아야 한다.
- Node: `libxmljs`에서 `noent: true`를 쓰지 않아야 하고, `fast-xml-parser`는 `processEntities: false`로 둬야 한다.
- 요청 본문·업로드 파일·압축을 푼 뒤의 크기에 각각 상한을 둬야 하고, 파서 오류 원문은 응답에 넣지 않아야 한다(읽어 온 파일 내용이 오류 문장에 섞여 나올 수 있음).
- SVG, 오피스 파일, RSS, SAML 응답처럼 XML인 줄 모르고 다루는 형식도 같은 파서 설정을 거쳐야 한다.

## 탐지 규칙

- **찾는 곳**: XML 파싱 호출과 입력 출처(Python `etree.fromstring(`, `lxml.etree.XMLParser(`, `xml.etree` · PHP `simplexml_load_string(`, `loadXML(`, `LIBXML_NOENT` · Node `libxmljs`, `fast-xml-parser` · 그 밖에 Python `xml.dom.minidom`, `xml.sax`, `etree.parse(` · PHP `DOMDocument::load(`, `simplexml_load_file(`, `XMLReader` · Node `parseXml(`, `xml2js`, `@xmldom/xmldom`).
- **검사 1 — 외부 개체를 따라가는 설정**: 지적할 때: 외부 입력 XML을 외부 개체 해석이 켜진 설정으로 파싱하는 경우(lxml `resolve_entities=True`나 `no_network=False`와 `load_dtd=True`의 조합, PHP `LIBXML_NOENT`·`LIBXML_DTDLOAD`, libxmljs `noent: true`, `xml.sax`의 `feature_external_ges` 켬). / 지적하지 않을 때: 입력이 저장소 안 고정 파일이나 빌드 산출물이라 외부에서 바꿀 수 없는 경우. 외부 개체가 기본으로 꺼진 라이브러리를 기본 설정 그대로 쓰는 경우.
- **검사 2 — 엔티티 폭탄**: 지적할 때: 외부 입력을 Python 표준 `xml.*`로 파싱하면서 `defusedxml`도 Expat 버전 확인도 없는 경우(Expat 버전에 따라 달라 주의 + "확인 필요"). PHP `LIBXML_PARSEHUGE`를 외부 입력에 쓰는 경우. / 지적하지 않을 때: DTD를 거절하거나, `defusedxml`을 쓰거나, `fast-xml-parser`를 `processEntities: false`로 쓰는 경우.
- **검사 3 — 크기 상한·오류 노출**: 지적할 때: 외부 XML을 크기 제한 없이 메모리에 올리거나, 파서 오류 문장을 그대로 응답에 넣는 경우. / 지적하지 않을 때: 본문 크기 상한(`limit`, 길이 인자)이 있고 오류를 일반 문구로 바꿔 내보내는 경우.
- **등급**: 위험: 외부 입력에 외부 개체 해석이 켜진 경우. / 주의: 외부 입력인데 엔티티 확장 한도를 알 수 없는 경우. `fast-xml-parser`를 `processEntities` 기본값(켜짐)으로 외부 입력에 쓰는 경우. / 참고: 크기 상한 없음, 오류 원문 노출.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-XML-001 api/import.py:27 — 업로드한 XML을 resolve_entities=True 인 lxml 파서로 읽음(XXE) → defusedxml.ElementTree.fromstring(data, forbid_dtd=True) 로 바꿈`
- **검토 주의**: PHP 8.4 이상·libxml 2.13 이상이면 `LIBXML_NO_XXE`로 외부 개체를 막을 수 있으므로, 이 옵션을 쓴 코드는 XXE 지적에서 빼야 한다.
- **고칠 방법**: 정상 문서에 DTD가 정말 필요한지 먼저 확인하고(대부분 불필요) DTD 거절을 기본으로 둬야 한다. 보고에는 XXE면 CWE-611, 엔티티 폭탄이면 CWE-776을 함께 적어야 한다.

## 코드 예시

### python

✗ 취약 코드

```python
from lxml import etree

def import_order(xml_bytes: bytes):
    parser = etree.XMLParser(resolve_entities=True, load_dtd=True, no_network=False)
    root = etree.fromstring(xml_bytes, parser)   # 문서 속 DTD가 서버 파일·내부 주소를 불러온다
    return root.findtext("customer/name")
```

✓ 안전 코드

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

✗ 취약 코드

```js
import express from "express";
import libxmljs from "libxmljs2";

const app = express();
app.post("/api/import", express.text({ type: "application/xml" }), (req, res) => {
  const doc = libxmljs.parseXml(req.body, { noent: true }); // 개체를 치환하며 외부 개체도 읽는다
  res.json({ name: doc.get("/order/customer/name")?.text() ?? null });
});
```

✓ 안전 코드

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

✗ 취약 코드

```php
<?php
$xml = file_get_contents('php://input');
$doc = new DOMDocument();
$doc->loadXML($xml, LIBXML_NOENT | LIBXML_DTDLOAD);   // 개체 치환과 외부 DTD 로딩을 켠다
header('Content-Type: application/json');
echo json_encode(['name' => $doc->getElementsByTagName('name')->item(0)?->textContent]);
```

✓ 안전 코드

```php
<?php
$xml = file_get_contents('php://input', false, null, 0, 1_000_001);
if ($xml === false || $xml === '') { http_response_code(400); exit; }   // 빈 문서는 loadXML 이 ValueError 를 낸다
if (strlen($xml) > 1_000_000) { http_response_code(413); exit; }
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
