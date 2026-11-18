<!-- generated from white-security issues; do not edit -->
# WS-BAAS-002 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

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

✓ 안전 코드 (safe example)

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
