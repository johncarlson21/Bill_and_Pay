<?php
//exit;
//ini_set('display_errors', 'On');
error_reporting(E_ALL);

require("./includes/Bill_N_Pay.php");
include("./includes/Array2XML.php");

$BNP = new Bill_N_Pay;

$invoices = $BNP->getInvoicesForDelete();
echo "Total invoices to remove: " . count($invoices) . PHP_EOL;
//print_r($invoices); exit;
foreach($invoices as $inv) {
	$result = $BNP->deleteInvoice($inv);
	echo $result . PHP_EOL;
	//exit;
}
echo "End invoice removal" . PHP_EOL;

?>