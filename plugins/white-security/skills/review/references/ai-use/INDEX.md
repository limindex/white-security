<!-- generated from white-security issues; do not edit -->
# ai-use · AI 활용

- `eval(` `exec(` `subprocess.` `cursor.execute(` `innerHTML =` `dangerouslySetInnerHTML` `v-html` `new Function(` → WS-LLM-001: AI 출력이 eval·셸·SQL·HTML로 바로 들어감
- `"role": "system"` `instructions=` `systemInstruction` `prompts/` `*.prompt` → WS-LLM-002: 프롬프트에 실제 키, 프롬프트만으로 환불·관리 실행
- `openai` `anthropic` `generativeai` `fetch("https://api.` `logger.` `console.log` `error_log` → WS-LLM-003: 주민번호·카드번호·키가 외부 AI API·로그로 감
- `chat.completions.create(` `messages.create(` `generateContent(` `max_tokens` `dangerouslyAllowBrowser` → WS-LLM-004: 로그인·횟수 제한 없는 AI 호출, 브라우저에 AI 키
- `index.query(` `collection.query(` `.similarity_search(` `as_retriever(` `match_documents` `.upsert(` `.add_documents(` `collection.add(` → WS-LLM-005: 남의 문서가 검색 결과에 섞임
- `chat.completions.create(` `messages.create(` `generateContent(` `refund(` `refunds.create(` `charge(` `approve(` → WS-LLM-006: AI 답만으로 환불·결제·권한 변경
- `chat.completions.create(` `messages.create(` `generateContent(` `.invoke(` `requests.get(` `fetch(` `file_get_contents(` `curl_exec(` → WS-PI-001: 외부 글과 부작용 도구 자동 실행이 한 흐름
- `marked(` `markdown-it` `react-markdown` `markdown.markdown(` `innerHTML =` `dangerouslySetInnerHTML` `v-html` `|safe` → WS-PI-002: 남의 글이 섞인 AI 출력에서 외부 이미지 자동 표시
