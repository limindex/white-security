<!-- generated from white-security issues; do not edit -->
# supply · 소프트웨어 공급망

When: 패키지 설치·업데이트·외부 코드와 모델 사용
Signals are candidates, not findings: open the matching `<ID>.md` here (max 2 per task) and decide with its checks (constant or request value?).
Stacks: Python · FastAPI · JavaScript · Next.js · PHP · Laravel · Shell · JSON · YAML · Dockerfile

- `package.json` `requirements*.txt` `pyproject.toml` `composer.json` `npm install` `pip install` `composer require` `.claude/settings.json` → WS-SUP-001 Slopsquatting
- `package-lock.json` `pnpm-lock.yaml` `uv.lock` `poetry.lock` `composer.lock` `dependabot.yml` `renovate.json` `npm audit fix --force` → WS-SUP-002 Vulnerable Dependencies
- `postinstall` `preinstall` `ignore-scripts` `allowBuilds` `dangerouslyAllowAllBuilds` `onlyBuiltDependencies` `allow-plugins` `--only-binary` → WS-SUP-003 Install Scripts (postinstall)·Build Plugins
- `npm install` `npm ci` `--frozen-lockfile` `--require-hashes` `--extra-index-url` `"resolved":` `composer update` `uv sync` → WS-SUP-004 Lockfile Integrity
- `pull_request_target` `workflow_run` `${{ github.event.` `${{ github.head_ref` `write-all` `persist-credentials` `| bash` `ADD http` → WS-SUP-005 CI/CD Integrity · Unverified Downloads (GitHub Actions, curl | sh)
- `jquery-1.` `jquery-2.` `/*! jQuery v` `public/vendor/` `static/vendor/` `third_party/` `"github:` `"overrides"` → WS-SUP-006 Vendored·Copied Code
- `requests.get(` `requests.post(` `httpx.` `axios.get(` `Http::get(` `allow_redirects` `follow_redirects=True` `AbortSignal.timeout(` → WS-SUP-007 Unsafe Consumption of APIs
- `torch.load(` `torch==2.` `weights_only=False` `trust_remote_code=True` `from_pretrained(` `hf_hub_download(` `snapshot_download(` `load_dataset(` → WS-SUP-008 Model Supply Chain
