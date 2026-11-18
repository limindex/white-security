---
id: WS-CFG-007
title: Insecure Container (root 실행 컨테이너·이미지 속 비밀값)
slug: insecure-container
group: deploy
severity: high
site_only: false
depth: brief
languages: [dockerfile, yaml]
maps:
  owasp2025: [A02]
  api2023: [API8]
  llm2026: []
  agentic2026: []
  asvs5: [V13.3.1, V13.2.2]
  cwe: [CWE-250, CWE-269, CWE-538, CWE-798]
  kr_guide: ["보안기능-3", "보안기능-6"]
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Dockerfile, Docker Compose, Kubernetes, 컨테이너로 배포하는 모든 앱]
severity_reason: "영향: 이미지를 받은 사람 누구나 비밀값 확인, 앱 하나가 뚫리면 서버 전체 장악 · 악용: 공개 이미지 층 분석, docker.sock 으로 호스트 명령 실행"
sources:
  - title: "Docker Docs - Build secrets (ARG·ENV 는 최종 이미지에 남음)"
    url: https://docs.docker.com/build/building/secrets/
    year: 2026
  - title: OWASP Docker Security Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Docker_Security_Cheat_Sheet.html
    year: 2026
  - title: "Kubernetes - Pod Security Standards (Restricted)"
    url: https://kubernetes.io/docs/concepts/security/pod-security-standards/
    year: 2026
  - title: "Docker Docs - Compose file reference: services"
    url: https://docs.docker.com/reference/compose-file/services/
    year: 2026
  - title: "OWASP ASVS 5.0 - V13.3 Secret Management"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

컨테이너를 root 사용자로 실행하고, `ENV`·`ARG`·`COPY .env`로 비밀값을 이미지 층(layer)에 넣거나 `privileged: true`·`/var/run/docker.sock` 마운트로 호스트 권한을 넘겨, 이미지가 새거나 앱 하나가 뚫리면 비밀값과 서버 전체가 넘어가는 문제(진단가이드 이름: 중요한 자원에 대한 잘못된 권한 설정). 이미지 층에 들어간 값은 다음 줄에서 지워도 `docker history`나 층 파일로 다시 꺼낼 수 있음.

## 대응 방법

```text
Dockerfile·docker-compose·Kubernetes 설정에서 root 실행, 이미지에 들어가는 비밀값(ENV·ARG·COPY .env), privileged·docker.sock 마운트를 찾아 줘.
앱은 일반 사용자로 실행하고, 빌드에 필요한 비밀값은 RUN --mount=type=secret 으로, 실행 비밀값은 실행 환경에서 넣게 바꿔 줘.
.dockerignore 에 .git·.env*·*.pem·*.key 를 넣어 줘.
```

- `docker run --rm <이미지> id -u` 결과가 0이 아니어야 한다.
- `docker history --no-trunc <이미지>` 출력에 키·비밀번호가 보이지 않아야 한다.

## 점검 항목

- 실행 이미지는 `USER`로 root가 아닌 사용자를 지정해야 한다.
- 비밀값을 `ENV`·`ARG`·`COPY`로 이미지에 넣지 않아야 한다.
- 빌드에 필요한 비밀값은 `RUN --mount=type=secret`으로만 써야 한다.
- `.dockerignore`에 `.git`·`.env*`·키 파일을 넣어야 한다.
- `privileged: true`·`docker.sock` 마운트·호스트 네트워크는 쓰지 않아야 한다.
- Kubernetes는 `runAsNonRoot`·`allowPrivilegeEscalation: false`를 둬야 한다.

## 탐지 규칙

- **찾는 곳**: `privileged: true`, `docker.sock`, `COPY . .`, `USER root`, `runAsNonRoot`, `allowPrivilegeEscalation`, 그 밖에 Dockerfile 의 ENV·ARG 줄과 COPY .env, .dockerignore 유무, compose 의 network_mode·cap_add, Kubernetes securityContext.
- **검사 1 — 이미지 속 비밀값**: 지적할 때: `ENV`·`ARG`에 이름이 KEY·SECRET·TOKEN·PASSWORD인 값을 넣는 경우(빌드 인자로 넘겨도 이미지 기록에 남음), `COPY .env`·키 파일 복사, `.dockerignore` 없이 `COPY . .`를 쓰는 경우. / 지적하지 않을 때: `RUN --mount=type=secret`, 실행 때 환경 변수·secrets로 넣는 경우, `.dockerignore`가 `.env*`·`.git`을 막는 경우.
- **검사 2 — 과한 권한**: 지적할 때: 실행 단계에 `USER`가 없거나 `USER root`, `privileged: true`, `/var/run/docker.sock` 마운트, `network_mode: host`, `cap_add`의 `SYS_ADMIN`, Kubernetes `runAsNonRoot` 없음·`allowPrivilegeEscalation: true`인 경우. / 지적하지 않을 때: 멀티 스테이지의 빌드 단계만 root이고 실행 단계는 일반 사용자인 경우, compose·Kubernetes 쪽에서 `user`·`runAsUser`를 정한 경우.
- **등급**: 위험: 실제 비밀값이 이미지에 들어간 경우, `docker.sock` 마운트·`privileged: true`. / 주의: root 실행, `.dockerignore` 없이 `COPY . .`. / 참고: 기반 이미지 `latest` 태그, 읽기 전용 파일 시스템 미설정.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-CFG-007 Dockerfile:6 — ARG NPM_TOKEN 이 빌드 기록(docker history)에 남음 → RUN --mount=type=secret,id=npmrc 로 바꾸고 이미 쓴 토큰은 폐기`
- **검토 주의**: 이미 배포·공유된 이미지에 비밀값이 들어갔다면 이미지를 지우는 것으로 끝나지 않으므로 WS-SEC-001 기준으로 키 교체를 먼저 알려야 한다.
- **고칠 방법**: 비밀값은 빌드 secret 마운트와 실행 환경으로 옮기고, 실행 단계에 일반 사용자와 최소 권한 설정을 넣어야 한다.

## 코드 예시

### dockerfile

✗ 취약 코드

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

✓ 안전 코드

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
COPY --from=build --chown=node:node /app ./
# 공식 node 이미지에 있는 일반 사용자
USER node
CMD ["node", "server.js"]
# 빌드: docker build --secret id=npmrc,src=$HOME/.npmrc .
# DATABASE_URL 같은 실행 비밀값은 실행 때 환경 변수·secrets 로 넣는다
```

### yaml

✗ 취약 코드

```yaml
# docker-compose.yml
services:
  app:
    build: .
    privileged: true
    volumes:
      - /var/run/docker.sock:/var/run/docker.sock   # 컨테이너에서 호스트 Docker 조종 = 호스트 root
    environment:
      - DATABASE_URL=postgres://app:Winter2026!@db:5432/shop
```

✓ 안전 코드

```yaml
# docker-compose.yml
services:
  app:
    build: .
    user: "1000:1000"
    read_only: true
    tmpfs: [/tmp]
    cap_drop: [ALL]
    security_opt: ["no-new-privileges:true"]
    env_file: .env.production        # 서버에만 있는 파일, 저장소·이미지에 넣지 않음

# Kubernetes 라면 컨테이너 securityContext 에
#   runAsNonRoot: true, allowPrivilegeEscalation: false,
#   readOnlyRootFilesystem: true, capabilities: { drop: [ALL] }
```
