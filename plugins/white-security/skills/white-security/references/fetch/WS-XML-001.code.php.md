<!-- generated from white-security issues; do not edit -->
# WS-XML-001 코드 예시 — php

✗ 취약 코드 (vulnerable example — do not use)

```php
<?php
$xml = file_get_contents('php://input');
$doc = new DOMDocument();
$doc->loadXML($xml, LIBXML_NOENT | LIBXML_DTDLOAD);   // 개체 치환과 외부 DTD 로딩을 켠다
header('Content-Type: application/json');
echo json_encode(['name' => $doc->getElementsByTagName('name')->item(0)?->textContent]);
```

✓ 안전 코드 (safe example)

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
