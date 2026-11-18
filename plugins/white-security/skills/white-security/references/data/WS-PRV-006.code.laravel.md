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

    // 접속기록(WS-PRV-003): 운영자·IP·대상·"다운로드"·사유. 비밀번호는 파일과 다른 경로(화면 1회 표시 등)로 전달
    return response()->download($path)->deleteFileAfterSend();
}
```
