# Production Readiness Issues

## Current State: ⚠️ NOT PRODUCTION READY

This code needs the following fixes before going live:

---

## 1. ❌ HARDCODED SECRETS

**Files affected:**
- `process.php` (line 5)
- `gateway.php` (lines 27, 37)
- `hosted_callback.php` (line 6)

**Issue:** API keys and secrets are hardcoded in source files.

**Fix:** Use environment variables:

```php
// Add to config.php or use getenv()
$merchantID = getenv('TAKEPAYMENTS_MERCHANT_ID') ?: '278346';
$merchantSecret = getenv('TAKEPAYMENTS_SECRET') ?: '';
$gatewayURL = getenv('TAKEPAYMENTS_GATEWAY_URL') ?: 'https://gw1.tponlinepayments.com/direct/';
```

---

## 2. ⚠️ WEAK COOKIE SECURITY

**File:** `process.php` (line 92)

**Current:**
```php
setcookie('threeDSRef', $res['threeDSRef'], time()+500);
```

**Issues:**
- No `HttpOnly` flag (XSS vulnerable)
- No `Secure` flag (sent over HTTP)
- No `SameSite` protection
- Very short expiry (500 seconds)

**Fix:**
```php
setcookie('threeDSRef', $res['threeDSRef'], [
    'expires' => time() + 600,
    'path' => '/',
    'domain' => '',
    'secure' => true,        // HTTPS only
    'httponly' => true,      // Prevent JavaScript access
    'samesite' => 'None'   // CSRF protection
]);
```

---

## 3. 🐛 DEBUG OUTPUT EXPOSED

**File:** `process.php` (lines 83-87)

**Issue:** Gateway requests/responses are printed to users:

```php
$html .= '<h2> Gateway request</h2>';
$html .= '<pre>' . print_r(isset($threeDSRequest) ? $threeDSRequest : $req, true) .'</pre>';
$html .= '<h2> Gateway response</h2>';
$html .= '<pre>' . print_r($res, true) .'</pre>';
```

**Risk:** Sensitive data exposed to users (card numbers, tokens, etc.)

**Fix:** Remove or wrap in debug flag:
```php
if (getenv('DEBUG_MODE') !== '1') {
    // Don't show debug info
}
```

---

## 4. ⚠️ NO SESSION MANAGEMENT

**File:** `process.php`

**Issue:** Uses cookies instead of secure PHP sessions for `threeDSRef`.

**Better approach:**
```php
session_start();
$_SESSION['threeDSRef'] = $res['threeDSRef'];
```

---

## 5. 🧪 TEST DATA IN CODE

**File:** `process.php` (line 44)

**Issue:** Hardcoded test phone number:
```php
"customerPhone" => format_phone_number('07900000000', 826),
```

**Fix:** Should come from user input or be configurable.

---

## 6. ❌ NO ERROR LOGGING

**File:** `process.php`

**Issue:** No proper logging of errors for production debugging.

**Fix:** Add error logging:
```php
error_log('Payment failed: ' . $res['responseMessage']);
```

---

## 7. ⚠️ INSECURE PHONE VALIDATION

**File:** `phone_country_codes.php`

**Issue:** No validation of phone numbers.

**Fix:** Add validation.

---

## 8. 🔒 MISSING CSRF PROTECTION

**Issue:** No CSRF tokens on forms.

**Fix:** Add CSRF token generation and validation.

---

## 9. ⚠️ WEAK TRANSACTION EXPIRY

**File:** `process.php` (line 92)

**Issue:** Cookie expires in 500 seconds (8 minutes).

**Fix:** Align with 3DS timeout requirements.

---

## 10. 📝 NO INPUT VALIDATION

**Files:** `index.php`, `process.php`

**Issue:** Limited client-side validation, no server-side sanitization.

**Fix:** Add proper validation and sanitization.

---

## Recommendations

### Immediate Actions Before Production:

1. ✅ Move all secrets to environment variables
2. ✅ Remove all debug output
3. ✅ Implement proper session management
4. ✅ Add secure cookie settings
5. ✅ Remove hardcoded test data
6. ✅ Add error logging
7. ✅ Implement CSRF protection
8. ✅ Add input validation/sanitization
9. ✅ Use HTTPS only in production
10. ✅ Add rate limiting on payment attempts

### Testing Checklist:

- [ ] Test with real payment gateway credentials
- [ ] Test 3DS authentication flow
- [ ] Test error handling
- [ ] Test on HTTPS
- [ ] Test on mobile devices
- [ ] Security audit
- [ ] PCI DSS compliance review

---

**Status:** 🔴 NOT PRODUCTION READY - Requires fixes above

