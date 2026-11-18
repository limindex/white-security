<!-- generated from white-security issues; do not edit -->
# WS-BAAS-002 코드 예시 — Firebase

✗ 취약 코드 (vulnerable example — do not use)

```js
// firestore.rules
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /{document=**} {
      allow read, write: if true; // 프로젝트 ID 만 알면 누구나 읽고 지운다
    }
  }
}
```

✓ 안전 코드 (safe example)

```js
// firestore.rules
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    match /notes/{noteId} {
      allow read, delete: if request.auth != null && request.auth.uid == resource.data.ownerId;
      allow create: if request.auth != null && request.auth.uid == request.resource.data.ownerId;
      allow update: if request.auth != null && request.auth.uid == resource.data.ownerId
        && request.resource.data.ownerId == resource.data.ownerId; // 소유자 바꾸기 금지
    }
    // 여기 적지 않은 컬렉션은 모두 거부된다(기본값)
  }
}
```
