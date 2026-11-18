---
id: WS-LOG-001
title: Security Logging & Alerting Failures (보안 로그·경보 없음)
slug: security-logging-alerting
group: deploy
severity: medium
site_only: false
depth: brief
languages: [laravel, django]
maps:
  owasp2025: [A09]
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: [V16.2.1, V16.2.5, V16.3.1, V16.3.2, V16.4.1, V16.4.2, V16.4.3]
  cwe: [CWE-778, CWE-223, CWE-532, CWE-117]
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel, Django, 로그인·관리자 화면·개인정보 내려받기가 있는 모든 서비스, 개인정보처리시스템]
severity_reason: "영향: 계정 탈취·데이터 유출을 몇 달 뒤에야 알고, 피해 범위와 원인을 밝히지 못함 · 악용: 기록이 없으면 공격이 오래 이어져도 드러나지 않음"
sources:
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제2조·제8조
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: 개인정보보호위원회 개인정보의 안전성 확보조치 기준 안내서 (2026.11, 접속기록 항목)
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS217&mCode=D010030000&nttId=12611
    year: 2026
  - title: "OWASP Top 10:2025 - A09 Security Logging and Alerting Failures"
    url: https://top10.owasp.org/2025/A09_2025-Security_Logging_and_Alerting_Failures
    year: 2025
  - title: "OWASP ASVS 5.0 - V16 Security Logging and Error Handling"
    url: https://github.com/OWASP/ASVS/tree/master/5.0/en
    year: 2025
  - title: OWASP Logging Cheat Sheet
    url: https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html
    year: 2026
  - title: "Django 6.0 - django.contrib.auth signals (user_login_failed 는 비밀번호를 가린 credentials 를 넘김)"
    url: https://docs.djangoproject.com/en/6.0/ref/contrib/auth/
    year: 2026
---

## 요약

로그인 실패·권한 거부·관리자 작업·개인정보 조회와 내려받기 같은 보안 사건을 기록하지 않거나, 기록만 하고 아무도 보지 않아 침입을 몇 달 뒤에야 알게 되는 문제(OWASP Top 10:2025 A09). 개인정보처리시스템은 「개인정보의 안전성 확보조치 기준」 제8조에 따라 취급자의 접속기록(누가·언제·어디서·누구의 정보를·무엇을 했는지 남긴 기록)을 1년 이상(5만 명 이상·고유식별정보·민감정보 처리 시 2년 이상) 보관하고 위·변조를 막아야 함.

## 대응 방법

```text
로그인 성공·실패, 권한 거부, 관리자 작업, 개인정보 조회·내려받기를 어디서 기록하는지 찾아 빠진 곳을 표로 보여 줘.
빠진 곳에 누가·언제·어디서(IP)·누구의 정보를·무엇을 했는지 남기는 보안 로그를 넣고, 비밀번호·토큰·주민등록번호는 남기지 않게 해 줘.
보안 로그는 앱이 지울 수 없는 별도 저장소로 보내고, 로그인 실패가 몰리거나 대량 내려받기가 생기면 알림이 가게 해 줘.
```

- 틀린 비밀번호로 로그인하면 보안 로그에 계정·IP·시각이 남고 비밀번호는 남지 않아야 한다.
- 보안 로그 보관 기간 설정이 1년(해당하면 2년) 이상이어야 한다.

## 점검 항목

- 로그인 성공·실패, 권한 거부, 관리자 작업은 보안 로그로 남겨야 한다.
- 접속기록은 계정·일시·접속지·처리한 정보주체·수행 업무를 담아야 한다.
- 접속기록은 1년 이상(5만 명 이상 등은 2년 이상) 보관해야 한다.
- 로그에는 비밀번호·토큰·주민등록번호·카드번호를 남기지 않아야 한다.
- 로그는 앱 계정이 지우거나 고칠 수 없는 별도 저장소로 보내야 한다.
- 로그인 실패 급증·대량 내려받기에는 알림이 가야 한다.

## 탐지 규칙

- **찾는 곳**: `user_login_failed`, `Events\Failed`, `Log::channel(`, `LOGGING = {`, 그 밖에 로그인·관리자·내보내기 핸들러, logging 설정의 보관 기간(days), 감사 로그 테이블, 요청 본문 전체를 찍는 로그.
- **검사 1 — 남기지 않는 보안 사건**: 지적할 때: 로그인 성공·실패, 권한 거부(403), 관리자 작업, 권한 변경, 개인정보 조회·내려받기 핸들러에 기록 코드가 없는 경우, 기록에 계정·시각·IP·대상 중 하나가 빠진 경우. / 지적하지 않을 때: 프레임워크 이벤트(`Failed`·`user_login_failed`)나 공통 미들웨어가 한곳에서 남기는 경우.
- **검사 2 — 로그에 남는 비밀·개인정보**: 지적할 때: 요청 본문 전체·`Authorization` 헤더·비밀번호·토큰·주민등록번호·카드번호를 로그에 넣는 경우, 사용자 입력을 줄바꿈 처리 없이 평문 로그에 넣는 경우(가짜 로그 줄을 끼워 넣는 로그 위조). / 지적하지 않을 때: 가림 처리한 값이나 식별자만 남기는 경우, JSON 같은 구조 로그로 남기는 경우.
- **검사 3 — 보관·보호·알림**: 지적할 때: 보안 로그 보관이 며칠(`'days' => 14`)뿐이거나 앱 서버 로컬 파일에만 있어 앱 계정이 지울 수 있는 경우, 로그인 실패 급증에 알림이 없는 경우. / 지적하지 않을 때: 별도 로그 저장소·추가만 되는 저장(append-only)·보관 기간 설정이 보이는 경우(인프라 설정이라 저장소에 없으면 "확인 필요"로 표시).
- **등급**: 위험: 로그에 비밀번호·토큰·주민등록번호가 남는 경우. / 주의: 로그인 실패·관리자 작업 기록 없음, 개인정보처리시스템 접속기록 보관 1년 미만. / 참고: 알림 없음, 로그 시각의 시간대 불명확.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-LOG-001 app/Http/Controllers/AuthController.php:31 — Log::info('login', $request->all()) 로 비밀번호가 로그에 남음 → 로그인 이벤트 리스너에서 계정·IP만 기록`
- **검토 주의**: 고시 제8조의 접속기록은 개인정보처리시스템에 접속한 취급자(관리자·운영자)가 대상이고 정보주체(일반 회원)는 빠지므로, 일반 회원 로그인 기록까지 2년 두라고 단정하지 않아야 한다. 오류 응답 내용은 WS-ERR-001에서 다룬다.
- **고칠 방법**: 보안 사건을 프레임워크 이벤트나 공통 미들웨어 한곳에서 구조 로그로 남기고, 비밀값은 빼며, 별도 저장소·보관 기간·알림을 함께 정해야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// 로그인 실패는 아무 데도 남기지 않고, 성공 때는 요청 전체를 남김
public function login(Request $request)
{
    if (! Auth::attempt($request->only('email', 'password'))) {
        return back()->withErrors(['email' => '로그인 실패']);
    }
    Log::info('login', $request->all());          // 비밀번호까지 로그에
    return redirect('/');
}
// config/logging.php: daily 채널 'days' => 14 — 2주 뒤 삭제
```

✓ 안전 코드

```php
// app/Providers/AppServiceProvider.php — 로그인 사건을 한곳에서 기록(비밀번호는 넣지 않음)
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;

public function boot(): void
{
    Event::listen(fn (Failed $e) => Log::channel('security')->warning('auth.failed', [
        'email' => $e->credentials['email'] ?? null, 'ip' => request()->ip(),
    ]));
    Event::listen(fn (Login $e) => Log::channel('security')->info('auth.login', [
        'user_id' => $e->user->getAuthIdentifier(), 'ip' => request()->ip(),
    ]));
}

// 관리자의 개인정보 내려받기(접속기록: 누가·언제·어디서·누구의 정보를·무엇을)
Log::channel('security')->info('admin.export', [
    'admin_id' => $request->user()->id, 'ip' => $request->ip(),
    'subject_ids' => $ids, 'action' => 'download',
]);

// config/logging.php — 'security' 채널은 별도 수집 서버로(앱이 지울 수 없게), 1년 이상 보관
// 'security' => ['driver' => 'syslog', 'level' => 'info', 'facility' => LOG_AUTH],
```

### django

✗ 취약 코드

```python
# views.py
def login_view(request):
    user = authenticate(request, username=request.POST["username"], password=request.POST["password"])
    logger.info("login attempt %s", request.POST)          # 비밀번호까지 기록
    if user is None:
        return render(request, "login.html", {"error": True})   # 실패는 기록 없음
    login(request, user)
    return redirect("/")
```

✓ 안전 코드

```python
# accounts/signals.py — apps.py 의 ready() 에서 import
import logging
from django.contrib.auth.signals import user_logged_in, user_login_failed
from django.dispatch import receiver

security = logging.getLogger("security")

def client_ip(request):
    return request.META.get("REMOTE_ADDR") if request else None   # 프록시 뒤면 신뢰하는 프록시가 넣은 값만

@receiver(user_login_failed)
def on_failed(sender, credentials, request=None, **kwargs):
    # credentials 는 비밀번호가 가려져 오지만, 아이디만 골라 남긴다
    security.warning("auth.failed", extra={"username": credentials.get("username"), "ip": client_ip(request)})

@receiver(user_logged_in)
def on_login(sender, request, user, **kwargs):
    security.info("auth.login", extra={"user_id": user.pk, "ip": client_ip(request)})

# settings.py LOGGING 의 "security" 로거는 별도 수집 서버로 보내고 1년 이상 보관, 실패 급증에 알림
```
