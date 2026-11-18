---
id: WS-BAAS-002
title: Insecure Security Rules (Firebase 보안 규칙 개방)
title_en: Insecure Firebase Security Rules · Admin SDK Key Exposure
slug: firebase-rules
group: data
severity: high
grade_hint: "쓰기를 여는 if true·기한부 규칙, 서비스 계정 키 노출"
site_only: false
depth: brief
languages: [firebase, nextjs]
maps:
  owasp2025: [A01, A02]
  api2023: [API1, API8]
  llm2026: []
  agentic2026: []
  asvs5: [V8.2.2, V8.3.1, V13.3.2]
  cwe: [CWE-284, CWE-732, CWE-862, CWE-798]
  kr_guide: ["보안기능-3", "보안기능-2", "보안기능-6"]
  kr_ciip: [IN]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Firebase(Cloud Firestore·Realtime Database·Cloud Storage), Next.js, React, Firebase를 백엔드로 쓰는 웹·모바일 앱]
severity_reason: "영향: 저장된 데이터·파일 전체 조회·변조·삭제 · 악용: 앱에 들어 있는 프로젝트 ID만으로 가능"
sources:
  - title: "Firebase Docs - Avoid insecure rules"
    url: https://firebase.google.com/docs/rules/insecure-rules
    year: 2026
  - title: "Firebase Docs - Get started with Cloud Firestore Security Rules (서버 라이브러리는 규칙을 건너뜀)"
    url: https://firebase.google.com/docs/firestore/security/get-started
    year: 2026
  - title: "Firebase Docs - Learn about using and managing API keys for Firebase"
    url: https://firebase.google.com/docs/projects/api-keys
    year: 2026
  - title: "The Record - Tea app data theft scandal worsens as stolen IDs leaked to cybercriminal forum"
    url: https://therecord.media/tea-app-data-breach-stolen-ids-leaked
    year: 2025
---

## 요약

Firestore·Realtime Database·Cloud Storage 보안 규칙을 `allow read, write: if true`나 기한부 테스트 모드로 두어, 앱에 들어 있는 공개 설정값만으로 누구나 데이터를 읽고 지울 수 있는 문제(진단가이드 이름: 중요한 자원에 대한 잘못된 권한 설정). Admin SDK(서버용 라이브러리)는 규칙을 건너뛰므로 서비스 계정 키가 새면 규칙도 소용없음.

## 대응 방법

```text
firestore.rules·storage.rules·database.rules.json에서 if true, request.time < timestamp, auth != null만 보는 규칙을 찾아 줘.
문서마다 request.auth.uid와 문서의 소유자 필드를 비교하는 규칙으로 바꾸고, Emulator 규칙 테스트도 만들어 줘.
서비스 계정 JSON이나 Admin SDK 초기화가 저장소·클라이언트 코드에 있으면 서버 전용으로 옮겨 줘.
```

- 로그인하지 않은 상태와 다른 계정으로 남의 문서를 읽으면 `permission-denied`가 나와야 한다.
- 콘솔에 배포된 규칙이 저장소의 규칙 파일과 같아야 한다.

## 점검 항목

- Firestore·Realtime Database·Storage 규칙 어디에도 `if true`가 없어야 한다.
- 테스트 모드 규칙(`request.time <`·`now <` 기한)은 출시 전에 실제 규칙으로 바꿔야 한다.
- `request.auth != null`만 보지 말고 `request.auth.uid`를 소유자 필드와 비교해야 한다.
- 쓰기 규칙은 소유자·역할 필드를 바꾸지 못하게 `request.resource.data`를 검사해야 한다.
- Realtime Database는 상위 경로에 `.read: true`를 주지 않아야 한다(하위 규칙으로 못 막음).
- 서비스 계정 키는 서버에만 둬야 한다.

## 탐지 규칙

- **찾는 곳**: `firestore.rules`, `storage.rules`, `database.rules.json`, `if true`, `request.time <`, `now <`, `firebase-admin`, `serviceAccountKey`, 그 밖에 service-account JSON 파일, credential.cert, `".read": true`.
- **검사 1 — 열린 규칙**: 지적할 때: `allow read, write: if true`, `match /{document=**}`에 넓은 허용, `request.time <`·`now <` 기한부 허용, Realtime Database 루트의 `".read": true`·`".write": true`가 있는 경우. / 지적하지 않을 때: 공지·상품 목록처럼 공개용 컬렉션의 `read`만 `true`이고 쓰기는 막힌 경우.
- **검사 2 — 로그인만 확인**: 지적할 때: 개인 데이터에 `request.auth != null`만 확인해 로그인한 누구나 남의 문서를 읽고 쓰는 경우. 쓰기 규칙이 소유자·역할 필드 변경을 막지 않는 경우. / 지적하지 않을 때: `request.auth.uid == resource.data.ownerId`처럼 소유자와 비교하고, 쓰기에서 `request.resource.data`의 소유자 필드를 고정하는 경우.
- **검사 3 — Admin SDK 키·우회**: 지적할 때: 서비스 계정 JSON이 저장소·클라이언트 번들에 있는 경우. Admin SDK로 요청 id만 보고 읽고 쓰며 ID 토큰 확인이 없는 경우. / 지적하지 않을 때: `apiKey` 등 `firebaseConfig` 값이 클라이언트에 있는 경우(공개 값). Admin SDK가 서버에서 `verifyIdToken` 뒤 소유자를 확인하는 경우.
- **등급**: 위험: 쓰기를 여는 `if true`·기한부 규칙, 서비스 계정 키 노출. / 주의: 로그인만 확인하는 규칙, 소유자 필드를 바꿀 수 있는 쓰기 규칙. / 참고: 규칙 파일이 저장소에 없어 콘솔 확인이 필요한 경우.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-BAAS-002 firestore.rules:5 — 모든 문서에 allow read, write: if true → 컬렉션별로 request.auth.uid 와 ownerId 를 비교하는 규칙으로 교체`
- **검토 주의**: `firebaseConfig`의 `apiKey`는 앱에 공개되는 값이라 비밀값 노출로 지적하지 않아야 한다.
- **고칠 방법**: 규칙을 바꾸기 전에 Emulator 규칙 테스트로 본인 허용·남 거부를 확인해야 하고, `firebase deploy --only firestore:rules`처럼 규칙만 따로 배포해야 한다.

## 코드 예시

### firebase

✗ 취약 코드

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

✓ 안전 코드

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

### nextjs

✗ 취약 코드

```ts
// app/api/notes/[id]/route.ts
import { cert, initializeApp } from "firebase-admin/app";
import { getFirestore } from "firebase-admin/firestore";
import key from "@/service-account.json"; // 비밀 키 파일을 저장소에 커밋

initializeApp({ credential: cert(key as any) });

export async function GET(_req: Request, { params }: { params: Promise<{ id: string }> }) {
  const { id } = await params;
  const snap = await getFirestore().collection("notes").doc(id).get(); // Admin SDK 는 규칙을 건너뛴다
  return Response.json(snap.data()); // 로그인 확인이 없어 남의 문서도 열린다
}
```

✓ 안전 코드

```ts
// app/api/notes/[id]/route.ts
import "server-only"; // 클라이언트 번들에 들어가면 빌드 실패
import { applicationDefault, getApps, initializeApp } from "firebase-admin/app";
import { getAuth } from "firebase-admin/auth";
import { getFirestore } from "firebase-admin/firestore";

if (!getApps().length) initializeApp({ credential: applicationDefault() }); // 키는 저장소 밖(서버 환경)

export async function GET(req: Request, { params }: { params: Promise<{ id: string }> }) {
  const token = req.headers.get("authorization")?.replace(/^Bearer /, "");
  if (!token) return new Response(null, { status: 401 });
  let uid: string;
  try { ({ uid } = await getAuth().verifyIdToken(token)); } catch { return new Response(null, { status: 401 }); }
  const { id } = await params;
  const snap = await getFirestore().collection("notes").doc(id).get();
  if (!snap.exists || snap.get("ownerId") !== uid) return new Response(null, { status: 404 }); // 규칙 대신 서버가 소유자 확인
  return Response.json(snap.data());
}
```
