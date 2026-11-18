<!-- generated from white-security issues; do not edit -->
# supply · 소프트웨어 공급망

- `package.json` `requirements*.txt` `pyproject.toml` `composer.json` `npm install` `pip install` `composer require` `.claude/settings.json` → WS-SUP-001: 레지스트리에 없는 이름 설치
- `package-lock.json` `pnpm-lock.yaml` `uv.lock` `poetry.lock` `composer.lock` `dependabot.yml` `renovate.json` `npm audit fix --force` → WS-SUP-002: KEV·원격 코드 실행·인증 우회 취약 버전
- `postinstall` `preinstall` `yarnPath` `ignore-scripts` `allowBuilds` `dangerouslyAllowAllBuilds` `onlyBuiltDependencies` `allow-plugins` → WS-SUP-003: 비밀값 있는 환경에서 낯선 설치 스크립트 실행
- `npm install` `npm ci` `--frozen-lockfile` `--extra-index-url` `_authToken=${` `"resolved":` `--require-hashes` `npmAuthToken` → WS-SUP-004: 잠금 파일 주소가 낯선 서버·http로 바뀜
- `pull_request_target` `workflow_run` `${{ github.event.` `${{ github.head_ref` `write-all` `persist-credentials` `| bash` `ADD http` → WS-SUP-005: pull_request_target에서 PR 코드 실행, run:에 입력
- `jquery-1.` `jquery-2.` `/*! jQuery v` `public/vendor/` `static/vendor/` `third_party/` `"github:` `"overrides"` → WS-SUP-006: 알려진 XSS·RCE 버전 복사본을 운영에서 사용
- `requests.get(` `requests.post(` `httpx.` `axios.get(` `Http::get(` `allow_redirects` `follow_redirects=True` `AbortSignal.timeout(` → WS-SUP-007: 외부 응답이 SQL·HTML·명령으로, 실패 때 허용
- `torch.load(` `torch==2.` `weights_only=False` `trust_remote_code=True` `from_pretrained(` `hf_hub_download(` `snapshot_download(` `load_dataset(` → WS-SUP-008: 외부 모델을 pickle·weights_only=False로 열기
