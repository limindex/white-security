---
id: WS-PPL-001
title: Phishing · Deepfake Voice Scam (피싱·AI 딥보이스·딥페이크 사기)
slug: phishing-deepfake-voice-scam
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
applies_to: [회사 메일·메신저·전화로 송금·계좌 변경을 처리하는 직원, 관리자 계정을 가진 개발자·운영자, 가족 사이 긴급 송금 요청]
severity_reason: "영향: 송금 사기·계정 탈취·악성 앱 설치로 회사와 개인이 직접 돈과 정보를 잃음 · 악용: 공개된 이름·목소리·사진만으로 그럴듯한 메시지와 음성을 만들 수 있음"
sources:
  - title: "FBI IC3 PSA I-120324-PSA - Criminals Use Generative Artificial Intelligence to Facilitate Financial Fraud"
    url: https://www.ic3.gov/PSA/2024/PSA241203
    year: 2024
  - title: "FBI IC3 PSA I-051525-PSA - Senior US Officials Impersonated in Malicious Messaging Campaign (AI 음성 메시지)"
    url: https://www.ic3.gov/PSA/2025/PSA250515
    year: 2025
  - title: "FBI IC3 PSA I-091124-PSA - Business Email Compromise: The $55 Billion Scam"
    url: https://www.ic3.gov/PSA/2024/PSA240911
    year: 2024
  - title: "NCSC - Phishing attacks: defending your organisation"
    url: https://www.ncsc.gov.uk/guidance/phishing
    year: 2024
  - title: "NCSC - Phishing scams: how to spot and report them"
    url: https://www.ncsc.gov.uk/collection/phishing-scams/spot-scams
    year: 2022
---

## 요약

메일·문자·메신저·전화로 대표·거래처·가족·기관인 척하며 링크를 누르게 하거나 송금·계좌 변경·인증번호·로그인 정보를 요구하는 사기. 사기범이 생성형 AI로 어색한 문장을 고치고 짧은 음성만으로 아는 사람 목소리와 화상 통화 속 얼굴까지 흉내 내므로(FBI, 2024), 말투나 목소리보다 다른 경로로 다시 확인하는 절차가 핵심.

## 발생 상황

- 대표·거래처를 사칭한 메일·메신저의 "오늘 안에 송금, 계좌가 바뀌었음" 요청(BEC, 비즈니스 메일 사기)
- 가족·상사 목소리를 흉내 낸 전화·음성 메시지의 긴급 송금·인증번호 요구, 화상 회의 속 딥페이크 임원
- 택배·결제·계정 잠금 안내 문자의 링크로 열리는 가짜 로그인 페이지, 앱 설치 파일(APK) 설치 유도

## 대응 방법

- 송금·계좌 변경·인증번호·비밀번호 요청은 받은 채널로 답하지 말고, 미리 알고 있던 번호나 공식 앱으로 직접 연락해 확인.
- 가족·팀끼리 확인용 암호 문구를 정해 두고, 급한 요청일수록 그 문구로 확인(FBI 권고).
- 메시지 속 링크 대신 즐겨찾기나 공식 앱으로 접속하고, 문자로 받은 앱 설치 파일은 설치하지 않음.
- 이미 눌렀거나 입력했다면 그 계정 비밀번호를 바꾸고 다른 기기 로그아웃, 송금했다면 바로 은행에 지급정지를 요청한 뒤 경찰(112)에 신고.

## 점검 항목

- 송금·계좌 변경은 두 사람 이상 확인하거나 미리 등록한 연락처로 다시 확인하는 절차가 있어야 한다.
- 인증번호·비밀번호·복구 코드는 누구에게도 알려 주지 않아야 한다.
- 메시지 속 링크로 로그인하지 않고 공식 앱·즐겨찾기로 들어가야 한다.
- 가족·팀 확인용 암호 문구를 정해 두어야 한다.
- 피해가 의심되면 비밀번호 변경·지급정지·신고 순서를 바로 밟을 수 있게 연락처를 정리해 두어야 한다.
