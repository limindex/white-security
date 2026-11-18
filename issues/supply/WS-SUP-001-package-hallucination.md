---
id: WS-SUP-001
title: Slopsquatting (패키지 환각)
slug: package-hallucination
group: supply
severity: high
site_only: false
depth: full
languages: [python, js, php]
maps:
  owasp2025: [A03]
  api2023: []
  llm2026: [LLM07]
  agentic2026: [ASI05]
  asvs5: [V15.2]
  cwe: []
  kr_guide: ["보안기능-15"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python(pip), Node.js(npm), PHP(Composer)]
severity_reason: "영향: 개발자 컴퓨터·서버에서 남의 코드 실행 · 악용: AI가 지어낸 이름을 먼저 등록해 두는 것만으로 가능"
sources:
  - title: "Spracklen 외, We Have a Package for You! A Comprehensive Analysis of Package Hallucinations by Code Generating LLMs (USENIX Security 2025, arXiv:2406.10279)"
    url: https://arxiv.org/abs/2406.10279
    year: 2025
  - title: "OWASP Top 10:2025 - A03 Software Supply Chain Failures"
    url: https://top10.owasp.org/2025/A03_2025-Software_Supply_Chain_Failures
    year: 2025
  - title: "OWASP ASVS 5.0 - V15 Secure Coding and Architecture"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "The Register - AI bots hallucinate software packages and devs download them"
    url: https://www.theregister.com/2024/03/28/ai_bots_hallucinate_software_packages/
    year: 2024
  - title: "Socket - Slopsquatting: How AI Hallucinations Are Fueling a New Class of Supply Chain Attacks"
    url: https://socket.dev/blog/slopsquatting-how-ai-hallucinations-are-fueling-a-new-class-of-supply-chain-attacks
    year: 2025
  - title: "pip - Secure installs (--require-hashes)"
    url: https://pip.pypa.io/en/stable/topics/secure-installs/
    year: 2026
  - title: "npm Docs - npm ci"
    url: https://docs.npmjs.com/cli/v11/commands/npm-ci
    year: 2026
  - title: 과학기술정보통신부·KISA·국가정보원 SW 공급망 보안 가이드라인 1.0
    url: https://www.kisa.or.kr/2060204/form?postSeq=15
    year: 2024
---

## 요약

Slopsquatting(슬롭스쿼팅)은 AI가 지어낸 패키지(남이 만들어 둔 코드 묶음) 이름을 공격자가 먼저 등록해 두어, 확인 없이 설치하면 그 사람의 코드가 내 컴퓨터와 서버에서 실행되는 공급망 공격에 대한 이슈(진단가이드 이름: 무결성 검사 없는 코드 다운로드). AI가 없는 패키지를 지어내는 현상은 Package Hallucination(패키지 환각).

## 발생 상황

- "엑셀 파일 읽는 기능 만들어 줘"라는 요청에 AI가 그럴듯하지만 세상에 없던 이름의 패키지 설치를 권하고, 누군가 그 이름으로 패키지를 미리 올려 둔 경우. 설치는 남이 만든 프로그램을 내 컴퓨터에서 돌려도 된다는 허락.
- 에이전트(명령 실행까지 스스로 하는 AI 코딩 도구)가 오류를 고치다 `npm install`·`pip install`을 직접 실행해, 이름을 확인할 틈이 없는 경우.
- 유명 패키지와 한 글자만 다른 이름(Typosquatting)도 같은 함정.

## 위험 배경

- 2025년 USENIX Security(보안 분야 주요 학회) 연구에서 코드 생성 AI 16개로 만든 코드 57만 6천 개를 분석한 결과, 실제로 없는 패키지 추천 비율이 상용 AI 5.2% 이상, 공개 모델 21.7%. 지어낸 이름만 205,474개.
- 2024년 3월 공개 실험에서 한 보안 연구자가 AI가 자주 지어내던 이름 `huggingface-cli`로 빈 패키지를 올리자 석 달 만에 1만 5천 번 넘게 내려받힘(보도 기준). 실험용이라 피해는 없었지만 악성 코드였다면 그만큼 퍼졌을 구조.
- OWASP Top 10:2025의 '소프트웨어 공급망 실패'(A03, 남이 만든 부품을 통해 들어오는 문제) 3위. 국내 과기정통부·KISA·국정원 「SW 공급망 보안 가이드라인 1.0」(2024)은 프로그램에 들어간 부품 목록(SBOM) 관리를 안내.

## 대응 방법

```text
설치하기 전에 이 패키지들이 공식 저장소(pypi.org·npmjs.com·packagist.org)에 실제로 있는지,
처음 등록된 날짜와 내려받은 수를 확인해서 표로 보여 줘. 확인이 끝나기 전에는 설치 명령을 실행하지 마.
설치할 때는 버전을 정확히 고정하고 잠금 파일도 함께 만들어 줘.
```

- 공식 사이트에서 이름을 직접 검색해 안 나오거나, 몇 주 전에 생겼거나, 내려받은 수가 거의 없으면 설치하지 않아야 한다.
- 잠금 파일(설치한 버전을 정확히 적어 두는 `package-lock.json`, `poetry.lock`, `composer.lock` 등)이 함께 생겨야 하고, AI 코딩 도구 설정에서 설치 명령을 '묻지 않고 실행'으로 허용해 두지 않아야 한다.

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
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SUP-001 package.json:14 — "<이름>" 이 npm 레지스트리에 없음(404) → 원래 쓰려던 기능의 공식 문서에서 패키지 이름을 다시 확인하고, 없으면 의존성 제거`
- **고칠 방법**: 비슷한 이름을 새로 지어내 바꿔 넣지 않고 공식 문서에서 이름을 확인해 사용자 승인을 받아야 하며, 정확한 버전으로 고정하고 잠금 파일을 함께 커밋해야 한다.

## 코드 예시

### python

✗ 취약 코드

```python
import subprocess

def add_dependencies(suggested: list[str]) -> None:
    # AI가 제안한 이름을 확인 없이 설치한다 (설치 중 패키지 코드가 실행될 수 있다)
    for name in suggested:
        subprocess.run(["pip", "install", name], check=True)
```

✓ 안전 코드

```python
import json, re, urllib.error, urllib.request
from datetime import datetime, timedelta, timezone

def check_pypi(name: str, min_age_days: int = 90) -> str:
    """'있음'이 아니면 설치하지 말고 사람에게 묻는다."""
    if not re.fullmatch(r"[A-Za-z0-9][A-Za-z0-9._-]{0,99}", name):
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

✗ 취약 코드

```js
import { exec } from "node:child_process";

// 에이전트 도구: 모델이 준 이름을 그대로 설치한다
export function installPackage(name) {
  exec(`npm install ${name}`);
}
```

✓ 안전 코드

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

### php

✗ 취약 코드

```php
<?php
// 관리 도구의 "패키지 추가": AI가 추천한 이름을 그대로 설치한다
shell_exec('composer require ' . escapeshellarg($suggested)); // 따옴표 처리를 해도 실재 확인은 없다
```

✓ 안전 코드

```php
<?php
/** @return array{0: bool, 1: string} [설치해도 되는가, 이유] */
function check_packagist(string $name, int $minAgeDays = 90): array
{
    if (!preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$#', $name)) return [false, '이름 형식이 아님'];
    $ch = curl_init("https://packagist.org/packages/{$name}.json");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($body === false) return [false, '확인 못 함(네트워크)'];
    if ($status === 404) return [false, '없음'];
    if ($status !== 200) return [false, "확인 못 함(HTTP $status)"];
    $created = strtotime(json_decode($body, true)['package']['time'] ?? '');
    if ($created === false) return [false, '등록일 확인 못 함'];
    if (time() - $created < $minAgeDays * 86400) return [false, '새 패키지(' . date('Y-m-d', $created) . ' 등록)'];
    return [true, '있음'];
}

// 사람이 승인한 뒤: composer require --no-plugins vendor/name:<정확한 버전> (config.allow-plugins 에는 허용한 플러그인만)
// composer.lock 을 함께 커밋하고, 배포는 composer install 로 잠금 파일대로만 설치한다.
```
