<style>
    span{width:100vw;text-align: center;font-size: 2.5rem}
</style>
<?php //echo var_dump($_POST);
include_once('gateway.php');
$key = "5CZ4T3pdVLUN011UrKFD";
if(!class_exists("gateway.php")){
    $CSGW = new P3\SDK\Gateway;
    $res = $_POST;
    unset($res['signature']);
    echo ($CSGW::verifyResponse($_POST, $key)) ? '<span style="color:green">Return Signature Verified Successfully</span>' : '<span class="color:red">Could not verifiy response</span>';
}
if($_POST['responseCode'] == 0)
{
    echo '<span style="color:green">Payment Successful</span>';

    // Retrieve cart items from session if available
    session_start();
    $cartItems = $_SESSION['cartItems'] ?? [];

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
}
else
{
    echo '<span style="color:red">Payment Unuccessful</span>';
    echo '<span style="color:red">'.$_POST['responseMessage'].'</span>';
    
}
echo '<pre>'. print_r($_POST,true).'</pre>';

