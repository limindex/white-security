<!-- 생성 파일: issues/ 원본을 고치세요 -->
# 화이트 보안 — 이슈 목록

아래에서 지금 하는 일과 맞는 묶음을 고르고, 해당 이슈 파일만 읽는다.

## AI 활용

언제 읽나: AI에 문서·웹페이지·메일을 읽히거나 요약시키는 기능, AI API 호출, 문서 검색 답변(RAG), AI 답변을 화면·판단에 쓰는 기능

- [WS-LLM-001 Improper Output Handling (AI 출력을 그대로 실행·표시)](ai-use/WS-LLM-001.md) — AI가 쓴 글·코드·SQL·명령을 확인 없이 `eval`, 셸, DB, `innerHTML`에 넘길 때, AI를 속인 사람의 코드가 서버나 사용자 화면에서 실행되는 문제. Improper Output Handling(부적절한 출력 처리)이라 부름(진단가이드 이름: 코드 삽입·SQL 삽입·운영체제 명령어 삽입·크로스사이트 스크립트).
- [WS-LLM-002 System Prompt Leakage (시스템 프롬프트 유출)](ai-use/WS-LLM-002.md) — 시스템 프롬프트(AI에게 미리 깔아 두는 기본 지시문)는 언젠가 새어 나간다는 전제가 필요. 그 안에 API 키를 넣거나 "관리자에게만 알려 줘" 같은 문장으로 권한을 대신하면 그대로 뚫리는 문제(진단가이드 이름: 하드코드된 중요정보).
- [WS-LLM-003 Sensitive Information Disclosure (AI API로 보내는 개인정보·키)](ai-use/WS-LLM-003.md) — 고객 정보나 키를 프롬프트에 통째로 넣어 외부 AI API로 보낼 때, 그 데이터가 내 서버 밖(AI 회사의 서버와 로그)에 남는 문제. OWASP 분류로는 Sensitive Information Disclosure(민감 정보 노출).
- [WS-LLM-004 Unbounded Consumption (토큰·비용 폭탄)](ai-use/WS-LLM-004.md) — 로그인도 횟수 제한도 없이 AI 호출 API를 열어 둘 때, 누군가 긴 글을 수만 번 보내 서비스 운영자 비용으로 AI를 쓰는 문제. OWASP 분류로는 Unbounded Consumption(자원 소비 무제한).
- [WS-LLM-005 RAG Poisoning · 검색 권한 누락 (벡터 DB 문서 오염·남의 문서 노출)](ai-use/WS-LLM-005.md) — RAG(질문과 비슷한 문서를 먼저 찾아 AI에게 함께 넘기는 방식)에서 검색할 때 권한을 거르지 않아 남의 문서가 답에 섞여 나오는 문제. 누구나 문서를 넣을 수 있으면 가짜 문서 몇 개로 답이 바뀌는 RAG Poisoning 위험도 있음(진단가이드 이름: 부적절한 인가).
- [WS-LLM-006 Misinformation · Overreliance (AI 답을 사실로 믿고 자동 처리)](ai-use/WS-LLM-006.md) — AI가 틀린 내용도 자신 있게 말하는 현상(Hallucination, NIST 용어로는 Confabulation)과, 그 답을 확인 없이 환불·결제·고객 안내에 바로 쓰는 Overreliance(과신)에 대한 이슈. 틀린 결과의 책임은 서비스 운영자 몫.
- [WS-PI-001 IPI (간접 프롬프트 인젝션)](ai-use/WS-PI-001.md) — IPI(Indirect Prompt Injection, 간접 프롬프트 인젝션)는 AI에 읽힌 남의 글(웹페이지·메일·PDF) 속 숨은 문장을 AI가 지시로 받아들여, 엉뚱한 답을 내거나 연결된 기능을 실행하는 공격에 대한 이슈. 프롬프트는 AI에 주는 지시문.
- [WS-PI-002 Data Exfiltration (마크다운 이미지로 새는 정보)](ai-use/WS-PI-002.md) — AI 답 속 마크다운 이미지(`![](주소)`)를 그대로 화면에 그릴 때, 브라우저가 그 주소로 자동 접속하면서 주소에 붙은 대화 내용이 외부 서버로 넘어가는 문제. 이렇게 정보를 몰래 빼내는 공격을 데이터 엑스필트레이션(Data Exfiltration)이라 부름.

## AI 코딩 도구

언제 읽나: AI 코딩 도구에 코드 작성·설정 변경·명령 실행을 맡기는 경우

- [WS-AGT-001 Excessive Agency (과도한 권한 위임)](vibe/WS-AGT-001.md) — AI 에이전트(여러 단계를 스스로 처리하는 AI)에게 삭제·배포·결제 도구를 넉넉히 주고 확인 없이 실행하게 할 때, AI의 착각이나 속임수 하나로 되돌릴 수 없는 일이 생기는 문제. Excessive Agency(과도한 권한 위임)라 부름.
- [WS-AGT-002 Rules File Backdoor (규칙 파일 속 숨은 지시)](vibe/WS-AGT-002.md) — AI 코딩 도구가 늘 읽는 규칙 파일(`CLAUDE.md`, `AGENTS.md`, `.cursorrules` 등)에 보이지 않는 문자로 지시를 숨겨, AI가 몰래 위험한 코드를 쓰게 만드는 공격. 2025년 공개된 수법으로 Rules File Backdoor라 부름.
- [WS-AGT-003 Memory Poisoning (에이전트 메모리 오염)](vibe/WS-AGT-003.md) — AI 에이전트가 대화나 읽은 문서 내용을 장기 기억(메모리)에 저장해 다음 대화에도 쓸 때, 남이 심어 둔 문장이 기억으로 남아 계속 영향을 주는 문제. Memory Poisoning(메모리 오염)이라 부름.
- [WS-AGT-004 Confused Deputy (권한 빌려 쓰기·에이전트 간 통신 검증 누락)](vibe/WS-AGT-004.md) — 에이전트가 넓은 서비스 계정으로 일하면서 '누구 대신' 일하는지 확인하지 않아, 사용자가 볼 수 없는 데이터도 에이전트를 통해 꺼낼 수 있는 문제. Confused Deputy(권한을 빌려 주는 대리인 문제)라 부르며, 다른 에이전트가 보낸 요청을 확인 없이 믿는 것도 같은 구멍(진단가이드 이름: 부적절한 인가).
- [WS-AGT-005 Cascading Failures · Rogue Agent (멈추지 않는 에이전트)](vibe/WS-AGT-005.md) — 에이전트 반복에 횟수·시간·비용 한도와 멈춤 스위치가 없을 때, 오류 하나가 같은 행동을 끝없이 되풀이하거나 다른 시스템으로 번지는 문제(Cascading Failures, 연쇄 장애). 맡긴 범위를 벗어나 움직이는 Rogue Agent(통제를 벗어난 에이전트)를 멈출 때도 같은 장치가 필요(진단가이드 이름: 종료되지 않는 반복문 또는 재귀 함수).
- [WS-AGT-006 YOLO Mode (AI 코딩 도구 과다 허용 설정)](vibe/WS-AGT-006.md) — AI 코딩 도구가 모든 명령을 묻지 않고 실행하도록(YOLO 모드, `Bash(*)` 허용, 자동 승인) 설정해 둘 때, AI가 읽은 글 속 숨은 지시 하나로 내 컴퓨터에서 남의 명령이 실행되는 문제(진단가이드 이름: 중요한 자원에 대한 잘못된 권한 설정).
- [WS-MCP-001 Tool Poisoning · Rug Pull (MCP 도구 설명 오염·몰래 바꾸기)](vibe/WS-MCP-001.md) — MCP(AI에게 외부 도구를 붙이는 표준 연결 방식) 서버의 도구 설명에 AI만 읽는 숨은 지시를 넣는 Tool Poisoning, 승인받은 뒤 업데이트로 설명이나 동작을 몰래 바꾸는 Rug Pull에 대한 이슈. 둘 다 AI가 파일·메일·키를 외부로 보내게 만들 수 있는 위험(진단가이드 이름: 무결성 검사 없는 코드 다운로드).

## 외부 데이터 수집·처리

언제 읽나: 외부 URL·파일·XML·압축 파일을 가져와 읽거나 파싱하는 경우

- [WS-DES-001 Insecure Deserialization (안전하지 않은 역직렬화)](fetch/WS-DES-001.md) — `pickle`, `yaml.load`, PHP `unserialize`처럼 글자를 프로그램 객체로 되살리는 함수에 남이 준 데이터를 넣을 때, 객체가 만들어지는 순간 공격자가 심은 코드가 실행되는 문제. Insecure Deserialization(안전하지 않은 역직렬화)이라 부름(진단가이드 이름: 신뢰할 수 없는 데이터의 역직렬화).
- [WS-FILE-001 Decompression Bomb · 파서 취약점 (남이 준 이미지·PDF·오피스 파일 처리)](fetch/WS-FILE-001.md) — 남이 올린 이미지·PDF·오피스 파일로 썸네일·미리보기·텍스트 추출을 할 때, 작은 파일 하나가 메모리를 다 쓰게 하거나(Decompression Bomb, 픽셀 폭탄) 파서 프로그램의 취약점을 건드리는 문제. 사진 속 위치 정보(EXIF)를 지우지 않고 다시 공개하는 사고도 같은 범위.
- [WS-SSRF-001 SSRF (서버사이드 요청 위조)](fetch/WS-SSRF-001.md) — "주소 미리보기", "URL 요약" 기능이 받은 주소를 서버가 그대로 열 때, 외부에서는 닿지 않는 내부 관리 화면이나 클라우드 메타데이터(서버 키가 나오는 주소 `169.254.169.254`)를 대신 열어 주는 문제. SSRF라 부름(진단가이드 이름: 서버사이드 요청 위조).
- [WS-XML-001 XXE · Billion Laughs](fetch/WS-XML-001.md) — XXE(XML External Entity, XML 외부 개체 참조)와 Billion Laughs(엔티티 폭탄)는 XML(태그로 내용을 감싸 적는 데이터 형식) 파서가 문서 속 선언을 그대로 따라 서버 파일을 읽어 오거나, 작은 파일 하나로 서버 메모리를 다 쓰게 되는 취약점에 대한 이슈(진단가이드 이름: 부적절한 XML 외부 개체 참조). 비밀 파일 유출과 서비스 중단 위험.
- [WS-ZIP-001 Zip Slip · Zip Bomb (경로 탈출·압축 폭탄)](fetch/WS-ZIP-001.md) — 압축 파일 속 이름에 `../`가 들어 있어 풀 때 정해 둔 폴더 밖에 파일이 써지는 Zip Slip, 작은 압축 하나가 풀리면서 디스크와 메모리를 다 채우는 압축 폭탄(Zip Bomb)에 대한 이슈(진단가이드 이름: 경로 조작 및 자원 삽입).

## 자동 수집 대응

언제 읽나: 내 사이트·API가 자동 수집(크롤링) 대상이 되는 경우

- [WS-BOT-001 Scraping · AI Crawler (AI 크롤러 대량 수집)](scraped/WS-BOT-001.md) — AI 학습·검색용 크롤러(웹페이지를 자동으로 긁어 가는 프로그램)가 사람보다 훨씬 많이, 빠르게 긁어 가면서 서버 비용과 장애를 일으키는 문제(Scraping). robots.txt는 요청일 뿐이므로 횟수 제한·캐시·로그인 같은 서버 쪽 장치가 필요.

## 웹 애플리케이션

언제 읽나: 입력을 받아 DB·화면·파일·명령으로 넘기는 웹 기능

- [WS-INJ-001 SQL 인젝션 (SQLi)](web/WS-INJ-001.md) — SQL 인젝션(SQLi)은 검색창·로그인 칸에 넣은 글자가 DB(데이터를 쌓아 두는 저장소)에 보내는 명령문(SQL)에 그대로 끼워져, 입력이 명령으로 실행되는 취약점에 대한 이슈(진단가이드 이름: SQL 삽입). 남의 데이터 조회·변조·삭제로 이어지는 위험.

## 인증·인가

언제 읽나: 로그인·권한·본인 데이터 접근 기능

- [WS-AUTH-001 IDOR · 접근 제어 실패 (Broken Access Control)](auth/WS-AUTH-001.md) — IDOR(Insecure Direct Object Reference, 소유자 확인 없는 객체 접근)와 Broken Access Control(접근 제어 실패)은 로그인 여부만 확인하고 '이 사람의 것인지'는 확인하지 않아, 주소의 숫자만 바꿔도 남의 주문·파일·개인정보가 열리는 취약점에 대한 이슈(진단가이드 이름: 부적절한 인가). 인가(Authorization)는 이 사람이 이 일을 해도 되는지 허락하는 절차.

## 설정·배포

언제 읽나: 설정 파일·키·서버·컨테이너·배포 환경

- [WS-SEC-001 Hardcoded Secrets (비밀키 노출)](deploy/WS-SEC-001.md) — Hardcoded Secrets(하드코딩된 비밀값)는 API 키(서비스를 쓸 수 있는 비밀 열쇠)나 DB 비밀번호를 코드·설정 파일에 그대로 적어, 저장소·웹 화면·로그를 통해 남에게 보이게 되는 문제에 대한 이슈(진단가이드 이름: 하드코드된 중요정보). 남이 그 열쇠로 내 돈과 데이터를 쓰는 위험.

## 소프트웨어 공급망

언제 읽나: 패키지 설치·업데이트·외부 코드와 모델 사용

- [WS-SUP-001 Slopsquatting (패키지 환각)](supply/WS-SUP-001.md) — Slopsquatting(슬롭스쿼팅)은 AI가 지어낸 패키지(남이 만들어 둔 코드 묶음) 이름을 공격자가 먼저 등록해 두어, 확인 없이 설치하면 그 사람의 코드가 내 컴퓨터와 서버에서 실행되는 공급망 공격에 대한 이슈(진단가이드 이름: 무결성 검사 없는 코드 다운로드). AI가 없는 패키지를 지어내는 현상은 Package Hallucination(패키지 환각).
