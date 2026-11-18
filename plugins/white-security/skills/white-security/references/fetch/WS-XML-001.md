<!-- generated from white-security issues; do not edit -->
# WS-XML-001 XXE · Billion Laughs

심각도: 위험
기준: OWASP Top 10:2025 A02(보안 설정 오류) · ASVS 5.0 V1.5 · CWE-611 · CWE-776 · 국내 보안약점 49 입력데이터 검증 및 표현-8(부적절한 XML 외부 개체 참조)

## 점검 항목

- 외부에서 온 XML에 DTD(`<!DOCTYPE`)가 있으면 거절해야 한다. DTD가 꼭 필요하면 외부 개체·외부 DTD 로딩을 끄고 개체 확장 한도를 둬야 하며, XInclude와 XSLT의 외부 문서 불러오기도 꺼야 한다.
- Python: 외부 XML은 표준 `xml.*` 대신 `defusedxml`(`forbid_dtd=True`)로 읽어야 한다. lxml은 `resolve_entities=False`, `no_network=True`, `load_dtd=False`로 둬야 하고, 표준 모듈을 써야 하면 `pyexpat.EXPAT_VERSION`이 2.7.2 이상인지 확인해야 한다.
- PHP: 외부 XML에 `LIBXML_NOENT`, `LIBXML_DTDLOAD`, `LIBXML_DTDVALID`, `LIBXML_PARSEHUGE`를 쓰지 않아야 한다. PHP 8.4 이상·libxml 2.13 이상이면 `LIBXML_NO_XXE` 사용 가능. `libxml_disable_entity_loader()`는 PHP 8.0부터 폐기된 함수라 기대지 않아야 한다.
- Node: `libxmljs`에서 `noent: true`를 쓰지 않아야 하고, `fast-xml-parser`는 `processEntities: false`로 둬야 한다.
- 요청 본문·업로드 파일·압축을 푼 뒤의 크기에 각각 상한을 둬야 하고, 파서 오류 원문은 응답에 넣지 않아야 한다(읽어 온 파일 내용이 오류 문장에 섞여 나올 수 있음).
- SVG, 오피스 파일, RSS, SAML 응답처럼 XML인 줄 모르고 다루는 형식도 같은 파서 설정을 거쳐야 한다.

## 탐지 규칙

- **찾는 곳**: XML 파싱 호출과 입력 출처(Python `etree.fromstring(`, `lxml.etree.XMLParser(`, `xml.etree` · PHP `simplexml_load_string(`, `loadXML(`, `LIBXML_NOENT` · Node `libxmljs`, `fast-xml-parser` · 그 밖에 Python `xml.dom.minidom`, `xml.sax`, `etree.parse(` · PHP `DOMDocument::load(`, `simplexml_load_file(`, `XMLReader` · Node `parseXml(`, `xml2js`, `@xmldom/xmldom`).
- **검사 1 — 외부 개체를 따라가는 설정**: 지적할 때: 외부 입력 XML을 외부 개체 해석이 켜진 설정으로 파싱하는 경우(lxml `resolve_entities=True`나 `no_network=False`와 `load_dtd=True`의 조합, PHP `LIBXML_NOENT`·`LIBXML_DTDLOAD`, libxmljs `noent: true`, `xml.sax`의 `feature_external_ges` 켬). / 지적하지 않을 때: 입력이 저장소 안 고정 파일이나 빌드 산출물이라 외부에서 바꿀 수 없는 경우. 외부 개체가 기본으로 꺼진 라이브러리를 기본 설정 그대로 쓰는 경우.
- **검사 2 — 엔티티 폭탄**: 지적할 때: 외부 입력을 Python 표준 `xml.*`로 파싱하면서 `defusedxml`도 Expat 버전 확인도 없는 경우(Expat 버전에 따라 달라 주의 + "확인 필요"). PHP `LIBXML_PARSEHUGE`를 외부 입력에 쓰는 경우. / 지적하지 않을 때: DTD를 거절하거나, `defusedxml`을 쓰거나, `fast-xml-parser`를 `processEntities: false`로 쓰는 경우.
- **검사 3 — 크기 상한·오류 노출**: 지적할 때: 외부 XML을 크기 제한 없이 메모리에 올리거나, 파서 오류 문장을 그대로 응답에 넣는 경우. / 지적하지 않을 때: 본문 크기 상한(`limit`, 길이 인자)이 있고 오류를 일반 문구로 바꿔 내보내는 경우.
- **등급**: 위험: 외부 입력에 외부 개체 해석이 켜진 경우. / 주의: 외부 입력인데 엔티티 확장 한도를 알 수 없는 경우. `fast-xml-parser`를 `processEntities` 기본값(켜짐)으로 외부 입력에 쓰는 경우. / 참고: 크기 상한 없음, 오류 원문 노출.
- **보고 예**: `[위험] WS-XML-001 api/import.py:27 — 업로드한 XML을 resolve_entities=True 인 lxml 파서로 읽음(XXE) → defusedxml.ElementTree.fromstring(data, forbid_dtd=True) 로 바꿈`
- **고칠 방법**: 정상 문서에 DTD가 정말 필요한지 먼저 확인하고(대부분 불필요) DTD 거절을 기본으로 둬야 한다. 보고에는 XXE면 CWE-611, 엔티티 폭탄이면 CWE-776을 함께 적어야 한다.

Code examples (read only the file for the language you are writing): `WS-XML-001.code.python.md` · `WS-XML-001.code.js.md` · `WS-XML-001.code.php.md`
