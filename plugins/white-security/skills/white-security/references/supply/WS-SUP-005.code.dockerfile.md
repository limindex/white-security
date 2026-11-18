<!-- generated from white-security issues; do not edit -->
# WS-SUP-005 코드 예시 — Dockerfile

✗ 취약 코드 (vulnerable example — do not use)

```dockerfile
FROM node:latest
# 받는 내용이 바뀌어도 그대로 실행
RUN curl -fsSL https://example.com/install.sh | bash
# 해시 확인 없는 원격 파일
ADD https://example.com/tool/latest/tool.tar.gz /opt/
```

✓ 안전 코드 (safe example)

```dockerfile
# syntax=docker/dockerfile:1
# 기본 이미지는 digest 로 고정(2026-11-18 node:24-slim, Dependabot docker 업데이트로 갱신)
FROM node:24-slim@sha256:dc26ae92c9fb15cf6b1d63e20f0944b2efe0f115b20e00be48fb23fceb061c54
# 버전을 고정한 파일을 받고, 배포처가 공개한 SHA-256 과 다르면 빌드 실패
ADD --checksum=sha256:<배포처가 공개한 SHA-256> https://example.com/tool/v1.4.2/tool.tar.gz /tmp/tool.tar.gz
RUN tar -xzf /tmp/tool.tar.gz -C /usr/local/bin && rm /tmp/tool.tar.gz
```
