<!-- generated from white-security issues; do not edit -->
# WS-AGT-006 코드 예시 — json

✗ 취약 코드 (vulnerable example — do not use)

```json
{
  "permissions": {
    "defaultMode": "bypassPermissions",
    "allow": ["Bash(*)", "WebFetch"]
  }
}
```

✓ 안전 코드 (safe example)

```json
{
  "permissions": {
    "allow": ["Bash(npm run test)", "Bash(npm run lint)", "Bash(git status)", "Bash(git diff *)"],
    "ask": ["Bash(git push *)", "Bash(npm install *)", "Bash(rm *)"],
    "deny": ["Read(./.env)", "Read(./.env.*)", "Read(./secrets/**)", "Bash(curl *)"],
    "disableBypassPermissionsMode": "disable"
  }
}
```
