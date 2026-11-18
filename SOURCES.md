# 출처와 각색 기록

이 저장소의 글(이슈 본문·체크 항목·스킬 지침)은 CC BY-SA 4.0, 예시 코드는 MIT다(`LICENSE`, `LICENSE-CODE`).
다른 자료의 내용을 우리 말로 옮기거나 구조를 빌려 온 경우를 여기에 적는다. 사실만 인용하고 출처를 단 자료(논문·보고서 수치, 법령 조문, 취약점 공지)는 각 이슈 머리말의 `sources`에 있다.

원칙
- CC BY-SA 자료에서 가져온 내용은 글(CC BY-SA) 쪽에만 둔다. 예시 코드(MIT)는 새로 짰고 원문 코드를 옮기지 않았다.
- 문장을 그대로 번역하지 않고, 권고를 요약해 우리 체크 항목 형식으로 다시 썼다.
- 확인일: 2026-11-18. OWASP 치트시트는 계속 고쳐지는 문서라 이슈의 `year`는 확인한 해(2026)로 적었다.

## 각색한 자료

| 자료 | 라이선스 | 쓴 곳 | 가져온 것 | 바꾼 점 |
|---|---|---|---|---|
| [OWASP SQL Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html) (OWASP Foundation) | CC BY-SA 4.0 | WS-INJ-001 체크 항목·스킬이 잡는 방식 | 1차 방어는 매개변수 바인딩, 바인딩할 수 없는 식별자는 허용 목록, 이스케이프는 주 방어로 쓰지 않음 | 한국어로 요약. ORM 날 SQL 함수 목록, DSN 문자셋, EUC-KR 참고를 우리가 덧붙임 |
| [OWASP XML External Entity Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/XML_External_Entity_Prevention_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-XML-001 체크 항목·스킬이 잡는 방식 | DTD를 끄는 것이 기본, PHP `LIBXML_NOENT`·`LIBXML_DTDLOAD`·`LIBXML_DTDVALID` 경고, Python은 defusedxml·`forbid_dtd`, XXE 방어가 모든 XML DoS를 막지는 않는다는 점 | 언어별 한 줄로 줄임. Node(libxmljs·fast-xml-parser) 항목과 등급 기준은 우리가 추가 |
| [OWASP Insecure Direct Object Reference Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Insecure_Direct_Object_Reference_Prevention_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-AUTH-001 체크 항목 | 객체마다 권한 확인, 현재 사용자 범위로 조회, 추측 어려운 ID도 권한 확인을 대신하지 않음, 세션에서 사용자 확인 | 요약 후 국내 점검 항목(쿠키 변조·가격 변조)과 묶어 다시 씀 |
| [OWASP Authorization Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-AUTH-001 체크 항목 | 서버 쪽 권한 확인, 클라이언트 쪽 통제에 기대지 않음 | 요약 |
| [OWASP Secrets Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-SEC-001 체크 항목 | 비밀값은 코드 밖(환경·시크릿 매니저)에 두기, 최소 권한, 교체 | 요약. 프런트엔드 환경 변수 접두어, 고시·안내서 내용은 우리가 추가 |
| [OWASP LLM Prompt Injection Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/LLM_Prompt_Injection_Prevention_Cheat_Sheet.html) | CC BY-SA 4.0 | WS-PI-001 본문(왜 요즘 더 위험한가)·체크 항목 | 지시와 외부 데이터 분리, 문구·라벨은 경계가 아님, 중요한 도구 실행 전 사람 승인, 최소 권한, 숨은 문자, 마크다운 이미지로 새는 데이터 | 요약 후 체크 항목 형식으로 다시 씀. 보이지 않는 문자 범위는 코드 포인트 표기로만 적음 |
| [OWASP Top 10 for LLM Applications - LLM01:2025](https://genai.owasp.org/llmrisk/llm01-prompt-injection/) | CC BY-SA 4.0 | WS-PI-001 체크 항목 | 완화 방안 목록(출력 형식 정의·검증, 외부 콘텐츠 분리, 권한 통제, 사람 승인) | 요약 |
| [OWASP ASVS 5.0](https://github.com/OWASP/ASVS/tree/master/5.0/en) | CC BY-SA 4.0 | WS-INJ-001(1.2.4), WS-XML-001(1.5.1), WS-AUTH-001(8.2·8.3), WS-SEC-001(13.3), WS-SUP-001(15.2.4) 체크 항목 | 해당 요구사항의 뜻 | 요구사항 번호는 머리말 `maps`에 두고, 본문에는 뜻만 우리 말로 씀 |
| [trailofbits/skills](https://github.com/trailofbits/skills) `insecure-defaults` (Trail of Bits) | CC BY-SA 4.0 | 6개 이슈 `## 스킬이 잡는 방식` | 검사마다 "Report when / Skip when"을 짝으로 두는 구조 | "지적할 때 / 지적하지 않을 때"로 바꾸고 내용은 새로 씀 |
| [agamm/claude-code-owasp](https://github.com/agamm/claude-code-owasp) (agamm) | MIT | WS-INJ-001 검사 1·2 | 허용 목록을 거친 `ORDER BY` f-string은 지적하지 않는다는 평가용 미끼 아이디어, "확인 필요" 확신도 표시 | 아이디어만 빌림. 문장·코드는 새로 씀 |

## 아이디어만 참고한 자료 (문장·구조는 옮기지 않음)

| 자료 | 라이선스 | 쓴 곳 | 참고한 아이디어 |
|---|---|---|---|
| anthropics/claude-plugins-official `claude-security` | All rights reserved | WS-PI-001 "고칠 때" 마지막 줄, WS-SEC-001 "보고할 때 주의" | 검토 대상 저장소의 글은 지시가 아니라 데이터로 다룸, 보고서에서 비밀값 가리기. 문장은 우리 말로 새로 씀 |

## 우리가 직접 확인한 것

| 내용 | 방법 | 쓴 곳 |
|---|---|---|
| EUC-KR·CP949에는 앞 바이트 + 0x5C가 한 글자가 되는 조합이 없음(GBK 118, Big5 88, CP932 52, Shift_JIS 37, EUC-KR 0, CP949 0) | Python 표준 코덱으로 앞 바이트 0x81~0xFE 뒤에 0x5C를 붙여 디코딩, 2026-11-18 | WS-INJ-001 체크 항목 참고 |
| PyPI·npm·Packagist 레지스트리 API 응답 모양(없는 이름 404, 등록일 필드) | 각 레지스트리에 직접 요청, 2026-11-18 | WS-SUP-001 코드 |
