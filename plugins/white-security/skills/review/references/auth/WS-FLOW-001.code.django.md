<!-- generated from white-security issues; do not edit -->
# WS-FLOW-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
@login_required
@require_POST
def confirm_booking(request, slot_id):
    # 좌석 홀드·결제 단계를 건너뛰고 바로 불러도 확정된다
    Booking.objects.create(user=request.user, slot_id=slot_id, status="confirmed")
    return redirect("my-bookings")
```

✓ 안전 코드 (safe example)

```python
from django_ratelimit.decorators import ratelimit

@login_required
@require_POST
@ratelimit(key="user", rate="10/m", block=True)       # 계정당 분당 10회
def confirm_booking(request, hold_id):
    with transaction.atomic():
        hold = (Hold.objects.select_for_update()
                .filter(id=hold_id, user=request.user, status="paid", expires_at__gt=timezone.now())
                .first())
        if hold is None:                                # 앞 단계(홀드·결제)를 마친 것만
            return HttpResponseBadRequest("앞 단계를 먼저 완료해 주세요")
        Booking.objects.create(user=request.user, slot_id=hold.slot_id, status="confirmed")  # slot_id unique
        hold.status = "used"
        hold.save(update_fields=["status"])
    return redirect("my-bookings")
```
