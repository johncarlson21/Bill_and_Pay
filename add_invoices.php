<?php
//exit;
//ini_set('display_errors', 'On');
error_reporting(E_ALL);

require("./includes/Bill_N_Pay.php");
include("./includes/Array2XML.php");

$BNP = new Bill_N_Pay;

// get invoices to add
$invoices = $BNP->getInvoices();
$count = (count($invoices)) ? count($invoices) : 0;
echo "Starting Add Invoice to BNP." . PHP_EOL;
echo "Total Invoices: " . $count . PHP_EOL;

echo "<pre>";
foreach ($invoices as $row) {
	$invHeadID = $row['Invoice_Header_ID'];
	unset($row['Invoice_Header_ID']);
	$requestArray = array(
		"response" => array( "type" => "json" ),
		"biller" => array(
			"authenticate" => array( "id" => $BNP->getApiUser(), "password" => $BNP->getApiPass() ),
			"invoiceadd" => $row
		)
	);
	
	$xml = \LaLit\Array2XML::createXML("request", $requestArray);
	//print_r($xml->saveXML()); // output xml
	$BNP->addInvoice($xml->saveXML(), $invHeadID);
}
echo "</pre>";

echo PHP_EOL;
echo "Script Complete!" . PHP_EOL;


?>