<!-- generated from white-security issues; do not edit -->
# WS-INJ-002 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
public function zip(Request $request)
{
    $dir = $request->input('folder');
    shell_exec("cd storage/app/exports && zip -r out.zip $dir");   // 입력이 셸 문자열에 들어간다
    return response()->download(storage_path('app/exports/out.zip'));
}
```

✓ 안전 코드 (safe example)

```php
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

public function zip(Request $request)
{
    $data = $request->validate(['folder' => ['required', 'string', 'regex:/^[a-z0-9_]{1,40}$/']]); // 형식 검사
    $base = storage_path('app/exports/' . $request->user()->id);           // 본인 폴더 안에서만 고른다
    abort_unless(is_dir("$base/{$data['folder']}"), 404);
    $out = storage_path('app/tmp/' . Str::uuid() . '.zip');                 // 요청마다 새 파일 이름
    $result = Process::path($base)->timeout(60)
        ->run(['zip', '-r', $out, $data['folder']]);                         // 배열 = 셸 없이 인자 목록
    abort_unless($result->successful(), 500);
    return response()->download($out, 'export.zip')->deleteFileAfterSend(); // 보낸 뒤 지운다
}
```
