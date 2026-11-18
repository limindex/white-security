<!-- generated from white-security issues; do not edit -->
# WS-DES-001 Insecure Deserialization (안전하지 않은 역직렬화)

## 탐지 규칙

- **찾는 곳**: `pickle.load(s)`, `yaml.load(`·`yaml.unsafe_load`, `unserialize(`, `node-serialize`, `serialize-javascript`를 되살릴 때의 eval, `joblib.load`, `jsonpickle.decode`, `cPickle`, `dill.load`, `ObjectInputStream`, `XMLDecoder`, `BinaryFormatter`.
- **검사 1 — 바깥 데이터의 역직렬화**: 지적할 때: 요청·쿠키·파일·큐·외부 API에서 온 값이 위 함수로 들어가는 경우. / 지적하지 않을 때: 서버가 만들고 서버만 쓰는 파일(배포 산출물, 사내 캐시)이고 외부에서 쓸 수 없는 경우(참고로 JSON 전환 권장).
- **검사 2 — YAML 로더**: 지적할 때: `yaml.load(x)`에 `Loader`가 없거나 `Loader=yaml.Loader`·`UnsafeLoader`인 경우. `yaml.unsafe_load`를 쓰는 경우. / 지적하지 않을 때: `yaml.safe_load`나 `Loader=yaml.SafeLoader`인 경우. `FullLoader`는 참고로만 `safe_load` 권장.
- **등급**: 위험: 요청·쿠키·업로드 데이터가 `pickle`·`unserialize`·`node-serialize`·안전하지 않은 YAML 로더로 들어가는 경우. / 주의: 서명은 확인하지만 여전히 `pickle`·`unserialize`를 쓰는 경우(키가 새면 바로 뚫림). / 참고: 서버 내부 데이터에 `pickle`을 쓰는 경우.
- **보고 예**: `[위험] WS-DES-001 shop/cart.py:19 — cart 쿠키를 pickle.loads 로 되살림 → JSON + HMAC 서명 확인, 또는 쿠키에는 장바구니 ID만`

## 점검 항목

- 남이 준 데이터(쿠키·요청 본문·큐 메시지·업로드 파일)는 `pickle`, 안전 로더 없는 `yaml.load`, PHP `unserialize`, Java `ObjectInputStream`, `node-serialize`로 되살리지 않고 JSON을 써야 한다.
- YAML은 `yaml.safe_load`를 써야 한다.
- PHP `unserialize`에는 `allowed_classes` 옵션과 관계없이 믿을 수 없는 입력을 넣지 않아야 한다(PHP 공식 문서의 경고).
- 서버가 만든 값을 클라이언트에 맡겨야 하면 HMAC 서명(서버 비밀 키로 만든 확인값)을 붙이고 되살리기 전에 확인해야 한다.
- JSON으로 읽은 뒤에도 타입·필드를 검사하고, 쓸 값만 꺼내야 한다.
- AI 모델 파일(`.pkl`, `.pt` 등)도 pickle일 수 있음. 모델 공급망은 WS-SUP-008 범위.

Code examples (read only the file for the stack you are writing): `WS-DES-001.code.python.md` (Python) · `WS-DES-001.code.js.md` (JavaScript) · `WS-DES-001.code.php.md` (PHP)

기본 심각도: 위험
기준: OWASP Top 10:2025 A08(소프트웨어·데이터 무결성 실패) · ASVS 5.0 V1.5.2 · CWE-502(신뢰할 수 없는 데이터의 역직렬화) · 국내 보안약점 49 코드오류-5(신뢰할 수 없는 데이터의 역직렬화)
