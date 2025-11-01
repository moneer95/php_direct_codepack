<?php
/**
 * Configuration Example
 * 
 * Copy this file to config.php and update with your production credentials.
 * NEVER commit config.php to version control!
 */

return [
    // TakePayments Gateway Credentials
    'merchantID' => getenv('TAKEPAYMENTS_MERCHANT_ID') ?: '278346',
    'merchantSecret' => getenv('TAKEPAYMENTS_SECRET') ?: '5CZ4T3pdVLUN011UrKFD',
    
    // Gateway URLs
    'gatewayDirectURL' => getenv('TAKEPAYMENTS_DIRECT_URL') ?: 'https://gw1.tponlinepayments.com/direct/',
    'gatewayHostedURL' => getenv('TAKEPAYMENTS_HOSTED_URL') ?: 'https://gw1.tponlinepayments.com/hosted/',
    
    // Payment Settings
    'countryCode' => 826,  // UK = 826
    'currencyCode' => 826,  // GBP = 826
    
    // Security Settings
    'cookieExpiry' => 600,  // 10 minutes for 3DS
    'sessionExpiry' => 3600, // 1 hour session timeout
    
    // Debug Settings
    'debugMode' => getenv('DEBUG_MODE') === '1',
    'showDebugOutput' => false, // NEVER set to true in production!
    
    // SSL/HTTPS Settings
    'requireHTTPS' => true,
    'secureCookies' => true,
    
    // Customer Defaults (production should ask for these)
    'defaultPhoneCountry' => 826,
    'defaultPhoneNumber' => '', // Should be empty or from user input
    
    // Logging
    'logErrors' => true,
    'logFile' => __DIR__ . '/logs/payment-errors.log',
];

