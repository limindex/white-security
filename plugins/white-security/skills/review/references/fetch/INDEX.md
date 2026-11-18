<!-- generated from white-security issues; do not edit -->
# fetch · 외부 데이터 수집·처리

- `pickle.load(s)` `yaml.load(` `yaml.unsafe_load` `unserialize(` `node-serialize` `serialize-javascript` `joblib.load` `jsonpickle.decode` → WS-DES-001: 요청 데이터를 pickle·unserialize·unsafe YAML로
- `PIL.Image.open` `Image.MAX_IMAGE_PIXELS` `Imagick` `imagecreatefrom*` `getimagesize` `subprocess.` `magick` `gs -` → WS-FILE-001: 남의 파일을 정책 없는 ImageMagick·Ghostscript로 처리
- `requests.get(` `httpx.` `urllib.request.urlopen(` `fetch(` `axios(` `file_get_contents(` `curl_init(` `page.goto(` → WS-SSRF-001: 사용자·AI가 준 URL을 검사 없이 서버가 엶
- `etree.fromstring(` `lxml.etree.XMLParser(` `xml.etree` `simplexml_load_string(` `loadXML(` `LIBXML_NOENT` `libxmljs` `fast-xml-parser` → WS-XML-001: 외부 XML에 외부 개체 해석 켜짐
- `zipfile` `tarfile.open(` `shutil.unpack_archive` `ZipArchive` `PharData` `adm-zip` `yauzl` `unzipper` → WS-ZIP-001: 남이 올린 압축을 경로 검사 없이 풂
