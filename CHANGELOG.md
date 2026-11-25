# Changelog

플러그인(`plugins/white-security/`) 버전별 변경. 버전은 `plugins/white-security/.claude-plugin/plugin.json` 의 `version` 과 같아야 한다.

## 0.1.2

- 보고서 표에 `국내 기준` 열 추가: 발견 항목마다 대표 기준 번호(예: 행안부 입력-1 · 주통기 SI)를 표시. 스킬의 이슈 id 목록에 국내 기준 대표 항목을 붙임(행정안전부 구현단계 보안약점 첫 항목, 주요정보통신기반시설 웹 취약점 첫 코드).
- 재측정 반영: 국내 기준은 그 id 의 괄호 값만 옮기도록 예시·금지 규칙 추가, 주의 표에도 7열 머리줄을 그대로 적음, 메인 대화는 검토 에이전트 보고서를 고치지 않고 그대로 옮김(이상하면 보고서 뒤에 한 줄).

## 0.1.1

- `changed` 범위의 git 명령을 파이프 없는 한 줄(`git status --porcelain=v1 -z --untracked-files=all`)로 줄임. 실사용 시험에서 git 명령 세 번에 허락 창이 세 번 뜨던 문제.
- 결론 줄 규칙: 위험이 없으면 "없음"이 아니라 "찾지 못함"으로, 범위 안 파일을 다 읽지 못했으면 결론에 `파일 N/M개만 읽음`을 밝힘. 큰 프로젝트에서 일부만 읽고 "위험 없음"으로 단정하던 문제.

## 0.1.0

- 첫 판: `white-security` 스킬(Agent Skills 형식)과 Claude Code 용 `/white-security:review` 명령. 요청할 때만 실행하는 프로젝트 전체 읽기 전용 검토(변경분만 보지 않음), 배포 전 점검 권장(배포 이야기에는 점검 여부를 먼저 질문).
- 가벼운 안내 방식: 판단은 모델이 하고, 스킬은 검토 범위·놓치기 쉬운 항목·안전 규칙·한국어 보고 형식(결론·영향·고칠 방법·문서 id)·감시 목록·이슈 id 목록만 안내. 검토 중 규칙 파일을 읽지 않아 허용 설정(`allowed-tools`) 없음.
- Claude Code에서 검토를 맡는 읽기 전용 보안 검토 에이전트(`white-security-reviewer`, 도구는 Read·Grep·Glob뿐이라 허용 확인 없이 진행).
- 고칠 때 쓰는 이슈별 규칙(references/<group>/)과 언어별 코드 예시, 의존성 점검(deps.md), 감시 목록(advisories.md), 프로젝트 설정(config.md).
