<?php
// Load security helpers and configuration
require_once __DIR__ . '/security.php';

// Start secure session
startSecureSession();

// Load configuration
$config = getConfig();

// Initialize gateway
require_once __DIR__ . '/gateway.php';
require_once __DIR__ . '/phone_country_codes.php';

$CSGW = new P3\SDK\Gateway;
$key = $config['merchantSecret'];
$samplecodeURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Gateway URL from config
$gatewayURL = $config['gatewayDirectURL'];

// Validate CSRF token on form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['threeDSAcsResponse'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        logError('CSRF token validation failed');
        die(json_encode(['error' => 'Invalid request. Please refresh and try again.']));
    }
}

// Request
if (!isset($_GET['threeDSAcsResponse'])) {

    // Validate all inputs
    $errors = [];
    
    // Validate card number
    $cardNumber = str_replace(' ', '', $_POST['CardNumber'] ?? '');
    if (!validateCardNumber($cardNumber)) {
        $errors[] = 'Invalid card number';
    }
    
    // Validate CVV
    $cvv = $_POST['CVV'] ?? '';
    if (!validateCVV($cvv)) {
        $errors[] = 'Invalid CVV';
    }
    
    // Validate expiry
    $expiryMonth = isset($_POST['cardExpiryMonth']) ? intval($_POST['cardExpiryMonth']) : 0;
    $expiryYear = isset($_POST['cardExpiryYear']) ? intval($_POST['cardExpiryYear']) : 0;
    $expiryResult = validateExpiryDate($expiryMonth, $expiryYear);
    
    if (!$expiryResult) {
        $errors[] = 'Invalid expiry date';
    }
    
    // Validate required fields
    if (empty($_POST['customerName'] ?? '')) {
        $errors[] = 'Cardholder name required';
    }
    if (empty($_POST['customerEmail'] ?? '') || !filter_var($_POST['customerEmail'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email required';
    }
    if (empty($_POST['customerAddress'] ?? '')) {
        $errors[] = 'Address required';
    }
    if (empty($_POST['customerPostCode'] ?? '')) {
        $errors[] = 'Postcode required';
    }
    
    // If validation errors, return to form
    if (!empty($errors)) {
        logError('Payment validation failed', ['errors' => $errors]);
        $_SESSION['payment_errors'] = $errors;
        header('Location: index.php?amount=' . ($_POST['Amount'] ?? ''));
        exit;
    }
    
    // Sanitize inputs
    $customerName = sanitizeInput($_POST['customerName']);
    $customerEmail = sanitizeInput($_POST['customerEmail']);
    $customerAddress = sanitizeInput($_POST['customerAddress']);
    $customerPostCode = sanitizeInput($_POST['customerPostCode']);
    
    // Clean card number (remove spaces)
    $cardNumber = str_replace(' ', '', $cardNumber);
    
    // Process expiry month and year
    $expiryMonth = $expiryResult['month'];
    $expiryYear = $expiryResult['year'];

    $req = array(
        'merchantID' => $config['merchantID'],
        'action' => 'SALE',
        'type' => 1,
        'countryCode' => $config['countryCode'],
        'currencyCode' => $config['currencyCode'],
        'amount' => $_POST['Amount'],
        'cardNumber' => $cardNumber,
        'cardExpiryMonth' => $expiryMonth,
        'cardExpiryYear' => $expiryYear,
        'cardCVV' => $cvv,
        'customerName' => $customerName,
        'customerEmail' => $customerEmail,
        'customerAddress' => $customerAddress,
        'customerPostCode' => $customerPostCode,
        'orderRef' => 'Order-' . uniqid(),
        'transactionUnique' => (isset($_REQUEST['transactionUnique']) ? $_REQUEST['transactionUnique'] : uniqid()),
        // Use configured phone number from config or leave empty if not provided
        "customerPhone" => !empty($config['defaultPhoneNumber']) ? format_phone_number($config['defaultPhoneNumber'], $config['defaultPhoneCountry']) : '',
        'remoteAddress' => $_SERVER['REMOTE_ADDR'],
        'threeDSRedirectURL' => $samplecodeURL . '?threeDSAcsResponse',
        'deviceChannel' => 'browser',
        'deviceIdentity' => (isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : null),
        'deviceTimeZone' => '0',
        'deviceCapabilities' => '',
        'deviceScreenResolution' => '1x1x1',
        'deviceAcceptContent' => (isset($_SERVER['HTTP_ACCEPT']) ? htmlentities($_SERVER['HTTP_ACCEPT']) : null),
        'deviceAcceptEncoding' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'deviceAcceptLanguage' => (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']) : null),
        'deviceAcceptCharset' => (isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? htmlentities($_SERVER['HTTP_ACCEPT_CHARSET']) : null),
    );
    
    // Sign the request
    $req['signature'] = createSignature($req, $key);

    // Log transaction attempt (without sensitive data)
    logError('Payment attempt initiated', [
        'orderRef' => $req['orderRef'],
        'amount' => $req['amount'],
        'customerEmail' => $customerEmail
    ]);

    // Send request
    try {
        $res = $CSGW->directRequest($req);
    } catch (Exception $e) {
        logError('Gateway communication error', ['error' => $e->getMessage()]);
        die('Payment processing error. Please try again later.');
    }

    // Else if this is a response from 3DS
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['threeDSAcsResponse'])) {

    // Check for threeDSRef in cookie
    if (empty($_COOKIE['threeDSRef'])) {
        logError('3DS response without threeDSRef');
        die('Invalid 3DS response. Please try again.');
    }

    // Build the request containing the threeDSResponse with data from the 3DS page
    $threeDSRequest = array(
        'threeDSRef' => $_COOKIE['threeDSRef'],
        'threeDSResponse' => $_POST,
    );
    
    // Sign the request
    $threeDSRequest['signature'] = createSignature($threeDSRequest, $key);

    // Send the 3DS response back to the gateway and get the response.
    try {
        $res = sendRequest($threeDSRequest, $gatewayURL);
    } catch (Exception $e) {
        logError('3DS gateway communication error', ['error' => $e->getMessage()]);
        die('3DS authentication error. Please try again.');
    }
}

// Prepare HTML response
$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Payment Processing</title>';
$html .= '<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;} .success{color:green;} .error{color:red;} .info{background:#f0f0f0;padding:15px;border-radius:5px;margin:20px 0;}</style>';
$html .= '</head><body>';

// Check the response code
if (isset($res['responseCode'])) {
    
    if ($res['responseCode'] == 65802) {
        
        // Set secure cookie for threeDSRef
        setSecureCookie('threeDSRef', $res['threeDSRef']);

        // Start of HTML form with URL
        $html .= "<div class='info'><p><strong>Your transaction requires 3D Secure Authentication!</strong></p><p>You will be redirected to your bank's authentication page.</p></div>";
        $html .= "<form action=\"" . htmlentities($res['threeDSURL']) . "\" method=\"post\" id=\"3dsform\">";

        // Add threeDSRef from the gateway response
        $html .= '<input type="hidden" name="threeDSRef" value="' . htmlentities($res['threeDSRef']) . '">';

        // For each of the fields in threeDSRequest output a hidden input field with its key/value
        foreach ($res['threeDSRequest'] as $key => $value) {
            $html .= '<input type="hidden" name="' . htmlentities($key) . '" value="' . htmlentities($value) . '">';
        }

        // End of html form with submit button.
        $html .= "<button type=\"submit\" style=\"padding:12px 24px;background:#22207E;color:white;border:none;border-radius:8px;cursor:pointer;font-size:16px;\">Continue to 3D Secure</button>";
        $html .= "</form>";
        $html .= "<script>document.getElementById('3dsform').submit();</script>";

        echo $html;
        exit;

    } elseif ($res['responseCode'] == 0) {
        
        // Get cartItems from session
        $cartItems = $_SESSION['cartItems'] ?? [];
        
        // Successful payment
        $html .= '<div class="success"><h2>✓ Payment Successful</h2>';
        $html .= "<p>" . htmlentities($res['responseMessage']) . "</p></div>";
        
        // Display cart items if available
        if (!empty($cartItems)) {
            $html .= '<div style="margin-top:20px;padding:15px;background:#f9f9f9;border-radius:8px;">';
            $html .= '<h3>Order Details:</h3><ul>';
            foreach ($cartItems as $item) {
                $itemName = htmlspecialchars($item['name'] ?? 'Item');
                $itemPrice = floatval($item['price'] ?? 0);
                $itemQty = intval($item['quantity'] ?? 1);
                $itemTotal = $itemPrice * $itemQty;
                $html .= "<li><strong>$itemName</strong> × $itemQty = £" . number_format($itemTotal, 2) . "</li>";
            }
            $html .= '</ul></div>';
        }

      // --- Webhook trigger ---
      $webhookUrl = "https://ea-dental.com/api/payment-succeed"; // your webhook endpoint
      $payload = json_encode([
          'status'          => 'success',
          'transactionRef'  => $_POST['transactionUnique'] ?? null,
          'orderRef'        => $_POST['orderRef'] ?? null,
          'amount'          => $_POST['amount'] ?? null,
          'responseMessage' => $_POST['responseMessage'] ?? null,
          'cardType'        => $_POST['cardType'] ?? null,
          'timestamp'       => date('c'),
          'cartItems'       => $cartItems, 
      ]);

      $ch = curl_init($webhookUrl);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type: application/json',
          'Content-Length: ' . strlen($payload)
      ]);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $error    = curl_error($ch);
      curl_close($ch);

      if ($error) {
          error_log("Webhook failed: " . $error);
      } else {
          error_log("Webhook sent: " . $response);
      }


    
      





      
        // Important, you must verify the returned signature
        try {
            if ($CSGW::verifyResponse($res, $key)) {
                $html .= "<p class='success'><strong>Signature verified successfully</strong></p>";
            } else {
                $html .= "<p class='error'><strong>Warning: Signature verification failed</strong></p>";
                logError('Signature verification failed', ['responseCode' => $res['responseCode']]);
            }
        } catch (Exception $e) {
            $html .= "<p class='error'><strong>Warning: Signature verification error</strong></p>";
            logError('Signature verification error', ['error' => $e->getMessage()]);
        }
        
        // Clean up session
        unset($_SESSION['csrf_token']);
        unset($_SESSION['payment_errors']);
        unset($_SESSION['cartItems']);
        
        // Log success
        logError('Payment successful', [
            'orderRef' => $res['orderRef'] ?? 'unknown',
            'responseMessage' => $res['responseMessage'] ?? '',
            'cartItemsCount' => count($cartItems)
        ]);
        
    } else {
        
        // Failed payment
        $html .= '<div class="error"><h2>✗ Payment Failed</h2>';
        $html .= "<p>" . htmlentities($res['responseMessage']) . "</p></div>";
        
        // Log failure
        logError('Payment failed', [
            'responseCode' => $res['responseCode'],
            'responseMessage' => $res['responseMessage'] ?? ''
        ]);
    }
} else {
    $html .= '<div class="error"><h2>✗ Processing Error</h2>';
    $html .= "<p>No response from payment gateway. Please try again.</p></div>";
    logError('No response from gateway');
}

$html .= '</body></html>';
echo $html;

/**
 * Send request to gateway
 *
 * @param Array $request
 * @param String $gatewayURL
 *
 * @return Array $response
 */
function sendRequest($request, $gatewayURL) {

    // Send request to the gateway

    // Initiate and set curl options to post to the gateway
    $ch = curl_init($gatewayURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request));
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    // Send the request and parse the response
    $responseData = curl_exec($ch);
    
    // Check for CURL errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Gateway communication error: $error");
    }
    
    // Close the connection to the gateway
    curl_close($ch);

    // Parse response
    parse_str($responseData, $response);
    
    // Log response (without sensitive data)
    logError('Gateway response received', [
        'responseCode' => $response['responseCode'] ?? 'unknown'
    ]);

    return $response;
}

/**
 * Sign request
 *
 * @param Array $data
 * @param String $key
 *
 * @return String Hash
 */
function createSignature(array $data, $key) {
    // Sort by field name
    ksort($data);

    // Create the URL encoded signature string
    $ret = http_build_query($data, '', '&');

    // Normalise all line endings (CRNL|NLCR|NL|CR) to just NL (%0A)
    $ret = str_replace(array('%0D%0A', '%0A%0D', '%0D'), '%0A', $ret);

    // Hash the signature string and the key together
    return hash('SHA512', $ret . $key);
}
