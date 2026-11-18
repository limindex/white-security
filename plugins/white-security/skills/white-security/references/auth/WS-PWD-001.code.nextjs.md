<!-- generated from white-security issues; do not edit -->
# WS-PWD-001 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
import { createHash } from "crypto";

// 가입: 솔트·반복 없음 → 같은 비밀번호는 언제나 같은 값, GPU 로 빠르게 대입된다
const passwordHash = createHash("sha256").update(password).digest("hex");
await db.user.create({ data: { email, passwordHash } });

// 로그인
const ok = user.passwordHash === createHash("sha256").update(password).digest("hex");
```

✓ 안전 코드 (safe example)

```ts
import { hash, verify } from "@node-rs/argon2";

const ARGON = { memoryCost: 19456, timeCost: 2, parallelism: 1 };  // OWASP 최소값(19MiB·2회·1), 기본 Argon2id

// 가입
// 15자 이상(2단계 인증과 함께 쓰면 8자까지 허용)
if (password.length < 15 || password.length > 128) return Response.json({ error: "15~128자" }, { status: 400 });
if (await isBreached(password)) return Response.json({ error: "유출된 적 있는 비밀번호" }, { status: 400 });
await db.user.create({ data: { email, passwordHash: await hash(password, ARGON) } });
// isBreached: 유출 비밀번호 조회(예: Pwned Passwords range API, SHA-1 앞 5자만 보내는 방식)

// 로그인: 문자열 비교 대신 verify(솔트·설정은 해시 문자열 안에 있다)
const ok = await verify(user.passwordHash, password);
```
