<!-- generated from white-security issues; do not edit -->
# WS-SUP-007 코드 예시 — Next.js

✗ 취약 코드 (vulnerable example — do not use)

```ts
// lib/rate.ts — 결제 금액 계산에 쓰는 환율
export async function getRate(cur: string) {
  const res = await fetch(`http://rates.example.com/v1/${cur}`);   // http, 시간 제한 없음, 리다이렉트 추적
  return res.json().catch(() => ({ rate: 1 }));                    // 실패하면 환율 1 로 결제 진행
}
```

✓ 안전 코드 (safe example)

```ts
// lib/rate.ts
import { z } from "zod";

const Rate = z.object({ currency: z.string().regex(/^[A-Z]{3}$/), rate: z.number().positive().finite() });

export async function getRate(cur: string) {
  const res = await fetch(`https://rates.example.com/v1/${encodeURIComponent(cur)}`, {
    signal: AbortSignal.timeout(5000),   // 5초 시간 제한
    redirect: "error",                   // 리다이렉트는 따라가지 않음
    cache: "no-store",
  });
  if (!res.ok) throw new Error("rate unavailable");   // 실패하면 결제를 멈춤
  return Rate.parse(await res.json());                // 형식이 다르면 예외
}
```
