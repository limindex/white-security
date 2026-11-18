<!-- generated from white-security issues; do not edit -->
# WS-SUP-001 코드 예시 — JavaScript

✗ 취약 코드 (vulnerable example — do not use)

```js
import { exec } from "node:child_process";

// 에이전트 도구: 모델이 준 이름을 그대로 설치한다
export function installPackage(name) {
  exec(`npm install ${name}`);
}
```

✓ 안전 코드 (safe example)

```js
import { execFile } from "node:child_process";
import { promisify } from "node:util";

const NAME = /^(@[a-z0-9-~][a-z0-9-._~]*\/)?[a-z0-9-~][a-z0-9-._~]*$/;

export async function checkNpm(name, minAgeDays = 90) {
  if (!NAME.test(name)) return { ok: false, why: "이름 형식이 아님" };
  const res = await fetch(`https://registry.npmjs.org/${name.replace("/", "%2F")}`).catch(() => null);
  if (!res) return { ok: false, why: "확인 못 함(네트워크)" };
  if (res.status === 404) return { ok: false, why: "없음" };
  if (!res.ok) return { ok: false, why: `확인 못 함(HTTP ${res.status})` };
  const meta = await res.json();
  const created = new Date(meta.time?.created);
  if (Number.isNaN(created.getTime())) return { ok: false, why: "등록일 확인 못 함" };
  if (Date.now() - created.getTime() < minAgeDays * 86_400_000) {
    return { ok: false, why: `새 패키지(${created.toISOString().slice(0, 10)} 등록)` };
  }
  return { ok: true, latest: meta["dist-tags"]?.latest };
}

// 사람이 승인한 뒤에만, 정확한 버전으로, 설치 스크립트 없이 설치한다
export const installApproved = (name, version) =>
  promisify(execFile)("npm", ["install", "--save-exact", "--ignore-scripts", `${name}@${version}`]);
```
