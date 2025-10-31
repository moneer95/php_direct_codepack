<?php
include('gateway.php');
include("phone_country_codes.php");
$CSGW = new P3\SDK\Gateway;
$key = '5CZ4T3pdVLUN011UrKFD'; // Should be $merchantSecret from the file gateway.php -> change if needed
$samplecodeURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Gateway URL
$gatewayURL = 'https://gateway.cardstream.com/direct/';

// Request
if (!isset($_GET['threeDSAcsResponse'])) {

$req = array(
    'merchantID' => '278346', // Should be $merchantID from the file gateway.php -> change if needed
    'action' => 'SALE',
    'type' => 1,
    'countryCode' => 826,
    'currencyCode' => 826,
    'amount' => $_POST['Amount'],
    'cardNumber' => $_POST['CardNumber'],
    'cardExpiryMonth' => 12,
    'cardExpiryYear' => 55,
    'cardCVV' => $_POST['CVV'],
    'customerName' => 'Test Customer',
    'customerEmail' => 'test@testcustomer.com',
    'customerAddress' => '16 Test Street',
    'customerPostCode' => 'TE15 5ST',
    'orderRef' => 'Test purchase - ' .uniqid(),
    'transactionUnique' => (isset($_REQUEST['transactionUnique']) ?
    $_REQUEST['transactionUnique'] : uniqid()),
    // 3DS requests now require either a phone number with area code or customer email address
    "customerPhone"    => format_phone_number('07900000000', 826),
    "customerEmail"   => "nicolas.cage@takepayments.com",
    'remoteAddress'             => $_SERVER['REMOTE_ADDR'],
    'threeDSRedirectURL'        => $samplecodeURL . '?threeDSAcsResponse',
    'deviceChannel'				=> 'browser',
    'deviceIdentity'			=> (isset($_SERVER['HTTP_USER_AGENT']) ? htmlentities($_SERVER['HTTP_USER_AGENT']) : null),
    'deviceTimeZone'			=> '0',
    'deviceCapabilities'		=> '',
    'deviceScreenResolution'	=> '1x1x1',
    'deviceAcceptContent'		=> (isset($_SERVER['HTTP_ACCEPT']) ? htmlentities($_SERVER['HTTP_ACCEPT']) : null),
    'deviceAcceptEncoding'		=> 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
    'deviceAcceptLanguage'		=> (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? htmlentities($_SERVER['HTTP_ACCEPT_LANGUAGE']) : null),
    'deviceAcceptCharset'		=> (isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? htmlentities($_SERVER['HTTP_ACCEPT_CHARSET']) : null),
    );
    // Sign the request
    $req['signature'] = createSignature($req, $key);

    // send request
    $res = $CSGW->directRequest($req);

    // Else if this is a response from 3DS
    }  elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['threeDSAcsResponse'])) {

        // Build the request containing the threeDSResponse with data from the 3DS page
        // and include the threeDSRef stored in the cookie.
        $threeDSRequest = array(
            'threeDSRef' => $_COOKIE['threeDSRef'], // This is the threeDSref store in the cookie from the previous gateway response.
            'threeDSResponse' => $_POST, // <-- Note here no fields are hard coded. Whatever is POSTED from 3DS is returned.
        );
        // Sign the request
        $threeDSRequest['signature'] = createSignature($threeDSRequest, $key);

        // Send the 3DS response back to the gateway and get the response.
        $res = sendRequest($threeDSRequest, $gatewayURL);

        //This cycle continues until the response is 0 and the transaction is complete.

    }

  $html = "";
	$html .= '<h2> Gateway request</h2>';
	$html .= '<pre>' . print_r(isset($threeDSRequest) ? $threeDSRequest : $req, true) .'</pre>';
  // $html .= '<pre>' . print_r($threeDSRequest, true) .'</pre>';
	$html .= '<h2> Gateway response</h2>';
	$html .= '<pre>' . print_r($res, true) .'</pre>';

  // Check the response code
  if ($res['responseCode'] == 65802) {

    setcookie('threeDSRef', $res['threeDSRef'], time()+500);

      // Start of HTML form with URL
      $html .= "<p>Your transaction requires 3D Secure Authentication!</p>
              <form action=\"" . htmlentities($res['threeDSURL']) . "\"method=\"post\">";

      // Add threeDSRef from the gateway response
      $html .= '<input type="hidden" name="threeDSRef" value="'. $res['threeDSRef'] . '">';

      // For each of the fields in threeDSRequest output a hidden input field with it's key/value
      foreach($res['threeDSRequest'] as $key => $value) {
          $html .= '<input type="hidden" name="'. $key .'" value="'. $value. '">';
      }

      // End of html form with submit button.
      $html .= "<input type=\"submit\" value=\"Continue\">
              </form>";

      echo $html;

  } else if ($res['responseCode'] == 0) {
  echo "<p>Thank you for your payment. ". htmlentities($res['responseMessage']). "</p>";
    // Important, you must verifiy the returned signature
    echo ($CSGW::verifyResponse($res, $key)) ? "<p style='color:green'>Signature Verified successfuly</p>" : "<p style='color:red'>Signature is not valid</p>";
  } else {
  echo "<p>Failed to take payment: " . htmlentities($res['responseMessage']) .
  "</p>";
  }


  /**
   * Send request
   *
   * @param Array $request
   * @param String $gatewayURL
   *
   * @return Array $responseponse
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

      // Send the request and parse the response
      parse_str(curl_exec($ch), $response);

      // Close the connection to the gateway
      curl_close($ch);

  	error_log(print_r($response, true));

      // Check the response code for 3DS Authentication (65802). If 3DS authentication required
      // Build a HTML form to submit to the threeDSURL

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
