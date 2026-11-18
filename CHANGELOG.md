# Changelog

플러그인(`plugins/white-security/`) 버전별 변경. 버전은 `plugins/white-security/.claude-plugin/plugin.json` 의 `version` 과 같아야 한다.

## 0.1.0

- 첫 판: `white-security` 스킬(Agent Skills 형식)과 Claude Code 용 `/white-security:review` 명령. 요청할 때만 실행하는 프로젝트 전체 읽기 전용 검토(변경분만 보지 않음), 배포 전 점검 권장(배포 이야기에는 점검 여부를 먼저 질문).
- 가벼운 안내 방식: 판단은 모델이 하고, 스킬은 검토 범위·놓치기 쉬운 항목·안전 규칙·한국어 보고 형식(결론·영향·고칠 방법·문서 id)·감시 목록·이슈 id 목록만 안내. 검토 중 규칙 파일을 읽지 않아 허용 설정(`allowed-tools`) 없음.
- 소스 파일 40개가 넘는 큰 프로젝트용 읽기 전용 보안 검토 에이전트(`white-security-reviewer`, Claude Code).
- 고칠 때 쓰는 이슈별 규칙(references/<group>/)과 언어별 코드 예시, 의존성 점검(deps.md), 감시 목록(advisories.md), 프로젝트 설정(config.md).
