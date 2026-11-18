<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-SEC-001 비밀키·토큰 노출

위험 · OWASP A02 · OWASP A07 · ASVS V13.3 · CWE-200 · 진단가이드 보안기능-6 · 진단가이드 보안기능-5 · 기반시설 IL

## 체크 항목

- 소스·설정 파일·테스트 데이터·주석에 비밀값 리터럴이 없다(키 접두어가 붙은 문자열, `password=`, `secret`, `token`, 비밀번호가 든 접속 문자열, `-----BEGIN ... PRIVATE KEY-----`).
- 비밀값은 실행 환경(환경 변수, 시크릿 매니저, KMS)에서 읽는다. 값이 없으면 기본값으로 대신하지 않고 시작을 멈춘다.
- `.env`, `*.pem`, `*.key`, 서비스 계정 JSON이 `.gitignore`에 있고, 저장소에는 값을 비운 `.env.example`만 있다.
- 브라우저로 나가는 코드에는 공개용 키만 둔다. `VITE_`, `NEXT_PUBLIC_`, `REACT_APP_`, `EXPO_PUBLIC_` 접두어 변수에 비밀키를 넣지 않는다. 비밀키가 필요한 호출은 서버가 대신한다.
- 로그, 오류 응답, 디버그 출력에 비밀값과 `Authorization` 헤더를 찍지 않는다.
- 이미 커밋되거나 밖에 보인 비밀값은 교체가 먼저, 기록 지우기는 그다음이다.
- 개인정보처리시스템이 저장하는 인증정보(비밀번호, 액세스·리프레시 토큰, 접근 키)는 암호화해 저장하고 비밀번호는 일방향 암호화한다. 암호 키는 소스·설정 파일에 평문으로 두지 않는다(고시 제7조 제1항·제6항, 안내서).
- 키는 필요한 권한만 주고 만료·교체 주기를 정한다.
- 저장소에 비밀값 검사(pre-commit 훅, 푸시 보호, CI 스캔)를 둔다.

## 스킬이 잡는 방식

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

## 코드

### python

✗ 뚫리는 코드

```python
# settings.py (저장소에 커밋됨)
import os

DATABASE_URL = "postgresql://app:Winter2026!@db.internal:5432/shop"
PAYMENT_SECRET_KEY = "live_secret_9f8a7c6d5e4b3a2f1e0d"
JWT_SECRET = os.getenv("JWT_SECRET", "dev-secret")  # 없으면 모두가 아는 값으로 돈다
```

✓ 막는 코드

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

✗ 뚫리는 코드

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

✓ 막는 코드

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

✗ 뚫리는 코드

```php
<?php
// config.php (저장소에 커밋됨)
$pdo = new PDO('mysql:host=db;dbname=shop;charset=utf8mb4', 'shop', 'Winter2026!');
define('SMS_API_SECRET', 'b1946ac92492d2347c6235b4d2611184');
```

✓ 막는 코드

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
