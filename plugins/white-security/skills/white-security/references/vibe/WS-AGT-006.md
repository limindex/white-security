<!-- generated from white-security issues; do not edit -->
# WS-AGT-006 YOLO Mode (AI 코딩 도구 과다 허용 설정)

심각도: 위험
기준: OWASP Top 10:2025 A02(보안 설정 오류) · OWASP LLM 2026 LLM03(과도한 권한 위임) · OWASP 에이전트 2026 ASI02(도구 오용·악용) · OWASP 에이전트 2026 ASI05(예상치 못한 코드 실행) · CWE-183 · CWE-250 · 국내 보안약점 49 보안기능-3(중요한 자원에 대한 잘못된 권한 설정)

## 점검 항목

- repository에 커밋하는 AI 도구 설정에는 `Bash(*)`·`Bash` 같은 전체 허용, `"defaultMode": "bypassPermissions"`, VS Code의 `"chat.tools.global.autoApprove": true`(1.104 전 이름 `chat.tools.autoApprove`), `chat.tools.terminal.autoApprove`의 `"/.*/": true` 같은 넓은 정규식이 없어야 한다.
- 허용(allow)에는 정확한 명령만 두어야 한다(`Bash(npm run test)`). push·설치·삭제·배포·DB 명령은 묻게(ask) 하고, `.env`·키 파일 읽기는 막아야(deny) 한다.
- 권한 확인을 건너뛰는 모드(`--dangerously-skip-permissions`, `bypassPermissions`)는 키·운영 접속 정보가 없는 격리 환경(컨테이너·VM)에서만 써야 한다. Claude Code는 `disableBypassPermissionsMode`로 이 모드를 막을 수 있음.
- AI가 자기 설정 파일(`.vscode/settings.json`, `.cursor/mcp.json`, `.claude/settings.json`)을 고치거나 새로 만들 때는 사람이 확인해야 한다. 2025년 GitHub Copilot(CVE-2025-53773)과 Cursor(CVE-2025-54135)에서 프롬프트 인젝션으로 이런 설정 파일을 써서 확인 없이 명령을 실행하게 만드는 취약점이 수정됨.

## 탐지 규칙

- **찾는 곳**: `.claude/settings.json`, `.claude/settings.local.json`, `.vscode/settings.json`, `.cursor/**`, `.mcp.json`, CI 워크플로(`.github/workflows/*.yml`)의 AI 도구 실행 줄, 자동 승인 스크립트.
- **검사 1 — 전체 허용·확인 건너뛰기**: 지적할 때: `Bash(*)`, `Bash`, `Bash(sudo *)`, `Bash(curl *)` 같은 넓은 allow, `bypassPermissions` 기본 모드, `chat.tools.global.autoApprove: true`(옛 이름 `chat.tools.autoApprove`), `chat.tools.terminal.autoApprove`의 `/.*/`처럼 모든 명령에 맞는 규칙, 스크립트·CI의 `--dangerously-skip-permissions`·`--yolo`가 있는 경우. / 지적하지 않을 때: 확인 건너뛰기가 네트워크·비밀값 없는 컨테이너 안에서만 돌고 그 사실이 설정이나 문서에 있는 경우.
- **검사 2 — 위험한 명령의 자동 허용**: 지적할 때: `rm`, `git push`, 패키지 설치, 배포, DB 마이그레이션 명령이 allow(또는 VS Code terminal 자동 승인)에 있는 경우. `.env`·키 파일 읽기를 막는 deny가 없는 경우. / 지적하지 않을 때: 그런 명령이 ask나 deny에 있는 경우.
- **등급**: 위험: repository에 커밋된 설정에 전체 허용이나 확인 건너뛰기가 있는 경우. 외부 입력(PR·이슈 글)을 읽는 CI에서 확인 없이 AI 도구가 도는 경우. / 주의: 설치·push·삭제 명령이 자동 허용인 경우. `.env` deny가 없는 경우. / 참고: 개인 설정(`settings.local.json`)에만 넓은 허용이 있는 경우.
- **보고 예**: `[위험] WS-AGT-006 .claude/settings.json:4 — allow 에 "Bash(*)" 와 bypassPermissions 기본 모드 → 정확한 명령만 allow, push·설치·삭제는 ask, .env 는 deny, disableBypassPermissionsMode 설정`

## 코드 예시

### json

✗ 취약 코드 (vulnerable example — do not use)

```json
{
  "permissions": {
    "defaultMode": "bypassPermissions",
    "allow": ["Bash(*)", "WebFetch"]
  }
}
```

✓ 안전 코드 (safe example)

```json
{
  "permissions": {
    "allow": ["Bash(npm run test)", "Bash(npm run lint)", "Bash(git status)", "Bash(git diff *)"],
    "ask": ["Bash(git push *)", "Bash(npm install *)", "Bash(rm *)"],
    "deny": ["Read(./.env)", "Read(./.env.*)", "Read(./secrets/**)", "Bash(curl *)"],
    "disableBypassPermissionsMode": "disable"
  }
}
```
