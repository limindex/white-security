<!-- generated from white-security issues; do not edit -->
# WS-SUP-008 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
import torch
from transformers import AutoModel

# 저장소의 파이썬 코드를 실행, 버전 고정 없음(주인이 바꾸면 다음 배포에 그대로)
model = AutoModel.from_pretrained("example-user/fast-ko-model", trust_remote_code=True)

def load_user_checkpoint(path: str):
    return torch.load(path, weights_only=False)   # 사용자가 올린 파일: 여는 순간 코드 실행
```

✓ 안전 코드 (safe example)

```python
from safetensors.torch import load_file
from transformers import AutoModel

model = AutoModel.from_pretrained(
    "google-bert/bert-base-uncased",
    revision="86b5e0934494bd15c9632b12f734a8a67f723594",  # 검토한 커밋으로 고정
    use_safetensors=True,       # safetensors 가 없으면 받지 않고 오류
    trust_remote_code=False,    # 기본값: 저장소 코드는 실행하지 않음
)

def load_user_checkpoint(path: str):
    if not path.endswith(".safetensors"):
        raise ValueError("safetensors 파일만 받습니다")
    return load_file(path)      # 텐서만 읽고 코드는 실행하지 않음

# 오래된 .pt 를 꼭 열어야 하면 PyTorch 2.6 이상에서 torch.load(path, weights_only=True) — 출처를 아는 파일만
# (2.6 미만은 weights_only=True 도 우회됨, CVE-2025-32434)
```
