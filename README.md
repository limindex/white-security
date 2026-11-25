<div align="center">

<img src="assets/img/favicon.svg" width="72" height="72" alt="White Security logo">

# White Security

**AI 코딩 도구가 만든 코드를 배포 전에 읽기 전용으로 점검하는 보안 검토 스킬**

[![CI](https://github.com/limindex/white-security/actions/workflows/ci.yml/badge.svg)](https://github.com/limindex/white-security/actions/workflows/ci.yml)
[![Version](https://img.shields.io/badge/version-0.1.1-2f6feb)](CHANGELOG.md)
[![License](https://img.shields.io/badge/license-CC%20BY--SA%204.0%20%2B%20MIT-555)](#라이선스)

**한국어** | [English](README.en.md)

</div>

> 🌐 A read-only security review for code your AI coding agent wrote. Run it before you deploy.\
> English docs: [README.en.md](README.en.md)

White Security는 AI 코딩 도구가 만든 코드에서 흔한 보안 실수를 찾는 스킬입니다. `보안 점검해 줘`라고 요청하면 설정 파일을 포함한 프로젝트 전체를 읽기 전용으로 검토하고, 문제와 영향, 고칠 방법을 표로 보고합니다. 브랜치 변경분을 보는 Claude Code 내장 `/security-review`와 달리, 배포 전에 프로젝트 전체를 한 번 점검하는 용도입니다.

이슈별 설명과 취약 코드·안전 코드 예시는 [바이브 코딩 보안 가이드](https://limindex.github.io/white-security/)에서 확인할 수 있습니다.

## 설치

**Claude Code**

```
/plugin marketplace add limindex/skills
/plugin install white-security@limindex
```

**Codex**

점검할 프로젝트 폴더에서 실행하십시오. 스킬이 `.agents/skills/`에 복사되며 Codex가 자동으로 인식합니다.

```
git clone https://github.com/limindex/white-security.git ~/white-security
mkdir -p .agents/skills && cp -r ~/white-security/plugins/white-security/skills/white-security .agents/skills/
```

`.agents/skills/`를 읽는 다른 에이전트(Cursor, GitHub Copilot, OpenCode 등)에서도 같은 방법으로 쓸 수 있을 것으로 예상하지만, 아직 시험하지 않았습니다.

## 사용법

| | Claude Code | Codex |
|---|---|---|
| 전체 | `/white-security:review` | `$white-security` |
| 바뀐 파일 | `/white-security:review changed` | `$white-security changed` |
| 폴더 하나 | `/white-security:review src/api` | `$white-security src/api` |

두 도구 모두 `보안 점검해 줘`라고만 요청해도 전체 점검이 실행됩니다. 검토는 요청할 때만 실행되며, 검토 중에는 파일을 변경하지 않습니다. 보고 뒤 고칠 항목을 고르면 그 항목만 고칩니다.

## 결과 예시

**결론:** 지금 고칠 위험 1건 — 검색어가 SQL에 그대로 들어가 DB 전체를 읽고 지울 수 있음.

| # | id | 파일:줄 | 영향 | 이유 | 고칠 방법 |
|---|---|---|---|---|---|
| 1 | [WS-INJ-001](https://limindex.github.io/white-security/issues/WS-INJ-001) | `app/search.py:18` | DB 유출 | 검색어를 SQL에 붙임 | 바인딩 |
| 2 | [WS-SEC-001](https://limindex.github.io/white-security/issues/WS-SEC-001) | `app/config.py:7` | 로그인 위조 | 비밀키 기본값 노출 | 기본값 삭제 |

실제 보고서는 위험·주의·참고 등급별로 나뉘며, 기본은 한국어입니다. 영어로 요청하면 영어로 작성합니다.

## 점검 범위

| 묶음 | 이슈 | 예 |
|---|---|---|
| AI 활용 | 8 | 간접 프롬프트 인젝션(IPI), 마크다운 이미지를 통한 데이터 유출, 비용 폭주 |
| AI 코딩 도구 | 7 | Rules File Backdoor, MCP Tool Poisoning, YOLO 모드 |
| 외부 데이터 수집·처리 | 5 | SSRF, XXE, Zip Slip, 역직렬화 |
| 자동 수집 대응 | 1 | 스크래핑·AI 크롤러 |
| 웹 애플리케이션 | 17 | SQL·명령어 Injection, XSS, Path Traversal, 파일 업로드 |
| 인증·인가 | 12 | IDOR, Mass Assignment, OTP 악용, 결제 금액 변조 |
| 설정·배포 | 12 | 디버그 모드, `.env` 노출, 하드코딩된 비밀값, 요청 횟수 제한 없음 |
| 개인정보 보호 | 8 | Supabase RLS 누락, `service_role` 키 노출, 로그 속 민감정보 |
| 소프트웨어 공급망 | 8 | Slopsquatting, 취약한 의존성, SHA 고정 안 된 Actions |
| 모바일 앱 | 6 | 앱 번들 속 비밀값, 딥링크, WebView |

OWASP Top 10:2025, LLM·Agentic Top 10 2026, ASVS 5.0, CWE Top 25에 대응합니다. 지원 스택은 Python, JavaScript/TypeScript, PHP, Supabase, Firebase, React Native, Expo, Flutter입니다. 심각한 CVE는 감시 목록으로 manifest와 lockfile의 버전을 대조합니다.

## 국내 보안 기준

국내 보안 점검에서 쓰는 기준에도 대응합니다.

- 행정안전부 구현단계 보안약점 49개
- 주요정보통신기반시설 웹 취약점 21개
- 개인정보 보호 기준: 주민등록번호 처리 제한, 고유식별정보 암호화 저장, 개인정보 접속기록

각 이슈 문서에 해당 기준의 항목 번호가 표기되어 있으며, 기준별 대응 현황은 [점검 기준 대응표](https://limindex.github.io/white-security/coverage)에 정리되어 있습니다. 공식 진단이나 인증을 대신하지는 않습니다.

## 실측 결과

작은 예제 앱 5개에 문제 46개를 심고, Claude Code에서 스킬을 켠 경우와 끈 경우를 비교했습니다(2026년 11월, 앱마다 2회).

| 모델 | 스킬 켬 | 스킬 끔 | 1회 비용 | 비용 | 시간 |
|---|:---:|:---:|:---:|:---:|:---:|
| Sonnet 5.5 | **92 / 92** | 91 / 92 | 약 $0.26 | 1.8배 | 약 1.9배 |
| Haiku 5.5 | **92 / 92** | 89 / 92 | 약 $0.02 | 1.9배 | 약 2.2배 |
| Opus 5.5 | **32 / 32** | 32 / 32 | 약 $0.60 | 1.5배 | 약 2배 |

찾는 수는 스킬이 없을 때와 비슷합니다. 추가 비용으로 얻는 것은 허락 창 없는 검토, 매번 같은 보고 형식, 감시 목록 CVE 대조입니다. `changed` 범위는 변경 목록을 얻기 위해 git 명령을 한 번 허락해야 합니다.

## 안전

- 검토는 읽기 전용입니다. plugin에는 훅, MCP 서버, 설정 변경, 실행 파일이 없습니다.
- `.env`, 키, 자격 증명 파일은 열지 않고 비밀값은 가려서 표시합니다.
- 네트워크 호출과 스캐너는 동의를 받은 뒤에만 실행합니다.
- 프로젝트 안의 글이 검토를 건너뛰라고 지시하면, 그 지시 자체를 발견 항목으로 보고합니다.

White Security는 별도 서버가 없으며, 코드는 사용 중인 AI 코딩 도구의 모델 제공사로만 전달됩니다. 흔한 실수를 줄이기 위한 도구이며 보안 감사나 침투 테스트를 대신하지 않습니다.

## 기여와 제보

- 기여 방법: [CONTRIBUTING.md](CONTRIBUTING.md)
- 이 프로젝트의 취약점 제보: [SECURITY.md](SECURITY.md)
- 변경 내역: [CHANGELOG.md](CHANGELOG.md)

## 라이선스

문서와 스킬 글은 [CC BY-SA 4.0](LICENSE), 코드와 코드 예시는 [MIT](LICENSE-CODE)를 따릅니다. 발행: LimIndex.
