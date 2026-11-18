<!-- generated from white-security issues; do not edit -->
# WS-SUP-004 코드 예시 — Dockerfile

✗ 취약 코드 (vulnerable example — do not use)

```dockerfile
FROM node:24-slim
WORKDIR /app
COPY . .
RUN npm install
CMD ["node", "server.js"]
```

✓ 안전 코드 (safe example)

```dockerfile
FROM node:24-slim
WORKDIR /app
# 잠금 파일을 먼저 복사해 그대로 설치(다르면 빌드 실패)
COPY package.json package-lock.json ./
RUN npm ci --omit=dev --ignore-scripts
COPY . .
USER node
CMD ["node", "server.js"]
# 파이썬 이미지라면: COPY requirements.txt . 후 RUN pip install --no-deps --require-hashes -r requirements.txt
```
