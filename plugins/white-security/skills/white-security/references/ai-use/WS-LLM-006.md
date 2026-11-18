<!-- generated from white-security issues; do not edit -->
# WS-LLM-006 Misinformation · Overreliance (AI 답을 사실로 믿고 자동 처리)

## 탐지 규칙

- **찾는 곳**: LLM 응답(`chat.completions.create(`, `messages.create(`, `generateContent(`)을 받은 뒤 상태를 바꾸는 호출(`refund(`, `refunds.create(`, `charge(`, `approve(`, update·send 같은 변경·발송 함수)과, 고객 화면에 AI 답을 내보내는 곳.
- **검사 1 — AI 답이 곧바로 결정이 되는 구조**: 지적할 때: LLM 응답 속 금액·승인 여부·대상이 검증 없이 돈·권한·데이터 변경 함수로 들어가는 경우. / 지적하지 않을 때: 응답은 분류 값만 쓰고 금액·자격은 서버 규칙이 다시 계산하는 경우. 사람 승인 단계를 거치는 경우.
- **검사 2 — 사실처럼 나가는 AI 답**: 지적할 때: 정책·가격·의료·법률 안내를 AI가 쓴 그대로 고객에게 보내고, 근거나 AI 표시가 없는 경우. / 지적하지 않을 때: 정해진 문서에서 찾은 내용만 답하고 근거 링크를 붙이는 경우.
- **등급**: 위험: AI 답만으로 환불·결제·권한 변경이 실행되는 경우. / 주의: 정책·가격 안내가 근거 없이 고객에게 나가는 경우. / 참고: AI 표시나 근거 링크가 없는 경우.
- **보고 예**: `[위험] WS-LLM-006 support/auto.py:33 — 모델이 정한 refund_amount 로 바로 환불 실행 → 금액은 주문·정책 규칙으로 계산하고, 규칙 밖이면 사람 확인 대기열로`

## 점검 항목

- 금액·자격·기한 같은 사실은 AI가 아니라 서버 코드가 DB와 규칙으로 정해야 하고, AI에게는 분류·초안 작성만 맡겨야 한다.
- AI 답은 정해진 형식(JSON 스키마)으로 받아 검사하고, 틀리거나 규칙 밖이면 사람에게 넘겨야 한다.
- 고객에게 보이는 AI 답에는 "AI 답변" 표시와 근거 문서 링크를 붙여야 한다. 2024년 캐나다 BC 민사분쟁심판소(BC Civil Resolution Tribunal)는 챗봇이 틀린 할인 환급 규정을 안내한 항공사에 손해배상·이자·심판 비용을 합쳐 812.02 캐나다달러 지급을 명령함.
- 사람이 승인하는 화면에는 AI의 설명이 아니라 실제로 바뀔 값(대상·금액)을 보여야 한다.
- AI가 쓴 코드·패키지 이름·법령 조문은 공식 문서로 확인해야 한다(패키지는 WS-SUP-001).

Code examples (read only the file for the stack you are writing): `WS-LLM-006.code.python.md` (Python)

기본 심각도: 주의
기준: OWASP LLM 2026 LLM07(잘못된 정보) · OWASP 에이전트 2026 ASI09(사람-에이전트 신뢰 악용) · CWE-1426
