<!-- generated from white-security issues; do not edit -->
# WS-OAUTH-001 코드 예시 — FastAPI

✗ 취약 코드 (vulnerable example — do not use)

```python
@app.post("/auth/google/app")                  # 모바일 앱이 받은 토큰으로 로그인
def app_login(body: TokenIn, db: Session = Depends(get_db)):
    info = httpx.get("https://openidconnect.googleapis.com/v1/userinfo",
                     headers={"Authorization": f"Bearer {body.access_token}"}).json()
    # 다른 앱에서 발급된 토큰이어도 사용자 정보는 나온다 → 그 사람으로 로그인(토큰 바꿔치기)
    user = db.query(User).filter_by(email=info["email"]).first()
    return issue_session(user)
```

✓ 안전 코드 (safe example)

```python
from google.oauth2 import id_token
from google.auth.transport import requests as google_requests

@app.post("/auth/google/app")
def app_login(body: IdTokenIn, db: Session = Depends(get_db)):
    try:  # 서명·만료·iss 확인, aud 가 우리 client_id 인지 확인
        claims = id_token.verify_oauth2_token(body.id_token, google_requests.Request(), GOOGLE_CLIENT_ID)
    except ValueError:
        raise HTTPException(401)
    if claims.get("nonce") != pop_login_nonce(body.login_id):  # 로그인 시작 때 발급한 1회용 값
        raise HTTPException(401)
    account = db.query(SocialAccount).filter_by(provider="google", sub=claims["sub"]).first()
    if account is None:
        raise HTTPException(404, "가입이 필요합니다")
    return issue_session(account.user)
```
