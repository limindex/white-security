<!-- generated from white-security issues; do not edit -->
# ai-use · AI 활용

When: AI에 문서·웹페이지·메일을 읽히거나 요약시키는 기능, AI API 호출, 문서 검색 답변(RAG), AI 답변을 화면·판단에 쓰는 기능
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.

- `eval(` `exec(` `subprocess.` `shell_exec(` `cursor.execute(` `innerHTML =` `dangerouslySetInnerHTML` `v-html` → WS-LLM-001 Improper Output Handling
- `"role": "system"` `instructions=` `systemInstruction` `prompts/` `*.prompt` `prompts/*.txt` → WS-LLM-002 System Prompt Leakage
- `openai` `anthropic` `generativeai` `fetch("https://api.` `logger.` `console.log` `error_log` → WS-LLM-003 Sensitive Information Disclosure
- `chat.completions.create(` `messages.create(` `generateContent(` `max_tokens` `dangerouslyAllowBrowser` → WS-LLM-004 Unbounded Consumption
- `index.query(` `collection.query(` `.similarity_search(` `as_retriever(` `match_documents` `.upsert(` `.add_documents(` `collection.add(` → WS-LLM-005 RAG Poisoning · 검색 권한 누락
- `chat.completions.create(` `messages.create(` `generateContent(` `refund(` `refunds.create(` `charge(` `approve(` → WS-LLM-006 Misinformation · Overreliance
- `chat.completions.create(` `messages.create(` `generateContent(` `.invoke(` `requests.get(` `fetch(` `file_get_contents(` `curl_exec(` → WS-PI-001 IPI (Indirect Prompt Injection)
- `marked(` `markdown-it` `react-markdown` `markdown.markdown(` `innerHTML =` `dangerouslySetInnerHTML` `v-html` `|safe` → WS-PI-002 Data Exfiltration via Markdown Image
