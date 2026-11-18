<!-- generated from white-security issues; do not edit -->
# WS-RACE-001 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
@require_POST
@login_required
def withdraw(request):
    amount = int(request.POST["amount"])
    acct = Account.objects.get(user=request.user)
    if acct.balance < amount:                # 확인
        return HttpResponse(status=400)
    acct.balance -= amount                   # 파이썬에서 계산해 저장: 동시 요청이 같은 잔액을 읽는다
    acct.save()
    return HttpResponse("ok")
```

✓ 안전 코드 (safe example)

```python
from django.db import transaction

@require_POST
@login_required
def withdraw(request):
    amount = int(request.POST["amount"])
    if not 1 <= amount <= 1_000_000:
        return HttpResponse(status=400)
    with transaction.atomic():
        acct = Account.objects.select_for_update().get(user=request.user)  # 행 잠금: 다른 요청은 기다린다
        if acct.balance < amount:
            return HttpResponse(status=400)
        acct.balance -= amount
        acct.save(update_fields=["balance"])
    return HttpResponse("ok")
```
