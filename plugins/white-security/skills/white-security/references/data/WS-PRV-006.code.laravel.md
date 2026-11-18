<!-- generated from white-security issues; do not edit -->
# WS-PRV-006 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 관리자 회원 내려받기: 모든 컬럼·모든 회원, 평문 CSV
public function export()
{
    return response()->streamDownload(function () {
        $out = fopen('php://output', 'w');
        foreach (User::cursor() as $u) {
            fputcsv($out, $u->toArray(), escape: '');
        }
    }, 'members.csv');
}
```

✓ 안전 코드 (safe example)

```php
public function export(ExportRequest $request)          // 사유(reason) 필수 입력
{
    $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : $v;  // 수식 실행 막기
    $csv = fopen('php://temp', 'w+');
    fputcsv($csv, ['회원번호', '이름', '가입일'], escape: '');    // 업무에 필요한 항목만
    foreach (User::select('id', 'name', 'created_at')->cursor() as $u) {
        fputcsv($csv, [$u->id, $safe($u->name), $u->created_at->toDateString()], escape: '');
    }
    rewind($csv);

    $password = Str::password(16);
    $path = storage_path('app/private/exports/' . Str::uuid() . '.zip');
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('members.csv', stream_get_contents($csv));
    $zip->setEncryptionName('members.csv', ZipArchive::EM_AES_256, $password);  // AES-256 암호 압축
    $zip->close();

    $token = Str::random(40);
    Cache::put("export:{$token}", ['path' => $path, 'admin' => $request->user()->id], now()->addMinutes(10));
    // 접속기록(WS-PRV-003): 운영자·IP·대상·"다운로드"·사유
    // 비밀번호는 이 화면에서 한 번만 보여 주고 저장하지 않음. 파일은 별도 링크로 받음
    return response()->view('admin.export-ready', [
        'password' => $password,
        'url' => route('admin.export.download', $token),
    ])->header('Cache-Control', 'no-store');
}

public function download(Request $request, string $token)
{
    $job = Cache::pull("export:{$token}");                     // 한 번만, 10분 안에, 만든 사람만
    abort_unless($job && $job['admin'] === $request->user()->id, 404);
    return response()->download($job['path'])->deleteFileAfterSend();
}
```
