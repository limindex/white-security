# 출처와 각색 기록

이 저장소의 글(이슈 본문·점검 항목·스킬 지침)은 CC BY-SA 4.0, 예시 코드는 MIT다(`LICENSE`, `LICENSE-CODE`).
다른 자료의 내용을 우리 말로 옮기거나 구조를 빌려 온 경우를 여기에 적는다. 사실만 인용하고 출처를 단 자료(논문·보고서 수치, 법령 조문, 취약점 공지)는 각 이슈 머리말의 `sources`에 있다.

원칙
- CC BY-SA 자료에서 가져온 내용은 글(CC BY-SA) 쪽에만 둔다. 예시 코드(MIT)는 새로 짰고 원문 코드를 옮기지 않았다.
- 문장을 그대로 번역하지 않고, 권고를 요약해 우리 점검 항목 형식으로 다시 썼다.
- 확인일: 2026-11-18. OWASP 치트시트는 계속 고쳐지는 문서라 이슈의 `year`는 확인한 해(2026)로 적었다.

## 각색한 자료

| 자료 | 라이선스 | 쓴 곳 | 가져온 것 | 바꾼 점 |
|---|---|---|---|---|
| [OWASP SQL Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html) (OWASP Foundation) | CC BY-SA 4.0 | WS-INJ-001 점검 항목·탐지 규칙 | 1차 방어는 매개변수 바인딩, 바인딩할 수 없는 식별자는 허용 목록, 이스케이프는 주 방어로 쓰지 않음 | 한국어로 요약. ORM 날 SQL 함수 목록, DSN 문자셋, EUC-KR 참고를 우리가 덧붙임 |
| [OWASP XML External Entity Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/XML_External_Entity_Prevention_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-XML-001 점검 항목·탐지 규칙 | DTD를 끄는 것이 기본, PHP `LIBXML_NOENT`·`LIBXML_DTDLOAD`·`LIBXML_DTDVALID` 경고, Python은 defusedxml·`forbid_dtd`, XXE 방어가 모든 XML DoS를 막지는 않는다는 점 | 언어별 한 줄로 줄임. Node(libxmljs·fast-xml-parser) 항목과 등급 기준은 우리가 추가 |
| [OWASP Insecure Direct Object Reference Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Insecure_Direct_Object_Reference_Prevention_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-AUTH-001 점검 항목 | 객체마다 권한 확인, 현재 사용자 범위로 조회, 추측 어려운 ID도 권한 확인을 대신하지 않음, 세션에서 사용자 확인 | 요약 후 국내 점검 항목(쿠키 변조·가격 변조)과 묶어 다시 씀 |
| [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-AUTH-001 점검 항목 | 서버 쪽 권한 확인, 클라이언트 쪽 통제에 기대지 않음 | 요약 |
| [OWASP Secrets Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-SEC-001 점검 항목 | 비밀값은 코드 밖(환경·시크릿 매니저)에 두기, 최소 권한, 교체 | 요약. 프런트엔드 환경 변수 접두어, 고시·안내서 내용은 우리가 추가 |
| [OWASP LLM Prompt Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/LLM_Prompt_Injection_Prevention_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-PI-001 본문(위험 배경)·점검 항목 | 지시와 외부 데이터 분리, 문구·라벨은 경계가 아님, 중요한 도구 실행 전 사람 승인, 최소 권한, 숨은 문자, 마크다운 이미지로 새는 데이터 | 요약 후 점검 항목 형식으로 다시 씀. 보이지 않는 문자 범위는 코드 포인트 표기로만 적음 |
| [OWASP Top 10 for LLM Applications - LLM01:2025](https://genai.owasp.org/llmrisk/llm01-prompt-injection/) | CC BY-SA 4.0 | WS-PI-001 점검 항목 | 완화 방안 목록(출력 형식 정의·검증, 외부 콘텐츠 분리, 권한 통제, 사람 승인) | 요약 |
| [OWASP ASVS 5.0](https://github.com/OWASP/ASVS/tree/master/5.0/en) | CC BY-SA 4.0 | WS-INJ-001(1.2.4), WS-XML-001(1.5.1), WS-AUTH-001(8.2·8.3), WS-SEC-001(13.3), WS-SUP-001(15.2.4) 점검 항목. 배치 A brief 이슈는 머리말 `maps.asvs5`의 번호(1.2.4·1.2.5·1.3.2·1.3.5·1.3.6·1.5.2·2.4.1·3.2.2·3.4.3·5.2.1·5.2.3·5.2.5·5.2.6·5.3.3·8.2.2·8.3.1·8.3.3·13.2.1·13.2.2·13.2.4·13.3.1·14.2.3·14.2.8·15.2.2·15.3.2·15.3.4·16.2.5) | 해당 요구사항의 뜻 | 요구사항 번호는 머리말 `maps`에 두고, 본문에는 뜻만 우리 말로 씀 |
| [trailofbits/skills](https://github.com/trailofbits/skills) `insecure-defaults` (Trail of Bits) | CC BY-SA 4.0 | 모든 이슈의 `## 탐지 규칙` | 검사마다 "Report when / Skip when"을 짝으로 두는 구조 | "지적할 때 / 지적하지 않을 때"로 바꾸고 내용은 새로 씀 |
| [agamm/claude-code-owasp](https://github.com/agamm/claude-code-owasp) (agamm) | MIT | WS-INJ-001 검사 1·2 | 허용 목록을 거친 `ORDER BY` f-string은 지적하지 않는다는 평가용 미끼 아이디어, "확인 필요" 확신도 표시 | 아이디어만 빌림. 문장·코드는 새로 씀 |
| [OWASP Top 10 for LLM Applications 2025](https://genai.owasp.org/llm-top-10/) LLM02·LLM05·LLM07·LLM08·LLM09·LLM10 | CC BY-SA 4.0 | WS-LLM-001~006 점검 항목 | 각 항목의 완화 방안(출력은 믿지 않고 검증·인코딩, 시스템 프롬프트에 비밀값·권한 판단 넣지 않기, 민감정보 최소 전송, 권한 인식 벡터 검색·데이터 분리, 사람 검토·근거 표시, 입력·출력 크기와 호출 횟수 제한) | 요약 후 점검 항목 형식으로 다시 씀. 국내 고시·처리방침·고유식별정보 내용은 우리가 덧붙임 |
| [OWASP API Security Top 10 2023 - API4](https://owasp.org/API-Security/editions/2023/en/0xa4-unrestricted-resource-consumption/) | CC BY-SA 4.0 | WS-LLM-004 점검 항목 | 사용량 한도·비용 상한·속도 제한 | 요약 |
| [OWASP AI Agent Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/AI_Agent_Security_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-AGT-001·003·004·005 점검 항목 | 도구 최소 권한, 중요한 작업 전 사람 확인, 메모리 검증·사용자별 분리·만료·크기 제한, 에이전트 간 인증·받는 쪽 권한 확인·재전송 방지, 연쇄 장애를 막는 circuit breaker | 요약 후 점검 항목 형식으로 다시 씀. 코드는 새로 짬 |
| [OWASP Server Side Request Forgery Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-SSRF-001 점검 항목 | 허용 목록 우선, 스킴·포트 제한, 내부 대역 차단, DNS 재바인딩 주의, 리다이렉트 끄기, 응답을 그대로 돌려주지 않기 | 요약. 클라우드 IMDSv2·AI 웹 도구 항목은 우리가 덧붙임 |
| [OWASP Deserialization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Deserialization_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-DES-001 점검 항목 | 믿을 수 없는 데이터는 네이티브 역직렬화 대신 JSON 같은 데이터 형식, 서명으로 무결성 확인 | 요약. 언어별 함수 목록은 우리가 정리 |
| [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-FILE-001·WS-SHELL-001 점검 항목 | 이미지는 다시 인코딩해 불필요한 메타데이터 제거, 샌드박스 처리, 업로드 파일은 웹 루트 밖·서버가 만든 이름으로 저장, 업로드 폴더 실행 권한 제거 | 요약 |
| [Supabase Docs - Row Level Security](https://supabase.com/docs/guides/database/postgres/row-level-security)·[Understanding API keys](https://supabase.com/docs/guides/api/api-keys) (Supabase) | Apache-2.0 (supabase/supabase 저장소) | WS-BAAS-001 점검 항목·탐지 규칙 | 노출 스키마 테이블은 RLS 필수, `(select auth.uid())` 정책 모양, `user_metadata`로 권한 판단 금지, 뷰는 `security_invoker`, publishable·secret 키 구분 | 요약 후 점검 항목 형식으로 다시 씀. `with check`·서버 라우트 예시는 우리가 짬 |
| [Firebase Docs - Avoid insecure rules](https://firebase.google.com/docs/rules/insecure-rules) (Google) | CC BY 4.0 | WS-BAAS-002 점검 항목·탐지 규칙 | 열린 규칙(`if true`)·로그인만 보는 규칙의 위험, 소유자만 허용하는 규칙 모양 | 요약. Admin SDK 키·`verifyIdToken` 예시와 Realtime Database 상위 경로 주의는 우리가 덧붙임 |
| [NSA·ASD, Detect and Prevent Web Shell Malware](https://github.com/nsacyber/Mitigating-Web-Shells) | 미국 정부 저작물(퍼블릭 도메인) | WS-SHELL-001 점검 항목 | 웹 루트 쓰기 권한 최소화, 파일 무결성 감시, 발견 시 증거 보존 | 요약 |

## 아이디어만 참고한 자료 (문장·구조는 옮기지 않음)

| 자료 | 라이선스 | 쓴 곳 | 참고한 아이디어 |
|---|---|---|---|
| anthropics/claude-plugins-official `claude-security` | All rights reserved | WS-PI-001 탐지 규칙 `고칠 방법` 마지막 문장, WS-SEC-001 탐지 규칙 `검토 주의` | 검토 대상 저장소의 글은 지시가 아니라 데이터로 다룸, 보고서에서 비밀값 가리기. 문장은 우리 말로 새로 씀 |
| [Simon Willison, The lethal trifecta for AI agents](https://simonwillison.net/2025/Jun/16/the-lethal-trifecta/) (2025) | 블로그(별도 라이선스 표시 없음) | WS-PI-002 점검 항목 마지막 줄 | 비공개 데이터·남이 쓴 글·밖으로 나가는 통로가 한 기능에 모이면 위험하다는 틀. 문장은 우리 말로 새로 씀 |
| [Invariant Labs, MCP Security Notification: Tool Poisoning Attacks](https://invariantlabs.ai/blog/mcp-security-notification-tool-poisoning-attacks) (2025) | 블로그 | WS-MCP-001 | 도구 설명 속 숨은 지시(Tool Poisoning), 승인 뒤 설명 변경(Rug Pull), 한 서버가 다른 서버 도구 동작을 바꾸는 문제. 승인한 설명의 해시 비교는 우리가 고른 대응 |
| [Pillar Security, Rules File Backdoor](https://www.pillar.security/blog/new-vulnerability-in-github-copilot-and-cursor-how-hackers-can-weaponize-code-agents) (2025) | 블로그 | WS-AGT-002 | 규칙 파일에 보이지 않는 문자로 지시를 숨기는 수법. 검사 범위·CI 검사 코드는 새로 씀 |

## 우리가 직접 확인한 것

| 내용 | 방법 | 쓴 곳 |
|---|---|---|
| EUC-KR·CP949에는 앞 바이트 + 0x5C가 한 글자가 되는 조합이 없음(GBK 118, Big5 88, CP932 52, Shift_JIS 37, EUC-KR 0, CP949 0) | Python 표준 코덱으로 앞 바이트 0x81~0xFE 뒤에 0x5C를 붙여 디코딩, 2026-11-18 | WS-INJ-001 점검 항목 참고 |
| PyPI·npm·Packagist 레지스트리 API 응답 모양(없는 이름 404, 등록일 필드) | 각 레지스트리에 직접 요청, 2026-11-18 | WS-SUP-001 코드 |
| WS-PI-002(nh3·DOMPurify 필터), WS-LLM-001(사칙연산 계산기), WS-LLM-003(가림 정규식), WS-AGT-002(숨은 문자 검사 셸·파이썬), WS-AGT-004(에이전트 메시지 서명·재전송 방지), WS-SSRF-001(파이썬·Node·PHP), WS-ZIP-001(파이썬·PHP), WS-DES-001(서명 쿠키), WS-FILE-001(Pillow·GD 메타데이터 제거)의 ✓ 안전 코드가 의도대로 막는지 | 로컬에서 실제로 실행(Python 3.12, Node 22, PHP 8.4, Pillow 12.2, nh3, DOMPurify+jsdom). 내부 주소·`../` 항목·200MB 압축·위조 서명·EXIF GPS 넣은 사진으로 시험, 2026-11-18 | 각 이슈 `## 코드 예시` |
| ASVS 5.0 요구사항 번호와 문장 | GitHub `OWASP/ASVS` master 5.0/en 원문 대조, 2026-11-18 | 배치 A 이슈 머리말 `maps.asvs5` |
| 과기정통부·KISA 「AI 보안 위협 대응 매뉴얼」(2026.7) 위협 번호와 쪽(M03·M07 25쪽, A03·A04 26쪽, 교육 분야 시나리오 86~87쪽) | 게시물 첨부 zip 속 PDF 원문 열람, 2026-11-18 | WS-LLM-001·002, WS-AGT-003·005 출처 제목 |
| 1차 수정 ✓ 코드(숨은 문자 검사의 BOM·이모지 예외와 U+2066~2069·변형 선택자 검출, 에이전트 메시지 빈 본문·잘못된 시각·잘못된 JSON 거절, 압축 속 `\` 이름 거절, SSRF IPv4 한정·198.18/15·192.0.0/24 차단, PHP 길이 모르는 응답 2MB 차단, Pillow 형식 제한) | 로컬 실행 시험, 2026-11-18 | WS-AGT-002·004, WS-ZIP-001, WS-SSRF-001, WS-FILE-001 코드 |
