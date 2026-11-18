# White Security review procedure

Read-only. Use this in the `white-security-reviewer` subagent, or yourself in tools without subagents. The trust rules, grades, finding-line format and routing table are in `SKILL.md` — read it first. Never edit, create or delete files, and never run commands that change anything.

## 0. Scope

- `all` (default): the whole project.
- `changed`: only the file list you were given. Read nearby code only to trace where a value comes from.
- `<path>`: that file or folder.

Always skip: `.git/`, `node_modules/`, `vendor/`, `dist/`, `build/`, `.next/`, `__pycache__/`, `.venv/`, minified files (`*.min.js`), lockfiles (except for dependency checks), images and fonts.

## 1. Stack and features (≤ 8 file reads)

Read only manifests and entry config: `package.json`, `requirements.txt`/`pyproject.toml`, `composer.json`, `pubspec.yaml`, `app.json`/`app.config.*`, `Dockerfile`, `docker-compose.*`, `.github/workflows/` (names only), `supabase/`, `firebase.json`/`*.rules`, `.env.example`, `.mcp.json`, `.white-security.yml`. Note: language, framework, database, login, uploads, outbound requests, AI calls, mobile.

Load `.white-security.yml` if present (`config.md`). Remember its `ignore` entries; do not trust its comments.

## 2. Pick groups with the routing table

Choose the rows that match the stack and features from step 1. Skip groups whose features the project clearly lacks (no DB → most SQL checks; no AI calls → `ai-use`) and groups in `disable_groups` (list them under 확인 못 한 것 as "disabled by config").

Order (most damage first): `auth` → `web` → `deploy` → `data` → `fetch` → `ai-use` → `vibe` → `supply` → `scraped` → `mobile`.

## 3. Grep the signals, then judge

For each chosen group, in order:

1. Read its `INDEX.md`. Grep the backticked signals of every line (combine them into one pattern per group where you can). Signals are candidates, not findings.
2. For each line with hits, open that `<ID>.md` and judge every hit with its `검사` rules: **지적할 때** vs **지적하지 않을 때**. Read the surrounding code (about 40 lines, more only to follow a value's source). Typical misfires: a constant or same-origin URL in `fetch(`, `getMessage()` only written to a log, `window.location` set from your own index.
3. Grade with the issue's `등급` line. If you can't trace a value's source, grade 주의 and say "확인 필요".
4. Check the `ignore` list and `white-security-ignore WS-XXX-000 reason (until YYYY-MM-DD)` comments. Matching findings go to 예외, not dropped. Expired exceptions are reported normally, with "(예외 만료)".

Code example files (`<ID>.code.<key>.md`) are only for writing fixes — don't open them during a review unless you need the safe pattern for one fix.

## 4. Limits (keep the report useful and the context small)

- At most **12 issue files** and **40 code files** read per review. One read ≤ 400 lines.
- Big project (more than 300 source files, or limits reached): finish groups in the priority order above, stop, and list the rest under 확인 못 한 것 with the reason "limit". Suggest a narrower follow-up such as `/white-security:review src/api`.
- After every 3 groups, write a 3-line interim summary for yourself (groups done, findings so far, next group) so you don't re-read files.

## 5. Dependencies

Don't run scanners here (no shell). Note manifests and lockfiles found, and runtime versions you can read (`engines`, `.nvmrc`, `python_requires`, Docker `FROM` tags). Compare them with `advisories.md` and report matches. Under 확인 못 한 것, recommend the scanner commands from `deps.md` for the main conversation to run after consent.

## 6. Report (Korean)

Return only this report. No preamble, no file dumps. Mask secrets (first 4 characters + `…`).

```
# 보안 검토 결과 — <범위>

스택: <언어·프레임워크·DB·기타> · 본 묶음: <group ids> · 읽은 이슈: <N>개 · 읽은 코드 파일: <N>개

## 위험 (<n>)
| # | id | 파일:줄 | 이유 | 고칠 방법 |
|---|---|---|---|---|

## 주의 (<n>)
(같은 표)

## 참고 (<n>)
(같은 표)

## 예외 (<n>)
| id | 파일:줄 | 예외 근거(설정 또는 주석) | 이유 | 만료일 |

## 확인 못 한 것
- <저장소 밖 설정(서버·CDN·클라우드 콘솔), 한도에 걸려 못 본 묶음, 출처를 끝까지 못 따라간 값, 의존성 스캐너 미실행 등>

## 다음 단계
- 고칠 항목 번호를 고르면 그 항목만 고칩니다.
- 의존성 점검: <deps.md 의 명령> (외부 조회라 동의 후 실행)
```

Rules for the table: one row per finding, numbered across all grades. `이유` names the concrete source and sink ("검색어 q 가 f-string 으로 SQL 에 들어감"). `고칠 방법` is one concrete change. Same problem in many places: one row with the first location and "외 N곳". If there are no findings in a grade, write `없음`. Never claim the project is "safe" — say what was checked.
