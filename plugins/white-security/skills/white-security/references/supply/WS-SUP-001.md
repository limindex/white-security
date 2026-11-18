<!-- generated from white-security issues; do not edit -->
# WS-SUP-001 Slopsquatting (패키지 환각)

심각도: 위험
기준: OWASP Top 10:2025 A03(소프트웨어 공급망 실패) · OWASP LLM 2026 LLM07(잘못된 정보) · OWASP 에이전트 2026 ASI05(예상치 못한 코드 실행) · ASVS 5.0 V15.2 · 국내 보안약점 49 보안기능-15(무결성 검사 없는 코드 다운로드)

## 점검 항목

- 새 의존성은 설치 전에 레지스트리에 실제로 있는지 확인해야 한다(PyPI `https://pypi.org/pypi/<이름>/json`, npm `https://registry.npmjs.org/<이름>`, Packagist `https://packagist.org/packages/<vendor>/<이름>.json` 이 404면 없는 패키지). 처음 등록일이 최근(기본 90일 이내)이거나 내려받은 수가 매우 적으면 멈추고 사람에게 물어야 한다.
- 유명 패키지와 한두 글자, 하이픈·밑줄, 단·복수만 다른 이름이 최근 등록·적은 내려받기·공식 문서와 다른 저장소 같은 신호와 함께 보이면 멈춰야 한다. 이름이 비슷하다는 것만으로는 막지 않음(`react`·`preact`처럼 정상인 경우가 많음). 저장소 링크·관리자가 그 도구의 공식 문서와 같은지도 확인해야 한다.
- 잠금 파일(`package-lock.json`, `poetry.lock`·`uv.lock`·해시를 고정한 `requirements.txt`, `composer.lock`)을 커밋하고, CI와 배포는 잠금 파일대로만 설치해야 한다(`npm ci`, `pip install --require-hashes -r requirements.txt`, `composer install`).
- 처음 들이는 패키지는 설치 중 코드가 돌지 않게 받아야 한다(`npm install --ignore-scripts`, `pip install --only-binary :all:`). Composer는 의존성 패키지의 `scripts`는 실행하지 않지만 플러그인은 실행하므로 `composer require --no-plugins`로 받고, `composer.json`의 `config.allow-plugins`에는 허용한 플러그인만 둬야 한다.
- 에이전트의 설치 명령(`npm install`, `pip install`, `composer require`)은 자동 승인 목록에 넣지 않아야 한다.
- 사내 전용 패키지 이름이 공개 레지스트리에서 비어 있어 남이 먼저 등록할 수 있는지(Dependency Confusion, 의존성 혼동) 확인하고, 레지스트리 범위를 고정해야 한다.

## 탐지 규칙

- **찾는 곳**: 이번 변경에서 추가된 의존성(`package.json`, `requirements*.txt`, `pyproject.toml`, `composer.json`의 차이), 코드의 새 import·require·use, 에이전트가 실행하려는 설치 명령(`npm install`, `pip install`, `composer require`), 에이전트 설정의 허용 목록(예: `.claude/settings.json`의 `Bash(npm install:*)`).
- **검사 1 — 실재 확인(모델 기억이 아니라 레지스트리 실제 조회, 조회하지 못하면 안전 판단 대신 "확인 못 함")**: 지적할 때: 조회 결과 404, 처음 등록일 90일 이내, 유명 패키지와 편집 거리 1~2이면서 다른 신호(90일 이내 등록, 매우 적은 내려받기, 공식 문서와 다른 저장소 링크)가 함께 있는 경우. / 지적하지 않을 때: 이미 잠금 파일에 있고 이번 변경에서 이름이 바뀌지 않은 의존성, 표준 라이브러리 모듈, 워크스페이스 안 패키지(`file:`, `workspace:`, `path` 저장소), 다른 신호 없이 이름만 비슷한 정상 짝(`react`·`preact`, `vue`·`vite`, `qs`·`ms`).
- **검사 2 — 잠금 파일**: 지적할 때: 매니페스트는 바뀌었는데 잠금 파일이 없거나 함께 바뀌지 않은 경우. CI가 `npm ci` 대신 `npm install`로 설치하는 경우. / 지적하지 않을 때: 라이브러리 저장소라 잠금 파일을 일부러 두지 않고 그 이유가 문서에 있는 경우(참고로만 기록).
- **검사 3 — 자동 설치**: 지적할 때: 코드나 에이전트 설정이 모델이 제안한 이름을 확인 없이 설치하는 경우(`subprocess.run(["pip", "install", name])`, `` exec(`npm install ${name}`) ``, 설치 명령 자동 승인). / 지적하지 않을 때: 설치 전에 레지스트리 조회와 사람 승인이 있는 경우.
- **등급**: 위험: 레지스트리에 없는 이름(404) 설치 시도. 90일 이내 등록이고 공식 문서·저장소 링크로 확인되지 않는데 설치 명령이 실행될 수 있는 경우. 유명 패키지와 비슷한 이름에 다른 신호가 함께 있는 경우. / 주의: 90일 이내 등록이지만 공식 문서 설치 안내·저장소 링크와 맞는 경우(새 공식 패키지일 수 있음). 잠금 파일 없음. 설치 명령 자동 승인. 설치 스크립트(Composer는 플러그인)가 켜진 채 처음 보는 패키지 설치. / 참고: 이름만 유명 패키지와 비슷함("확인 필요"), 내려받은 수만 적음, 조회는 됐지만 저장소 링크 없음.
- **보고 예**: `[위험] WS-SUP-001 package.json:14 — "<이름>" 이 npm 레지스트리에 없음(404) → 원래 쓰려던 기능의 공식 문서에서 패키지 이름을 다시 확인하고, 없으면 의존성 제거`
- **고칠 방법**: 비슷한 이름을 새로 지어내 바꿔 넣지 않고 공식 문서에서 이름을 확인해 사용자 승인을 받아야 하며, 정확한 버전으로 고정하고 잠금 파일을 함께 커밋해야 한다.

Code examples (read only the file for the stack you are writing): `WS-SUP-001.code.python.md` (Python) · `WS-SUP-001.code.js.md` (JavaScript) · `WS-SUP-001.code.php.md` (PHP)
