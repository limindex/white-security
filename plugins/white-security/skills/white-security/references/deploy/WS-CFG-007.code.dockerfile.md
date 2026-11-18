<!-- generated from white-security issues; do not edit -->
# WS-CFG-007 코드 예시 — Dockerfile

✗ 취약 코드 (vulnerable example — do not use)

```dockerfile
FROM node:22
WORKDIR /app
# .dockerignore 가 없어 .env·.git 까지 이미지에 들어감
COPY . .
# 빌드 인자 값은 docker history 에 남는다(.npmrc 를 지워도 소용없음)
ARG NPM_TOKEN
RUN echo "//registry.npmjs.org/:_authToken=${NPM_TOKEN}" > .npmrc && npm ci && rm .npmrc
ENV DATABASE_URL=postgres://app:Winter2026!@db:5432/shop
RUN npm run build
# USER 가 없어 root 로 실행
CMD ["npm", "start"]
```

✓ 안전 코드 (safe example)

```dockerfile
# syntax=docker/dockerfile:1
FROM node:22-slim AS build
WORKDIR /app
COPY package.json package-lock.json ./
# 비밀값은 이 RUN 동안만 보이고 이미지 층에 남지 않는다
RUN --mount=type=secret,id=npmrc,target=/root/.npmrc npm ci
# .dockerignore 에 .git .env* *.pem *.key 를 넣어 둔다
COPY . .
RUN npm run build && npm prune --omit=dev

FROM node:22-slim
WORKDIR /app
ENV NODE_ENV=production
# 앱 파일은 root 소유로 두어 실행 사용자가 고칠 수 없게 한다(쓰기가 필요한 곳만 따로 볼륨·tmpfs)
COPY --from=build /app ./
# 공식 node 이미지에 있는 일반 사용자
USER node
CMD ["node", "server.js"]
# 빌드: docker build --secret id=npmrc,src=$HOME/.npmrc .
# DATABASE_URL 같은 실행 비밀값은 실행 때 환경 변수·secrets 로 넣는다
```
