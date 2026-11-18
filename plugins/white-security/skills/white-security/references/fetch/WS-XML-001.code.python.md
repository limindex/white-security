<!-- generated from white-security issues; do not edit -->
# WS-XML-001 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
from lxml import etree

def import_order(xml_bytes: bytes):
    parser = etree.XMLParser(resolve_entities=True, load_dtd=True, no_network=False)
    root = etree.fromstring(xml_bytes, parser)   # 문서 속 DTD가 서버 파일·내부 주소를 불러온다
    return root.findtext("customer/name")
```

✓ 안전 코드 (safe example)

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
