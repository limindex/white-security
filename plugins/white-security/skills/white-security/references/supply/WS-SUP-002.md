<!-- generated from white-security issues; do not edit -->
# WS-SUP-002 Vulnerable Dependencies (알려진 취약점이 있는 패키지)

심각도: 위험
기준: OWASP Top 10:2025 A03(소프트웨어 공급망 실패) · ASVS 5.0 V15.1.1 · ASVS 5.0 V15.1.2 · ASVS 5.0 V15.2.1 · CWE-1395 · CWE-1104

## 점검 항목

- 의존성 취약점 검사(npm audit·pip-audit·composer audit·osv-scanner)를 CI에서 돌려야 한다.
- 높음·심각 취약점은 정해 둔 기한 안에 고친 버전으로 올려야 한다.
- 업데이트는 Dependabot·Renovate로 받아 테스트를 거쳐 합쳐야 한다.
- 간접 의존성 취약점은 상위 패키지 업데이트나 `overrides`로 고쳐야 한다.
- 지원이 끝난(EOL) 런타임·프레임워크 버전은 지원 버전으로 옮겨야 한다.
- 검사 무시 항목에는 사유와 다시 볼 날짜를 적어야 한다.

## 탐지 규칙

- **찾는 곳**: `package-lock.json`, `pnpm-lock.yaml`, `uv.lock`, `poetry.lock`, `composer.lock`, `dependabot.yml`, `renovate.json`, `npm audit fix --force`, 그 밖에 requirements.txt, CI 워크플로의 설치·검사 단계, Dockerfile 기본 이미지의 런타임 버전, 검사 무시 목록.
- **검사 1 — 알려진 취약점**: 지적할 때: 의존성이 바뀐 변경에서 실제로 돌린 검사 도구나 OSV 조회 결과에 높음·심각 취약점이 나오는 경우. / 지적하지 않을 때: 취약한 기능을 쓰지 않는다는 판단이 무시 목록에 사유·기한과 함께 남아 있는 경우. 도구를 돌리지 못했으면 안전하다고 하지 않고 "확인 못 함"으로 보고.
- **검사 2 — 업데이트 체계**: 지적할 때: Dependabot·Renovate 설정과 CI 검사가 모두 없는 경우, 런타임·프레임워크가 지원 종료 버전인 경우. / 지적하지 않을 때: 자동 업데이트 PR과 CI 검사가 있는 경우.
- **검사 3 — 무시·강제 수정**: 지적할 때: 무시 목록(`--ignore-vuln`·`audit.ignore`)에 사유·기한이 없는 경우, `npm audit fix --force`를 배포 과정에서 자동으로 돌려 검토 없이 메이저 버전이 바뀌는 경우. / 지적하지 않을 때: 사유와 다시 볼 날짜가 적힌 무시 항목.
- **등급**: 위험: CISA KEV(실제 악용 목록)에 오른 취약점, 원격 코드 실행·인증 우회 취약점이 있는 버전. / 주의: 높음 등급 취약점, 지원 종료 런타임, 업데이트 체계 없음. / 참고: 낮음·보통 등급, 사유 없는 무시 항목.
- **보고 예**: `[위험] WS-SUP-002 package-lock.json:1 — next 15.2.2 사용, CVE-2025-29927(미들웨어 인증 우회) → 15.2.3 이상으로 올리고 잠금 파일 함께 커밋`
- **검토 주의**: 모델이 기억하는 취약점 목록으로 단정하지 말고 검사 도구·OSV 조회 결과로만 판단해야 하며, 스캐너는 의존성이 바뀐 경우에만 돌려야 한다. 새 패키지의 실재 확인은 WS-SUP-001, 잠금 파일 무결성은 WS-SUP-004, 복사해 넣은 라이브러리 파일은 WS-SUP-006에서 다룬다. 개인정보처리자에 해당하면 고시 제9조 ②(제작업체의 보안 업데이트 공지가 있으면 정당한 사유가 없는 한 즉시 업데이트)도 근거로 안내할 수 있다.
- **고칠 방법**: 고친 버전으로 올려 잠금 파일을 함께 커밋하고, 메이저 업데이트는 변경 사항을 확인한 뒤 테스트로 확인해야 한다.

Code examples (read only the file for the stack you are writing): `WS-SUP-002.code.yaml.md` (YAML) · `WS-SUP-002.code.shell.md` (Shell)
