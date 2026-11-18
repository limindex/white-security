<!-- generated from white-security issues; do not edit -->
# WS-RACE-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
"use server";
export async function claimSignupBonus() {
  const user = await requireUser();
  const u = await prisma.user.findUnique({ where: { id: user.id } });
  if (u?.bonusClaimed) throw new Error("이미 받은 보상입니다");   // 확인
  await prisma.user.update({                                      // 따로 변경
    where: { id: user.id },
    data: { bonusClaimed: true, points: { increment: 1000 } },
  });
}
```

✓ 안전 코드 (safe example)

```ts
"use server";
export async function claimSignupBonus() {
  const user = await requireUser();
  const { count } = await prisma.user.updateMany({
    where: { id: user.id, bonusClaimed: false },                  // 조건을 UPDATE 안에
    data: { bonusClaimed: true, points: { increment: 1000 } },
  });
  if (count !== 1) throw new Error("이미 받은 보상입니다");        // 바뀐 행 수로 판단
}
```
