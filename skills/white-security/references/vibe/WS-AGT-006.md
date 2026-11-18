<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-AGT-006 YOLO 모드 · 과다 허용 설정(AI 코딩 도구 권한)

위험 · OWASP A02 · LLM LLM03 · 에이전트 ASI02 · 에이전트 ASI05 · CWE-183 · CWE-250 · 진단가이드 보안기능-3

## 체크 항목

- 저장소에 커밋하는 AI 도구 설정에 `Bash(*)`·`Bash` 같은 전체 허용, `"defaultMode": "bypassPermissions"`, VS Code의 `"chat.tools.autoApprove": true`를 두지 않는다.
- 허용(allow)은 정확한 명령만 둔다(`Bash(npm run test)`). push·설치·삭제·배포·DB 명령은 묻게(ask) 하고, `.env`·키 파일 읽기는 막는다(deny).
- 권한 확인을 건너뛰는 모드(`--dangerously-skip-permissions`, `bypassPermissions`)는 키·운영 접속 정보가 없는 격리 환경(컨테이너·VM)에서만 쓴다. Claude Code는 `disableBypassPermissionsMode`로 이 모드를 막을 수 있다.
- AI가 자기 설정 파일(`.vscode/settings.json`, `.cursor/mcp.json`, `.claude/settings.json`)을 고치거나 새로 만들 때는 사람이 확인한다. 2025년 GitHub Copilot(CVE-2025-53773)과 Cursor(CVE-2025-54135)에서 프롬프트 인젝션으로 이런 설정 파일을 써서 확인 없이 명령을 실행하게 만드는 취약점이 고쳐졌다.

## 스킬이 잡는 방식

**찾는 곳**: `.claude/settings.json`, `.claude/settings.local.json`, `.vscode/settings.json`, `.cursor/**`, `.mcp.json`, CI 워크플로(`.github/workflows/*.yml`)의 AI 도구 실행 줄, 자동 승인 스크립트.

**검사 1. 전체 허용·확인 건너뛰기**
- 지적할 때: `Bash(*)`, `Bash`, `Bash(sudo *)`, `Bash(curl *)` 같은 넓은 allow, `bypassPermissions` 기본 모드, `chat.tools.autoApprove: true`, 스크립트·CI의 `--dangerously-skip-permissions`·`--yolo`가 있다.
- 지적하지 않을 때: 확인 건너뛰기가 네트워크·비밀값 없는 컨테이너 안에서만 돌고 그 사실이 설정이나 문서에 있다.

**검사 2. 위험한 명령이 자동 허용된다**
- 지적할 때: `rm`, `git push`, 패키지 설치, 배포, DB 마이그레이션 명령이 allow에 있다. `.env`·키 파일 읽기를 막는 deny가 없다.
- 지적하지 않을 때: 그런 명령은 ask나 deny에 있다.

**등급**
- 위험: 저장소에 커밋된 설정에 전체 허용이나 확인 건너뛰기가 있다. 또는 외부 입력(PR·이슈 글)을 읽는 CI에서 확인 없이 AI 도구가 돈다.
- 주의: 설치·push·삭제 명령이 자동 허용이다. `.env` deny가 없다.
- 참고: 개인 설정(`settings.local.json`)에만 넓은 허용이 있다.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-AGT-006 .claude/settings.json:4 — allow 에 "Bash(*)" 와 bypassPermissions 기본 모드 → 정확한 명령만 allow, push·설치·삭제는 ask, .env 는 deny, disableBypassPermissionsMode 설정`

## 코드

### json

✗ 뚫리는 코드

```json
{
  "permissions": {
    "defaultMode": "bypassPermissions",
    "allow": ["Bash(*)", "WebFetch"]
  }
}
```

✓ 막는 코드

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
