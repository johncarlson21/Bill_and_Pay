<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
exit;
require("./includes/Bill_N_Pay.php");

$BNP = new Bill_N_Pay;

/*
$customer = $BNP->getCustomerById(3);
print_r($customer);
*/

// get customers to add
/*$customers = $BNP->getCustomersToAdd();
echo "<pre>";
foreach ($customers as $row) {
	$BNP->addCustomer($row);
}
echo "</pre>";
*/



?>