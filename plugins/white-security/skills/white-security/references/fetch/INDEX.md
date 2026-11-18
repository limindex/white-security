<!-- generated from white-security issues; do not edit -->
# fetch · 외부 데이터 수집·처리

When: 외부 URL·파일·XML·압축 파일을 가져와 읽거나 파싱하는 경우
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.

- `pickle.load(s)` `yaml.load(` `yaml.unsafe_load` `unserialize(` `node-serialize` `serialize-javascript` `joblib.load` `jsonpickle.decode` → WS-DES-001 Insecure Deserialization
- `PIL.Image.open` `Image.MAX_IMAGE_PIXELS` `Imagick` `imagecreatefrom*` `getimagesize` `subprocess.` `magick` `gs -` → WS-FILE-001 Decompression Bomb · 파서 취약점
- `requests.get(` `httpx.` `urllib.request.urlopen(` `fetch(` `axios(` `file_get_contents(` `curl_init(` `page.goto(` → WS-SSRF-001 SSRF (Server-Side Request Forgery)
- `etree.fromstring(` `lxml.etree.XMLParser(` `xml.etree` `simplexml_load_string(` `loadXML(` `LIBXML_NOENT` `libxmljs` `fast-xml-parser` → WS-XML-001 XXE (XML External Entity) · Billion Laughs
- `zipfile` `tarfile.open(` `shutil.unpack_archive` `ZipArchive` `PharData` `adm-zip` `yauzl` `unzipper` → WS-ZIP-001 Zip Slip · Zip Bomb
