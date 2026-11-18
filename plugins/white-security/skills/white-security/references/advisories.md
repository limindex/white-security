# Watch list — critical CVEs in common stacks

Quick comparison when scanners can't run. This is not a full database: the real check is `deps.md`. At most 10 entries; each is removed or re-checked after its expiry date. If today is past an entry's expiry, still use it but say "감시 목록 만료, 스캐너로 확인 필요".

Grade a match as 위험 (`WS-SUP-002`), unless the condition column says the vulnerable feature isn't used.

## Java4Shell — CVE-2026-114938 (Java HotSpot JVM)
- Published: 2026-11-17 · CVSS 3.1 8.1 (Red Hat preliminary 9.8) · CISA alert 2026-11-17
- Affects: HotSpot JVM on x86-64 (Base64 decoding path; Red Hat: OpenJDK 17.0.6 and later on CPUs with AVX-512 VBMI). Oracle lists Java SE 8, 17, 21, 25, 27 and GraalVM for JDK 17/21 builds.
- Check: `java -version`, Docker `FROM` images with a JDK/JRE (`eclipse-temurin`, `openjdk`, `amazoncorretto`), and JVMs bundled in other apps.
- Fixed: Oracle Critical Security Patch Update November 2026; Amazon Corretto 27.0.1.1.4. Other vendors: follow their advisory. Until patched (CISA): validate Base64 input size at the boundary, limit unauthenticated access.
- Expires: 2027-05-31
- Sources: https://www.cve.org/CVERecord?id=CVE-2026-114938 · https://www.cisa.gov/news-events/alerts/2026/11/17/critical-java-vulnerability-requires-immediate-patching · https://access.redhat.com/security/cve/cve-2026-114938

## Next.js middleware auth bypass — CVE-2025-29927
- Published: 2025-03-21 · CVSS 3.1 9.1
- Affects: `next` 12.0.0–12.3.4, 13.0.0–13.5.8, 14.0.0–14.2.24, 15.0.0–15.2.2 when auth is done in middleware.
- Check: `next` version in `package-lock.json`/`pnpm-lock.yaml`/`yarn.lock`.
- Fixed: 12.3.5, 13.5.9, 14.2.25, 15.2.3. Can't upgrade: strip the `x-middleware-subrequest` header at the proxy/CDN.
- Expires: 2027-03-31
- Source: https://github.com/advisories/GHSA-f82v-jwr5-mffw

## PyTorch `torch.load` RCE even with `weights_only=True` — CVE-2025-32434
- Published: 2025-04-17 · CVSS 4.0 9.3
- Affects: `torch` < 2.6.0 when loading model files from outside.
- Check: `torch` in `requirements.txt`/`pyproject.toml`/lockfile, `pip show torch`.
- Fixed: 2.6.0. Prefer `safetensors`.
- Expires: 2027-03-31
- Source: https://github.com/advisories/GHSA-53q9-r3pm-6pq6

## lodash `_.template` code injection — CVE-2026-4800
- Published: 2026-03-31 · CVSS 3.1 8.1
- Affects: `lodash`, `lodash-es`, `lodash-amd` 4.0.0–4.17.23; `lodash.template` 4.0.0 up to (not incl.) 4.18.0 — when `options.imports` key names can come from outside.
- Check: lockfile, and vendored copies (`lodash.js` / `lodash.min.js` with a version banner).
- Fixed: 4.18.0. Until then, only static, developer-written `imports` keys.
- Expires: 2027-03-31
- Source: https://github.com/advisories/GHSA-r5fr-rjxr-66jc

## Python `tarfile` extraction filter bypass — CVE-2025-4517
- Published: 2025-06-03 · CVSS 3.1 9.4
- Affects: CPython before 3.9.23, 3.10.18, 3.11.13, 3.12.11, 3.13.4 (and 3.14 pre-releases before 3.14.0b3) when extracting untrusted tar files with `filter="data"` or `"tar"`.
- Check: `python3 --version`, `python_requires`, Docker `FROM python:` tags; code calling `extractall(`/`extract(` on uploaded or downloaded archives.
- Fixed: 3.9.23, 3.10.18, 3.11.13, 3.12.11, 3.13.4, 3.14.0b3.
- Expires: 2027-03-31
- Source: https://www.cve.org/CVERecord?id=CVE-2025-4517

## tj-actions/changed-files compromise — CVE-2025-30066 (CISA KEV)
- Published: 2025-03-15 · CVSS 3.1 8.6 · KEV added 2025-03-18
- Affects: GitHub Actions workflows using `tj-actions/changed-files` before v46 (tags v1–v45.0.7 were repointed 2025-03-14/15); CI secrets could leak into logs.
- Check: `grep -rn "tj-actions/changed-files" .github/workflows`.
- Fixed: v46 or later, pinned to a full commit SHA. Rotate secrets used by runs in that window.
- Expires: 2027-03-31
- Source: https://www.cisa.gov/news-events/alerts/2025/03/18/supply-chain-compromise-third-party-tj-actionschanged-files-cve-2025-30066-and-reviewdogaction
