<!-- generated from white-security issues; do not edit -->
# WS-INJ-002 OS Command Injection (운영체제 명령어 인젝션)

## 탐지 규칙

- **찾는 곳**: `shell=True`, `os.system(`, `child_process`, `shell_exec(`, `Process::run`, 그 밖에 exec·execSync·popen·passthru·proc_open·백틱, Symfony Process 의 fromShellCommandline.
- **검사 1 — 셸 문자열에 입력**: 지적할 때: 요청·파일 이름·DB·AI 출력 값이 셸 명령 문자열에 이어 붙는 경우(`shell=True`, `os.system`, `exec`, `execSync`, `shell_exec`, 백틱, 문자열 `Process::run`). / 지적하지 않을 때: 셸 없이 인자 목록으로 실행하는 경우(`subprocess.run([...])`, `execFile`, 기본값 `spawn`, 배열 `Process::run([...])`). 명령 문자열이 전부 코드 상수인 경우.
- **검사 2 — 인자 주입**: 지적할 때: 목록 실행이어도 입력이 `-`로 시작할 수 있고 형식 검사가 없는 경우(`git`·`curl`·`tar`처럼 옵션으로 다른 일을 하는 프로그램). / 지적하지 않을 때: 값이 허용 목록·정규식을 통과했거나 서버가 만든 절대 경로인 경우, `--` 뒤에 오는 경우.
- **등급**: 위험: 외부 값이 셸 문자열에 들어가는 경우. / 주의: 목록 실행이지만 인자 검사가 없는 경우, 이스케이프 함수에만 기대는 경우. / 참고: 명령이 root처럼 넓은 권한 계정으로 도는 경우.
- **보고 예**: `[위험] WS-INJ-002 app/thumbs.py:21 — 업로드 파일 이름을 f-string 으로 넣어 subprocess.run(shell=True) 실행 → Pillow 로 처리하거나 인자 목록 실행, 이름은 서버가 만든 값만`
- **고칠 방법**: 셸 문자열을 인자 목록으로 바꿔야 하고, 파이프·리다이렉트가 필요하면 셸 대신 코드에서 두 프로그램을 이어야 한다.

## 점검 항목

- 명령은 셸 문자열이 아니라 프로그램 이름과 인자 목록으로 실행해야 한다.
- 이미지·압축·PDF 처리는 외부 명령보다 언어 라이브러리를 먼저 써야 한다.
- 인자 값은 허용 목록이나 형식(정규식·숫자) 검사를 거쳐야 한다.
- `-`로 시작하는 값이 옵션으로 읽히지 않게 막아야 한다(`--` 지원 여부 확인).
- `escapeshellarg`·`shlex.quote` 같은 이스케이프를 주된 방어로 쓰지 않아야 한다.
- 명령을 실행하는 계정은 최소 권한으로 둬야 한다.

Code examples (open only if 고칠 방법 is not enough; your stack only): `WS-INJ-002.code.fastapi.md` (FastAPI) · `WS-INJ-002.code.express.md` (Express) · `WS-INJ-002.code.laravel.md` (Laravel)

기본 심각도: 위험 · CWE-78 · CWE-77 · CWE-88
