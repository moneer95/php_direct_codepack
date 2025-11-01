# 🚀 Pre-Deployment Checklist

Use this checklist before deploying to production.

---

## ✅ Configuration (Required)

- [ ] Created `config.php` from `config.example.php`
- [ ] Added your **real** `merchantID`
- [ ] Added your **real** `merchantSecret`
- [ ] Set `requireHTTPS` to `true`
- [ ] Set `debugMode` to `false`
- [ ] Set `showDebugOutput` to `false`
- [ ] Removed test phone numbers
- [ ] Verified country/currency codes correct
- [ ] Checked gateway URLs are production URLs

---

## ✅ Directory Setup (Required)

- [ ] Created `logs/` directory
- [ ] Set `logs/` permissions to `755`
- [ ] Created `logs/payment-errors.log` file
- [ ] Set `logs/payment-errors.log` permissions to `644`
- [ ] Verified logs directory is writable

---

## ✅ File Permissions (Required)

- [ ] `config.php` permissions set to `644` (not executable)
- [ ] `index.php` permissions set to `755` or `644`
- [ ] `process.php` permissions set to `755` or `644`
- [ ] `gateway.php` permissions set to `644`
- [ ] `security.php` permissions set to `644`
- [ ] Other PHP files set appropriately

---

## ✅ Server Configuration (Required)

- [ ] HTTPS is enabled and working
- [ ] SSL certificate is valid and not expired
- [ ] HTTPS redirect configured (HTTP → HTTPS)
- [ ] Web server allows POST requests
- [ ] PHP sessions are working
- [ ] Cookies are working
- [ ] cURL extension is enabled
- [ ] SSL/TLS support enabled in PHP

---

## ✅ Security Configuration (Required)

- [ ] `.htaccess` or nginx config protects `config.php`
- [ ] `.htaccess` or nginx config protects `logs/` directory
- [ ] `config.php` is in `.gitignore` (if using git)
- [ ] `logs/` is in `.gitignore` (if using git)
- [ ] Debug mode is OFF
- [ ] Error display is OFF (in php.ini)
- [ ] File uploads disabled (if not needed)

---

## ✅ Testing (Required Before Production)

### Local Testing
- [ ] Payment form loads correctly
- [ ] CSRF tokens are generated
- [ ] Validation works (try invalid cards)
- [ ] Form submission works
- [ ] Error messages display correctly

### Gateway Testing (Use Test Credentials)
- [ ] Successful payment flow works
- [ ] Declined payment handled correctly
- [ ] 3DS authentication redirects properly
- [ ] 3DS authentication completes
- [ ] Payment success page displays
- [ ] Error logging writes to file
- [ ] No sensitive data in logs
- [ ] No debug output visible to users

### Security Testing
- [ ] CSRF protection rejects invalid tokens
- [ ] HTTPS redirect works
- [ ] Invalid inputs are rejected
- [ ] SQL injection attempts fail (if DB used)
- [ ] XSS attempts are sanitized
- [ ] Cookies have secure flags
- [ ] Sessions timeout correctly

---

## ✅ Production Gateway (Final Test)

### Before Going Live
- [ ] Switched to production credentials
- [ ] Tested with **small** real amount
- [ ] Verified payment appears in gateway dashboard
- [ ] Confirmed webhook delivery (if using)
- [ ] Checked error logs are clean
- [ ] Verified no debug output
- [ ] Tested on multiple browsers
- [ ] Tested on mobile devices

---

## ✅ Monitoring Setup

- [ ] Error logging is working
- [ ] Know where to check logs
- [ ] Set up log rotation
- [ ] Gateway dashboard access
- [ ] Email alerts configured (if available)
- [ ] Uptime monitoring set up

---

## ✅ Documentation

- [ ] Read `PRODUCTION_SETUP.md`
- [ ] Read `README.md`
- [ ] Read gateway PDF documentation
- [ ] Know how to troubleshoot
- [ ] Have support contacts ready

---

## ✅ Compliance

- [ ] PCI DSS requirements reviewed
- [ ] Privacy policy updated
- [ ] Terms of service updated
- [ ] Data retention policy set
- [ ] GDPR considerations addressed (if EU)
- [ ] Legal compliance verified

---

## ✅ Backup & Recovery

- [ ] Backup strategy in place
- [ ] Database backup configured (if used)
- [ ] Config file backed up securely
- [ ] Log rotation configured
- [ ] Recovery procedure documented
- [ ] Test recovery process

---

## ✅ Performance

- [ ] Server resources adequate
- [ ] CDN configured (if using)
- [ ] Caching enabled (if appropriate)
- [ ] Load testing completed
- [ ] Response times acceptable

---

## ✅ Post-Deployment

### First 24 Hours
- [ ] Monitor error logs hourly
- [ ] Check gateway dashboard
- [ ] Verify all payments processing
- [ ] Check server resources
- [ ] Review access logs for suspicious activity

### First Week
- [ ] Review error patterns
- [ ] Check payment success rate
- [ ] Verify 3DS completion rate
- [ ] Monitor server performance
- [ ] Gather user feedback

---

## ❌ STOP! DON'T DEPLOY IF:

- Config file not created
- Using test credentials
- Debug mode enabled
- HTTPS not working
- Haven't tested with gateway
- Logs directory missing
- File permissions wrong
- Haven't read documentation

---

## ✅ READY TO DEPLOY WHEN:

- [x] All required items checked
- [x] Testing completed successfully
- [x] Security verified
- [x] Documentation read
- [x] Support contacts ready
- [x] Monitoring in place
- [x] Backup strategy ready

---

## 🆘 Emergency Contacts

**TakePayments Support:** _________________  
**Technical Support:** _________________  
**Server Admin:** _________________  
**Security Contact:** _________________  

---

## 📝 Deployment Notes

Date: _______________  
Deployed by: _______________  
Tested by: _______________  
Production URL: _______________  

**Notes:**
_________________________________
_________________________________
_________________________________

---

## ✅ Final Sign-Off

By checking this box, I confirm:
- [ ] All critical items are complete
- [ ] Testing has been successful
- [ ] I am ready to deploy to production
- [ ] I have read and understood the risks

**Signature:** _______________  
**Date:** _______________

---

**Last Updated:** Version 3.0.0

