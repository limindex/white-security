<!-- generated from white-security issues; do not edit -->
# WS-PRV-006 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
# 운영 DB 에서: python manage.py dumpdata accounts.User --output tests/fixtures/users.json
class OrderTest(TestCase):
    fixtures = ["users.json"]          # 실제 회원 이름·전화번호·주소가 저장소에 커밋됨
```

✓ 안전 코드 (safe example)

```python
# tests/factories.py — factory_boy + Faker 로 만든 가짜 회원
import factory
from accounts.models import User

class UserFactory(factory.django.DjangoModelFactory):
    class Meta:
        model = User

    username = factory.Sequence(lambda n: f"user{n}")
    name = factory.Faker("name", locale="ko_KR")
    email = factory.Sequence(lambda n: f"user{n}@example.com")   # 예시용으로 예약된 도메인
    phone = factory.Sequence(lambda n: f"010-0000-{n:04d}")

class OrderTest(TestCase):
    def setUp(self):
        self.user = UserFactory()
```
