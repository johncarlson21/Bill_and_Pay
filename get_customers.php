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

// prepare for insert from BNP
$BNP->preparePull();

// get customers to add
$date = "2019-01-01";
$page = 0;
$rows = 500;
$customers = $BNP->getCustomersPull($date, $page, $rows);
$records = $BNP->recordCount;
$pages = ceil($records / $rows);

//print_r("Total Records: " . $records . "; Pages: " . $pages . "\n"); //exit;


$count = 0;
//echo "<pre>";
while($page < $pages) {
	if ( $page > 0 ) { $customers = $BNP->getCustomersPull($date, $page, $rows); }
	foreach ($customers as $row) {
		$BNP->addCustomerFromPull($row);
		echo ($row['name'] . " - " . $row['id']);
		//print_r($row);
		//print_r("\n");
		echo ("\n");
		$count++;
		//if ($count == 51) { exit; }
	}
	print_r("Page: " . $page . "; Count: " . $count . "\n");
	$page++;
	sleep(1);
}
print_r("Total Records: " . $records . "; Pages: " . $pages . "\n");
print_r("Total Added: " . $count . "\n");
//echo "</pre>";




?>