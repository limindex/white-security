<!-- generated from white-security issues; do not edit -->
# ai-use · AI 활용

When: AI에 문서·웹페이지·메일을 읽히거나 요약시키는 기능, AI API 호출, 문서 검색 답변(RAG), AI 답변을 화면·판단에 쓰는 기능
Match code signals to the lines below, then read only the matching issue file (`<ID>.md` in this folder), at most 2.

- `reply` `completion` `response.text` `output` `content` `eval(` `exec(` `new Function(` `subprocess` `child_process.exec(` `shell_exec(` `query(` `execute(` `innerHTML` `dangerouslySetInnerHTML` `v-html` `echo` → WS-LLM-001 Improper Output Handling
- `system` `instructions=` `systemInstruction` `prompts/` `*.prompt` `*.txt` → WS-LLM-002 System Prompt Leakage
- `openai` `anthropic` `generativeai` `fetch("https://api.` `logger.` `console.log` `error_log` → WS-LLM-003 Sensitive Information Disclosure
- `max_tokens` `timeout` → WS-LLM-004 Unbounded Consumption
- `.query(` `.similarity_search(` `.search(` `as_retriever(` `match_documents` `.upsert(` `.add_documents(` `.add(` → WS-LLM-005 RAG Poisoning · 검색 권한 누락
- `refund(` `charge(` `approve(` `update(` `send(` → WS-LLM-006 Misinformation · Overreliance
- `messages` `prompt` `chat(` `completions` `generate(` `invoke(` `requests.get` `fetch(` `file_get_contents(` `curl_exec(` `tool_calls` `function_call` `toolCalls` `innerHTML` `dangerouslySetInnerHTML` `v-html` `marked(` `|safe` `echo` → WS-PI-001 IPI
- `marked(` `markdown-it` `react-markdown` `markdown.markdown(` `innerHTML` `dangerouslySetInnerHTML` `v-html` `|safe` → WS-PI-002 Data Exfiltration
