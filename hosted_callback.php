<style>
    span{width:100vw;text-align: center;font-size: 2.5rem}
</style>
<?php //echo var_dump($_POST);
include_once('gateway.php');
$key = "9GXwHNVC87VqsqNM";
if(!class_exists("gateway.php")){
    $CSGW = new P3\SDK\Gateway;
    $res = $_POST;
    unset($res['signature']);
    echo ($CSGW::verifyResponse($_POST, $key)) ? '<span style="color:green">Return Signature Verified Successfully</span>' : '<span class="color:red">Could not verifiy response</span>';
}
if($_POST['responseCode'] == 0)
{
    echo '<span style="color:green">Payment Successful</span>';
}
else
{
    echo '<span style="color:red">Payment Unuccessful</span>';
    echo '<span style="color:red">'.$_POST['responseMessage'].'</span>';
    
}
echo '<pre>'. print_r($_POST,true).'</pre>';

