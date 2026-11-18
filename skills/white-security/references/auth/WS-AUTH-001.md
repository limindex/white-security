<!-- 생성 파일: issues/ 원본을 고치세요 -->
# WS-AUTH-001 IDOR · 접근 제어 실패 (Broken Access Control)

위험 · OWASP A01 · API API1 · API API3 · API API5 · ASVS V8.2 · ASVS V8.3 · CWE-862 · CWE-639 · CWE-863 · CWE-306 · 진단가이드 보안기능-2 · 진단가이드 보안기능-1 · 진단가이드 입력데이터 검증 및 표현-15 · 기반시설 IN · 기반시설 IA · 기반시설 CC · 기반시설 PV

## 점검 항목

- 객체(주문·파일·게시글·회원정보)를 읽고·바꾸고·지우는 모든 요청에서 서버가 현재 사용자와 객체의 소유·권한 관계를 확인해야 하고, 조회 쿼리에 소유자 조건을 함께 넣는 방식을 기본으로 해야 한다. 목록·검색·내려받기·엑셀 내보내기에도 같은 범위 제한이 필요하고, UUID처럼 추측하기 어려운 ID도 권한 확인을 대신하지 않음. 남의 객체에는 403 대신 404로 답해 존재 여부를 덜 드러내는 방식 권장.
- 현재 사용자와 역할은 요청 값(`user_id` 파라미터, 본문, 평문 쿠키, 숨은 입력란)이 아니라 서버 세션이나 검증된 토큰에서 꺼내야 한다.
- 관리자·운영 기능은 서버에서 역할을 확인해야 한다(메뉴 숨김이나 프런트엔드 라우터 가드만으로는 불가). 결제 완료 처리, 관리자 API, 외부 서비스 웹훅이 로그인이나 서명 확인 없이 열려 있지 않아야 한다.
- 결제 금액·단가·할인·포인트는 서버의 주문·상품 데이터로 다시 계산해야 하고, 결제 뒤에는 결제대행사 조회 결과의 금액과 주문 금액을 맞춰 봐야 한다.
- 수정 API는 요청 본문을 통째로 모델에 넣지 않고 바꿔도 되는 필드만 골라 넣어야 한다(`role`, `owner_id`, `is_admin` 같은 필드 보호).

## 탐지 규칙

**찾는 곳**: 경로·쿼리·본문의 ID로 DB를 조회하는 라우트(`get(id)`, `findUnique`, `findById`, `find($id)`, `WHERE id = ?`), 관리자 경로(`/admin`, `/manage`), 역할 판단(`role`, `is_admin`, `level`, `grade`), 금액 처리(`price`, `amount`, `discount`), 모델 갱신(`update(`, `fill(`, `Object.assign(`).

**검사 1. 소유자 확인 없는 객체 접근**
- 지적할 때: 요청에서 온 ID로 조회한 객체를 소유자·권한 비교 없이 응답하거나 수정·삭제하는 경우.
- 지적하지 않을 때: 조회 조건에 현재 사용자 조건이 있거나 조회 뒤 소유자 비교가 있는 경우. 누구나 봐도 되는 공개 데이터인 경우. 미들웨어·정책 클래스·ORM 전역 스코프·DB 행 수준 보안(RLS)이 소유자 제한을 거는 것을 실제로 찾은 경우(못 찾으면 "확인 필요"와 볼 곳 표시).

**검사 2. 기능 수준 권한**
- 지적할 때: 관리자 동작 핸들러에 서버 쪽 역할 확인이 없는 경우(로그인 확인만 있는 경우 포함). 중요 기능에 로그인 확인조차 없는 경우.
- 지적하지 않을 때: 라우트 그룹 미들웨어가 역할을 확인하는 것을 실제 설정에서 찾은 경우.

**검사 3. 클라이언트가 정한 권한·금액·필드**
- 지적할 때: `role`, `is_admin`, `level`, `price`, `amount`, `discount`를 요청·쿠키에서 읽어 권한 판단이나 결제 금액에 그대로 쓰는 경우. 요청 본문 전체를 모델 갱신에 넘기는 경우(`**request.json`, `data: req.body`, `$user->fill($request->all())`).
- 지적하지 않을 때: 받은 값을 서버 계산 값과 비교만 하고 다르면 거절하는 경우. 허용 필드 목록(`$fillable`, DTO)으로 바꿀 수 있는 필드를 제한하는 경우.

**등급**
- 위험: 남의 개인정보·주문·파일을 읽거나 바꿀 수 있는 경우. 관리자 기능을 권한 없이 부를 수 있는 경우. 금액을 바꿔 결제할 수 있는 경우.
- 주의: 통째 갱신에 민감 필드가 들어갈 수 있는 경우. 목록 범위 제한을 끝까지 확인하지 못한 경우.
- 참고: 403과 404 응답 차이로 객체 존재가 드러나는 경우.

**보고 형식**: `[위험|주의|참고] <id> <파일:줄> — <이유> → <고칠 방법>`

예: `[위험] WS-AUTH-001 routes/orders.js:31 — 주문 ID만으로 조회하고 소유자를 확인하지 않음(IDOR) → 조회 조건에 userId: req.user.id 를 넣고 없으면 404`

**고칠 방법**: 조회 뒤 비교(빠뜨리기 쉬움)보다 조회 조건에 소유자를 넣는 방식을 먼저 골라야 하고, 계정 두 개로 읽기·수정·삭제·내려받기를 각각 확인하는 테스트를 함께 추가해야 한다.

## 코드 예시

### python

✗ 취약 코드

```python
# Flask + Flask-SQLAlchemy
@app.get("/api/orders/<int:order_id>")
@login_required
def get_order(order_id):
    order = db.session.get(Order, order_id)        # 로그인만 확인, 누구 주문인지는 안 본다
    if order is None:
        abort(404)
    return jsonify(order.to_dict())

@app.post("/api/orders/<int:order_id>/refund")
@login_required
def refund(order_id):
    if request.cookies.get("role") != "admin":     # 쿠키는 사용자가 바꿀 수 있다
        abort(403)
    issue_refund(order_id, amount=request.json["amount"])  # 금액도 요청 값 그대로
    return {"ok": True}
```

✓ 안전 코드

```python
from sqlalchemy import select

@app.get("/api/orders/<int:order_id>")
@login_required
def get_order(order_id):
    order = db.session.execute(
        select(Order).where(Order.id == order_id, Order.user_id == current_user.id)
    ).scalar_one_or_none()
    if order is None:
        abort(404)                                  # 남의 주문도 "없음"으로 답한다
    return jsonify(order.to_dict())

@app.post("/api/orders/<int:order_id>/refund")
@login_required
def refund(order_id):
    if current_user.role != "admin":               # 앱의 User 모델에 저장된 역할(DB 값)로 판단
        abort(403)
    order = db.get_or_404(Order, order_id)
    issue_refund(order.id, amount=order.paid_amount)  # 금액은 서버 기록으로
    return {"ok": True}
```

### js

✗ 취약 코드

```js
// Express + Prisma
app.get("/api/orders/:id", requireLogin, async (req, res) => {
  const order = await prisma.order.findUnique({ where: { id: req.params.id } });
  if (!order) return res.sendStatus(404);
  res.json(order); // 누구 주문인지 확인하지 않는다
});

app.patch("/api/me", requireLogin, async (req, res) => {
  const user = await prisma.user.update({
    where: { id: req.body.userId }, // 대상도 요청 값
    data: req.body,                 // role 같은 필드까지 바뀐다
  });
  res.json(user);
});
```

✓ 안전 코드

```js
app.get("/api/orders/:id", requireLogin, async (req, res) => {
  const order = await prisma.order.findFirst({
    where: { id: req.params.id, userId: req.user.id }, // 소유자 조건
  });
  if (!order) return res.sendStatus(404);
  res.json(order);
});

app.patch("/api/me", requireLogin, async (req, res) => {
  const data = {};                                      // 바꿔도 되는 필드만
  if (typeof req.body.nickname === "string") data.nickname = req.body.nickname.slice(0, 30);
  if (typeof req.body.marketingOptIn === "boolean") data.marketingOptIn = req.body.marketingOptIn;
  const user = await prisma.user.update({
    where: { id: req.user.id },                         // 대상은 세션 사용자
    data,
    select: { id: true, nickname: true, marketingOptIn: true },
  });
  res.json(user);
});
```

### php

✗ 취약 코드

```php
<?php
// download.php?id=1043
session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); exit; }

$st = $pdo->prepare('SELECT path, name FROM files WHERE id = ?');
$st->execute([$_GET['id'] ?? 0]);           // 로그인만 보고 누구 파일인지는 안 본다
$file = $st->fetch();
if (!$file) { http_response_code(404); exit; }
readfile($file['path']);

// pay.php: 금액을 브라우저가 보낸 값 그대로 쓴다
$amount = (int)($_POST['amount'] ?? 0);
```

✓ 안전 코드

```php
<?php
session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); exit; }

$st = $pdo->prepare('SELECT path, name FROM files WHERE id = ? AND owner_id = ?');
$st->execute([(int)($_GET['id'] ?? 0), $_SESSION['user_id']]);
$file = $st->fetch();
if (!$file) { http_response_code(404); exit; }   // 남의 파일도 "없음"
header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($file['name']));
readfile($file['path']);

// pay.php: 금액은 서버의 주문 기록으로 정한다
$st = $pdo->prepare('SELECT total_amount FROM orders WHERE id = ? AND user_id = ? AND status = ?');
$st->execute([(int)($_POST['order_id'] ?? 0), $_SESSION['user_id'], 'pending']);
$amount = $st->fetchColumn();
if ($amount === false) { http_response_code(404); exit; }
```
