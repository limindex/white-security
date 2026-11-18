[English](README.md) | 한국어

# White Security

AI 코딩 도구가 만든 코드를 배포 전에 읽기 전용으로 점검하는 보안 검토 스킬.

`보안 점검해 줘` 한마디면 AI 코딩 도구가 설정 파일까지 프로젝트 전체를 읽음. 결과는 표 하나로 정리됨: 무엇이 문제인지, 어떤 피해가 나는지, 어떻게 고치는지. 점검 대상은 SQL Injection·IDOR부터 프롬프트 인젝션·MCP Tool Poisoning·Slopsquatting까지 84개 이슈. 보고서는 기본이 한국어이고, 영어로 요청하면 영어로 나옴.

> **상태:** v0.1.0 공개 전. Claude Code·Codex에서 동작 확인. 문서 사이트: [바이브 코딩 보안 가이드](https://limindex.github.io/white-security/)

## 설치

**Claude Code**

```
/plugin marketplace add limindex/skills
/plugin install white-security@limindex
```

**Codex**: 프로젝트의 `.agents/skills/` 폴더에 스킬을 복사.

```
git clone https://github.com/limindex/white-security.git ~/white-security
mkdir -p .agents/skills && cp -r ~/white-security/plugins/white-security/skills/white-security .agents/skills/
```

**다른 에이전트**: 스킬은 [Agent Skills](https://agentskills.io) 형식의 일반 폴더(`SKILL.md`와 참고 파일). Cursor·GitHub Copilot·OpenCode 등 `.agents/skills/`를 읽는 도구라면 인식할 것으로 예상되지만, 아직 직접 시험하지 않음.

## 사용법

| | Claude Code | Codex |
|---|---|---|
| 한마디로 | `보안 점검해 줘` | 같음 |
| 확실하게 실행 | `/white-security:review` | `$white-security` |
| 바뀐 파일만 | `/white-security:review changed` | `$white-security changed` |
| 폴더 하나만 | `/white-security:review src/api` | `$white-security src/api` |

요청할 때만 실행됨. "배포할게"처럼 배포 이야기가 나오면 점검을 먼저 돌릴지 한 번 물어봄.

보고 뒤에는 고칠 항목을 사용자가 직접 고르고, 고르기 전에는 아무것도 바꾸지 않음. 고칠 때는 그 이슈의 규칙 문서를 따르고, 설치나 명령 실행이 필요하면 먼저 보여 주고 확인을 받음.

## 결과 예시

줄인 예시. 실제 보고서도 같은 제목과 열로 나옴.

```
# 보안 검토 결과 — 전체

**결론:** 지금 고칠 위험 1건 — 검색어가 SQL에 그대로 들어가 DB 전체를 읽고 지울 수 있음.

스택: Python · FastAPI · PostgreSQL · 설정 파일: Dockerfile, .github/workflows/ci.yml

## 위험 (1)
| # | id         | 파일:줄           | 영향                  | 이유                          | 고칠 방법              |
| 1 | WS-INJ-001 | app/search.py:18  | DB 전체 조회·삭제 가능 | 검색어 q가 f-string으로 SQL에 들어감 | 매개변수 바인딩으로 교체 |

## 주의 (1)
| 2 | WS-SEC-001 | app/config.py:7   | 세션 위조 가능         | 환경 변수가 없으면 고정 비밀키로 동작 | 기본값을 지우고 값이 없으면 시작 단계에서 오류 |

## 참고 (1)
- 3. WS-XSS-001 app/main.py:30 — 응답에 CSP 헤더 없음
```

- 발견 항목마다 `WS-INJ-001` 같은 번호가 붙음. 번호는 [가이드](https://limindex.github.io/white-security/)의 문서와 같고, 문서에는 공격 설명과 스택별 취약 코드·안전 코드가 있음.
- 영어 보고서가 필요하면 영어로 요청하거나 `.white-security.yml`에 `report_language: en` 설정.

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

84개 이슈는 OWASP Top 10:2025, API Security Top 10:2023, Top 10 for LLM Applications 2026, Top 10 for Agentic Applications 2026, Mobile Top 10 2024, ASVS 5.0, CWE Top 25(2025)에 대응. 행정안전부 구현단계 보안약점 49개, 주요정보통신기반시설 웹 취약점 21개, 개인정보 보호 기준에도 대응. 가이드에는 코드 점검 대상이 아닌 사용자 보안(피싱 등) 문서 5개가 더 있음.

**지원 스택**(스택별 코드 예시 제공): Python(FastAPI·Django·Flask) · JavaScript / TypeScript(Next.js·React·Express) · PHP(Laravel·순수 PHP) · Supabase · Firebase · React Native · Expo · Flutter

**감시 목록**: manifest·lockfile·Docker 이미지 버전을 심각한 CVE 목록과 대조. 예: React2Shell(CVE-2025-55182), Next.js 미들웨어 인증 우회(CVE-2025-29927). 항목마다 만료일이 있고 목록은 매월 점검.

## 실측 결과

파일 19~23개짜리 작은 앱 5개에 문제 46개를 심고, Claude Code에서 스킬을 켠 경우와 끈 경우를 비교(`"보안 점검해 줘"`, 2026년 11월, 앱마다 모델별 2회).

| 모델 | 찾은 수(스킬 켬) | 찾은 수(스킬 끔) | 비용(끔 대비) | 시간(끔 대비) |
|---|---|---|---|---|
| Claude Sonnet 5.5 | 92 / 92 | 91 / 92 | 1.8배(1회 약 $0.26) | 약 1.9배(약 70초) |
| Claude Haiku 5.5 | 92 / 92 | 89 / 92 | 1.9배(1회 약 $0.02) | 약 2.2배 |
| Claude Opus 5.5 | 32 / 32 | 32 / 32 | 1.5배(1회 약 $0.60) | 약 2배 |

**해석:** 요즘 모델은 이런 문제를 대부분 이미 알고 있음. 스킬이 모델을 더 똑똑하게 만들지는 않으며, 심어 둔 문제를 찾는 수는 스킬이 없을 때와 비슷함. 추가 비용으로 얻는 것은 아래 네 가지.

- **허락 창 없음.** Claude Code에서는 읽기 전용 검토 에이전트가 파일을 읽음. 스킬 켬 25회에서 허락 요청 0건, 스킬 끔에서는 1회 평균 1.6건의 명령이 거절됨.
- **매번 같은 보고서.** 결론 한 줄, 쉬운 말로 쓴 영향, 구체적인 고칠 방법, 이슈 번호. 스킬 켬 25회 중 25회, 끔 25회 중 0~1회.
- **버전 대조.** React2Shell 영향 버전 실행 11회 중 스킬 켬은 11회, 끔은 1회 지적. 감시 목록이 최신일 때만 생기는 이점.
- **프로젝트 전체.** 브랜치 변경분만이 아니라 설정·CI·에이전트 설정·DB 규칙까지 읽음.

Codex에서는 `보안 점검해 줘`만으로 스킬이 발동했고, 앱 3개에서 심은 문제를 10/10·10/10·8/8 찾음(각 1회). 스캐너 실행과 비밀값 파일 열기는 0건.

Opus는 어려운 앱 2개(문제 16개)에서만 실행. 한계: 문제를 심은 작은 앱이고 설정마다 2회뿐이라 1~3건 차이는 오차 범위. 비용은 Claude Code가 집계한 정가 기준.

## 다른 도구와 비교

| | White Security | 내장 `/security-review` | `security-guidance` plugin |
|---|---|---|---|
| 실행 시점 | 요청할 때(주로 배포 전) | 요청할 때 | 편집·커밋할 때 자동 |
| 범위 | 프로젝트 전체(또는 바뀐 파일·경로) | 현재 브랜치의 변경분 | 진행 중인 편집·커밋 |
| 방식 | 읽기 전용 검토, 훅 없음 | 오탐 거르기를 포함한 검토 | 훅: 패턴 검사 + 모델 검토 |
| 프롬프트 인젝션·오래된 의존성·규칙 파일 | 포함 | 설계상 제외([명령 원문](https://github.com/anthropics/claude-code-security-review/blob/main/.claude/commands/security-review.md)) | — |
| 보고 | 이슈 번호·수정 문서 링크가 있는 고정 표, 한국어 또는 영어 | 발견 목록 | 에이전트에게 피드백 |

역할이 다름. `security-guidance`는 코딩하는 동안 지켜보고, `/security-review`는 브랜치 변경분을 보며, White Security는 배포 전 프로젝트 전체를 한 번 훑음. 셋을 함께 써도 됨(2026년 11월 확인).

## 안전

보안 도구 때문에 보안이 약해지면 안 되므로 아래 원칙을 지킴.

- **읽기 전용 검토.** Claude Code 검토 에이전트의 도구는 Read·Grep·Glob뿐. plugin에는 훅·MCP 서버·설정·스크립트·실행 파일·`allowed-tools` 권한이 없고, 테스트로 강제함.
- **비밀값을 읽지 않음.** `.env`·키·자격 증명 파일은 열지 않고, 있는지와 git이 추적하는지만 확인. 다른 곳에서 보인 비밀값은 가려서 표시.
- **동의 없이 네트워크·스캐너 실행 안 함.** 기본 의존성 스캐너는 `osv-scanner`. 저장소 설정을 따르는 `npm`/`yarn`/`pnpm audit`은 신뢰하지 않는 코드에서 실행하지 않음.
- **프로젝트 글은 데이터.** 주석·README·`AGENTS.md`가 점검을 건너뛰라고 하거나 다른 경로를 열라고 하면 그것 자체를 발견 항목으로 보고.
- **내가 만든·신뢰하는 코드 대상.** 남이 만든 코드에서는 읽기 전용으로만 검토하고 보고서에 그렇게 표시.
- **보안 감사가 아님.** 흔한 실수를 줄이는 용도이며 보안 감사·침투 테스트를 대신하지 않음. 프로젝트가 "안전하다"고 말하지 않음.

이 프로젝트 자체의 취약점 제보는 [SECURITY.md](SECURITY.md) 참고.

## 자주 묻는 질문

**비용이 많이 드나?** 실측에서 스킬 없이 "보안 점검해 줘"라고 할 때의 약 1.5~1.9배. 작은 앱 기준 정가로 Sonnet 약 $0.26, Opus 약 $0.60, 1~2분. 프로젝트가 크면 더 듦.

**코딩하는 동안 계속 지켜보나?** 아님. 요청할 때만 실행. 실측에서 상시 모드는 비용이 약 3분의 1 늘었지만 더 찾지 못함.

**코드를 고치나?** 검토 중에는 고치지 않음. 보고 뒤 고칠 항목을 사용자가 고름.

**왜 한국어인가?** 국내 팀을 위한 가이드로 시작해 주민등록번호 처리·접속기록 같은 국내 개인정보 기준도 점검함. 보고서는 기본 한국어이고, 영어로 요청하거나 `report_language: en`을 설정하면 영어.

**특정 발견 항목을 끌 수 있나?** 가능. `.white-security.yml`의 만료일 있는 예외나 `white-security-ignore` 주석 사용. 규칙은 스킬의 `references/config.md`. 인증·웹·배포 묶음은 통째로 끌 수 없음.

## 기여

AI 코딩 도구가 자꾸 틀리는 패턴, 여전히 안전하지 않은 수정 예시, 이슈 문서의 오류를 발견했다면 [CONTRIBUTING.md](CONTRIBUTING.md) 참고.

## 라이선스

문서·스킬 글: [CC BY-SA 4.0](LICENSE). 코드·코드 예시: [MIT](LICENSE-CODE). 출처: [SOURCES.md](SOURCES.md). 발행 LimIndex.
