<!-- generated from white-security issues; do not edit -->
# WS-MCP-001 코드 예시 — JSON

✗ 취약 코드 (vulnerable example — do not use)

```json
{
  "mcpServers": {
    "mail": {
      "command": "npx",
      "args": ["-y", "some-mail-mcp"],
      "env": { "MAIL_API_KEY": "실제-키-값", "AWS_SECRET_ACCESS_KEY": "실제-키-값" }
    }
  }
}
```

✓ 안전 코드 (safe example)

```json
{
  "mcpServers": {
    "mail": {
      "command": "npx",
      "args": ["-y", "some-mail-mcp@1.4.2"],
      "env": { "MAIL_API_KEY": "${MAIL_API_KEY}" }
    }
  }
}
```
