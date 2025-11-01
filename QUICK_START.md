# Quick Start Guide

## 🚀 5 Steps to Production

### 1️⃣ Copy Config File
```bash
cp config.example.php config.php
```

### 2️⃣ Edit config.php
```php
'merchantID' => 'YOUR_MERCHANT_ID',          // ⚠️ Change this!
'merchantSecret' => 'YOUR_SECRET_KEY',       // ⚠️ Change this!
'requireHTTPS' => true,                       // ⚠️ Must be true!
'defaultPhoneNumber' => '',                   // ⚠️ Empty in production!
'debugMode' => false,                         // ⚠️ Must be false!
```

### 3️⃣ Create Logs Directory
```bash
mkdir logs
chmod 755 logs
touch logs/payment-errors.log
chmod 644 logs/payment-errors.log
```

### 4️⃣ Test
Visit: `https://yourdomain.com/index.php?amount=12.34`

### 5️⃣ Deploy
- Ensure HTTPS is enabled
- Test with real gateway
- Monitor error logs

---

## ✅ That's It!

Your payment gateway is now **production ready**!

---

## 📚 Need More Details?

- **Full setup:** `PRODUCTION_SETUP.md`
- **Security info:** `PRODUCTION_ISSUES.md`
- **Features:** `README.md`

---

## 🆘 Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| Payment fails | Check `config.php` credentials |
| 3DS not working | Ensure HTTPS enabled |
| CSRF errors | Check session working |
| No logs | Check permissions on `logs/` |
| Signature fails | Verify secret key correct |

---

**Time to setup:** ~10 minutes ⏱️

