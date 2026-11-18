<!-- generated from white-security issues; do not edit -->
# WS-AUTH-002 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
router = APIRouter(prefix="/api")

@router.get("/users/{user_id}/export")
def export_user(user_id: int, db: Session = Depends(get_db)):  # 인증 의존성 없음
    return build_export(db, user_id)

@router.post("/internal/resend-sms")                           # 아무나 문자 발송을 부를 수 있음
def resend(phone: str):
    send_sms(phone, "인증번호를 다시 보냈습니다")
```

✓ 안전 코드 (safe example)

```python
# 라우터 전체를 기본 잠금, 공개 경로는 public 라우터에만
router = APIRouter(prefix="/api", dependencies=[Depends(get_current_user)])
public = APIRouter(prefix="/api/public")

@router.get("/me/export")
def export_me(user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    return build_export(db, user.id)        # 대상은 요청 값이 아니라 로그인한 사용자

# 내부 발송 기능은 웹 경로로 열지 않고, 서버 작업 큐(예: Celery 작업)에서만 부른다
```
