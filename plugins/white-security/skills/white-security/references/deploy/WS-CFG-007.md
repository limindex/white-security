<!-- generated from white-security issues; do not edit -->
# WS-CFG-007 Insecure Container (root 실행 컨테이너·이미지 속 비밀값)

## 탐지 규칙

- **찾는 곳**: `privileged: true`, `docker.sock`, `COPY . .`, `USER root`, `runAsNonRoot`, `allowPrivilegeEscalation`, 그 밖에 Dockerfile 의 ENV·ARG 줄과 COPY .env, .dockerignore 유무, compose 의 network_mode·cap_add, Kubernetes securityContext.
- **검사 1 — 이미지 속 비밀값**: 지적할 때: `ENV`·`ARG`에 이름이 KEY·SECRET·TOKEN·PASSWORD인 값을 넣는 경우(빌드 인자로 넘겨도 이미지 기록에 남음), `COPY .env`·키 파일 복사, `.dockerignore` 없이 `COPY . .`를 쓰는 경우. / 지적하지 않을 때: `RUN --mount=type=secret`, 실행 때 환경 변수·secrets로 넣는 경우, `.dockerignore`가 `.env*`·`.git`을 막는 경우.
- **검사 2 — 과한 권한**: 지적할 때: 실행 단계에 `USER`가 없거나 `USER root`, `privileged: true`, `/var/run/docker.sock` 마운트, `network_mode: host`, `cap_add`의 `SYS_ADMIN`, Kubernetes `runAsNonRoot` 없음·`allowPrivilegeEscalation: true`인 경우. / 지적하지 않을 때: 멀티 스테이지의 빌드 단계만 root이고 실행 단계는 일반 사용자인 경우, compose·Kubernetes 쪽에서 `user`·`runAsUser`를 정한 경우.
- **등급**: 위험: 실제 비밀값이 이미지에 들어간 경우, `docker.sock` 마운트·`privileged: true`. / 주의: root 실행, `.dockerignore` 없이 `COPY . .`. / 참고: 기반 이미지 `latest` 태그, 읽기 전용 파일 시스템 미설정.
- **보고 예**: `[위험] WS-CFG-007 Dockerfile:6 — ARG NPM_TOKEN 이 빌드 기록(docker history)에 남음 → RUN --mount=type=secret,id=npmrc 로 바꾸고 이미 쓴 토큰은 폐기`
- **고칠 방법**: 비밀값은 빌드 secret 마운트와 실행 환경으로 옮기고, 실행 단계에 일반 사용자와 최소 권한 설정을 넣어야 한다.

## 점검 항목

- 실행 이미지는 `USER`로 root가 아닌 사용자를 지정해야 한다.
- 비밀값을 `ENV`·`ARG`·`COPY`로 이미지에 넣지 않아야 한다.
- 빌드에 필요한 비밀값은 `RUN --mount=type=secret`으로만 써야 한다.
- `.dockerignore`에 `.git`·`.env*`·키 파일을 넣어야 한다.
- `privileged: true`·`docker.sock` 마운트·호스트 네트워크는 쓰지 않아야 한다.
- Kubernetes는 `runAsNonRoot`·`allowPrivilegeEscalation: false`를 둬야 한다.

## 검토 주의

이미 배포·공유된 이미지에 비밀값이 들어갔다면 이미지를 지우는 것으로 끝나지 않으므로 WS-SEC-001 기준으로 키 교체를 먼저 알려야 한다.

Code examples (read only the file for the stack you are writing): `WS-CFG-007.code.dockerfile.md` (Dockerfile) · `WS-CFG-007.code.yaml.md` (YAML)

기본 심각도: 위험
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP API 2023 API8(보안 설정 오류) · ASVS 5.0 V13.3.1 · ASVS 5.0 V13.2.2 · CWE-250 · CWE-269 · CWE-538 · CWE-798 · 국내 보안약점 49 보안기능-3(중요한 자원에 대한 잘못된 권한 설정) · 국내 보안약점 49 보안기능-6(하드코드된 중요정보)
