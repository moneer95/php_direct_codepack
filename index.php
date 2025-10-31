<b style="font-size:3rem">Direct Integration Example</b>
<p>Enter your card details</p>
<form action="process.php" method="post">
<label>CardNumber</label><br>
<input type="text" name="CardNumber" value="4539791001730106"><br>
<label>CVV</label><br>
<input type="text" name="CVV" value="289"><br>
<label>Amount (in minor currency, i.e. &pound;12.34 = 1234)<br>
<input type="text" name="Amount" value="1234"><br>
<input type="submit" value="Continue">
</form>

<input type="tel"       pattern="[0-9]{3}"/>

<?php
include('gateway.php');
include("phone_country_codes.php");
$CSGW = new P3\SDK\Gateway;
$key = '9GXwHNVC87VqsqNM';

$tran = array (
    'merchantID' => '119837',
  "merchantSecret" => $key,
      'action' => 'SALE',
      'type' => 1,
      'customerAddress' => "1 test",
// 3DS requests now require either a phone number with area code or customer email address
    "customerPhone"    => format_phone_number('07900000000', 826),
    "customerEmail"   => "nicolas.cage@takepayments.com",      
    'countryCode' => 826,
      'currencyCode' => 826,
      'amount' => "900",
      'orderRef' => uniqid(),
      'formResponsive' => 'Y',
      'customerNameMandatory' => "Y",
      'transactionUnique' => uniqid(),
       'redirectURL' =>  'https://' .
       $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']. 'hosted_callback.php',
        'callbackURL' =>  'https://' .
       $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']. 'hosted_callback.php',
  );
  echo '<br><br><b style="font-size:3rem">Hosted Integration Example</b>';
  echo $CSGW->hostedRequest($tran);
