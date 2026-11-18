<!-- generated from white-security issues; do not edit -->
# WS-CFG-007 코드 예시 — YAML

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
