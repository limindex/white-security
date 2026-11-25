# Contributing

[한국어](#한국어)

Thanks for helping. Issues and pull requests in Korean or English are both welcome.

## What helps most

- **A pattern AI coding tools keep getting wrong.** Open an issue with the prompt you used, the tool and model, the code it wrote (trimmed to the unsafe part) and why it's unsafe. Use a harmless example: no live secrets, no real targets.
- **A fix that is still unsafe.** If a "safe code" example or a rule file can still be exploited, report it privately. See [SECURITY.md](SECURITY.md).
- **A mistake in an issue page.** Wrong facts, a standard mapped to the wrong item, an outdated version or a broken source link. A pull request is fine.
- **A false positive or a missed finding from the skill.** Include the report rows and a minimal code sample.

Vulnerabilities in this project itself go through [SECURITY.md](SECURITY.md), not public issues.

## Editing issue pages

Each issue is one Markdown file in `issues/<group>/WS-<CODE>-<NNN>-<slug>.md`. Front matter holds the id, severity, standard mappings, sources and revision history. Code examples live in the same file, one block per stack; `build.php` splits them into per-stack files for the skill.

- Sources: official or primary documents, as recent as possible. Add `checked:` with the date you read them.
- Add a line to `revisions:` for any change in meaning.
- Write invisible or control characters only as `U+XXXX`, never as the character itself.
- Attack examples stay harmless: they show the shape of an attack, not a working payload against a real service.

## Checks

Requires PHP 8.4. From the repository root:

```
php build.php
php tests/run.php
php scripts/check-hidden-chars.php
php scripts/check-code-examples.php
```

`build.php` regenerates the skill's reference files, watch list and issue list from `issues/` and `plugins/white-security/skills/white-security/references/advisories.md`. Commit the generated files together with your change. CI fails if a fresh build differs.

If you change anything under `plugins/white-security/`, raise `version` in `plugins/white-security/.claude-plugin/plugin.json` and add a matching heading to [CHANGELOG.md](CHANGELOG.md).

## License

By contributing you agree that text is licensed under [CC BY-SA 4.0](LICENSE) and code under [MIT](LICENSE-CODE), the same as the rest of the project.

---

## 한국어

이슈와 pull request는 한국어·영어 모두 가능.

### 가장 도움이 되는 기여

- **AI 코딩 도구가 자꾸 틀리는 패턴.** 사용한 프롬프트, 도구와 모델, 도구가 쓴 코드(안전하지 않은 부분만), 위험한 이유를 이슈로 등록. 예시는 무해하게 작성하고 실제 비밀값·대상은 넣지 않음.
- **여전히 안전하지 않은 수정 예시.** '안전 코드' 예시나 규칙 파일이 아직 악용 가능하면 [SECURITY.md](SECURITY.md)에 따라 비공개로 제보.
- **이슈 문서의 오류.** 틀린 사실, 잘못 연결된 기준 항목, 오래된 버전, 깨진 출처 링크. pull request로 보내도 됨.
- **스킬의 오탐·누락.** 보고서의 해당 줄과 최소 재현 코드를 함께 첨부.

이 프로젝트 자체의 취약점은 공개 이슈가 아니라 [SECURITY.md](SECURITY.md) 절차로 제보.

### 이슈 문서 고치기

이슈 하나는 `issues/<group>/WS-<CODE>-<NNN>-<slug>.md` 파일 하나. 머리말에 번호·심각도·기준 대응·출처·개정 이력을 두고 코드 예시는 같은 파일에 스택별 블록으로 둠. `build.php`가 이 블록을 스킬용 스택별 파일로 나눔.

- 출처는 공식·1차 문서, 가능한 한 최신 자료. 읽은 날짜를 `checked:`에 기록.
- 뜻이 바뀌는 수정은 `revisions:`에 한 줄 추가.
- 보이지 않는 문자·제어 문자는 실제 문자 대신 `U+XXXX`로만 표기.
- 공격 예시는 공격 형태만 보여 주도록 무해하게 작성. 실제 서비스에 통하는 페이로드는 넣지 않음.

### 검사

PHP 8.4 필요. 저장소 루트에서 위 영어 절의 명령을 실행. `build.php`가 `issues/`와 감시 목록(`advisories.md`)에서 스킬 참고 파일·감시 목록·이슈 목록을 다시 만듦. 생성된 파일도 함께 커밋. 새로 빌드한 결과가 다르면 CI가 실패함.

`plugins/white-security/` 아래 파일을 고치면 `plugins/white-security/.claude-plugin/plugin.json`의 `version`을 올리고 [CHANGELOG.md](CHANGELOG.md)에 같은 버전 제목을 추가.

### 라이선스

기여하면 글은 [CC BY-SA 4.0](LICENSE), 코드는 [MIT](LICENSE-CODE)로 공개하는 데 동의한 것으로 봄. 프로젝트 나머지 부분과 같은 조건.
