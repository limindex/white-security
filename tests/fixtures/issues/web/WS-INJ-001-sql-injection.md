---
id: WS-INJ-001
title: SQL 삽입
slug: sql-injection
group: web
severity: high
site_only: false
depth: full
languages: [python, php]
maps:
  owasp2025: [A05]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V1.2]
  cwe: [CWE-89]
  kr_guide: ["입력데이터 검증 및 표현-1"]
checked: 2026-11-18
sources:
  - title: OWASP Top 10 2025 - Injection
    url: https://owasp.org/Top10/
    year: 2025
  - title: CWE-89
    url: https://cwe.mitre.org/data/definitions/89.html
    year: 2024
---

## 한 줄 요약

사용자 입력이 SQL 문장에 그대로 붙으면 데이터베이스가 뚫린다.

## 이런 장면에서

검색창에 넣은 글자가 쿼리에 그대로 들어가는 화면.

## 왜 요즘 더 위험한가

AI가 만든 코드는 문자열 이어 붙이기를 자주 쓴다.

## 나는 뭘 하면 되나

값은 항상 바인딩으로 넘긴다.

## 체크 항목

- 쿼리에 문자열 연결이 없는가

## 스킬이 잡는 방식

쿼리 조립 지점에서 변수 연결을 찾는다.

## 코드

### python

✗ 뚫리는 코드

```python
## 주석처럼 보이는 줄
cur.execute("SELECT * FROM u WHERE id=" + uid)
```

✓ 막는 코드

```python
cur.execute("SELECT * FROM u WHERE id=%s", (uid,))
```

### php

✗ 뚫리는 코드

```php
$db->query("SELECT * FROM u WHERE id=" . $id);
```

✓ 막는 코드

```php
$st = $db->prepare("SELECT * FROM u WHERE id=?");
$st->execute([$id]);
```
