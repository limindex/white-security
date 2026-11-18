<!-- generated from white-security issues; do not edit -->
# WS-AUTH-003 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// app/api/login/route.ts
const fails = new Map<string, number>();            // 서버 여러 대·재시작이면 초기화된다

export async function POST(req: Request) {
  const { email, password } = await req.json();
  const user = await db.user.findUnique({ where: { email } });
  if (!user) return Response.json({ error: "가입되지 않은 이메일" }, { status: 404 });  // 계정 존재 노출
  if (!(await verify(user.passwordHash, password))) {
    fails.set(email, (fails.get(email) ?? 0) + 1);  // 세기만 하고 막지 않음
    return Response.json({ error: "비밀번호가 틀림" }, { status: 401 });
  }
  return startSession(user);
}
```

✓ 안전 코드 (safe example)

```ts
import { Ratelimit } from "@upstash/ratelimit";
import { Redis } from "@upstash/redis";
import { verify } from "@node-rs/argon2";

const redis = Redis.fromEnv();
const pairLimit = new Ratelimit({ redis, limiter: Ratelimit.slidingWindow(5, "1 m"), prefix: "login" });
const acctLimit = new Ratelimit({ redis, limiter: Ratelimit.slidingWindow(30, "1 h"), prefix: "login-acct" });

export async function POST(req: Request) {
  const { email, password } = await req.json();
  const id = String(email).toLowerCase();
  const ip = clientIp(req);                          // 배포 환경 프록시가 붙인 값만 쓰는 함수
  const [a, b] = await Promise.all([pairLimit.limit(`${id}|${ip}`), acctLimit.limit(id)]);
  if (!a.success || !b.success) return Response.json({ error: "잠시 뒤 다시 시도해 주세요." }, { status: 429 });
  const user = await db.user.findUnique({ where: { email: id } });
  const ok = await verify(user?.passwordHash ?? DUMMY_HASH, String(password));  // 없는 계정도 미리 만든 해시로 같은 시간
  if (!user || !ok) return Response.json({ error: "이메일 또는 비밀번호가 맞지 않습니다." }, { status: 401 });
  return startSession(user);
}
```
