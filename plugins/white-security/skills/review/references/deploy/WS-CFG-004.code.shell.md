<!-- generated from white-security issues; do not edit -->
# WS-CFG-004 코드 예시 — Shell

✗ 취약 코드 (vulnerable example — do not use)

```sh
# 업로드 버킷의 공개 차단을 끄고 버킷 전체를 누구나 읽게 함
aws s3api put-public-access-block --bucket my-uploads \
  --public-access-block-configuration BlockPublicAcls=false,IgnorePublicAcls=false,BlockPublicPolicy=false,RestrictPublicBuckets=false
aws s3api put-bucket-policy --bucket my-uploads --policy \
  '{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Principal":"*","Action":"s3:GetObject","Resource":"arn:aws:s3:::my-uploads/*"}]}'
```

✓ 안전 코드 (safe example)

```sh
# 버킷과 계정 단위 공개 차단 4가지를 모두 켠다
aws s3api put-public-access-block --bucket my-uploads \
  --public-access-block-configuration BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true
aws s3control put-public-access-block --account-id 111122223333 \
  --public-access-block-configuration BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true
aws s3api get-public-access-block --bucket my-uploads   # 네 값이 모두 true 인지 확인

# 파일은 짧게 유효한 서명 URL 로만(앱 서버는 SDK 의 presign 함수 사용)
aws s3 presign s3://my-uploads/receipts/123.pdf --expires-in 300
# 꼭 공개해야 하는 정적 파일은 별도 버킷 + CloudFront(OAC)로만 내보낸다
```
