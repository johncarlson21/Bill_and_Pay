<?php
error_reporting(E_ALL);
//exit;
require("./includes/Bill_N_Pay.php");

$BNP = new Bill_N_Pay;

$payments = $BNP->getPaymentsToAdd();
$total = count($payments);
echo "Starting Add Payment to BNP." . PHP_EOL;
echo "Total Payments: " . $total . PHP_EOL;
$z = 1;
//exit;
foreach($payments as $payment) {
	$BNP->addPayment($payment);
	echo "Payment Added: " . $z . PHP_EOL;
	$z++;
}

echo PHP_EOL;
echo "Script Complete!" . PHP_EOL;

?>