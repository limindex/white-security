<!-- generated from white-security issues; do not edit -->
# WS-PRV-005 코드 예시 — Laravel

✗ 취약 코드 (vulnerable example — do not use)

```php
// 판매자 주문 목록: 구매자 모델 전체(전화번호·주소·이메일)가 JSON 으로
public function index(Request $request)
{
    return Order::with('buyer')->where('seller_id', $request->user()->id)->paginate(20);
}
```

✓ 안전 코드 (safe example)

```php
class SellerOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->buyer->name;
        return [
            'id'          => $this->id,
            'status'      => $this->status,
            'buyer_name'  => Str::mask($name, '*', 1, max(1, mb_strlen($name) - 2)),   // 홍*동
            'buyer_phone' => self::maskPhone($this->buyer->phone),                     // 010-****-1234
        ];
    }

    private static function maskPhone(?string $p): ?string
    {
        return $p && preg_match('/^(\d{3})-?\d{3,4}-?(\d{4})$/', $p, $m) ? "{$m[1]}-****-{$m[2]}" : null; // 형식이 다르면 빼기
    }
}

public function index(Request $request)
{
    return SellerOrderResource::collection(
        Order::with('buyer:id,name,phone')->where('seller_id', $request->user()->id)->paginate(20)
    );
}
// 배송에 실제 연락처가 필요하면 배송 처리 화면에서만, 그 주문 1건 단위로 보여 주고 접속기록(WS-PRV-003)을 남김
```
