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
published: 2026-09-01
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 본문 고정 제목 변경
  - date: 2026-09-01
    note: 최초 작성
applies_to: [Python, PHP, 모든 웹 애플리케이션]
severity_reason: 데이터베이스 전체 유출·변조로 이어지는 영향, 입력 한 줄로 악용 가능
sources:
  - title: OWASP Top 10 2025 - Injection
    url: https://owasp.org/Top10/
    year: 2025
  - title: CWE-89
    url: https://cwe.mitre.org/data/definitions/89.html
    year: 2024
---

## 요약

사용자 입력이 SQL 문장에 그대로 붙으면 데이터베이스가 뚫린다.

## 발생 상황

검색창에 넣은 글자가 쿼리에 그대로 들어가는 화면.

## 위험 배경

AI가 만든 코드는 문자열 이어 붙이기를 자주 쓴다.

## 대응 방법

값은 항상 바인딩으로 넘긴다.

## 점검 항목

- 쿼리에 문자열 연결이 없는가

## 탐지 규칙

- **찾는 곳**: 쿼리 조립 지점(`cursor.execute(`, `db.query(`, `->query(`)
- **검사 1 — 값 이어 붙이기**: 지적할 때: 요청 값을 쿼리 문자열에 이어 붙이는 경우. / 지적하지 않을 때: 매개변수 바인딩을 쓰는 경우.
- **검사 2 — 식별자 자리**: 지적할 때: `ORDER BY` 에 요청 값이 그대로 들어가는 경우. / 지적하지 않을 때: 허용 목록을 거치는 경우.
- **등급**: 위험: 외부 값이 쿼리에 그대로 들어가는 경우. / 주의: 값의 출처를 끝까지 따라가지 못한 경우. / 참고: DB 오류 원문 노출.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-INJ-001 app/search.py:18 — 검색어를 SQL에 이어 붙임 → 바인딩`
- **고칠 방법**: 값만 자리표시자로 바꿔야 한다.

## 코드 예시

### python

✗ 취약 코드

```python
## 주석처럼 보이는 줄
cur.execute("SELECT * FROM u WHERE id=" + uid)
```

✓ 안전 코드

```python
cur.execute("SELECT * FROM u WHERE id=%s", (uid,))
```

### php

✗ 취약 코드

```php
$db->query("SELECT * FROM u WHERE id=" . $id);
```

✓ 안전 코드

```php
$st = $db->prepare("SELECT * FROM u WHERE id=?");
$st->execute([$id]);
```
