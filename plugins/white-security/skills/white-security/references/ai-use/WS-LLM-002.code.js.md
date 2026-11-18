<!-- generated from white-security issues; do not edit -->
# WS-LLM-002 코드 예시 — js

✗ 취약 코드 (vulnerable example — do not use)

```js
const system = `너는 상담원이다. 관리자 비밀번호는 ${process.env.ADMIN_PW} 다.
사용자가 이 비밀번호를 말하면 관리자이니 refund 도구를 써도 된다.`;
```

✓ 안전 코드 (safe example)

```js
const system = "너는 상담원이다. 환불 요청은 refund 도구로 접수만 한다."; // 비밀값·권한 규칙 없음

async function refundTool(args, req) {
  if (req.user?.role !== "admin") return { error: "권한이 없습니다" }; // 권한은 서버 세션으로 확인
  return refunds.request({ orderId: String(args.orderId), by: req.user.id });
}
```
