---
id: WS-AUTH-004
title: Exposed Admin Interface (관리자 페이지 노출)
slug: exposed-admin-interface
group: auth
severity: high
site_only: false
depth: brief
languages: [laravel, nextjs, django]
maps:
  owasp2025: [A01, A02]
  api2023: [API5]
  llm2026: []
  agentic2026: []
  asvs5: [V8.2.1, V8.3.1, V8.4.2, V7.3.1]
  cwe: [CWE-306, CWE-862, CWE-284]
  kr_guide: ["보안기능-1", "보안기능-2"]
  kr_ciip: [AE]
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [Laravel(Telescope·Horizon 포함), Next.js, Django admin, 관리자 화면이 있는 모든 웹 서비스]
severity_reason: "영향: 전체 회원 정보 조회·내려받기, 서비스 설정 변경 · 악용: 잘 알려진 주소 접속과 비밀번호 대입만으로 가능"
sources:
  - title: KISA 2026 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드 (관리자 페이지 노출)
    url: https://www.kisa.or.kr/2060204/form?postSeq=22
    year: 2025
  - title: 개인정보의 안전성 확보조치 기준 (개인정보보호위원회고시 제2026-9호) 제6조
    url: https://www.law.go.kr/%ED%96%89%EC%A0%95%EA%B7%9C%EC%B9%99/%EA%B0%9C%EC%9D%B8%EC%A0%95%EB%B3%B4%EC%9D%98%EC%95%88%EC%A0%84%EC%84%B1%ED%99%95%EB%B3%B4%EC%A1%B0%EC%B9%98%EA%B8%B0%EC%A4%80
    year: 2026
  - title: "OWASP API Security Top 10:2023 - API5 Broken Function Level Authorization"
    url: https://owasp.org/API-Security/editions/2023/en/0xa5-broken-function-level-authorization/
    year: 2023
  - title: "Laravel 12.x - Telescope (Local Only Installation·Dashboard Authorization)"
    url: https://laravel.com/docs/12.x/telescope
    year: 2026
  - title: "django-otp - Authentication and Authorization (OTPAdminSite)"
    url: https://django-otp-official.readthedocs.io/en/stable/auth.html
    year: 2026
  - title: 행정안전부·KISA 소프트웨어 보안약점 진단가이드 (2021)
    url: https://www.kisa.or.kr/2060204/form?postSeq=9
    year: 2021
---

## 요약

관리자 화면·운영 도구(Django admin, Laravel Telescope·Horizon, DB 관리 도구)가 누구나 닿는 주소에 열려 아이디·비밀번호만으로 들어가거나, 화면에서만 권한을 확인해 관리용 API는 그대로 열린 문제(진단가이드 이름: 적절한 인증 없는 중요 기능 허용). 개인정보를 다루는 관리자 접속은 「개인정보의 안전성 확보조치 기준」 제6조에 따라 외부 접속 시 안전한 인증수단(2단계 인증 등)과 일정 시간 미사용 시 자동 접속 차단이 필요.

## 대응 방법

```text
관리자 화면, 관리용 API, 운영 도구(Telescope·Horizon·Adminer·phpMyAdmin 등)의 경로를 모두 찾아 줘.
각각 서버에서 역할을 확인하는지, 2단계 인증과 IP 제한이 있는지, 운영 서버에서 켜져 있는지 표로 보여 줘.
화면만 막고 API는 열린 곳은 API에도 역할 확인을 넣고, 운영 도구는 로컬에서만 켜지게 바꿔 줘.
```

- 일반 회원 계정으로 관리자 화면 주소와 관리용 API를 직접 열면 404나 403이 나와야 한다.
- 운영 서버에서 `/telescope`·`/horizon`·`/adminer.php`를 열면 열리지 않아야 한다.

## 점검 항목

- 관리자 화면과 API는 서버에서 DB의 역할을 확인해야 한다(메뉴 숨김·주소 바꾸기는 보조).
- 관리자 로그인은 2단계 인증을 쓰고, 접속은 사무실·VPN IP로 제한해야 한다.
- Telescope·Horizon·Adminer·phpMyAdmin 같은 운영 도구는 운영 서버에서 끄거나 잠가야 한다.
- 관리자 세션은 짧은 비활동 시간이 지나면 끊어야 한다.
- 관리자 로그인 실패와 관리 작업은 접속기록(누가·언제·무엇을 했는지 남기는 기록)으로 남겨야 한다.

## 탐지 규칙

- **찾는 곳**: `admin.site.urls`, `viewTelescope`, `viewHorizon`, `app/admin/`, `adminer.php`, `phpmyadmin`, `useSession(`, 그 밖에 /manage·/backoffice 경로와 관리자 로그인 화면.
- **검사 1 — 서버 확인 없는 관리 화면**: 지적할 때: 관리 화면·API가 클라이언트 컴포넌트의 역할 확인(`useSession(` 뒤 조건 렌더링), 메뉴 숨김, 추측하기 어려운 주소에만 기대는 경우. `viewTelescope`·`viewHorizon` 게이트가 항상 참이거나 운영 환경에서 켜진 경우. / 지적하지 않을 때: 서버 컴포넌트·미들웨어·정책이 DB 역할을 확인하고, 관리용 API도 따로 확인하는 경우.
- **검사 2 — 외부 접속 보호**: 지적할 때: 인터넷에 열린 관리자 로그인이 아이디·비밀번호만 받는 경우. Adminer·phpMyAdmin 같은 DB 관리 도구가 웹 루트에 있는 경우. / 지적하지 않을 때: OTP·패스키 같은 2단계 인증이나 VPN·IP 허용 목록이 설정에 보이는 경우(코드만으로 판단이 안 되면 "확인 필요"로 표시).
- **검사 3 — 오래 유지되는 관리자 세션**: 지적할 때: 관리자 세션이 며칠씩 유지되거나 "로그인 유지"가 관리자에게도 적용되는 경우. / 지적하지 않을 때: 비활동 시간 제한이 있는 경우.
- **등급**: 위험: 관리 기능·운영 도구가 로그인이나 역할 확인 없이 열리는 경우, DB 관리 도구가 웹에 노출된 경우. / 주의: 관리자 로그인에 2단계 인증·IP 제한이 없는 경우. / 참고: 관리자 세션 시간 제한 없음, 기본 주소(`/admin/`) 그대로.
- **보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`
- **보고 예**: `[위험] WS-AUTH-004 app/Providers/TelescopeServiceProvider.php:22 — viewTelescope 게이트가 항상 true 라 운영에서 누구나 요청·쿼리 기록 열람 → Telescope 는 local 환경에서만 등록`
- **고칠 방법**: 역할 확인을 서버의 화면·API 양쪽에 넣어야 하고, 관리자 접속은 2단계 인증과 네트워크 제한을 함께 걸어야 한다.

## 코드 예시

### laravel

✗ 취약 코드

```php
// app/Providers/TelescopeServiceProvider.php — 운영에서도 누구나 요청·쿼리·메일 기록을 본다
protected function gate(): void
{
    Gate::define('viewTelescope', fn ($user = null) => true);
}

// routes/web.php — 로그인만 확인, 역할은 메뉴에서만 숨김
Route::middleware('auth')->prefix('admin')->group(base_path('routes/admin.php'));
```

✓ 안전 코드

```php
// app/Providers/AppServiceProvider.php — Telescope 는 로컬에서만 등록(공식 문서의 로컬 전용 설치)
public function register(): void
{
    if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->app->register(TelescopeServiceProvider::class);
    }
}

public function boot(): void
{
    Gate::define('access-admin', fn (User $user) => $user->is_admin);  // DB 에 저장된 역할
}

// routes/web.php — 역할 확인 + 중요 화면 진입 전 비밀번호 재확인
Route::middleware(['auth', 'can:access-admin', 'password.confirm'])
    ->prefix('admin')->group(base_path('routes/admin.php'));
// 관리자 계정은 2단계 인증 필수, 접속은 웹 서버·방화벽에서 사무실·VPN IP 로 제한
```

### nextjs

✗ 취약 코드

```tsx
// app/admin/page.tsx — 클라이언트에서만 역할을 본다
"use client";
import { useSession } from "next-auth/react";

export default function AdminPage() {
  const { data: session } = useSession();
  if (session?.user?.role !== "admin") return <p>권한 없음</p>;
  return <UserTable />;  // 아래 API 는 누구나 부를 수 있다
}

// app/api/admin/users/route.ts
export async function GET() {
  return Response.json(await db.user.findMany());
}
```

✓ 안전 코드

```tsx
// app/admin/layout.tsx — 서버에서 확인(role 은 세션 콜백에서 DB 값으로 넣은 것)
import { auth } from "@/auth";
import { notFound } from "next/navigation";

export default async function AdminLayout({ children }: { children: React.ReactNode }) {
  const session = await auth();
  if (session?.user?.role !== "admin") notFound();       // 남에게는 없는 페이지처럼
  return <>{children}</>;
}

// app/api/admin/users/route.ts — 레이아웃은 API 를 지켜 주지 않으므로 따로 확인
export async function GET() {
  const session = await auth();
  if (session?.user?.role !== "admin") return new Response(null, { status: 404 });
  return Response.json(await db.user.findMany({ select: { id: true, email: true, createdAt: true } }));
}
```

### django

✗ 취약 코드

```python
# urls.py — 누구나 아는 주소, 아이디·비밀번호만으로 로그인
urlpatterns = [
    path("admin/", admin.site.urls),
]

# settings.py
SESSION_COOKIE_AGE = 60 * 60 * 24 * 14   # 관리자 세션도 2주 유지
```

✓ 안전 코드

```python
# settings.py — 관리자 전용 배포(사내 관리 서버) 기준
INSTALLED_APPS += ["django_otp", "django_otp.plugins.otp_totp"]
MIDDLEWARE += ["django_otp.middleware.OTPMiddleware"]   # AuthenticationMiddleware 뒤에
SESSION_COOKIE_AGE = 60 * 30                            # 30분
SESSION_SAVE_EVERY_REQUEST = True                       # 요청마다 연장 → 30분 비활동 시 만료

# urls.py
from django_otp.admin import OTPAdminSite
admin.site.__class__ = OTPAdminSite                     # 관리자 로그인에 OTP(2단계 인증) 요구
urlpatterns = [path(settings.ADMIN_PATH, admin.site.urls)]  # 주소 바꾸기는 보조 수단
# 접속 자체는 웹 서버·방화벽에서 사무실·VPN IP 로 제한
```
