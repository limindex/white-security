<!-- generated from white-security issues; do not edit -->
# WS-BAAS-001 코드 예시 — Supabase

✗ 취약 코드 (vulnerable example — do not use)

```ts
import { createClient } from "@supabase/supabase-js";

// 브라우저 번들에 들어가는 변수에 비밀 키를 넣음 → RLS 를 전부 건너뛴다
export const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_SERVICE_ROLE_KEY!,
);
```

✓ 안전 코드 (safe example)

```ts
import { createClient } from "@supabase/supabase-js";

// 브라우저에는 공개용 publishable(옛 anon) 키만. 접근 범위는 RLS 정책이 정한다
export const supabase = createClient(
  process.env.NEXT_PUBLIC_SUPABASE_URL!,
  process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY!,
);

const { data } = await supabase.from("notes").select("id, body"); // 로그인한 본인 행만 돌아온다
```
