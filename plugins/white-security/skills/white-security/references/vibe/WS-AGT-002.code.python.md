<!-- generated from white-security issues; do not edit -->
# WS-AGT-002 코드 예시 — Python

✗ 취약 코드 (vulnerable example — do not use)

```python
import urllib.request

rules = urllib.request.urlopen("https://example.com/awesome-rules.md").read().decode()
with open("AGENTS.md", "a", encoding="utf-8") as f:
    f.write(rules)  # 보이지 않는 문자와 숨은 지시까지 그대로 붙는다
```

✓ 안전 코드 (safe example)

```python
# CI에서 돌리는 검사: 보이지 않는 문자가 있으면 실패한다 (문자는 U+ 코드로만 출력)
import re
import sys
from pathlib import Path

HIDDEN = re.compile(
    "[\u00ad\u180e\u200b\u200c\u200e\u200f\u202a-\u202e\u2060-\u2069\ufe00-\ufe0e\ufeff"
    "\U000e0000-\U000e007f\U000e0100-\U000e01ef]"
    "|(?<![\u2600-\u27bf\ufe0f\U0001f000-\U0001faff])[\u200d\ufe0f]"  # 이모지에 붙은 ZWJ·VS16 은 정상
)
NAMES = ["CLAUDE.md", "AGENTS.md", ".cursorrules", ".windsurfrules", ".github/copilot-instructions.md"]
paths = [Path(n) for n in NAMES] + list(Path(".cursor/rules").rglob("*")) + list(Path(".claude").rglob("*.md"))

found = False
for p in (p for p in paths if p.is_file()):
    text = p.read_text(encoding="utf-8-sig", errors="replace")  # utf-8-sig: 맨 앞 BOM 은 건너뛴다
    for n, line in enumerate(text.splitlines(), 1):
        for ch in HIDDEN.findall(line):
            print(f"{p}:{n}: 보이지 않는 문자 U+{ord(ch):04X}")
            found = True
sys.exit(1 if found else 0)
```
