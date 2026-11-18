---
id: WS-LLM-005
title: RAG Poisoning · 검색 권한 누락 (벡터 DB 문서 오염·남의 문서 노출)
slug: rag-access-and-poisoning
group: ai-use
severity: high
grade_hint: "남의 문서가 검색 결과에 섞임"
always_on: "`index.query(` `.similarity_search(` → 벡터 검색에 사용자·조직 조건을 걸고, 검색 뒤 원본 문서 권한을 다시 확인해야 한다."
site_only: false
depth: brief
languages: [python]
maps:
  owasp2025: [A01]
  api2023: []
  llm2026: [LLM09, LLM05]
  agentic2026: []
  asvs5: [V8.2.2]
  cwe: [CWE-862]
  kr_guide: ["보안기능-2"]
  kr_ciip: [IN]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Python, RAG·벡터 DB 기반 서비스]
severity_reason: "영향: 권한 밖 문서 노출·답변 조작 · 악용: 일반 사용자 질문이나 문서 몇 개 등록으로 가능"
sources:
  - title: "OWASP Top 10 for LLM Applications 2026 - LLM09:2026 Vector and Embedding Weaknesses"
    url: https://genai.owasp.org/resource/owasp-genai-llm-top-10-2026/
    year: 2026
  - title: "Zou 외, PoisonedRAG: Knowledge Corruption Attacks to Retrieval-Augmented Generation of Large Language Models (USENIX Security 2025, arXiv:2402.07867)"
    url: https://arxiv.org/abs/2402.07867
    year: 2025
---

## 요약

RAG(질문과 비슷한 문서를 먼저 찾아 AI에게 함께 넘기는 방식)에서 검색할 때 권한을 거르지 않아 남의 문서가 답에 섞여 나오는 문제. 누구나 문서를 넣을 수 있으면 가짜 문서 몇 개로 답이 바뀌는 RAG Poisoning 위험도 있음(진단가이드 이름: 부적절한 인가).

## 위험 배경

- PoisonedRAG 연구(2025), 수백만 건의 지식 DB에 질문 하나당 가짜 글 5개를 넣어 90% 성공률 보고.

## 대응 방법

```text
벡터 DB 검색 코드를 찾아서, 로그인한 사용자가 볼 수 있는 문서만 검색되게 필터를 걸어 줘.
검색 뒤에도 원본 문서 권한을 한 번 더 확인하고, 답에 근거 문서 목록을 같이 보여 줘.
문서를 넣는 곳은 권한 있는 사람만 쓰게 하고 출처·올린 사람을 기록해 줘.
```

- A 회사 계정으로 B 회사에만 있는 문서 내용을 물었을 때, 답에 그 내용이 나오지 않아야 한다.

## 점검 항목

- 벡터 검색에는 사용자·조직 조건(메타데이터 필터)을 걸어 볼 수 있는 문서만 찾아야 하고, 검색 뒤 원본 문서 권한을 다시 확인해야 한다.
- 여러 회사(테넌트)가 함께 쓰면 인덱스·네임스페이스를 나누거나, 모든 검색에 테넌트 필터를 걸어야 한다.
- 문서를 넣을 수 있는 사람과 출처를 정하고, 문서마다 출처·올린 사람·날짜를 기록해야 한다.
- 원본 문서를 지우거나 권한을 바꾸면 벡터 DB에서도 지우거나 고쳐야 한다.
- 검색된 문서는 지시가 아니라 자료로 넣고(WS-PI-001), 답에는 근거 문서를 함께 보여야 한다.

## 탐지 규칙

- **찾는 곳**: 벡터 검색 호출(`index.query(`, `collection.query(`, `.similarity_search(`, 벡터 DB의 search, `as_retriever(`, `match_documents`), 문서 넣기(`.upsert(`, `.add_documents(`, `collection.add(`).
- **검사 1 — 검색의 권한 필터 누락**: 지적할 때: 여러 사용자·회사의 문서가 한 인덱스에 있는데 검색에 사용자·테넌트 필터가 없고, 검색 뒤 권한 확인도 없는 경우. / 지적하지 않을 때: 인덱스에 공개 문서만 있는 경우. 테넌트별로 인덱스가 나뉘어 있고 서버가 세션으로 인덱스를 고르는 경우.
- **검사 2 — 확인 없는 문서 등록**: 지적할 때: 로그인한 누구나(또는 외부 크롤링 결과가) 모든 사용자가 쓰는 공용 인덱스에 확인 없이 들어가는 경우. / 지적하지 않을 때: 넣는 사람 권한을 확인하고 출처를 기록하는 경우. 사용자 본인 전용 인덱스인 경우.
- **등급**: 위험: 다른 사용자·회사 문서가 검색될 수 있는 경우. / 주의: 공용 인덱스에 확인 없이 문서가 들어가는 경우. / 참고: 원본 삭제가 벡터 DB에 반영되지 않는 경우. 답에 근거가 없는 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-LLM-005 rag/search.py:18 — index.query 에 tenant 필터가 없어 다른 회사 문서가 검색됨 → filter={"tenant_id": user.tenant_id} 추가, 원본 권한 재확인`

## 코드 예시

### python

✗ 취약 코드

```python
def answer(user, question: str) -> str:
    hits = index.query(vector=embed(question), top_k=5)  # 모든 회사 문서에서 찾는다
    context = "\n".join(h.metadata["text"] for h in hits)
    return llm.chat(question, context=context)
```

✓ 안전 코드

```python
def answer(user, question: str) -> dict:
    hits = index.query(vector=embed(question), top_k=5,
                       filter={"tenant_id": user.tenant_id})       # 내 회사 문서에서만 찾는다
    docs = [d for d in docs_repo.get_many([h.id for h in hits]) if d.can_read(user)]  # 원본 권한 재확인
    context = "\n".join(f"[{d.id}] {d.text}" for d in docs)
    return {"answer": llm.chat(question, context=context), "sources": [d.id for d in docs]}

def ingest(doc, uploader) -> None:
    if not uploader.can_publish(doc.tenant_id):
        raise PermissionError("이 회사 문서함에 올릴 권한이 없습니다")
    meta = {"tenant_id": doc.tenant_id, "source": doc.source, "by": uploader.id}
    index.upsert([(doc.id, embed(doc.text), meta)])
```
