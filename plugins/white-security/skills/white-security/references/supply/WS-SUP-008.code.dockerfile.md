<!-- generated from white-security issues; do not edit -->
# WS-SUP-008 코드 예시 — Dockerfile

✗ 취약 코드 (vulnerable example — do not use)

```dockerfile
FROM python:3.13-slim
RUN pip install --no-cache-dir -r requirements.txt
COPY . /app
# 컨테이너가 뜰 때마다 기본 브랜치의 최신 파일(.bin 포함)을 받아 옴
CMD ["python", "/app/serve.py", "--model", "example-user/fast-ko-model"]
```

✓ 안전 코드 (safe example)

```dockerfile
FROM python:3.13-slim
RUN pip install --no-cache-dir --require-hashes -r requirements.txt
# 빌드 때 검토한 커밋의 safetensors·설정 파일만 받아 둠
RUN hf download google-bert/bert-base-uncased \
      --revision 86b5e0934494bd15c9632b12f734a8a67f723594 \
      --include "*.safetensors" --include "*.json" --include "vocab.txt" \
      --local-dir /models/bert
COPY . /app
# 실행 중에는 Hub 에서 새로 받지 않음
ENV HF_HUB_OFFLINE=1
CMD ["python", "/app/serve.py", "--model", "/models/bert"]
```
