<!-- generated from white-security issues; do not edit -->
# WS-AUTH-006 코드 예시 — Django

✗ 취약 코드 (vulnerable example — do not use)

```python
class UserSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = "__all__"        # is_staff·is_superuser·points·password 까지 읽고 쓸 수 있다

class MeView(generics.RetrieveUpdateAPIView):
    serializer_class = UserSerializer
    permission_classes = [IsAuthenticated]

    def get_object(self):
        return self.request.user
```

✓ 안전 코드 (safe example)

```python
class ProfileSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = ["id", "nickname", "marketing_opt_in", "points"]  # 받을·보일 필드만
        read_only_fields = ["id", "points"]                         # 보여 주기만, 수정 불가

class MeView(generics.RetrieveUpdateAPIView):
    serializer_class = ProfileSerializer
    permission_classes = [IsAuthenticated]

    def get_object(self):
        return self.request.user
```
