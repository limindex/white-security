---
id: WS-PPL-005
title: Pasting Sensitive Data into AI Chat (AI 채팅에 회사 자료·개인정보 붙여넣기)
slug: pasting-sensitive-data-ai-chat
group: people
severity: medium
site_only: true
depth: brief
languages: []
maps:
  owasp2025: []
  api2023: []
  llm2026: [LLM02]
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
applies_to: [ChatGPT·Claude·Gemini 같은 AI 채팅, AI 코딩 도구에 붙여 넣는 로그·설정 파일, 고객 명단·계약서·소스 코드·API 키]
severity_reason: "영향: 고객 개인정보·영업 비밀·키가 회사 밖 서비스에 저장되고, 설정에 따라 학습·검토에 쓰이거나 계정 탈취 때 함께 유출 · 악용: 대화 기록이 남은 계정 하나만 뚫으면 됨"
sources:
  - title: "개인정보보호위원회 - 생성형 인공지능(AI) 개발·활용을 위한 개인정보 처리 안내서 (2025.8)"
    url: https://www.pipc.go.kr/np/cop/bbs/selectBoardArticle.do?bbsId=BS074&mCode=C020010000&nttId=11410
    year: 2025
  - title: "NCSC - ChatGPT and large language models: what's the risk?"
    url: https://www.ncsc.gov.uk/blog-post/chatgpt-and-large-language-models-whats-the-risk
    year: 2023
  - title: "OWASP Top 10 for LLM Applications 2025 - LLM02 Sensitive Information Disclosure"
    url: https://genai.owasp.org/llmrisk/llm022025-sensitive-information-disclosure/
    year: 2025
---

## 요약

고객 명단·계약서·회의록·소스 코드·`.env`·오류 로그를 AI 채팅에 그대로 붙여 넣어, 개인정보와 비밀값이 회사 밖 서비스에 저장되고 설정에 따라 학습·검토에 쓰이는 문제. NCSC는 공개 AI 서비스 질문에 민감한 정보를 넣지 말라고 권고하므로, 붙여 넣기 전에 가리고 회사가 정한 계정·설정에서만 써야 함.

## 발생 상황

- 고객 문의 엑셀을 통째로 붙여 넣고 "요약해 줘"
- 오류를 고치려고 API 키·DB 비밀번호가 든 `.env`나 로그를 AI 채팅·코딩 도구에 붙여 넣음
- 개인 계정 무료 서비스에 회사 계약서·회의록을 올려 대화 기록이 개인 계정에 남음

## 대응 방법

- 붙여 넣기 전에 이름·전화번호·주민등록번호·계좌·키를 지우거나 가짜 값으로 바꿈.
- 회사가 계약하고 학습 사용을 끈 업무용 계정만 쓰고, 개인 계정에 회사 자료를 올리지 않음.
- 키·비밀번호가 든 파일은 AI 도구가 읽지 못하게 막고(`.env` 제외 설정), 이미 붙여 넣었다면 키를 폐기·재발급.
- 개인정보를 AI로 처리하는 업무는 처리 목적·위탁 여부를 개인정보 담당자와 먼저 확인(개인정보위 안내서).

## 점검 항목

- AI 채팅에 붙여 넣는 자료에서 개인정보와 비밀값을 지워야 한다.
- 회사 자료는 회사가 정한 업무용 계정·설정에서만 AI에 넣어야 한다.
- 학습 사용·대화 기록 보관 설정을 확인하고 회사 기준에 맞춰야 한다.
- 키가 든 파일은 AI 코딩 도구의 읽기 대상에서 빼야 한다(WS-AGT-006).
- 실수로 비밀값을 붙여 넣었다면 즉시 폐기·재발급해야 한다.
