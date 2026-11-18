---
id: WS-SEC-001
title: 비밀키·토큰 노출
slug: secret-exposure
group: deploy
severity: high
site_only: false
depth: full
languages: [python, js, php]
maps:
  owasp2025: [A02, A07]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V13.3]
  cwe: [CWE-798, CWE-540, CWE-312]
  kr_guide: ["보안기능-6", "보안기능-5"]
  kr_ciip: [IL]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, Node.js, PHP, 모든 애플리케이션·저장소·배포 환경]
severity_reason: "영향: 남이 내 키로 과금·데이터 접근 · 악용: 공개 저장소·화면 소스에서 자동 수집 도구가 수 분 안에 찾아냄"
sources:
  - title: OWASP Secrets Management Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html
    year: 2026
  - title: "OWASP ASVS 5.0 - V13.3 Secret Management"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "OWASP Top 10:2025 - A07 Authentication Failures (CWE-798)"
    url: https://top10.owasp.org/2025/A07_2025-Authentication_Failures
    year: 2025
  - title: "GitHub Docs - Removing sensitive data from a repository"
    url: https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository
    year: 2026
  - title: GitGuardian The State of Secrets Sprawl 2026
    url: https://blog.gitguardian.com/the-state-of-secrets-sprawl-2026/
    year: 2026
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호)
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: 개인정보보호위원회 개인정보의 안전성 확보조치 기준 안내서 (2026.11)
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=12611
    year: 2026
---

## 요약

API 키(서비스를 쓸 수 있는 비밀 열쇠)나 DB 비밀번호를 코드에 그대로 적어 두면, 코드를 올린 곳·웹 화면·로그를 통해 남에게 보인다. 그 열쇠로 남이 내 돈과 데이터를 쓴다. 진단가이드 이름은 '하드코드된 중요정보'다.

## 발생 상황

- AI에게 "AI 챗봇 붙여 줘"라고 하면서 키를 채팅에 붙여 넣었다. AI는 키를 코드에 그대로 적었다. 그 코드가 GitHub 공개 저장소에 올라갔다. 집 열쇠를 현관문에 테이프로 붙여 둔 셈이다.
- 웹페이지 코드에 키를 넣었다. `VITE_`, `NEXT_PUBLIC_`로 시작하는 설정값은 브라우저 코드에서 쓰면 그대로 방문자에게 내려간다. 누구나 브라우저 개발자 도구(F12)로 본다.
- 실수를 알고 `.env`(비밀값을 모아 두는 설정 파일)를 지운 뒤 다시 올렸다. 그래도 git(코드 변경 기록을 전부 남기는 도구)의 예전 기록에 그대로 남아 있다.
- 오류 화면이나 로그에 비밀번호가 찍힌다.

## 위험 배경

- 비밀값 유출을 조사하는 해외 보안 회사 GitGuardian의 2026년 보고서를 보면, 2025년 한 해 공개 GitHub에 새로 올라간 비밀값이 2,865만 개다. 전년보다 34% 늘었다.
- 같은 보고서에서 AI 코딩 도구(Claude Code)와 함께 만든 커밋(코드 변경 묶음)은 3.2%에 비밀값이 섞여 있었다. 공개 커밋 전체 평균은 1.5%다. AI 도구 연결 설정(MCP) 파일에서 나온 비밀값도 24,008개였다.
- 지운다고 끝나지 않는다. 2022년에 찾은 진짜 비밀값을 2026년 1월에 다시 확인하니 64% 넘게 아직 쓸 수 있었다. GitHub 공식 문서도 올라간 비밀값은 기록을 지우기 전에 먼저 폐기하거나 바꾸라고 안내한다.
- 개인정보를 다루는 서비스라면 법적 기준도 있다. 「개인정보의 안전성 확보조치 기준」(개인정보보호위원회고시 제2026-9호) 제7조 제1항은 비밀번호 같은 인증정보(로그인에 쓰는 정보)를 암호화해서 저장하고 비밀번호는 되돌릴 수 없게(일방향) 암호화하라고 정한다. 개인정보보호위원회 안내서(2026.11)는 액세스 토큰(로그인 상태를 증명하는 출입증 같은 값)과 비밀 접근 키도 인증정보라면 암호화 대상이라고 설명한다. 제7조 제6항은 일정 규모 이상 사업자에게 암호 키(데이터를 암호화하는 데 쓰는 키) 관리 절차를 요구하는데, 안내서는 키를 소스코드나 설정 파일에 평문(암호화하지 않은 글자 그대로)으로 두지 말라고 적는다.

## 대응 방법

**1. AI에게 이렇게 말한다**

```text
코드와 설정 파일에서 API 키·비밀번호·토큰이 직접 적힌 곳을 모두 찾아 줘. 값은 보여 주지 말고 위치만 알려 줘.
찾은 값은 환경 변수에서 읽게 바꾸고, .env 를 .gitignore 에 넣고, 값을 비운 .env.example 을 만들어 줘.
브라우저로 내려가는 코드에 비밀키가 있으면 서버가 대신 호출하게 바꿔 줘.
```

**2. 결과를 이렇게 확인한다**

- 코드에 길고 무작위한 문자열이 안 보이고 `os.environ`·`process.env`·`getenv`처럼 '환경 변수에서 읽기'만 보이면 된다. 환경 변수는 코드 밖, 서버 설정에 따로 두는 값이다.
- `.gitignore`(git에 올리지 않을 파일 목록)에 `.env`가 들어 있는지 본다.
- 가장 중요한 일: 키가 한 번이라도 밖에 나갔다면 코드를 고치는 것과 별개로, 그 서비스 관리 화면에서 키를 새로 발급하고 옛 키를 끈다. 사용 한도와 알림도 걸어 둔다.

**3. 코드 칸 보는 법**

- ✗ 코드는 비밀번호와 키를 코드 안에 글자 그대로 적었다. 코드를 본 사람은 누구나 열쇠를 갖게 된다.
- ✓ 코드는 '어디서 읽을지'만 적고 실제 값은 서버 환경에만 둔다. 값이 없으면 대충 넘어가지 않고 아예 시작하지 않는다.

**4.** 화이트 보안 스킬을 깔아 두면 AI가 키를 코드에 적으려 할 때 그 자리를 알려 주고 환경 변수로 바꾸자고 한다.

## 점검 항목

- 소스·설정 파일·테스트 데이터·주석에 비밀값 리터럴이 없다(키 접두어가 붙은 문자열, `password=`, `secret`, `token`, 비밀번호가 든 접속 문자열, `-----BEGIN ... PRIVATE KEY-----`).
- 비밀값은 실행 환경(환경 변수, 시크릿 매니저, KMS)에서 읽는다. 값이 없으면 기본값으로 대신하지 않고 시작을 멈춘다.
- `.env`, `*.pem`, `*.key`, 서비스 계정 JSON이 `.gitignore`에 있고, 저장소에는 값을 비운 `.env.example`만 있다.
- 브라우저로 나가는 코드에는 공개용 키만 둔다. `VITE_`, `NEXT_PUBLIC_`, `REACT_APP_`, `EXPO_PUBLIC_` 접두어 변수에 비밀키를 넣지 않는다. 비밀키가 필요한 호출은 서버가 대신한다.
- 로그, 오류 응답, 디버그 출력에 비밀값과 `Authorization` 헤더를 찍지 않는다.
- 이미 커밋되거나 밖에 보인 비밀값은 교체가 먼저, 기록 지우기는 그다음이다.
- 개인정보처리시스템이 저장하는 인증정보(비밀번호, 액세스·리프레시 토큰, 접근 키)는 암호화해 저장하고 비밀번호는 일방향 암호화한다. 암호 키는 소스·설정 파일에 평문으로 두지 않는다(고시 제7조 제1항·제6항, 안내서).
- 키는 필요한 권한만 주고 만료·교체 주기를 정한다.
- 저장소에 비밀값 검사(pre-commit 훅, 푸시 보호, CI 스캔)를 둔다.

## 탐지 규칙

**찾는 곳**: 이번에 쓰거나 고친 파일 전체. 특히 설정 파일(`.env*`, `config.*`, `settings.py`, `application.yml`, `docker-compose.yml`, CI 워크플로, `.mcp.json`), 브라우저 코드의 `import.meta.env.VITE_*`·`process.env.NEXT_PUBLIC_*`, 로그 출력부.

**검사 1. 리터럴 비밀값**
- 지적할 때: 이름에 `key`, `secret`, `token`, `password`, `passwd`, `credential`, `private`가 든 변수에 16자 이상의 무작위로 보이는 문자열이 들어 있다. 또는 알려진 키 형식(`AKIA`로 시작하는 20자, `ghp_`, `sk_live_`, `xoxb-`, `-----BEGIN ... PRIVATE KEY-----`)이나 비밀번호가 든 접속 문자열이 있다.
- 지적하지 않을 때: 값이 비어 있다. 자리표시자다(`changeme`, `your-key-here`, `xxx`, `<...>`, `example`). `.env.example`이나 문서의 예시다. 테스트 폴더 안의 가짜 값이고 실제 서비스에 닿지 않는다. 서비스 문서가 공개해도 된다고 한 공개용 키다(publishable key 등).

**검사 2. 브라우저로 나가는 비밀키**
- 지적할 때: 브라우저 번들 접두어 변수의 이름이나 쓰임새가 서버 전용 비밀키다(이름에 `SECRET`이 있음, `Authorization: Bearer`에 그대로 씀).
- 지적하지 않을 때: 공개용 키이고 권한 규칙이 서버 쪽에 따로 있다.

**검사 3. 없으면 고정값으로 도는 코드**
- 지적할 때: `os.getenv("SECRET_KEY", "dev-secret")`, `process.env.JWT_SECRET || "secret"`처럼 환경 변수가 없으면 고정값으로 돈다.
- 지적하지 않을 때: 기본값이 개발 전용 분기에만 있고 운영 설정에서는 값이 없으면 멈춘다.

**검사 4. 무시 목록 누락**
- 지적할 때: `.env`나 키 파일이 git에 추적되고 있거나 `.gitignore`에 없다.

**검사 5. 로그·오류로 새는 값**
- 지적할 때: 비밀값, 접속 문자열, 인증 헤더를 로그나 오류 응답에 그대로 넣는다.

**등급**
- 위험: 실제 값으로 보이는 비밀값이 저장소나 브라우저 번들에 있다.
- 주의: 없으면 고정값으로 도는 코드. `.gitignore` 누락. 로그 출력.
- 참고: 비밀값 검사 도구가 없다. 키 교체 주기가 정해져 있지 않다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <한 줄 이유> → <고칠 방법>`

예: `[위험] WS-SEC-001 src/config.php:12 — DB 비밀번호가 코드에 평문으로 있음(값 가림) → 환경 변수로 옮기고, 이미 커밋됐다면 비밀번호부터 바꾼다`

**보고할 때 주의**
- 비밀값 자체를 보고에 옮겨 적지 않는다. 앞 4자만 남기고 `…[가림]`으로 쓴다.

**고칠 때**
- 코드에서 값을 지우고 환경 변수나 시크릿 매니저에서 읽게 바꾼다.
- 사용자에게 "이 키는 이미 노출됐으니 서비스에서 새로 발급하고 옛 키를 폐기해야 한다"고 반드시 알린다. 코드만 고치고 끝내지 않는다.
- git 기록을 다시 쓰는 작업(`git filter-repo` 등)은 사용자 확인 없이 하지 않는다.

## 코드 예시

### python

✗ 취약 코드

```python
# settings.py (저장소에 커밋됨)
import os

DATABASE_URL = "postgresql://app:Winter2026!@db.internal:5432/shop"
PAYMENT_SECRET_KEY = "live_secret_9f8a7c6d5e4b3a2f1e0d"
JWT_SECRET = os.getenv("JWT_SECRET", "dev-secret")  # 없으면 모두가 아는 값으로 돈다
```

✓ 안전 코드

```python
# settings.py
import os

def required(name: str) -> str:
    value = os.environ.get(name)
    if not value:
        raise RuntimeError(f"{name} 환경 변수가 없습니다")  # 기본값으로 대신하지 않는다
    return value

DATABASE_URL = required("DATABASE_URL")
PAYMENT_SECRET_KEY = required("PAYMENT_SECRET_KEY")
JWT_SECRET = required("JWT_SECRET")

# .gitignore 에 .env 를 넣고, 저장소에는 값을 비운 .env.example 만 둔다.
# 이미 커밋된 값은 지금 서비스 관리 화면에서 새로 발급하고 옛 값은 폐기한다.
```

### js

✗ 취약 코드

```js
// src/api.js (브라우저 번들에 들어간다)
export async function ask(prompt) {
  const res = await fetch("https://api.example-ai.com/v1/chat", {
    method: "POST",
    headers: { Authorization: `Bearer ${import.meta.env.VITE_AI_SECRET_KEY}` },
    body: JSON.stringify({ prompt }),
  });
  return res.json();
}
```

✓ 안전 코드

```js
// server.js (Node, 서버에서만 실행)
import express from "express";

const AI_KEY = process.env.AI_SECRET_KEY;
if (!AI_KEY) throw new Error("AI_SECRET_KEY 환경 변수가 없습니다");

const app = express();
app.use(express.json({ limit: "20kb" }));

// requireLogin: 앱의 로그인 확인 미들웨어
app.post("/api/chat", requireLogin, async (req, res) => {
  const r = await fetch("https://api.example-ai.com/v1/chat", {
    method: "POST",
    headers: { Authorization: `Bearer ${AI_KEY}`, "Content-Type": "application/json" },
    body: JSON.stringify({ prompt: String(req.body.prompt ?? "").slice(0, 4000) }),
  });
  res.status(r.status).json(await r.json());
});

// 브라우저는 fetch("/api/chat") 만 부른다. 키는 번들에 들어가지 않는다.
```

### php

✗ 취약 코드

```php
<?php
// config.php (저장소에 커밋됨)
$pdo = new PDO('mysql:host=db;dbname=shop;charset=utf8mb4', 'shop', 'Winter2026!');
define('SMS_API_SECRET', 'b1946ac92492d2347c6235b4d2611184');
```

✓ 안전 코드

```php
<?php
// config.php
function env_required(string $name): string
{
    $v = getenv($name);
    if ($v === false || $v === '') {
        throw new RuntimeException("$name 환경 변수가 없습니다");
    }
    return $v;
}

$pdo = new PDO(
    'mysql:host=db;dbname=shop;charset=utf8mb4',
    env_required('DB_USER'),
    env_required('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$smsSecret = env_required('SMS_API_SECRET');

// .env 는 웹 루트 밖에 두고 .gitignore 에 넣는다. 이미 올라간 비밀번호·키는 먼저 바꾼다.
```
