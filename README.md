<div align="center">

<img src="assets/img/favicon.svg" width="72" height="72" alt="White Security logo">

# White Security

**AI 코딩 도구가 만든 코드를 배포 전에 읽기 전용으로 점검하는 보안 검토 스킬**

[![CI](https://github.com/limindex/white-security/actions/workflows/ci.yml/badge.svg)](https://github.com/limindex/white-security/actions/workflows/ci.yml)
[![Version](https://img.shields.io/badge/version-0.1.1-2f6feb)](CHANGELOG.md)
[![License](https://img.shields.io/badge/license-CC%20BY--SA%204.0%20%2B%20MIT-555)](#라이선스)

**한국어** | [English](README.en.md)

[설치](#설치) · [사용법](#사용법) · [결과 예시](#결과-예시) · [점검 범위](#점검-범위) · [실측 결과](#실측-결과) · [안전](#안전) · [자주 묻는 질문](#자주-묻는-질문)

</div>

> 🌐 A read-only security review for code your AI coding agent wrote. Run it before you deploy.\
> Reports in Korean or English. English docs: [README.en.md](README.en.md)

White Security는 AI 코딩 도구가 만든 코드에서 흔한 보안 실수를 찾아 주는 스킬입니다. `보안 점검해 줘`라고 입력하면 설정 파일까지 포함한 프로젝트 전체를 검토하고, 무엇이 문제인지와 어떤 피해가 생기는지, 어떻게 고치는지를 표 하나로 정리해 보고합니다. 파일이 많은 프로젝트는 진입점, 인증, 데이터 접근 코드부터 읽으며, 다 읽지 못한 경우 보고서에 읽은 범위를 밝힙니다.

SQL Injection과 IDOR부터 프롬프트 인젝션, MCP Tool Poisoning, Slopsquatting까지 84개 이슈를 점검합니다. 보고서는 기본적으로 한국어로 작성되며, 영어로 요청하면 영어로 작성됩니다.

Claude Code와 Codex에서 동작을 확인했습니다. 각 이슈의 설명 문서는 [바이브 코딩 보안 가이드](https://limindex.github.io/white-security/)에서 확인할 수 있습니다.

## 설치

**Claude Code**

Claude Code 대화창에서 다음 명령을 실행하십시오.

```
/plugin marketplace add limindex/skills
/plugin install white-security@limindex
```

**Codex**

점검할 프로젝트 폴더에서 다음 명령을 실행하십시오. 스킬이 프로젝트의 `.agents/skills/` 폴더에 복사되며, Codex가 이 폴더의 스킬을 자동으로 인식합니다.

```
git clone https://github.com/limindex/white-security.git ~/white-security
mkdir -p .agents/skills && cp -r ~/white-security/plugins/white-security/skills/white-security .agents/skills/
```

**다른 에이전트**

이 스킬은 [Agent Skills](https://agentskills.io) 형식의 일반 폴더(`SKILL.md`와 참고 파일)입니다. Cursor, GitHub Copilot, OpenCode처럼 `.agents/skills/`를 읽는 도구에서도 인식될 것으로 예상하지만, 아직 직접 시험하지는 않았습니다.

## 사용법

| | Claude Code | Codex |
|---|---|---|
| 한마디로 요청 | `보안 점검해 줘` | 같음 |
| 확실하게 실행 | `/white-security:review` | `$white-security` |
| 바뀐 파일만 | `/white-security:review changed` | `$white-security changed` |
| 폴더 하나만 | `/white-security:review src/api` | `$white-security src/api` |

검토는 요청할 때만 실행됩니다. "배포할게"처럼 배포 이야기가 나오면 점검을 먼저 실행할지 한 번 묻습니다.

보고가 끝나면 고칠 항목을 직접 고를 수 있으며, 고르기 전에는 아무것도 변경하지 않습니다. 수정할 때는 해당 이슈의 규칙 문서를 따르고, 설치나 명령 실행이 필요하면 먼저 보여 드린 뒤 확인을 받습니다.

## 결과 예시

아래는 줄인 예시입니다. 실제 보고서도 같은 제목과 열로 작성됩니다.

---

**보안 검토 결과 — 전체**

**결론:** 지금 고칠 위험 1건 — 검색어가 SQL에 그대로 들어가 DB 전체를 읽고 지울 수 있음.

스택: Python · FastAPI · PostgreSQL · 설정 파일: `Dockerfile`, `.github/workflows/ci.yml`

**위험 (1)**

| # | id | 파일:줄 | 영향 | 이유 | 고칠 방법 |
|---|---|---|---|---|---|
| 1 | [WS-INJ-001](https://limindex.github.io/white-security/issues/WS-INJ-001) | `app/search.py:18` | DB 유출·삭제 | 검색어를 SQL에 이어 붙임 | 바인딩 사용 |

**주의 (1)**

| # | id | 파일:줄 | 영향 | 이유 | 고칠 방법 |
|---|---|---|---|---|---|
| 2 | [WS-SEC-001](https://limindex.github.io/white-security/issues/WS-SEC-001) | `app/config.py:7` | 로그인 위조 | 비밀키 기본값이 코드에 있음 | 기본값 삭제 |

**참고 (1)**

- 3\. [WS-XSS-001](https://limindex.github.io/white-security/issues/WS-XSS-001) `app/main.py:30` — 응답에 CSP 헤더 없음

고칠 항목 번호와 보고서 저장 여부를 알려 주세요. 고른 항목만 고칩니다.

---

- 발견 항목마다 `WS-INJ-001` 같은 번호가 붙습니다. 이 번호는 [가이드](https://limindex.github.io/white-security/)의 문서 번호와 같으며, 문서에서 공격 방식과 스택별 취약 코드·안전 코드를 확인할 수 있습니다.
- 영어 보고서가 필요하면 영어로 요청하거나 `.white-security.yml`에 `report_language: en`을 설정하십시오.

## 점검 범위

| 묶음 | 이슈 | 예 |
|---|---|---|
| AI 활용 | 8 | IPI(간접 프롬프트 인젝션), 마크다운 이미지를 통한 데이터 유출, 시스템 프롬프트 노출, 비용 폭주 |
| AI 코딩 도구 | 7 | Rules File Backdoor, MCP Tool Poisoning·Rug Pull, YOLO 모드, Memory Poisoning |
| 외부 데이터 수집·처리 | 5 | SSRF, XXE·Billion Laughs, Zip Slip, 안전하지 않은 역직렬화 |
| 자동 수집 대응 | 1 | 스크래핑·AI 크롤러 |
| 웹 애플리케이션 | 17 | SQL·명령어·템플릿 Injection, XSS, Path Traversal, 경쟁 조건, 파일 업로드 |
| 인증·인가 | 12 | IDOR, Mass Assignment, OTP 악용, 결제 금액 변조, OAuth 로그인 결함 |
| 설정·배포 | 12 | 디버그 모드, CORS, `.git`·`.env` 노출, 하드코딩된 비밀값, 요청 횟수 제한 없음 |
| 개인정보 보호 | 8 | Supabase RLS 누락, 클라이언트 코드의 `service_role` 키, Firebase 보안 규칙, 로그 속 민감정보 |
| 소프트웨어 공급망 | 8 | Slopsquatting, 취약한 의존성, 설치 스크립트, SHA 고정 안 된 Actions, 모델 파일 |
| 모바일 앱 | 6 | 앱 번들 속 비밀값, 딥링크, WebView, 암호화 안 된 통신 |

84개 이슈는 OWASP Top 10:2025, API Security Top 10:2023, Top 10 for LLM Applications 2026, Top 10 for Agentic Applications 2026, Mobile Top 10 2024, ASVS 5.0, CWE Top 25(2025)에 대응합니다. 행정안전부 구현단계 보안약점 49개, 주요정보통신기반시설 웹 취약점 21개, 개인정보 보호 기준에도 대응합니다. 가이드에는 코드 점검 대상이 아닌 사용자 보안(피싱 등) 문서 5개가 더 있습니다.

**지원 스택**(스택별 코드 예시 제공): Python(FastAPI·Django·Flask) · JavaScript / TypeScript(Next.js·React·Express) · PHP(Laravel·순수 PHP) · Supabase · Firebase · React Native · Expo · Flutter

**감시 목록**: manifest, lockfile, Docker 이미지의 버전을 심각한 CVE 목록과 대조합니다. 예를 들어 React2Shell(CVE-2025-55182)과 Next.js 미들웨어 인증 우회(CVE-2025-29927)가 있습니다. 항목마다 만료일이 있으며, 목록은 매월 점검합니다.

## 실측 결과

파일 19~23개로 된 작은 앱 5개에 문제 46개를 심은 뒤, Claude Code에서 스킬을 켠 경우와 끈 경우를 비교했습니다(`"보안 점검해 줘"`, 2026년 11월, 앱마다 모델별 2회). *스킬 켬·끔*은 심은 문제 중 찾은 수이고, *비용*과 *시간*은 스킬을 끈 경우 대비 켠 경우의 배수입니다.

| 모델 | 스킬 켬 | 스킬 끔 | 1회 비용 | 비용 | 시간 |
|---|:---:|:---:|:---:|:---:|:---:|
| Sonnet 5.5 | **92 / 92** | 91 / 92 | 약 $0.26 | 1.8배 | 약 1.9배 |
| Haiku 5.5 | **92 / 92** | 89 / 92 | 약 $0.02 | 1.9배 | 약 2.2배 |
| Opus 5.5 | **32 / 32** | 32 / 32 | 약 $0.60 | 1.5배 | 약 2배 |

**해석:** 최신 모델은 이런 문제를 대부분 이미 알고 있습니다. 스킬을 켠다고 모델이 더 똑똑해지지는 않으며, 심어 둔 문제를 찾는 수도 스킬이 없을 때와 비슷합니다. 추가 비용으로 얻는 것은 다음 네 가지입니다.

- **허락 창 없음.** Claude Code에서는 읽기 전용 검토 에이전트가 파일을 읽습니다. 스킬을 켠 25회에서는 허락 요청이 0건이었고, 스킬을 끈 경우에는 1회 평균 1.6건의 명령이 거절되었습니다. 단, `changed`(바뀐 파일만) 범위는 변경 파일 목록을 얻기 위해 git 명령을 한 번 허락해야 합니다.
- **매번 같은 보고서.** 결론 한 줄, 쉬운 말로 쓴 영향, 구체적인 고칠 방법, 이슈 번호가 항상 들어갑니다. 스킬을 켠 25회 중 25회, 끈 25회 중 0~1회 이 형식을 갖췄습니다.
- **버전 대조.** React2Shell 영향 버전이 들어간 실행 11회 중 스킬을 켠 경우는 11회, 끈 경우는 1회 지적했습니다. 감시 목록이 최신일 때만 생기는 이점입니다.
- **프로젝트 전체 검토.** 브랜치 변경분뿐 아니라 설정, CI, 에이전트 설정, DB 규칙까지 검토 범위에 넣습니다.

Codex에서는 `보안 점검해 줘`만으로 스킬이 실행되었고, 앱 3개에 심은 문제를 10/10, 10/10, 8/8 찾았습니다(각 1회). 스캐너 실행과 비밀값 파일 열기는 0건이었습니다.

Opus는 어려운 앱 2개(문제 16개)에서만 실행했습니다. 작은 예제 앱이고 설정마다 2회만 실행했으므로 1~3건의 차이는 오차 범위입니다. 비용은 Claude Code가 집계한 정가 기준입니다.

## 다른 도구와 비교

| | White Security | 내장 `/security-review` | `security-guidance` plugin |
|---|---|---|---|
| 실행 시점 | 요청할 때(주로 배포 전) | 요청할 때 | 편집·커밋할 때 자동 |
| 범위 | 프로젝트 전체(또는 바뀐 파일·경로) | 현재 브랜치의 변경분 | 진행 중인 편집·커밋 |
| 방식 | 읽기 전용 검토, 훅 없음 | 오탐 거르기를 포함한 검토 | 훅: 패턴 검사 + 모델 검토 |
| 프롬프트 인젝션·오래된 의존성·규칙 파일 | 포함 | 설계상 제외([명령 원문](https://github.com/anthropics/claude-code-security-review/blob/main/.claude/commands/security-review.md)) | — |
| 보고 | 이슈 번호·수정 문서 링크가 있는 고정 표, 한국어 또는 영어 | 발견 목록 | 에이전트에게 피드백 |

세 도구는 역할이 다릅니다. `security-guidance`는 코딩하는 동안 지켜보고, `/security-review`는 브랜치 변경분을 검토하며, White Security는 배포 전에 프로젝트 전체를 한 번 점검합니다. 셋을 함께 사용해도 됩니다(2026년 11월 확인).

같은 앱 5개로 널리 쓰이는 공개 보안 검토 스킬 3개도 함께 실행해 보았습니다(Sonnet 5.5, 각 1회). 심은 문제 46개 중 White Security는 46개, 나머지는 43~45개를 찾아 찾은 수는 비슷했습니다. 한 맥락에서 바로 검토하는 스킬보다 비용은 약 1.2~1.4배 더 들었습니다. React2Shell(CVE-2025-55182) 지적과, 같은 시험 조건에서 허락 창 0건은 White Security만 해당했습니다.

## 안전

보안 도구 때문에 보안이 약해져서는 안 되므로 다음 원칙을 지킵니다.

- **읽기 전용 검토.** Claude Code 검토 에이전트가 쓰는 도구는 Read, Grep, Glob뿐입니다. plugin에는 훅, MCP 서버, 설정, 스크립트, 실행 파일, `allowed-tools` 권한이 없으며, 테스트로 이를 강제합니다.
- **비밀값을 읽지 않음.** `.env`, 키, 자격 증명 파일은 열지 않고, 파일이 있는지와 git이 추적하는지만 확인합니다. 다른 곳에서 보인 비밀값은 가려서 표시합니다.
- **동의 없이 네트워크·스캐너를 실행하지 않음.** 기본 의존성 스캐너는 `osv-scanner`입니다. 저장소 설정을 따르는 `npm`/`yarn`/`pnpm audit`은 신뢰하지 않는 코드에서 실행하지 않습니다.
- **프로젝트 글은 데이터로 취급.** 주석, README, `AGENTS.md`가 점검을 건너뛰라고 하거나 다른 경로를 열라고 하면, 그 지시 자체를 발견 항목으로 보고합니다.
- **직접 만들었거나 신뢰하는 코드 대상.** 남이 만든 코드는 읽기 전용으로만 검토하고, 보고서에도 그 사실을 표시합니다.
- **보안 감사를 대신하지 않음.** 흔한 실수를 줄이기 위한 도구이며, 보안 감사나 침투 테스트를 대신하지 않습니다. 프로젝트가 "안전하다"고 판정하지 않습니다.

이 프로젝트 자체의 취약점은 [SECURITY.md](SECURITY.md)의 절차에 따라 제보해 주십시오.

## 자주 묻는 질문

<details>
<summary><b>비용이 많이 드나요?</b></summary>

실측에서는 스킬 없이 "보안 점검해 줘"라고 요청할 때의 약 1.5~1.9배였습니다. 작은 앱 기준 정가로 Sonnet은 약 $0.26, Opus는 약 $0.60이며, 1~2분이 걸렸습니다. 프로젝트가 크면 비용이 더 듭니다.

</details>
<details>
<summary><b>코딩하는 동안 계속 지켜보나요?</b></summary>

아니요. 요청할 때만 실행됩니다. 실측에서 상시 점검 방식은 비용이 약 3분의 1 늘었지만 더 찾은 문제는 없었습니다.

</details>
<details>
<summary><b>코드를 직접 고치나요?</b></summary>

검토 중에는 고치지 않습니다. 보고가 끝난 뒤 고칠 항목을 직접 고르면 그 항목만 고칩니다.

</details>
<details>
<summary><b>왜 한국어인가요?</b></summary>

국내 팀을 위한 가이드로 시작했기 때문에 주민등록번호 처리, 접속기록 같은 국내 개인정보 기준도 점검합니다. 보고서는 기본적으로 한국어로 작성되며, 영어로 요청하거나 `report_language: en`을 설정하면 영어로 작성됩니다.

</details>
<details>
<summary><b>특정 발견 항목을 끌 수 있나요?</b></summary>

네. `.white-security.yml`에 만료일이 있는 예외를 두거나 `white-security-ignore` 주석을 사용할 수 있습니다. 자세한 규칙은 스킬의 `references/config.md`를 참고하십시오. 인증, 웹, 배포 묶음은 통째로 끌 수 없습니다.

</details>

## 기여

AI 코딩 도구가 자꾸 틀리는 패턴, 여전히 안전하지 않은 수정 예시, 이슈 문서의 오류를 발견했다면 [CONTRIBUTING.md](CONTRIBUTING.md)를 참고해 주십시오.

## 라이선스

문서와 스킬 글은 [CC BY-SA 4.0](LICENSE), 코드와 코드 예시는 [MIT](LICENSE-CODE) 라이선스를 따릅니다. 출처는 [SOURCES.md](SOURCES.md)에 정리되어 있습니다. 발행: LimIndex.
