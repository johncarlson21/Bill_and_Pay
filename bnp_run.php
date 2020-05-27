<?php
/*
 * Bill N Pay Batch Run File
 * Author: John Carlson
 * Email: jcarlson@mbcinteractive.com
 * Description:
 * This file runs the main functions at once for processing from and to T3
 * Functions Run: Add Customer, Add Invoice, Add Payent, Get Payment
 * See Class File for more information specific to each process.
 *
 */

error_reporting(E_ALL);

require("./includes/Bill_N_Pay.php");
include("./includes/Array2XML.php");

$BNP = new Bill_N_Pay;

// Add Customers

$count = $BNP->getCustomersToAddCount();
echo "Starting Add Customer to BNP." . PHP_EOL;
echo "Total Customers: " . $count . PHP_EOL;
$z = 1;
// get customers to add
while($count >= 1) {
	$customers = $BNP->getCustomersToAdd();
	foreach ($customers as $row) {
		$BNP->addCustomer($row);
		echo "Customers Added: " . $z . "         \r";
		$z++;
	}
	sleep(1);
	$count = $BNP->getCustomersToAddCount();
}
echo PHP_EOL;
echo "Add Customers Complete!" . PHP_EOL;

// Add Invoices

$invoices = $BNP->getInvoices();
$count = (count($invoices)) ? count($invoices) : 0;
echo "Starting Add Invoice to BNP." . PHP_EOL;
echo "Total Invoices: " . $count . PHP_EOL;
$z = 1;
foreach ($invoices as $row) {
	$invHeadID = $row['Invoice_Header_ID'];
	unset($row['Invoice_Header_ID']);
	$procId = $row['Process_Log_ID'];
	unset($row['Process_Log_ID']);
	$requestArray = array(
		"response" => array( "type" => "json" ),
		"biller" => array(
			"authenticate" => array( "id" => $BNP->getApiUser(), "password" => $BNP->getApiPass() ),
			"invoiceadd" => $row
		)
	);
	
	$xml = \LaLit\Array2XML::createXML("request", $requestArray);
	$BNP->addInvoice($xml->saveXML(), $invHeadID, $procId);
	echo "Invoice Added: " . $z . "         \r";
	$z++;
}

echo PHP_EOL;
echo "Add Invoices Complete!" . PHP_EOL;

// Add Payments

$payments = $BNP->getPaymentsToAdd();
$total = count($payments);
echo "Starting Add Payment to BNP." . PHP_EOL;
echo "Total Payments: " . $total . PHP_EOL;
$z = 1;
foreach($payments as $payment) {
	$BNP->addPayment($payment);
	echo "Payment Added: " . $z . "         \r";
	$z++;
}

echo PHP_EOL;
echo "Add Payments Complete!" . PHP_EOL;

// Get Payments

$payments = $BNP->getPaymentsPull();

echo "Starting Get Payments from BNP." . PHP_EOL;

if ($payments && count($payments)) {		
	echo "Total Payments: " . count($payments) . "." . PHP_EOL;
	$z = 1;
	foreach ($payments as $row) {
		$BNP->addPaymentsFromPull($row);
		echo "Payment Pulled: " . $z . "         \r";
		$z++;
	}
} else {
	echo "Total Payments: 0." . PHP_EOL; 
}
echo PHP_EOL;
echo "Get Payments from BNP Complete!" . PHP_EOL;

echo "All functions run!" . PHP_EOL;

?>