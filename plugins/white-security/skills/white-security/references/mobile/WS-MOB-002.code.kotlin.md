<!-- generated from white-security issues; do not edit -->
# WS-MOB-002 코드 예시 — Kotlin

✗ 취약 코드 (vulnerable example — do not use)

```kotlin
// 앱 안에 박힌 키로 직접 암호화 — APK 를 풀면 키도 함께 나옴
private const val APP_KEY = "0123456789abcdef"

fun saveToken(ctx: Context, token: String) {
    val cipher = Cipher.getInstance("AES/ECB/PKCS5Padding")            // ECB, 고정 키
    cipher.init(Cipher.ENCRYPT_MODE, SecretKeySpec(APP_KEY.toByteArray(), "AES"))
    val enc = Base64.encodeToString(cipher.doFinal(token.toByteArray()), Base64.NO_WRAP)
    ctx.getSharedPreferences("auth", Context.MODE_PRIVATE).edit().putString("token", enc).apply()
}
```

✓ 안전 코드 (safe example)

```kotlin
private const val ALIAS = "auth_token_key"

// 키는 Android Keystore 가 만들고 보관(앱 밖으로 꺼낼 수 없음)
private fun key(): SecretKey {
    val ks = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
    (ks.getKey(ALIAS, null) as? SecretKey)?.let { return it }
    val gen = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore")
    gen.init(KeyGenParameterSpec.Builder(ALIAS, KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
        .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
        .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
        .setKeySize(256)
        .build())
    return gen.generateKey()
}

fun saveToken(ctx: Context, token: String) {
    val c = Cipher.getInstance("AES/GCM/NoPadding").apply { init(Cipher.ENCRYPT_MODE, key()) } // IV 는 매번 새로(12바이트)
    val blob = c.iv + c.doFinal(token.toByteArray())
    ctx.getSharedPreferences("auth", Context.MODE_PRIVATE).edit()
        .putString("token", Base64.encodeToString(blob, Base64.NO_WRAP)).apply()
}

fun loadToken(ctx: Context): String? {
    val s = ctx.getSharedPreferences("auth", Context.MODE_PRIVATE).getString("token", null) ?: return null
    val blob = Base64.decode(s, Base64.NO_WRAP)
    val c = Cipher.getInstance("AES/GCM/NoPadding")
        .apply { init(Cipher.DECRYPT_MODE, key(), GCMParameterSpec(128, blob, 0, 12)) }
    return String(c.doFinal(blob, 12, blob.size - 12))                  // 변조되면 AEADBadTagException
}
```
