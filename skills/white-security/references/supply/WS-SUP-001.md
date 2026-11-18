<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-SUP-001 패키지 환각

위험 · OWASP A03 · LLM LLM07 · 에이전트 ASI05 · ASVS V15.2 · 진단가이드 보안기능-15

## 체크 항목

- 새 의존성은 설치 전에 레지스트리에 실제로 있는지 확인한다. PyPI `https://pypi.org/pypi/<이름>/json`, npm `https://registry.npmjs.org/<이름>`, Packagist `https://packagist.org/packages/<vendor>/<이름>.json` 이 404면 없는 패키지다.
- 처음 등록일이 최근(기본 90일 이내)이거나 내려받은 수가 매우 적으면 멈추고 사람에게 묻는다.
- 유명 패키지와 한두 글자, 하이픈·밑줄, 단·복수만 다른 이름을 의심한다.
- 패키지의 저장소 링크·관리자가 그 도구의 공식 문서에 나온 것과 같은지 본다.
- 잠금 파일(`package-lock.json`, `poetry.lock`·`uv.lock`·해시를 고정한 `requirements.txt`, `composer.lock`)을 커밋한다. CI와 배포는 잠금 파일대로만 설치한다(`npm ci`, `pip install --require-hashes -r requirements.txt`, `composer install`).
- 처음 들이는 패키지는 설치 스크립트를 끄고 받아 본다(`npm install --ignore-scripts`, `composer require --no-scripts`, `pip install --only-binary :all:`).
- 에이전트의 설치 명령(`npm install`, `pip install`, `composer require`)을 자동 승인 목록에 넣지 않는다.
- 사내 전용 패키지 이름이 공개 레지스트리에서 비어 있어 남이 먼저 등록할 수 있는지(의존성 혼동) 확인하고, 레지스트리 범위를 고정한다.

## 스킬이 잡는 방식

**찾는 곳**: 이번 변경에서 추가된 의존성(`package.json`, `requirements*.txt`, `pyproject.toml`, `composer.json`의 차이), 코드의 새 `import`·`require`·`use`, 에이전트가 실행하려는 설치 명령, 프로젝트 에이전트 설정의 허용 목록(예: `.claude/settings.json`의 `Bash(npm install:*)`).

**검사 1. 실재 확인**
- 의존성을 추가하거나 설치 명령을 쓰기 전에 레지스트리를 실제로 조회한다. 모델의 기억으로 "있는 패키지"라고 판단하지 않는다.
- 지적할 때: 조회 결과 404. 처음 등록일이 90일 이내. 유명 패키지와 편집 거리 1~2.
- 지적하지 않을 때: 이미 잠금 파일에 있고 이번 변경에서 이름이 바뀌지 않은 의존성. 표준 라이브러리 모듈. 워크스페이스 안 패키지(`file:`, `workspace:`, `path` 저장소).
- 네트워크가 막혀 조회하지 못하면 "확인 못 함"으로 적는다. 안전하다고 말하지 않는다.

**검사 2. 잠금 파일**
- 지적할 때: 매니페스트는 바뀌었는데 잠금 파일이 없거나 함께 바뀌지 않았다. CI가 `npm ci` 대신 `npm install`로 설치한다.
- 지적하지 않을 때: 라이브러리 저장소라서 잠금 파일을 일부러 두지 않고 그 이유가 문서에 있다. 이때는 참고로만 적는다.

**검사 3. 자동 설치**
- 지적할 때: 코드나 에이전트 설정이 모델이 제안한 이름을 확인 없이 설치한다(`subprocess.run(["pip", "install", name])`, `` exec(`npm install ${name}`) ``, 설치 명령 자동 승인).
- 지적하지 않을 때: 설치 전에 레지스트리 조회와 사람 승인이 있다.

**등급**
- 위험: 없는 이름이거나 90일 이내 등록인데 설치 명령이 실행될 수 있다.
- 주의: 잠금 파일 없음. 설치 명령 자동 승인. 설치 스크립트가 켜진 채로 처음 보는 패키지 설치.
- 참고: 내려받은 수만 적음. 조회는 됐지만 저장소 링크가 없음.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <한 줄 이유> → <고칠 방법>`

예: `[위험] WS-SUP-001 package.json:14 — "<이름>" 이 npm 레지스트리에 없음(404) → 원래 쓰려던 기능의 공식 문서에서 패키지 이름을 다시 확인하고, 없으면 의존성을 뺀다`

**고칠 때**
- 비슷한 이름을 새로 지어내 바꿔 넣지 않는다. 공식 문서에서 이름을 확인해 사용자에게 보여 주고 승인을 받는다.
- 설치는 정확한 버전으로 고정하고 잠금 파일을 함께 커밋한다.

## 코드

### python

✗ 뚫리는 코드

```python
import subprocess

def add_dependencies(suggested: list[str]) -> None:
    # AI가 제안한 이름을 확인 없이 설치한다 (설치 중 패키지 코드가 실행될 수 있다)
    for name in suggested:
        subprocess.run(["pip", "install", name], check=True)
```

✓ 막는 코드

```python
import json
import re
import urllib.error
import urllib.request
from datetime import datetime, timedelta, timezone

NAME = re.compile(r"[A-Za-z0-9][A-Za-z0-9._-]{0,99}")

def check_pypi(name: str, min_age_days: int = 90) -> str:
    """'있음'이 아니면 설치하지 말고 사람에게 묻는다."""
    if not NAME.fullmatch(name):
        return "이름 형식이 아님"
    try:
        with urllib.request.urlopen(f"https://pypi.org/pypi/{name}/json", timeout=10) as r:
            info = json.load(r)
    except urllib.error.HTTPError as e:
        return "없음" if e.code == 404 else f"확인 못 함(HTTP {e.code})"
    except OSError:
        return "확인 못 함(네트워크)"
    uploads = [f["upload_time_iso_8601"] for files in info["releases"].values() for f in files]
    if not uploads:
        return "올린 파일 없음"
    first = min(datetime.fromisoformat(t.replace("Z", "+00:00")) for t in uploads)
    if datetime.now(timezone.utc) - first < timedelta(days=min_age_days):
        return f"새 패키지({first.date()} 처음 등록)"
    return "있음"

# '있음'이고 사람이 이름·버전을 확인한 뒤에만 requirements.txt 에 버전과 해시를 고정한다.
# 설치: pip install --require-hashes -r requirements.txt
```

### js

✗ 뚫리는 코드

```js
import { exec } from "node:child_process";

// 에이전트 도구: 모델이 준 이름을 그대로 설치한다
export function installPackage(name) {
  exec(`npm install ${name}`);
}
```

✓ 막는 코드

```js
import { execFile } from "node:child_process";
import { promisify } from "node:util";

const run = promisify(execFile);
const NAME = /^(@[a-z0-9-~][a-z0-9-._~]*\/)?[a-z0-9-~][a-z0-9-._~]*$/;

export async function checkNpm(name, minAgeDays = 90) {
  if (!NAME.test(name)) return { ok: false, why: "이름 형식이 아님" };
  let res;
  try {
    res = await fetch(`https://registry.npmjs.org/${name.replace("/", "%2F")}`);
  } catch {
    return { ok: false, why: "확인 못 함(네트워크)" };
  }
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
export async function installApproved(name, version) {
  await run("npm", ["install", "--save-exact", "--ignore-scripts", `${name}@${version}`]);
}
```

### php

✗ 뚫리는 코드

```php
<?php
// 관리 도구의 "패키지 추가": AI가 추천한 이름을 그대로 설치한다
shell_exec('composer require ' . escapeshellarg($suggested)); // 따옴표 처리를 해도 실재 확인은 없다
```

✓ 막는 코드

```php
<?php
/** @return array{0: bool, 1: string} [설치해도 되는가, 이유] */
function check_packagist(string $name, int $minAgeDays = 90): array
{
    if (!preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $name)) {
        return [false, '이름 형식이 아님'];
    }
    $ch = curl_init("https://packagist.org/packages/{$name}.json");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($body === false) return [false, '확인 못 함(네트워크)'];
    if ($status === 404) return [false, '없음'];
    if ($status !== 200) return [false, "확인 못 함(HTTP $status)"];

    $created = strtotime(json_decode($body, true)['package']['time'] ?? '');
    if ($created === false) return [false, '등록일 확인 못 함'];
    if (time() - $created < $minAgeDays * 86400) {
        return [false, '새 패키지(' . date('Y-m-d', $created) . ' 등록)'];
    }
    return [true, '있음'];
}

// 사람이 승인한 뒤: composer require --no-scripts vendor/name:<정확한 버전>
// composer.lock 을 함께 커밋하고, 배포는 composer install 로 잠금 파일대로만 설치한다.
```
