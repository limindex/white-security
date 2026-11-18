<!-- generated from white-security issues; do not edit -->
# WS-CFG-004 코드 예시 — Supabase

✗ 취약 코드 (vulnerable example — do not use)

```ts
// 신분증 사진을 공개 버킷에 올림 — 주소만 알면 누구나 열람
await supabaseAdmin.storage.createBucket("id-cards", { public: true });

const { data } = supabase.storage.from("id-cards").getPublicUrl(`${user.id}.jpg`);
await saveProfile({ idCardUrl: data.publicUrl });
```

✓ 안전 코드 (safe example)

```ts
// 서버에서 한 번: 비공개 버킷
await supabaseAdmin.storage.createBucket("id-cards", { public: false });

// 본인 폴더만 읽게 하는 storage.objects 정책(SQL)
// create policy "id_cards_owner_read" on storage.objects for select to authenticated
//   using (bucket_id = 'id-cards' and (storage.foldername(name))[1] = (select auth.uid())::text);

// 볼 때마다 60초짜리 서명 URL 을 만든다(로그인한 사용자 클라이언트로, 정책이 적용됨)
const { data, error } = await supabase.storage
  .from("id-cards")
  .createSignedUrl(`${user.id}/front.jpg`, 60);
```
