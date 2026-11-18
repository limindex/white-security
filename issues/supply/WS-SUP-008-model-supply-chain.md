---
id: WS-SUP-008
title: Model Supply Chain (AI 모델·데이터셋 공급망)
slug: model-supply-chain
group: supply
severity: high
grade_hint: "외부 모델을 pickle·weights_only=False로 열기"
site_only: false
depth: brief
languages: [python, dockerfile]
maps:
  owasp2025: [A03, A08]
  api2023: []
  llm2026: [LLM04, LLM05]
  agentic2026: [ASI04]
  asvs5: [V1.5.2, V15.2.4]
  cwe: [CWE-502, CWE-494, CWE-829]
  kr_guide: ["코드오류-5", "보안기능-15"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [PyTorch, Hugging Face transformers·huggingface_hub·datasets, 공개 모델을 받아 서버에서 돌리는 AI 기능, 사용자가 모델 파일을 올리는 기능]
severity_reason: "영향: 모델 파일을 여는 순간 서버에서 남의 코드 실행, 오염된 가중치·데이터로 잘못된 답 · 악용: 공개 저장소에 그럴듯한 모델을 올리거나 기존 저장소 파일을 바꿔치기"
sources:
  - title: "PyTorch Docs - torch.load, Serialization semantics (weights_only)"
    url: https://docs.pytorch.org/docs/stable/notes/serialization.html
    year: 2026
  - title: "GitHub Advisory - PyTorch torch.load with weights_only=True leads to RCE (CVE-2025-32434, 2.6.0에서 수정)"
    url: https://github.com/advisories/GHSA-53q9-r3pm-6pq6
    year: 2025
  - title: "Hugging Face Hub Docs - Pickle Scanning"
    url: https://huggingface.co/docs/hub/security-pickle
    year: 2026
  - title: "OWASP Top 10 for LLM Applications 2026 - LLM04:2026 Supply Chain, LLM05:2026 Data and Model Poisoning"
    url: https://genai.owasp.org/resource/owasp-genai-llm-top-10-2026/
    year: 2026
  - title: "Hugging Face transformers Docs - Loading models (revision, use_safetensors, trust_remote_code)"
    url: https://huggingface.co/docs/transformers/main/en/models
    year: 2026
  - title: "Hugging Face Hub Docs - hf download"
    url: https://huggingface.co/docs/huggingface_hub/guides/cli
    year: 2026
---

## 요약

모델 저장소에서 받은 가중치를 pickle 기반 형식(`.bin`·`.pt`·`.pkl`)으로 열거나 `trust_remote_code=True`로 저장소의 파이썬 코드를 실행해, 모델을 불러오는 순간 남의 코드가 서버에서 돌아가는 문제(진단가이드 이름: 신뢰할 수 없는 데이터의 역직렬화).

## 위험 배경

- 버전(`revision`)을 고정하지 않으면 저장소 주인이 나중에 바꾼 가중치·데이터셋이 그대로 들어오는 구조(데이터·모델 오염).

## 대응 방법

```text
torch.load, joblib, from_pretrained, hf_hub_download, snapshot_download, load_dataset 로 모델·데이터를 불러오는 곳을 찾아, 파일 형식, trust_remote_code, revision 고정 여부를 표로 보여 줘.
가중치는 safetensors 로만 받게(use_safetensors=True) 하고, torch 는 2.6 이상으로 올려 torch.load 는 weights_only=True 로, trust_remote_code 는 끄게 고쳐 줘.
모델·데이터셋은 검토한 커밋 해시(revision)로 고정하고, 이미지 빌드 때 받아 두고 실행 중에는 새로 받지 않게 해 줘.
```

- 코드에 `trust_remote_code=True`·`weights_only=False`가 없어야 한다.
- 모든 `from_pretrained`·`hf_hub_download` 호출에 40자리 커밋 해시 `revision`이 있어야 한다.

## 점검 항목

- 가중치는 safetensors로 받고, pickle 기반 `.bin`·`.pt`·`.pkl`은 피해야 한다.
- PyTorch는 2.6 이상으로 올리고 `torch.load`는 `weights_only=True`로 써야 한다.
- `trust_remote_code=True`는 코드를 직접 검토한 저장소에만 써야 한다.
- 모델·데이터셋은 검토한 커밋(`revision`)으로 고정해야 한다.
- 사용자가 올린 모델 파일은 safetensors만 받거나 격리된 환경에서 열어야 한다.
- 모델 출처(조직·커밋)와 라이선스를 기록해야 한다.

## 탐지 규칙

- **찾는 곳**: `torch.load(`, `torch==2.`, `weights_only=False`, `trust_remote_code=True`, `from_pretrained(`, `hf_hub_download(`, `snapshot_download(`, `load_dataset(`, `torch<2.6`, `torch~=2.5.0`, `.safetensors`, 그 밖에 requirements·pyproject·잠금 파일의 torch 버전, joblib·pickle로 여는 모델 파일, 모델·체크포인트 업로드 기능, 모델을 받는 Dockerfile 단계, HF_HUB_OFFLINE 설정.
- **검사 1 — 여는 순간 코드가 실행되는 형식**: 지적할 때: `torch.load(..., weights_only=False)`, PyTorch 2.6 미만에서 쓰는 `torch.load`(`weights_only=True`여도 우회되는 취약점 CVE-2025-32434, requirements·pyproject의 `torch==2.5.1`, `torch<2.6`, `torch<=2.5.1`, `torch~=2.5.0`처럼 2.6 미만으로 묶는 범위와 `poetry.lock`·`uv.lock`·`requirements.txt` 해시 목록에 잠긴 2.6 미만 버전 포함), 밖에서 받은 `.pkl`·`.joblib`·`.bin` 모델을 pickle 계열로 여는 경우, `trust_remote_code=True`. / 지적하지 않을 때: `safetensors`·`use_safetensors=True`, PyTorch 2.6 이상에서 `weights_only=True`(`torch>=2.6`, 또는 `~=2.5`처럼 2.6 이상을 허용하는 범위인데 잠금 파일이 2.6 이상인 경우), 내 파이프라인이 만든 파일을 내 저장소에서만 읽는 경우.
- **검사 2 — 버전 고정**: 지적할 때: `from_pretrained`·`hf_hub_download`·`load_dataset`이 `revision` 없이 기본 브랜치를 받는 경우, 출처를 모르는 개인 저장소 모델, 서버가 실행 중에 새 버전을 받아 오는 경우. / 지적하지 않을 때: 40자리 커밋 해시 `revision`, 사내 미러나 빌드 때 받아 둔 파일만 쓰는 경우.
- **검사 3 — 업로드된 모델**: 지적할 때: 사용자가 올린 모델·체크포인트 파일을 서버 프로세스에서 바로 여는 경우. / 지적하지 않을 때: safetensors만 받거나, 네트워크·비밀값이 없는 격리 환경에서 여는 경우.
- **등급**: 위험: 외부·사용자 모델을 `weights_only=False`나 pickle로 열기, PyTorch 2.6 미만에서 외부 파일을 `torch.load`로 열기, 검토하지 않은 저장소에 `trust_remote_code=True`. / 주의: `revision` 고정 없음, safetensors가 있는데 `.bin`을 받음, 실행 중 자동 다운로드. / 참고: 출처·라이선스 기록 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-SUP-008 app/model.py:7 — 사용자가 올린 체크포인트를 torch.load(path, weights_only=False) 로 열어 임의 코드 실행 가능 → .safetensors 만 받고 safetensors.torch.load_file 로 열기`
- **검토 주의**: `weights_only=True`는 PyTorch 2.6 미만에서 우회되고(CVE-2025-32434) 2.6 이상에서도 서비스 거부까지 막지는 못하므로(PyTorch 문서), Hub의 pickle 검사도 완전하지 않다고 Hugging Face가 밝히므로 "Hub에서 받았으니 안전"이라고 판단하지 않아야 한다. 일반 역직렬화(pickle·yaml·unserialize)는 WS-DES-001, RAG 문서 오염은 WS-LLM-005, 새 파이썬 패키지는 WS-SUP-001에서 다룬다.
- **고칠 방법**: 가중치는 safetensors로 받고 모델·데이터셋을 검토한 커밋으로 고정하며, `trust_remote_code`와 `weights_only=False`를 없애야 한다.

## 코드 예시

### python

✗ 취약 코드

```python
import torch
from transformers import AutoModel

# 저장소의 파이썬 코드를 실행, 버전 고정 없음(주인이 바꾸면 다음 배포에 그대로)
model = AutoModel.from_pretrained("example-user/fast-ko-model", trust_remote_code=True)

def load_user_checkpoint(path: str):
    return torch.load(path, weights_only=False)   # 사용자가 올린 파일: 여는 순간 코드 실행
```

✓ 안전 코드

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

### dockerfile

✗ 취약 코드

```dockerfile
FROM python:3.13-slim
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY . /app
# 컨테이너가 뜰 때마다 기본 브랜치의 최신 파일(.bin 포함)을 받아 옴
CMD ["python", "/app/serve.py", "--model", "example-user/fast-ko-model"]
```

✓ 안전 코드

```dockerfile
FROM python:3.13-slim
COPY requirements.txt .
RUN pip install --no-cache-dir --require-hashes -r requirements.txt   # torch>=2.6 해시 고정, huggingface_hub(hf 명령) 포함
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
