---
id: WS-PPL-004
title: Fake AI Apps · Malicious Browser Extensions (가짜 AI 앱·브라우저 확장 프로그램)
slug: fake-ai-apps-extensions
group: people
severity: medium
site_only: true
depth: brief
languages: []
maps:
  owasp2025: []
  api2023: []
  llm2026: [LLM04]
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
applies_to: [AI 채팅·요약·번역 기능을 내세운 브라우저 확장 프로그램, 광고·검색 결과로 찾은 AI 앱·데스크톱 프로그램, 회사 PC와 개인 기기]
severity_reason: "영향: 방문하는 모든 사이트의 화면·입력값·로그인 세션과 AI 대화 내용을 빼 감 · 악용: 유명 AI 이름과 로고를 흉내 낸 앱·확장 프로그램, 설치 뒤 업데이트로 권한이나 동작을 바꿈"
sources:
  - title: "NCSC - Device security guidance: Managing web browser security (Third-party extensions)"
    url: https://www.ncsc.gov.uk/collection/device-security-guidance/policies-and-settings/managing-web-browser-security
    year: 2025
  - title: "Chrome Web Store Help - Install and manage extensions"
    url: https://support.google.com/chrome_webstore/answer/2664769
    year: 2026
  - title: "Chrome Enterprise and Education Help - Set Chrome app and extension policies (Windows)"
    url: https://support.google.com/chrome/a/answer/7532015
    year: 2026
---

## 요약

유명 AI 서비스 이름과 로고를 흉내 낸 앱·브라우저 확장 프로그램을 설치해, 방문하는 사이트의 내용·입력값·로그인 세션이나 AI 대화가 밖으로 새는 문제. 확장 프로그램은 보통 방문하는 모든 사이트의 데이터를 읽고 바꿀 수 있는 권한을 가지므로(NCSC), 공식 배포처에서 개발자를 확인하고 꼭 필요한 것만 설치해야 함.

## 발생 상황

- 검색 광고·SNS 광고로 찾은 "무료 AI 비서" 확장 프로그램이 모든 사이트 읽기 권한을 요청
- 공식 앱 이름을 흉내 낸 앱·설치 파일, 공식 사이트처럼 보이는 가짜 다운로드 페이지
- 처음엔 정상이던 확장 프로그램이 주인이 바뀐 뒤 업데이트로 데이터를 빼 가기 시작

## 대응 방법

- AI 앱은 서비스 회사의 공식 사이트에서 연결한 앱 스토어·다운로드 링크로만 설치하고, 개발자 이름을 확인.
- 확장 프로그램 설치 화면의 권한을 읽고, "모든 웹사이트의 데이터 읽기·변경"을 요구하면 꼭 필요한지 다시 판단.
- 쓰지 않는 확장 프로그램은 지우고, 남은 것은 특정 사이트에서만 동작하도록 사이트 접근 권한을 좁힘.
- 회사 PC는 관리자가 허용한 확장 프로그램만 설치되게 정책으로 묶음(Chrome Enterprise 허용 목록 등).

## 점검 항목

- AI 앱·프로그램은 공식 사이트에서 연결한 배포처로만 설치해야 한다.
- 설치한 확장 프로그램 목록을 주기적으로 확인하고 쓰지 않는 것은 지워야 한다.
- 모든 사이트 접근 권한을 가진 확장 프로그램은 꼭 필요한 것만 두어야 한다.
- 회사 PC는 허용한 확장 프로그램만 설치되게 관리해야 한다.
- 의심스러운 확장 프로그램을 지웠다면 그 브라우저로 로그인했던 계정의 비밀번호를 바꾸고 다른 기기 로그아웃을 해야 한다.
