# Changelog

플러그인(`plugins/white-security/`) 버전별 변경. 버전은 `plugins/white-security/.claude-plugin/plugin.json` 의 `version` 과 같아야 한다.

## 0.1.0

- 첫 판: `/white-security:review` 검토 스킬과 읽기 전용 보안 검토 에이전트(`white-security-reviewer`). 요청할 때만 실행하는 프로젝트 전체 검토, 배포 전 점검 권장(배포 이야기에는 점검 여부를 먼저 질문). 규칙 폴더 읽기 허용은 검토 스킬에만.
- 묶음별 점검 규칙(references/<group>/), 검토 절차(review.md), 의존성 점검(deps.md), 감시 목록(advisories.md), 프로젝트 설정(config.md).
