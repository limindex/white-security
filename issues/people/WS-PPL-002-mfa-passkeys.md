---
id: WS-PPL-002
title: MFA · Passkeys (2단계 인증·패스키·비밀번호 관리)
title_en: MFA (Multi-Factor Authentication) · Passkeys
slug: mfa-passkeys
group: people
severity: high
site_only: true
depth: brief
languages: []
maps:
  owasp2025: []
  api2023: []
  llm2026: []
  agentic2026: []
  asvs5: []
  cwe: []
  kr_guide: []
  kr_ciip: []
published: 2026-11-18
checked: 2026-11-18
revisions:
  - date: 2026-11-18
    note: 최초 작성
applies_to: [메일·클라우드·코드 저장소·도메인·결제·AI 도구 계정, AI 코딩에 쓰는 GitHub·Supabase·Vercel·Firebase 콘솔 계정]
severity_reason: "영향: 메일·코드 저장소·클라우드 콘솔 계정 하나가 넘어가면 서비스 전체와 고객 데이터가 넘어감 · 악용: 다른 곳에서 새어 나간 비밀번호 재사용, 피싱 페이지로 받은 인증번호"
sources:
  - title: "NIST SP 800-63B-4 Digital Identity Guidelines: Authentication and Authenticator Management (문자·음성 인증번호는 제한적 방식)"
    url: https://pages.nist.gov/800-63-4/sp800-63b.html
    year: 2025
  - title: "NCSC - Passkeys: they're not perfect but they're getting better"
    url: https://www.ncsc.gov.uk/blog-post/passkeys-not-perfect-getting-better
    year: 2025
  - title: "NCSC - Multi-factor authentication for your corporate online services"
    url: https://www.ncsc.gov.uk/collection/mfa-for-your-corporate-online-services
    year: 2024
  - title: "CISA - Implementing Phishing-Resistant MFA (Fact Sheet)"
    url: https://www.cisa.gov/sites/default/files/publications/fact-sheet-implementing-phishing-resistant-mfa-508c.pdf
    year: 2022
---

## 요약

같은 비밀번호를 여러 곳에 쓰거나 2단계 인증 없이 비밀번호만으로 메일·코드 저장소·클라우드 콘솔에 로그인해, 한 곳에서 새어 나간 비밀번호로 줄줄이 계정을 빼앗기는 문제.

## 발생 상황

- 오래전 가입한 사이트에서 새어 나간 비밀번호를 그대로 쓰는 메일·GitHub 계정
- 피싱 페이지가 비밀번호와 문자 인증번호를 함께 받아 곧바로 로그인(실시간 중계)
- "승인" 알림을 반복해 보내 지친 사용자가 누르게 만드는 푸시 폭탄

## 대응 방법

- 비밀번호 관리자를 쓰고, 계정마다 길고 다른 비밀번호를 자동으로 만들게 함.
- 메일·코드 저장소·클라우드·도메인·결제 계정은 패스키(기기에 저장된 열쇠로 로그인하는 방식)나 보안 키를 먼저, 안 되면 인증 앱을 켬. 문자 인증번호는 마지막 선택(NIST는 제한적 방식으로 분류).
- 복구 코드는 비밀번호 관리자나 종이에 따로 보관하고, 복구용 메일·전화번호를 최신으로 유지.
- 요청하지 않은 로그인 승인 알림은 누르지 않고, 받은 즉시 비밀번호를 바꿈.

## 점검 항목

- 중요한 계정은 모두 서로 다른 비밀번호를 써야 한다.
- 메일·코드 저장소·클라우드 콘솔·도메인 계정은 패스키·보안 키·인증 앱 중 하나로 2단계 인증을 켜야 한다.
- 복구 코드를 계정과 다른 곳에 보관해야 한다.
- 팀 공용 계정은 비밀번호 공유 대신 개인 계정 초대·역할로 바꿔야 한다.
- 퇴사·역할 변경 때 계정과 2단계 인증 기기를 정리해야 한다.
