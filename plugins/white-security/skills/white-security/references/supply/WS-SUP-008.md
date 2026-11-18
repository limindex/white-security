<!-- generated from white-security issues; do not edit -->
# WS-SUP-008 Model Supply Chain (pickle·safetensors, AI 모델·데이터셋 공급망)

## 탐지 규칙

- **찾는 곳**: `torch.load(`, `torch==2.`, `weights_only=False`, `trust_remote_code=True`, `from_pretrained(`, `hf_hub_download(`, `snapshot_download(`, `load_dataset(`, `torch<2.6`, `torch~=2.5.0`, `.safetensors`, 그 밖에 requirements·pyproject·잠금 파일의 torch 버전, joblib·pickle로 여는 모델 파일, 모델·체크포인트 업로드 기능, 모델을 받는 Dockerfile 단계, HF_HUB_OFFLINE 설정.
- **검사 1 — 여는 순간 코드가 실행되는 형식**: 지적할 때: `torch.load(..., weights_only=False)`, PyTorch 2.6 미만에서 쓰는 `torch.load`(`weights_only=True`여도 우회되는 취약점 CVE-2025-32434, requirements·pyproject의 `torch==2.5.1`, `torch<2.6`, `torch<=2.5.1`, `torch~=2.5.0`처럼 2.6 미만으로 묶는 범위와 `poetry.lock`·`uv.lock`·`requirements.txt` 해시 목록에 잠긴 2.6 미만 버전 포함), 밖에서 받은 `.pkl`·`.joblib`·`.bin` 모델을 pickle 계열로 여는 경우, `trust_remote_code=True`. / 지적하지 않을 때: `safetensors`·`use_safetensors=True`, PyTorch 2.6 이상에서 `weights_only=True`(`torch>=2.6`, 또는 `~=2.5`처럼 2.6 이상을 허용하는 범위인데 잠금 파일이 2.6 이상인 경우), 내 파이프라인이 만든 파일을 내 저장소에서만 읽는 경우.
- **검사 2 — 버전 고정**: 지적할 때: `from_pretrained`·`hf_hub_download`·`load_dataset`이 `revision` 없이 기본 브랜치를 받는 경우, 출처를 모르는 개인 저장소 모델, 서버가 실행 중에 새 버전을 받아 오는 경우. / 지적하지 않을 때: 40자리 커밋 해시 `revision`, 사내 미러나 빌드 때 받아 둔 파일만 쓰는 경우.
- **검사 3 — 업로드된 모델**: 지적할 때: 사용자가 올린 모델·체크포인트 파일을 서버 프로세스에서 바로 여는 경우. / 지적하지 않을 때: safetensors만 받거나, 네트워크·비밀값이 없는 격리 환경에서 여는 경우.
- **등급**: 위험: 외부·사용자 모델을 `weights_only=False`나 pickle로 열기, PyTorch 2.6 미만에서 외부 파일을 `torch.load`로 열기, 검토하지 않은 저장소에 `trust_remote_code=True`. / 주의: `revision` 고정 없음, safetensors가 있는데 `.bin`을 받음, 실행 중 자동 다운로드. / 참고: 출처·라이선스 기록 없음.
- **보고 예**: `[위험] WS-SUP-008 app/model.py:7 — 사용자가 올린 체크포인트를 torch.load(path, weights_only=False) 로 열어 임의 코드 실행 가능 → .safetensors 만 받고 safetensors.torch.load_file 로 열기`
- **고칠 방법**: 가중치는 safetensors로 받고 모델·데이터셋을 검토한 커밋으로 고정하며, `trust_remote_code`와 `weights_only=False`를 없애야 한다.

## 점검 항목

- 가중치는 safetensors로 받고, pickle 기반 `.bin`·`.pt`·`.pkl`은 피해야 한다.
- PyTorch는 2.6 이상으로 올리고 `torch.load`는 `weights_only=True`로 써야 한다.
- `trust_remote_code=True`는 코드를 직접 검토한 저장소에만 써야 한다.
- 모델·데이터셋은 검토한 커밋(`revision`)으로 고정해야 한다.
- 사용자가 올린 모델 파일은 safetensors만 받거나 격리된 환경에서 열어야 한다.
- 모델 출처(조직·커밋)와 라이선스를 기록해야 한다.

## 검토 주의

`weights_only=True`는 PyTorch 2.6 미만에서 우회되고(CVE-2025-32434) 2.6 이상에서도 서비스 거부까지 막지는 못하므로(PyTorch 문서), Hub의 pickle 검사도 완전하지 않다고 Hugging Face가 밝히므로 "Hub에서 받았으니 안전"이라고 판단하지 않아야 한다. 일반 역직렬화(pickle·yaml·unserialize)는 WS-DES-001, RAG 문서 오염은 WS-LLM-005, 새 파이썬 패키지는 WS-SUP-001에서 다룬다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-SUP-008.code.python.md` (Python) · `WS-SUP-008.code.dockerfile.md` (Dockerfile)

기본 심각도: 위험 · CWE-502 · CWE-494 · CWE-829
