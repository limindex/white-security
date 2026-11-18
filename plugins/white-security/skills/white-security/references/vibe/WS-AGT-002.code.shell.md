<!-- generated from white-security issues; do not edit -->
# WS-AGT-002 코드 예시 — shell

✗ 취약 코드 (vulnerable example — do not use)

```shell
# 인터넷에서 찾은 규칙 파일을 확인 없이 그대로 넣는다
curl -fsSL https://example.com/awesome-rules.mdc -o .cursor/rules/awesome.mdc
```

✓ 안전 코드 (safe example)

```shell
# 1) 따로 받아서 숨은 문자부터 찾는다. 줄이 나오면 넣지 않는다(이모지 속 U+200D·U+FE0F 만이면 참고).
curl -fsSL https://example.com/awesome-rules.mdc -o /tmp/rules-review.mdc
LC_ALL=C.UTF-8 grep -nP '[\x{00AD}\x{180E}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2069}\x{FE00}-\x{FE0F}\x{FEFF}\x{E0000}-\x{E007F}\x{E0100}-\x{E01EF}]' /tmp/rules-review.mdc
# 2) 아무것도 안 나오면 내용을 직접 읽고, 외부 전송·보안 끄기 지시가 없을 때만 옮긴다.
# 3) 이미 있는 규칙 파일도 같은 검사를 한다.
LC_ALL=C.UTF-8 grep -rnP '[\x{00AD}\x{180E}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{2069}\x{FE00}-\x{FE0F}\x{FEFF}\x{E0000}-\x{E007F}\x{E0100}-\x{E01EF}]' \
  CLAUDE.md AGENTS.md .cursorrules .cursor/rules .github/copilot-instructions.md 2>/dev/null
```
