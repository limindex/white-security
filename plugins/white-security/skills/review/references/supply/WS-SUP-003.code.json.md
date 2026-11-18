<!-- generated from white-security issues; do not edit -->
# WS-SUP-003 코드 예시 — JSON

✗ 취약 코드 (vulnerable example — do not use)

```json
{
  "require": { "laravel/framework": "^12.0" },
  "config": {
    "allow-plugins": true
  }
}
```

✓ 안전 코드 (safe example)

```json
{
  "require": { "laravel/framework": "^12.0" },
  "config": {
    "allow-plugins": {
      "pestphp/pest-plugin": true,
      "php-http/discovery": false
    }
  }
}
```
