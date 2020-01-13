<?php
exit;
ini_set('display_errors', 1);
ini_set('error_log', './error_log');

require("./includes/httpful.phar");

$uri = "https://www.billandpay.com/webservices/service.php";

$xml = '<?xml version="1.0"?>
<request>
  <response>
    <type>json</type>
  </response>
  <biller>
    <authenticate>
      <id>billcarpenter</id>
      <password>skiing</password>
    </authenticate>
    <customerinfo>
      <limit></limit>
      <field>internalid</field>
      <field>id</field>
      <field>updateddatetime</field>
      <field>active</field>
      <field>login</field>
      <field>name</field>
      <field>companyname</field>
      <field>firstname</field>
      <field>middlename</field>
      <field>lastname</field>
      <field>email</field>
      <field>phone</field>
      <field>altphone</field>
      <field>fax</field>
      <field>shippingaddress</field>
      <field>billingaddress</field>
      <field>invitation</field>
      <field>notes</field>
      <where>
        <id>3</id>
      </where>
    </customerinfo>
  </biller>
</request>
';

/*$response = \Httpful\Request::get($uri)
    ->expectsJson()
    ->sendIt();*/
$response = \Httpful\Request::post($uri)
	->body($xml)
	->sendsXml()
	->expectsJson()
	//->expectsXml()
	->send();

echo "<pre>";
//$body = (array) $response->body;
var_dump($response);
//var_dump($response->body->biller->customerinfo->customer{0});

echo "</pre>";


/* //DB Connections 
$conn = new PDO('sqlsrv:server=localhost;database=GGAX', 'billnpay', 'BillNPay!');

$sql = "SELECT * FROM GGAX.Bill_and_Pay.Customer";
$getCustomers = $conn->prepare($sql);
$getCustomers->execute();
$customers = $getCustomers->fetchAll(PDO::FETCH_ASSOC);
print("<pre>");
foreach ($customers as $row) {
    print_r($row);
} 
print("</pre>");
*/
?>