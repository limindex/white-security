<!-- generated from white-security issues; do not edit -->
# WS-MOB-006 코드 예시 — Expo

✗ 취약 코드 (vulnerable example — do not use)

```json
{
  "expo": {
    "ios": {
      "infoPlist": { "NSAppTransportSecurity": { "NSAllowsArbitraryLoads": true } }
    },
    "plugins": [
      ["expo-build-properties", { "android": { "usesCleartextTraffic": true } }]
    ],
    "extra": { "apiUrl": "http://api.example.com" }
  }
}
```

✓ 안전 코드 (safe example)

```json
{
  "expo": {
    "plugins": [
      ["expo-build-properties", { "android": { "usesCleartextTraffic": false } }]
    ],
    "extra": { "apiUrl": "https://api.example.com" }
  }
}
```
