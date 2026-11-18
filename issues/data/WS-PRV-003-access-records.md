---
id: WS-PRV-003
title: Missing Access Records (개인정보 접속기록 보관·위변조 방지)
slug: access-records
group: data
severity: medium
site_only: false
depth: brief
languages: [laravel, sql]
maps:
  owasp2025: [A09]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V16.2.1, V16.2.2, V16.3.2, V16.4.2, V16.4.3]
  cwe: [CWE-778, CWE-223]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, PostgreSQL, 관리자·운영자·상담원이 회원 개인정보를 조회·수정·내려받는 관리 화면, 개인정보처리시스템]
severity_reason: "영향: 내부자 무단 열람·대량 내려받기와 계정 탈취 뒤 유출을 누가 언제 했는지 밝히지 못함 · 악용: 기록이 없거나 지울 수 있으면 흔적 없이 반복 가능"
sources:
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제2조 제3호·제5조·제8조
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: 개인정보보호위원회 개인정보의 안전성 확보조치 기준 안내서 (2026.11, 접속기록의 보관 및 점검)
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=12611
    year: 2026
  - title: "OWASP ASVS 5.0 - V16 Security Logging and Error Handling"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: "PostgreSQL Docs - GRANT, CREATE TRIGGER"
    url: https://www.postgresql.org/docs/current/sql-grant.html
    year: 2026
---

## 요약

관리자·운영자·상담원 같은 개인정보취급자가 회원 개인정보를 조회·수정·내려받은 내역, 즉 접속기록을 남기지 않거나 짧게 보관하거나 앱이 지울 수 있게 두는 문제. 「개인정보의 안전성 확보조치 기준」 제8조는 개인정보처리시스템(개인정보를 다루는 DB·관리 화면)에 접속한 자(정보주체 제외)의 접속기록을 1년 이상, 5만 명 이상·고유식별정보·민감정보 처리 등에 해당하면 2년 이상 보관하고, 위·변조를 막고 정기적으로 점검하도록 정함.

## 대응 방법

```text
관리자·운영자 화면과 API 중 회원 개인정보를 조회·검색·수정·삭제·내려받는 곳을 찾아, 접속기록을 남기는지 표로 보여 줘.
빠진 곳은 공통 미들웨어로 계정·일시·접속지(IP)·처리한 정보주체(회원 id)·수행업무를 기록하게 고쳐 줘.
접속기록은 앱 DB 계정이 INSERT만 할 수 있는 별도 테이블이나 저장소에 두고, 보관 기간을 1년(2년 조건이면 2년) 이상으로 맞춰 줘.
```

- 관리자 계정으로 회원 한 명을 열어 보면 그 회원 id와 "열람"이 접속기록에 남아야 한다.
- 앱 DB 계정으로 접속기록을 UPDATE·DELETE 하면 거절되어야 한다.

## 점검 항목

- 취급자의 개인정보 조회·수정·삭제·내려받기는 모두 접속기록으로 남겨야 한다.
- 접속기록은 계정·일시·접속지·처리한 정보주체·수행업무를 담아야 한다.
- 접속기록은 1년 이상(2년 조건에 해당하면 2년 이상) 보관해야 한다.
- 앱 계정이 접속기록을 고치거나 지울 수 없게 해야 한다.
- 접속기록과 내려받기 내역은 정한 주기로 점검하고, 이상하면 조치해야 한다.
- 관리자 권한 부여·변경·말소 내역은 3년 이상 보관해야 한다.

## 탐지 규칙

- **찾는 곳**: `access_log`, `audit_log`, `AccessLog`, `activity()->`, `auditlog.register(`, `LogEntry`, `/admin`, `'days' =>`, 그 밖에 관리자·상담 화면의 회원 조회·검색·엑셀 내려받기 라우트, 접속기록을 지우는 정리 배치, 감사 테이블 권한(GRANT).
- **검사 1 — 남기지 않는 취급자 행위**: 지적할 때: 관리자·운영자 화면이나 API가 회원 개인정보를 조회·검색·수정·삭제·내려받는데 접속기록이 없는 경우, 기록에 계정·일시·접속지·처리한 정보주체·수행업무 중 빠진 것이 있는 경우, 여러 명을 한 번에 보는 목록·내려받기에서 정보주체를 남기지 않는 경우. / 지적하지 않을 때: 공통 미들웨어·감사 패키지가 관리자 경로 전체를 기록하는 경우, 회원 본인의 내 정보 조회.
- **검사 2 — 보관 기간**: 지적할 때: 접속기록을 지우는 배치나 로그 보관 설정이 1년 미만인 경우, 고유식별정보·민감정보를 다루는데 2년 미만인 경우. / 지적하지 않을 때: 조건에 맞는 보관 기간이 보이는 경우(인프라 설정이라 저장소에 없으면 "확인 필요").
- **검사 3 — 위·변조 방지**: 지적할 때: 앱 DB 계정이 접속기록 테이블에 UPDATE·DELETE 권한을 가진 경우, 관리자 화면에서 접속기록을 고치거나 지울 수 있는 경우, 앱 서버 로컬 파일에만 있는 경우. / 지적하지 않을 때: INSERT만 허용한 계정, 별도 저장소, 한 번 쓰면 고칠 수 없는 저장(WORM)을 쓰는 경우.
- **등급**: 위험: 개인정보 내려받기·대량 조회에 접속기록이 전혀 없음, 앱 계정이 접속기록을 지울 수 있음. / 주의: 필수 항목 누락, 보관 1년 미만(2년 조건 해당 시 2년 미만), 공용 관리자 계정. / 참고: 점검 절차·권한 변경 내역 보관을 저장소에서 확인할 수 없음.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-PRV-003 routes/web.php:42 — /admin/members-export 가 회원 전체를 내려받는데 접속기록 없음 → RecordAccess 미들웨어로 운영자·IP·회원 id·"다운로드" 기록`
- **검토 주의**: 제8조 접속기록의 대상은 개인정보처리시스템에 접속한 취급자이고 정보주체(일반 회원)는 빠지므로, 회원 로그인 기록까지 2년 두라고 단정하지 않아야 한다(보안 로그 일반은 WS-LOG-001). 2년 조건(5만 명 이상, 고유식별정보·민감정보 처리, 기간통신사업자)과 점검 주기는 저장소만으로 판단하기 어려워 사용자에게 확인해야 한다. 처리한 정보주체 칸에는 회원 id 같은 식별값을 쓰고 주민등록번호 같은 원문은 넣지 않아야 한다.
- **고칠 방법**: 관리자 경로 전체에 공통 미들웨어로 접속기록을 남기고, INSERT만 되는 별도 테이블·저장소에 조건에 맞는 기간 동안 보관하며, 대량 조회·업무 시간 외 내려받기를 찾는 정기 점검을 정해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// routes/web.php — 회원 열람·엑셀 내려받기에 기록 없음
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/admin/members/{member}', [MemberController::class, 'show']);
    Route::get('/admin/members-export', [MemberController::class, 'export']);
});

// routes/console.php — 90일 지난 접속기록 삭제
Schedule::call(fn () => AccessLog::where('created_at', '<', now()->subDays(90))->delete())->daily();
```

✓ 안전 코드

```php
// app/Http/Middleware/RecordAccess.php — 관리자 경로에 붙임
class RecordAccess
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        $response = $next($request);
        DB::connection('audit')->table('access_logs')->insert([   // INSERT 권한만 가진 별도 DB 계정
            'operator_id' => $request->user()->id,                // 식별자(취급자별 계정)
            'accessed_at' => now('UTC'),                          // 접속일시
            'ip'          => $request->ip(),                      // 접속지(프록시 뒤면 TrustProxies 설정 확인)
            'subjects'    => json_encode($request->attributes->get('subjects', [])), // 처리한 정보주체(회원 id)
            'action'      => $action,                             // 수행업무
            'detail'      => $request->path(),
        ]);
        return $response;
    }
}

// routes/web.php
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/admin/members/{member}', [MemberController::class, 'show'])->middleware(RecordAccess::class.':열람');
    Route::get('/admin/members-export', [MemberController::class, 'export'])->middleware(RecordAccess::class.':다운로드');
});
// 컨트롤러: $request->attributes->set('subjects', $members->pluck('id'));
// 정리 배치는 감사 관리 계정으로, 1년(2년 조건이면 2년)보다 오래된 기록만
```

### sql

✗ 취약 코드

```sql
-- 앱 계정이 접속기록을 고치고 지울 수 있음
grant all on table access_logs to app_user;
-- 정리 배치: 90일 지난 기록 삭제
delete from access_logs where accessed_at < now() - interval '90 days';
```

✓ 안전 코드

```sql
-- PostgreSQL. 접속기록은 별도 스키마, 소유자는 감사 관리 계정
create table audit.access_logs (
  id          bigint generated always as identity primary key,
  operator_id bigint      not null,                -- 식별자
  accessed_at timestamptz not null default now(), -- 접속일시
  ip          inet        not null,                -- 접속지 정보
  subjects    jsonb       not null,                -- 처리한 정보주체(회원 id, 원문 개인정보 금지)
  action      text        not null,                -- 수행업무(열람·검색·수정·삭제·다운로드 등)
  detail      text
);
grant usage on schema audit to app_user;
revoke all on table audit.access_logs from app_user;
grant insert on table audit.access_logs to app_user;   -- 앱은 쓰기만

-- 소유자라도 고치거나 지우지 못하게 막음(보관 기간 정리는 절차를 거쳐 트리거를 잠시 끄고)
create function audit.deny_change() returns trigger language plpgsql as $$
begin raise exception 'access_logs is append-only'; end $$;
create trigger access_logs_no_change before update or delete on audit.access_logs
  for each row execute function audit.deny_change();
create trigger access_logs_no_truncate before truncate on audit.access_logs
  for each statement execute function audit.deny_change();
```
