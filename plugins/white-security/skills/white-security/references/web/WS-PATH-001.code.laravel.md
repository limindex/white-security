<!-- generated from white-security issues; do not edit -->
# WS-PATH-001 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function download(Request $request)
{
    $path = storage_path('app/files/' . $request->query('name'));   // ../../.env 도 그대로
    return response()->download($path);
}
```

✓ 안전 코드 (safe example)

```php
// Route::get('/attachments/{attachment}/download', ...) — 경로 대신 DB id 로 찾는다
public function download(Request $request, Attachment $attachment)
{
    $this->authorize('view', $attachment);                           // 내 파일인지 확인
    return Storage::disk('local')->download($attachment->path, $attachment->original_name); // 경로는 서버가 저장한 값
}
```
