<?php
//exit;
//ini_set('display_errors', 1);
error_reporting(E_ALL);

require("./includes/Bill_N_Pay.php");

$BNP = new Bill_N_Pay;

$count = $BNP->getCustomersToAddCount();
echo "Starting Add Customer to BNP." . PHP_EOL;
echo "Total Customers: " . $count . PHP_EOL;
$z = 1;
// get customers to add
while($count >= 1) {
	//print_r("count: " . $count . "<br />");
	$customers = $BNP->getCustomersToAdd();
	//set_time_limit(0);
	//echo "<pre>";
	foreach ($customers as $row) {
		$BNP->addCustomer($row);
		print_r("Customers Added: " . $z . "\r");
		$z++;
	}
	//echo "</pre>";
	sleep(1);
	$count = $BNP->getCustomersToAddCount();
}
echo PHP_EOL;
echo "Script Complete!" . PHP_EOL;

?>