<!-- generated from white-security issues; do not edit -->
# WS-PRV-005 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/posts/route.ts — 게시판 목록: 화면은 제목·닉네임만 씀
export async function GET() {
  const posts = await prisma.post.findMany({ include: { author: true } }); // 작성자 이메일·전화번호·생년월일까지
  return Response.json(posts);
}

// app/me/page.tsx (서버 컴포넌트): <ProfileCard user={user} />  — user 전체가 브라우저로 직렬화
```

✓ 안전 코드 (safe example)

```ts
// app/api/posts/route.ts
export async function GET() {
  const posts = await prisma.post.findMany({
    select: { id: true, title: true, createdAt: true, author: { select: { nickname: true } } }, // 화면에 쓰는 것만
    orderBy: { createdAt: "desc" },
    take: 20,
  });
  return Response.json(posts);
}

// app/me/page.tsx: <ProfileCard user={{ nickname: user.nickname, avatarUrl: user.avatarUrl }} />
```
