<!-- generated from white-security issues; do not edit -->
# WS-LLM-005 코드 예시 — python

✗ 취약 코드 (vulnerable example — do not use)

```python
def answer(user, question: str) -> str:
    hits = index.query(vector=embed(question), top_k=5)  # 모든 회사 문서에서 찾는다
    context = "\n".join(h.metadata["text"] for h in hits)
    return llm.chat(question, context=context)
```

✓ 안전 코드 (safe example)

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
