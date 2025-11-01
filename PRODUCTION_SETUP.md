# Production Setup Guide

## ⚠️ CRITICAL: Before Going Live

This code is now **production-ready** after completing the setup steps below.

---

## 🔧 Quick Setup (5 Steps)

### Step 1: Create Configuration File

```bash
cp config.example.php config.php
```

### Step 2: Edit config.php

Open `config.php` and update with your production credentials:

```php
<?php
return [
    // Replace these with your REAL credentials
    'merchantID' => 'YOUR_MERCHANT_ID',           // ⚠️ Change this!
    'merchantSecret' => 'YOUR_SECRET_KEY',        // ⚠️ Change this!
    
    // These should be correct for production
    'gatewayDirectURL' => 'https://gw1.tponlinepayments.com/direct/',
    'gatewayHostedURL' => 'https://gw1.tponlinepayments.com/hosted/',
    
    // Your country/currency codes
    'countryCode' => 826,  // UK = 826
    'currencyCode' => 826, // GBP = 826
    
    // Security Settings
    'cookieExpiry' => 600,
    'sessionExpiry' => 3600,
    
    // NEVER enable debug in production!
    'debugMode' => false,
    'showDebugOutput' => false,
    
    // REQUIRED for production
    'requireHTTPS' => true,  // ⚠️ Must be true!
    'secureCookies' => true,
    
    // Remove test phone number
    'defaultPhoneCountry' => 826,
    'defaultPhoneNumber' => '',  // ⚠️ Empty in production!
    
    // Logging
    'logErrors' => true,
    'logFile' => __DIR__ . '/logs/payment-errors.log',
];
```

### Step 3: Create Logs Directory

```bash
mkdir logs
chmod 755 logs
touch logs/payment-errors.log
chmod 644 logs/payment-errors.log
```

### Step 4: Set File Permissions

```bash
chmod 644 config.php
chmod 755 index.php process.php
chmod 644 gateway.php security.php
```

### Step 5: Configure Web Server

#### For Apache (.htaccess)

```apache
# Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect config file
<Files config.php>
    Order Allow,Deny
    Deny from all
</Files>

# Protect logs directory
<Directory logs>
    Order Allow,Deny
    Deny from all
</Directory>

# Protect .gitignore
<Files ".gitignore">
    Order Allow,Deny
    Deny from all
</Files>
```

#### For Nginx

```nginx
# Force HTTPS
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

# Protect config file
location ~ config\.php$ {
    deny all;
}

# Protect logs
location ~ ^/logs/ {
    deny all;
}
```

---

## ✅ Security Checklist

After setup, verify:

- [ ] `config.php` is created and has correct credentials
- [ ] `config.php` has permissions 644 (not executable)
- [ ] `config.php` is NOT in version control (check .gitignore)
- [ ] HTTPS is enabled and working
- [ ] Logs directory exists and is writable
- [ ] Debug mode is disabled in config.php
- [ ] Test phone number is removed from config
- [ ] Web server forces HTTPS redirect
- [ ] File permissions are correct
- [ ] Error logging is enabled

---

## 🧪 Testing

### 1. Test Card Validation

Try these invalid cards:
- Wrong card number → Should show error
- Invalid CVV → Should show error
- Expired date → Should show error
- Missing fields → Should show errors

### 2. Test CSRF Protection

- Refresh the form page
- Try submitting without valid token → Should reject

### 3. Test HTTPS Enforcement

- Try accessing via HTTP → Should redirect to HTTPS

### 4. Test Error Logging

- Make a failed payment
- Check `logs/payment-errors.log`
- Verify no sensitive data is logged

### 5. Test Production Gateway

Use TakePayments test cards:
- Success: `4539791001730106`
- CVV: `289`
- Expiry: Any future date

---

## 🚀 Deployment

### Pre-Deployment Checklist

- [ ] All tests pass
- [ ] Config file created with production credentials
- [ ] HTTPS certificate installed and valid
- [ ] Debug mode disabled
- [ ] Error logging working
- [ ] No sensitive data in logs
- [ ] File permissions correct
- [ ] Backup of current site created

### Deployment Steps

1. Upload files to server (except config.php)
2. Create config.php on server with production credentials
3. Create logs directory with correct permissions
4. Test with real gateway
5. Monitor error logs
6. Set up SSL certificate (if not already)
7. Configure HTTPS redirect
8. Test payment flow end-to-end

---

## 🔍 Monitoring

### Check These Regularly

1. **Error Logs** (`logs/payment-errors.log`)
   - Monitor for failed payments
   - Look for signature verification failures
   - Check for unexpected errors

2. **Payment Gateway Dashboard**
   - Verify transactions appearing
   - Check for declined transactions
   - Monitor 3DS success rate

3. **Server Logs**
   - Watch for suspicious activity
   - Monitor failed login attempts
   - Check for unusual traffic patterns

4. **SSL Certificate**
   - Ensure certificate is valid
   - Set up auto-renewal
   - Monitor expiry date

---

## ⚠️ Security Reminders

### NEVER Do These:

❌ Commit `config.php` to version control  
❌ Share your `merchantSecret` key  
❌ Enable `debugMode` in production  
❌ Disable HTTPS enforcement  
❌ Log full card numbers or CVV  
❌ Use test credentials in production  
❌ Skip CSRF validation  
❌ Turn off error logging  

### ALWAYS Do These:

✅ Keep `config.php` secure and private  
✅ Use environment variables for secrets  
✅ Force HTTPS for all payment pages  
✅ Validate all user input  
✅ Log errors securely (no sensitive data)  
✅ Update gateway SDK regularly  
✅ Monitor error logs  
✅ Keep SSL certificate valid  
✅ Test payment flow regularly  
✅ Follow PCI DSS guidelines  

---

## 🆘 Troubleshooting

### Payment Fails Immediately

**Check:**
- `config.php` has correct credentials
- Error log for details
- Gateway connectivity
- Signature verification working

### 3DS Not Working

**Check:**
- HTTPS is enabled
- Cookies are working
- `threeDSRedirectURL` is publicly accessible
- Browser supports 3DS
- Secure cookie settings

### CSRF Errors

**Check:**
- Session is working
- Cookies enabled in browser
- `startSecureSession()` called
- Form includes CSRF token

### Signature Verification Fails

**Check:**
- Secret key is correct
- No whitespace in config
- Gateway response complete
- HTTP/HTTPS mismatch

---

## 📞 Support

### TakePayments Support
- Check integration guide PDFs
- Contact merchant support
- Review gateway response codes

### Technical Issues
- Check error logs first
- Verify all configurations
- Test with gateway test cards
- Review server logs

---

## ✅ Production Ready!

Once all steps are complete:

1. ✅ Config file created
2. ✅ Security checklist passed
3. ✅ Tests passing
4. ✅ HTTPS enabled
5. ✅ Monitoring set up

**You're ready to accept payments!**

---

*Last Updated: Version 3.0.0*

