# Watch list — critical CVEs in common stacks

Quick comparison when scanners can't run. Each entry's `Watch:` line (and `Expires:` date) is copied into SKILL.md by `build.php`, so the review never has to open this file — keep that line to one short sentence. This is not a full database: the real check is `deps.md`. At most 10 entries; each is removed or re-checked after its expiry date. If today is past an entry's expiry, still use it but say "감시 목록 만료, 스캐너로 확인 필요".

Grade a match as 위험 (`WS-SUP-002`), unless the entry's "Affects" line names a condition (feature, CPU, usage) that the project clearly doesn't meet — then don't report it. If you can't tell whether the condition is met, grade `주의 — 확인 필요`.

## Java4Shell — CVE-2026-114938 (Java HotSpot JVM)
- Published: 2026-11-17 · CVSS 3.1 8.1 (Red Hat preliminary 9.8) · CISA alert 2026-11-17
- Watch: Java HotSpot JVM on x86-64 with AVX-512 VBMI (Red Hat: OpenJDK 17.0.6+); CPU unknown → 주의 → November 2026 patch
- Affects: HotSpot JVM on x86-64 (Base64 decoding path; Red Hat: OpenJDK 17.0.6 and later on CPUs with AVX-512 VBMI). Oracle lists Java SE 8u511b33-perf, 17.0.21.0.3, 21.0.13.0.3, 25.0.5.0.3, 27.0.1.0.3, GraalVM for JDK 17.0.21.0.3 / 21.0.13.0.3 and GraalVM Enterprise Edition 21.3.20.0.3.
- CPU: per Red Hat, ARM64 and x86-64 CPUs without AVX-512 VBMI have no known impact (known to run only there → no finding). Server CPU unknown (usual for a repo review) → `주의 — 확인 필요`.
- Check: `java -version`, Docker `FROM` images with a JDK/JRE (`eclipse-temurin`, `openjdk`, `amazoncorretto`), and JVMs bundled in other apps.
- Fixed: Oracle Critical Security Patch Update November 2026; Amazon Corretto 27.0.1.1.4. Other vendors: follow their advisory. Until patched (CISA): validate Base64 input size at the boundary, limit unauthenticated access.
- Expires: 2027-05-31
- Sources: https://www.cve.org/CVERecord?id=CVE-2026-114938 · https://www.cisa.gov/news-events/alerts/2026/11/17/critical-java-vulnerability-requires-immediate-patching · https://access.redhat.com/security/cve/cve-2026-114938

## React2Shell — React Server Components RCE — CVE-2025-55182 (CISA KEV)
- Published: 2025-12-03 · CVSS 3.1 10.0 · KEV added 2025-12-05
- Watch: `react-server-dom-webpack`/`-parcel`/`-turbopack` 19.0.0, 19.1.0, 19.1.1, 19.2.0, or `next` 15.0–16.0 with App Router before its line's patch 15.0.5 / 15.1.9 / 15.2.6 / 15.3.6 / 15.4.8 / 15.5.7 / 16.0.7 (also 14.3.0-canary.77+ canaries) → that patch, `react-server-dom-*` 19.0.1 / 19.1.2 / 19.2.1; rotate secrets
- Affects: `react-server-dom-webpack`, `react-server-dom-parcel`, `react-server-dom-turbopack` 19.0.0, 19.1.0, 19.1.1, 19.2.0 (unauthenticated request to a Server Function endpoint; apps that support React Server Components may be affected even without Server Functions). Next.js (CVE-2025-66478, rejected by NVD as a duplicate of this CVE): 15.x, 16.x and 14.3.0-canary.77 and later canaries using the App Router. Not affected: Next.js 13.x, 14.x stable, Pages Router only, Edge Runtime, and apps without a React Server Components framework or bundler. Other affected frameworks per React: `react-router` (unstable RSC APIs), `waku`, `@parcel/rsc`, `@vitejs/plugin-rsc`, `rwsdk`.
- Check: `next` and `react-server-dom-*` versions in `package-lock.json`/`pnpm-lock.yaml`/`yarn.lock`; whether `app/` (App Router) exists.
- Fixed: `react-server-dom-*` 19.0.1, 19.1.2, 19.2.1; `next` 15.0.5, 15.1.9, 15.2.6, 15.3.6, 15.4.8, 15.5.7, 16.0.7, 15.6.0-canary.58, 16.1.0-canary.12; 14.3.0-canary.77+ → latest stable 14.x. No workaround. Prefer the latest patch in each line (React lists later releases that also fix follow-up DoS and source-exposure CVEs). Apps online and unpatched on 2025-12-04: rotate secrets after upgrading.
- Expires: 2027-06-30
- Sources: https://react.dev/blog/2025/12/03/critical-security-vulnerability-in-react-server-components · https://github.com/advisories/GHSA-fv66-9v8q-g76r · https://nextjs.org/blog/CVE-2025-66478 · https://github.com/vercel/next.js/security/advisories/GHSA-9qr9-h5gf-34mp · https://nvd.nist.gov/vuln/detail/CVE-2025-55182

## Next.js middleware auth bypass — CVE-2025-29927
- Published: 2025-03-21 · CVSS 3.1 9.1
- Watch: `next` 12.0.0–12.3.4, 13.0.0–13.5.8, 14.0.0–14.2.24, 15.0.0–15.2.2 with auth in middleware → 12.3.5 / 13.5.9 / 14.2.25 / 15.2.3
- Affects: `next` 12.0.0–12.3.4, 13.0.0–13.5.8, 14.0.0–14.2.24, 15.0.0–15.2.2 when auth is done in middleware.
- Check: `next` version in `package-lock.json`/`pnpm-lock.yaml`/`yarn.lock`.
- Fixed: 12.3.5, 13.5.9, 14.2.25, 15.2.3. Can't upgrade: strip the `x-middleware-subrequest` header at the proxy/CDN.
- Expires: 2027-03-31
- Source: https://github.com/advisories/GHSA-f82v-jwr5-mffw

## PyTorch `torch.load` RCE even with `weights_only=True` — CVE-2025-32434
- Published: 2025-04-17 · CVSS 4.0 9.3
- Watch: `torch` < 2.6.0 loading outside model files → 2.6.0
- Affects: `torch` < 2.6.0 when loading model files from outside.
- Check: `torch` in `requirements.txt`/`pyproject.toml`/lockfile, `pip show torch`.
- Fixed: 2.6.0. Prefer `safetensors`.
- Expires: 2027-03-31
- Source: https://github.com/advisories/GHSA-53q9-r3pm-6pq6

## lodash `_.template` code injection — CVE-2026-4800
- Published: 2026-03-31 · CVSS 3.1 8.1
- Watch: `lodash`/`lodash-es`/`lodash-amd` 4.0.0–4.17.23, `lodash.template` < 4.18.0, outside `imports` keys → 4.18.0
- Affects: `lodash`, `lodash-es`, `lodash-amd` 4.0.0–4.17.23; `lodash.template` 4.0.0 up to (not incl.) 4.18.0 — when `options.imports` key names can come from outside.
- Check: lockfile, and vendored copies (`lodash.js` / `lodash.min.js` with a version banner).
- Fixed: 4.18.0. Until then, only static, developer-written `imports` keys.
- Expires: 2027-03-31
- Source: https://github.com/advisories/GHSA-r5fr-rjxr-66jc

## Python `tarfile` extraction filter bypass — CVE-2025-4517
- Published: 2025-06-03 · CVSS 3.1 9.4
- Watch: CPython < 3.9.23 / 3.10.18 / 3.11.13 / 3.12.11 / 3.13.4 extracting untrusted tar with `filter=` → those versions
- Affects: CPython before 3.9.23, 3.10.18, 3.11.13, 3.12.11, 3.13.4 (and 3.14 pre-releases before 3.14.0b3) when extracting untrusted tar files with `filter="data"` or `"tar"`.
- Check: `python3 --version`, `python_requires`, Docker `FROM python:` tags; code calling `extractall(`/`extract(` on uploaded or downloaded archives.
- Fixed: 3.9.23, 3.10.18, 3.11.13, 3.12.11, 3.13.4, 3.14.0b3.
- Expires: 2027-03-31
- Source: https://www.cve.org/CVERecord?id=CVE-2025-4517

## tj-actions/changed-files compromise — CVE-2025-30066 (CISA KEV)
- Published: 2025-03-15 · CVSS 3.1 8.6 · KEV added 2025-03-18
- Watch: `tj-actions/changed-files` < v46 → v46+ pinned to a SHA; rotate CI secrets
- Affects: GitHub Actions workflows using `tj-actions/changed-files` before v46 (tags v1–v45.0.7 were repointed 2025-03-14/15); CI secrets could leak into logs.
- Check: `grep -rn "tj-actions/changed-files" .github/workflows`.
- Fixed: v46 or later, pinned to a full commit SHA. Rotate secrets used by runs in that window.
- Expires: 2027-03-31
- Source: https://www.cisa.gov/news-events/alerts/2025/03/18/supply-chain-compromise-third-party-tj-actionschanged-files-cve-2025-30066-and-reviewdogaction
