<!-- generated from white-security issues; do not edit -->
# WS-AUTH-002 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/actions/post.ts
"use server";
import { db } from "@/lib/db";

// 화면에서 삭제 버튼을 숨겨도 Server Action 은 POST 요청으로 직접 부를 수 있다
export async function deletePost(id: string) {
  await db.post.deleteMany({ where: { id } });
}
// middleware.ts 의 matcher 에만 로그인 확인이 있다
```

✓ 안전 코드 (safe example)

```ts
// app/actions/post.ts
"use server";
import { auth } from "@/auth";
import { db } from "@/lib/db";

export async function deletePost(id: string) {
  const session = await auth();                         // 함수 안에서 직접 확인
  if (!session?.user?.id) throw new Error("Unauthorized");
  await db.post.deleteMany({ where: { id, authorId: session.user.id } });  // 내 글만
}

// app/api/reports/route.ts — Route Handler 도 같은 방식
export async function GET() {
  const session = await auth();
  if (!session?.user) return new Response(null, { status: 401 });
  return Response.json(await buildReport(session.user.id));
}
```
