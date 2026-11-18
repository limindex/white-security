<!-- generated from white-security issues; do not edit -->
# WS-SUP-004 Lockfile Integrity (잠금 파일·무결성 해시 없음)

## 탐지 규칙

- **찾는 곳**: `npm install`, `npm ci`, `--frozen-lockfile`, `--require-hashes`, `--extra-index-url`, `"resolved":`, `composer update`, `uv sync`, 그 밖에 Dockerfile의 RUN 설치 줄, CI 설치 단계, .gitignore 속 잠금 파일, .npmrc·pip.conf의 레지스트리 설정.
- **검사 1 — 잠금 파일 없음·무시**: 지적할 때: 앱 저장소에 잠금 파일이 없거나 .gitignore로 빠진 경우, CI·Dockerfile이 `npm install`·`pnpm install --no-frozen-lockfile`·`composer update`나 버전 고정(`==`)이 빠진 요구 사항 파일로 `pip install -r` 하는 경우. / 지적하지 않을 때: `npm ci`·`pnpm install --frozen-lockfile`·`uv sync --locked`·`composer install`, 일부러 잠금 파일을 두지 않는 라이브러리 저장소(참고로만).
- **검사 2 — 잠금 파일 바꿔치기**: 지적할 때: 잠금 파일의 `resolved`가 공식 레지스트리가 아닌 주소·`http://`·깃 주소로 바뀐 경우, `integrity`가 빠진 항목, 의존성 변경이 없는데 잠금 파일만 크게 바뀐 PR. / 지적하지 않을 때: 설정 파일에 근거가 있는 사내 미러 주소로 일관되게 바뀐 경우.
- **검사 3 — 섞인 출처**: 지적할 때: `--extra-index-url`이나 범위 지정 없는 여러 레지스트리 설정으로, 사내 패키지와 같은 이름을 공개 저장소에서도 받을 수 있는 경우(Dependency Confusion, 의존성 혼동). / 지적하지 않을 때: `--index-url` 하나만 쓰거나 npm 범위(`@회사/`)별로 레지스트리를 고정한 경우.
- **등급**: 위험: 잠금 파일 주소가 낯선 서버·http로 바뀜, 사내 패키지 이름을 공개 저장소에서도 찾는 설정. / 주의: 잠금 파일 없음·커밋 안 함, CI·Docker가 잠금 파일 무시, 버전 고정 없는 requirements. / 참고: 모든 줄이 `==`로 고정됐지만 해시가 없는 requirements, 라이브러리 저장소의 잠금 파일 정책.
- **보고 예**: `[주의] WS-SUP-004 Dockerfile:5 — RUN npm install 로 잠금 파일과 다른 버전이 설치될 수 있음 → package*.json 만 먼저 복사하고 RUN npm ci`
- **고칠 방법**: 잠금 파일을 커밋하고 모든 자동 설치를 잠금 파일 고정 명령으로 바꾸며, 잠금 파일 주소·해시 검사를 CI에 넣어야 한다.

## 점검 항목

- 앱 저장소는 잠금 파일을 커밋해야 한다.
- CI·Docker는 잠금 파일대로만 설치해야 한다(`npm ci`·`uv sync --locked`·`composer install`).
- 파이썬 요구 사항은 해시까지 고정하고 `--require-hashes`로 설치해야 한다.
- 잠금 파일 변경은 리뷰에서 바뀐 주소·해시를 확인해야 한다.
- 패키지 저장소는 공식·사내 미러 하나로 고정하고 출처를 섞지 않아야 한다.

## 검토 주의

새 패키지 이름의 실재 확인은 WS-SUP-001, 취약한 버전은 WS-SUP-002, 설치 스크립트는 WS-SUP-003, CI 액션·외부 스크립트 다운로드는 WS-SUP-005에서 다룬다. `npm ci`는 잠금 파일과 `package.json`이 맞지 않으면 설치하지 않고 실패하며, `composer install`은 잠금 파일이 오래돼도 경고만 하므로 `composer validate`(잠금 파일이 최신이 아니면 오류)를 함께 돌리라고 안내해야 한다.

Code examples (read only the file for the stack you are writing): `WS-SUP-004.code.yaml.md` (YAML) · `WS-SUP-004.code.dockerfile.md` (Dockerfile)

기본 심각도: 주의
기준: OWASP Top 10:2025 A03(소프트웨어 공급망 실패) · OWASP Top 10:2025 A08(소프트웨어·데이터 무결성 실패) · ASVS 5.0 V15.1.2 · ASVS 5.0 V15.2.4 · CWE-494 · CWE-353 · CWE-829 · 국내 보안약점 49 보안기능-15(무결성 검사 없는 코드 다운로드)
