<?php
//exit;
//ini_set('display_errors', 1);
error_reporting(E_ALL);
//exit;
require("./includes/Bill_N_Pay.php");

$BNP = new Bill_N_Pay;

// get customers to add
$payments = $BNP->getPaymentsPull();

echo "Starting Add Payment from BNP." . PHP_EOL;

if ($payments && count($payments)) {		
	echo "Total Payments: " . count($payments) . "." . PHP_EOL;
	echo "<pre>";
	foreach ($payments as $row) {
		$BNP->addPaymentsFromPull($row);
		print_r($row);
	}
	echo "</pre>";
} else {
	echo "Total Payments: 0." . PHP_EOL; 
}
echo PHP_EOL;
echo "Script Complete!" . PHP_EOL;



?>