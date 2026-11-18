<!-- generated from white-security issues; do not edit -->
# WS-AUTH-006 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/me/route.ts
export async function PATCH(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return new Response(null, { status: 401 });
  const body = await req.json();
  const user = await db.user.update({ where: { id: session.user.id }, data: body });  // role·points 도 바뀐다
  return Response.json(user);                                                        // passwordHash 까지 응답
}
```

✓ 안전 코드 (safe example)

```ts
import { z } from "zod";

const ProfilePatch = z.object({
  nickname: z.string().min(1).max(30).optional(),
  marketingOptIn: z.boolean().optional(),
}).strict();                                       // 목록에 없는 필드가 오면 거절

export async function PATCH(req: Request) {
  const session = await auth();
  if (!session?.user?.id) return new Response(null, { status: 401 });
  const parsed = ProfilePatch.safeParse(await req.json());
  if (!parsed.success) return Response.json({ error: "허용되지 않은 값" }, { status: 400 });
  const user = await db.user.update({
    where: { id: session.user.id },
    data: parsed.data,
    select: { id: true, nickname: true, marketingOptIn: true },  // 응답 필드도 고른다
  });
  return Response.json(user);
}
```
