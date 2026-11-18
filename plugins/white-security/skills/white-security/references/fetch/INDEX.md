<!-- generated from white-security issues; do not edit -->
# fetch · 외부 데이터 수집·처리

When: 외부 URL·파일·XML·압축 파일을 가져와 읽거나 파싱하는 경우
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.

- `pickle.load(s)` `joblib.load` `yaml.load(` `yaml.unsafe_load` `jsonpickle.decode` `unserialize(` `node-serialize` `serialize-javascript` → WS-DES-001 Insecure Deserialization
- `PIL.Image.open` `Image.MAX_IMAGE_PIXELS` `wand.image` `subprocess.` `gs -` `pdf.js` `pdf-parse` `sharp(` → WS-FILE-001 Decompression Bomb · 파서 취약점
- `requests.get(` `httpx.` `urllib.request.urlopen(` `fetch(` `http.get(` `file_get_contents(` `curl_init(` `page.goto(` → WS-SSRF-001 SSRF (Server-Side Request Forgery)
- `xml.dom.minidom` `lxml.etree.XMLParser(` `etree.fromstring(` `DOMDocument::load(` `simplexml_load_string(` `simplexml_load_file(` `fast-xml-parser` `@xmldom/xmldom` → WS-XML-001 XXE (XML External Entity) · Billion Laughs
- `tarfile.open(` `shutil.unpack_archive` `ZipArchive` `PharData` `adm-zip` `tar.x(` `tar.extract(` `tar -x` → WS-ZIP-001 Zip Slip · Zip Bomb
