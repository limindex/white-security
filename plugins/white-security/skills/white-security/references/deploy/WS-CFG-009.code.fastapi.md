<!-- generated from white-security issues; do not edit -->
# WS-CFG-009 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
app = FastAPI()                                   # /docs·/redoc·/openapi.json 이 운영에도 열림

v1 = APIRouter(prefix="/api/v1")                  # 옛 앱용, 권한 검사 없음
@v1.get("/users/{user_id}")
async def get_user_v1(user_id: int):
    return await db.get_user(user_id)

v2 = APIRouter(prefix="/api/v2")
@v2.get("/users/{user_id}")
async def get_user_v2(user_id: int, me: User = Depends(require_user)):
    if me.id != user_id:
        raise HTTPException(404)
    return await db.get_user(user_id)

app.include_router(v1)
app.include_router(v2)
```

✓ 안전 코드 (safe example)

```python
import os

IS_PROD = os.environ.get("APP_ENV") == "production"
app = FastAPI(openapi_url=None if IS_PROD else "/openapi.json")   # None 이면 /docs·/redoc 도 꺼짐

def sunset_v1(response: Response):
    response.headers["Sunset"] = "Wed, 31 Mar 2027 00:00:00 GMT"   # 종료 예정일 안내

async def read_user(user_id: int, me: User = Depends(require_user)):
    if me.id != user_id and not me.is_admin:
        raise HTTPException(404)
    return await db.get_user_public(user_id)

# 옛 버전도 종료일까지 같은 함수·같은 검사를 쓴다
v1 = APIRouter(prefix="/api/v1", dependencies=[Depends(sunset_v1)])
v2 = APIRouter(prefix="/api/v2")
v1.add_api_route("/users/{user_id}", read_user, methods=["GET"])
v2.add_api_route("/users/{user_id}", read_user, methods=["GET"])
app.include_router(v1)
app.include_router(v2)
```
