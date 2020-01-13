<?php

/*
 * Bill N Pay Class
 * Author: John Carlson
 * Email: jcarlson@mbcinteractive.com
 * Description:
 * This class connects T3 to Bill N Pay and can perform multiple functions such as:
 * Get Customers, Add Customers, Create Invoices, Set Manual Payment
 * -------------------------------------
 * This class uses the HTTPFUL library
 *
 */

require("./includes/httpful.phar");


class Bill_N_Pay {
	
	var $uri; // url to webservice
	
	var $user; // bill n pay user
	
	var $pass; // bill n pay pass for user
	
	var $conn; // connection to sql server
	var $db;
	var $dbU;
	var $dbP;
	var $config; // main config from config.ini
	
	var $invTempArray = array(
		"id" => "",
		"customer" => array("id" => ""),
		"createddate" => "",
		"number" => "",
		"ponumber" => "",
		"duedate" => "",
		"startdate" => "",
		"enddate" => "",
		"shipdate" => "",
		"salestax" => "",
		"terms" => "",
		"billingaddress" => array(
				"address1" => "",
				"address2" => "",
				"address3" => "",
				"city" => "",
				"state" => "",
				"zip" => ""
			),
		"shippingaddress" => array(
				"address1" => "",
				"address2" => "",
				"address3" => "",
				"city" => "",
				"state" => "",
				"zip" => ""
			),
		"lineitem" => array(),
		"salesrep" => array("name" => ""),
		"memo" => ""
	);
	
	var $lineItemTempArray = array(
		"id" => "",
		"quantity" => "",
		"rate" => "",
		"amount" => "",
		"itemname" => "",
		"description" => "",
		"other1" => "",
		"other2" => "",
		"other3" => "",
		"servicedate" => "",
		"displayorder" => ""
	);
	
	public $recordCount;
	
	public $batchID;
	
	function __construct() {
		$this->uri = "https://www.billandpay.com/webservices/service.php";
		$config = parse_ini_file(dirname(__FILE__) . "/config.ini");
		if (empty($config) || !is_array($config)) { die("Config file not found, or is empty!"); exit; }
		$this->config = $config;
		$this->user = $this->config['user'];
		$this->pass = $this->config['pass'];
		$this->db = $this->config['db'];
		$this->dbU = $this->config['dbuser'];
		$this->dbP = $this->config['dbpass'];
		$this->conn = new PDO('sqlsrv:server=localhost;database=' . $this->db, $this->dbU, $this->dbP, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
	}
	
	function getApiUser() {
		return $this->user;
	}
	
	function getApiPass() {
		return $this->pass;
	}
	
	function preparePull() {
		$sql = "UPDATE " . $this->db . ".Bill_and_Pay.Customer SET Is_Active = 0 WHERE Is_Active = 1 AND Is_Read = 1";
		$prepare = $this->conn->prepare($sql);
		$prepare->execute();
		$prepare->closeCursor();
	}
	
	function getCustomersPull($date, $page=0, $rows=500) {
		/*
		<where>
			<updatedsince>' . $date . '</updatedsince>
		</where>
		*/
		
		$xml = '<?xml version="1.0"?>
<request>
  <response>
    <type>json</type>
  </response>
  <biller>
    <authenticate>
      <id>' . $this->user . '</id>
      <password>' . $this->pass . '</password>
    </authenticate>
    <customerinfo>
		<limit>
			<rows>' . $rows . '</rows>
			<page>' . $page . '</page> 
		</limit>
		
	  <recordcount>1</recordcount>
    </customerinfo>
  </biller>
</request>
		';
		$response = \Httpful\Request::post($this->uri)
			->body($xml)
			->sendsXml()
			->expectsJson()
			->send();
		$customers = json_decode($response, true);
		if ($customers && !empty($customers['biller']['customerinfo'])) {
			$this->recordCount = $customers['biller']['customerinfo']['recordcount'];
			return $customers['biller']['customerinfo']['customer'];
		} else {
			return "Error: No Customers.";
		}
	}
	
	function addCustomerFromPull($customer) {
		$params = array(
			(isset($customer['internalid']) && !empty($customer['internalid'])) ? $customer['internalid'] : "",
			(isset($customer['id']) && !empty($customer['id'])) ? $customer['id'] : "",
			(isset($customer['parent']['id']) && !empty($customer['parent']['id'])) ? $customer['parent']['id'] : "",
			(isset($customer['accountingaccountnumber']) && !empty($customer['accountingaccountnumber'])) ? $customer['accountingaccountnumber'] : "",
			(isset($customer['companyname']) && !empty($customer['companyname'])) ? $customer['companyname'] : "",
			(isset($customer['firstname']) && !empty($customer['firstname'])) ? $customer['firstname'] : "",
			(isset($customer['middlename']) && !empty($customer['middlename'])) ? $customer['middlename'] : "",
			(isset($customer['lastname']) && !empty($customer['lastname'])) ? $customer['lastname'] : "",
			(isset($customer['email']) && !empty($customer['email'])) ? $customer['email'] : "",
			(isset($customer['phone']) && !empty($customer['phone'])) ? $customer['phone'] : "",
			(isset($customer['altphone']) && !empty($customer['altphone'])) ? $customer['altphone'] : "",
			(isset($customer['fax']) && !empty($customer['fax'])) ? $customer['fax'] : "",
			(isset($customer['billingaddress']['address1']) && !empty($customer['billingaddress']['address1'])) ? $customer['billingaddress']['address1'] : "",
			(isset($customer['billingaddress']['address2']) && !empty($customer['billingaddress']['address2'])) ? $customer['billingaddress']['address2'] : "",
			(isset($customer['billingaddress']['address3']) && !empty($customer['billingaddress']['address3'])) ? $customer['billingaddress']['address3'] : "",
			(isset($customer['billingaddress']['city']) && !empty($customer['billingaddress']['city'])) ? $customer['billingaddress']['city'] : "",
			(isset($customer['billingaddress']['state']) && !empty($customer['billingaddress']['state'])) ? $customer['billingaddress']['state'] : "",
			(isset($customer['billingaddress']['zip']) && !empty($customer['billingaddress']['zip'])) ? $customer['billingaddress']['zip'] : "",
			(isset($customer['billingaddress']['country']) && !empty($customer['billingaddress']['country'])) ? $customer['billingaddress']['country'] : "",
			(isset($customer['shippingaddress']['address1']) && !empty($customer['shippingaddress']['address1'])) ? $customer['shippingaddress']['address1'] : "",
			(isset($customer['shippingaddress']['address2']) && !empty($customer['shippingaddress']['address2'])) ? $customer['shippingaddress']['address2'] : "",
			(isset($customer['shippingaddress']['address3']) && !empty($customer['shippingaddress']['address3'])) ? $customer['shippingaddress']['address3'] : "",
			(isset($customer['shippingaddress']['city']) && !empty($customer['shippingaddress']['city'])) ? $customer['shippingaddress']['city'] : "",
			(isset($customer['shippingaddress']['state']) && !empty($customer['shippingaddress']['state'])) ? $customer['shippingaddress']['state'] : "",
			(isset($customer['shippingaddress']['zip']) && !empty($customer['shippingaddress']['zip'])) ? $customer['shippingaddress']['zip'] : "",
			(isset($customer['shippingaddress']['country']) && !empty($customer['shippingaddress']['country'])) ? $customer['shippingaddress']['country'] : "",
			1,
			1,
			0,
			0,
			0
		);
		$sql = "INSERT INTO " . $this->db . ".Bill_and_Pay.Customer (Internal_ID, Customer_Number, Parent_Customer_Number, Accounting_Account_Number, Company_Name, First_Name, Middle_Name, Last_Name, Email, Phone, Alt_Phone, Fax, Billing_Address1, Billing_Address2, Billing_Address3, Billing_City, Billing_State, Billing_zip, Billing_Country, Shipping_Address1, Shipping_Address2, Shipping_Address3, Shipping_City, Shipping_State, Shipping_zip, Shipping_Country, Is_Active, Is_Read, Updated_By, Created_By, Is_Error) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		
		$prepare = $this->conn->prepare($sql);
		$prepare->execute($params);
		$prepare->closeCursor();
		
	}
	
	function getCustomerById($id) {
		$xml = '<?xml version="1.0"?>
<request>
  <response>
    <type>json</type>
  </response>
  <biller>
    <authenticate>
      <id>' . $this->user . '</id>
      <password>' . $this->pass . '</password>
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
        <id>' . $id . '</id>
      </where>
    </customerinfo>
  </biller>
</request>
		';
		$response = \Httpful\Request::post($this->uri)
			->body($xml)
			->sendsXml()
			->expectsJson()
			->send();
		$customer = json_decode($response, true);
		if ($customer && !empty($customer['biller']['customerinfo'])) {
			return $customer['biller']['customerinfo']['customer'];
		} else {
			return "Error: No Customer with ID: " . $id;
		}
	}
	
	function getCustomersToAddCount() {
		$sql = "SELECT COUNT(Customer_ID) as ct FROM " . $this->db . ".Bill_and_Pay.Customer WHERE Is_Active = 1 AND Is_Sent = 0 AND Is_Add = 1";
		$getCustomers = $this->conn->prepare($sql);
		$getCustomers->execute();
		$customers = $getCustomers->fetch(PDO::FETCH_ASSOC);
		$getCustomers->closeCursor();
		return $customers['ct'];
	}
	
	function getCustomersToAdd() {
		$sql = "SELECT TOP 500 * FROM " . $this->db . ".Bill_and_Pay.Customer WHERE Is_Active = 1 AND Is_Sent = 0 AND Is_Add = 1";
		$getCustomers = $this->conn->prepare($sql);
		$getCustomers->execute();
		$customers = $getCustomers->fetchAll(PDO::FETCH_ASSOC);
		$getCustomers->closeCursor();
		return $customers;
	}
	
	
	/*
	 * function: addCustomer
	 * @var $customer (array)
	 * Description: Add customer to Bill N Pay and return data for T3 or return error
	 *
	 */
	function addCustomer($customer) {
		/* xml layout ------ */
		$xml = '<?xml version="1.0"?>
<request>
  <response>
    <type>json</type>
  </response>
  <biller>
    <authenticate>
      <id>' . $this->user . '</id>
      <password>' . $this->pass . '</password>
    </authenticate>
    <customeradd>
      <id>' . $customer['Customer_Number'] . '</id>
      <active>1</active>
      <companyname>' . $customer['Company_Name'] . '</companyname>
	  <lastname>' . $customer['Last_Name'] . '</lastname>
	  <shippingaddress>
        <address1>' . $customer['Shipping_Address1'] . '</address1>
		<address2>' . $customer['Shipping_Address2'] . '</address2>
		<address3>' . $customer['Shipping_Address3'] . '</address3>
        <city>' . $customer['Shipping_City'] . '</city>
        <state>' . $customer['Shipping_State'] . '</state>
        <zip>' . $customer['Shipping_zip'] . '</zip>
		<country>' . $customer['Shipping_Country'] . '</country>
      </shippingaddress>
      <billingaddress>
        <address1>' . $customer['Billing_Address1'] . '</address1>
		<address2>' . $customer['Billing_Address2'] . '</address2>
		<address3>' . $customer['Billing_Address3'] . '</address3>
        <city>' . $customer['Billing_City'] . '</city>
        <state>' . $customer['Billing_State'] . '</state>
        <zip>' . $customer['Billing_zip'] . '</zip>
		<country>' . $customer['Billing_Country'] . '</country>
      </billingaddress>
	  <phone>' . $customer['Phone'] . '</phone>
	  <altphone>' . $customer['Alt_Phone'] . '</altphone>
	  <fax>' . $customer['Fax'] . '</fax>
	  <email><![CDATA[' . $customer['Email'] . ']]></email>
	  <parent>
		<id>' . $customer['Parent_Customer_Number'] . '</id>
	  </parent>
      <accountingaccountnumber>' . $customer['Accounting_Account_Number'] . '</accountingaccountnumber>
	  <emaildelivery>
		<optoutinvoicenotify>0</optoutinvoicenotify>
		<optoutpaymentconfirmation>0</optoutpaymentconfirmation>
		<optoutpaymentduereminder>0</optoutpaymentduereminder>
		<optoutpaymetoverduereminder>0</optoutpaymetoverduereminder>
		<optoutstatement>0</optoutstatement>
		<optoutautopayreminder>0</optoutautopayreminder>
		<optoutpayccexpiration>0</optoutpayccexpiration>
		<optoutpaymentplanreminder>0</optoutpaymentplanreminder>
	  </emaildelivery>
    </customeradd>
  </biller>
</request>'; //print_r($xml); exit;
		/* ----- end xml layout */
		
		$response = \Httpful\Request::post($this->uri)
			->body($xml)
			->sendsXml()
			->expectsJson()
			//->autoParse(false)
			->send();
		//print_r($response);
		$body = json_decode($response, true);
		// check for error
		if (isset($body['biller']['customeradd']['error'])) {
			$sql = "UPDATE " . $this->db . ".Bill_and_Pay.Customer SET Is_Active = 0 WHERE Customer_ID=" . $customer['Customer_ID'];
			$this->conn->exec('SET ANSI_PADDING ON');
			$prepare = $this->conn->prepare($sql);
			$prepare->execute();
			//print_r($prepare->errorInfo());
			$prepare->closeCursor();
			// log event
			$this->logEvent($xml, $response, $body, $customer['Customer_ID'], "", $body['biller']['customeradd']['error']['number'], 'addCustomer', 0, 1);
		} else { // we have a successful addition
			// print_r("we are setting this customer");
			$this->conn->exec('SET ANSI_WARNINGS OFF');
			$this->conn->exec('SET ANSI_PADDING ON');
			$params = array(
				1, // set is_sent
				0, // set is_error
				$body['biller']['customeradd']['internalid'] // set customer_id/internal_id
			);
			$sql = "UPDATE " . $this->db . ".Bill_and_Pay.Customer SET Is_Sent = ?, Is_Error = ?, Internal_ID = ? WHERE Customer_ID=" . $customer['Customer_ID'];
			
			$prepare = $this->conn->prepare($sql);
			$prepare->execute($params);
			//print_r($prepare->errorInfo());
			$prepare->closeCursor();
			// log event
			$this->logEvent($xml, $response, $body, $customer['Customer_ID'], "", "null", 'addCustomer', 1, 0);
		}
		//print_r($body); //exit;
	}
	
	function getInvoices() {
		$sql = "SELECT * FROM " . $this->db . ".Bill_and_Pay.Invoice_Header WHERE Is_Active = 1 AND Is_Sent = 0";
		$getInvoices = $this->conn->prepare($sql);
		$getInvoices->execute();
		$invoices = $getInvoices->fetchAll(PDO::FETCH_ASSOC);
		$getInvoices->closeCursor();
		$fullInvoices = array();
		foreach ($invoices as $inv) {
			$temp = $this->prepareInvoice($inv);
			$dsql = "SELECT * FROM " . $this->db . ".Bill_and_Pay.Invoice_Detail WHERE Invoice_Header_ID = " . $inv['Invoice_Header_ID'] . " ORDER BY Line_ID ASC";
			$getInvoiceDetail = $this->conn->prepare($dsql);
			$getInvoiceDetail->execute();
			$details = $getInvoiceDetail->fetchAll(PDO::FETCH_ASSOC);
			if ($details && !empty($details)) {
				foreach ($details as $det) {
					$temp['lineitem'][] = $this->prepareLineItems($det);
				}
			}
			$fullInvoices[] = $temp;
		}
		return $fullInvoices;
	}
	
	function prepareInvoice($inv) {
		$tempArr = array(
			"Invoice_Header_ID" => $inv['Invoice_Header_ID'],
			"id" => $inv['Invoice_Number'],
			"customer" => array("id" => $inv['Customer_Number']),
			"createddate" => $inv['Created_Date'],
			"number" => $inv['Invoice_Number'],
			"ponumber" => "",
			"duedate" => $inv['Due_Date'],
			"startdate" => $inv['Start_Date'],
			"enddate" => $inv['End_Date'],
			"shipdate" => $inv['Ship_Date'],
			"salestax" => $inv['Sales_Tax'],
			"terms" => $inv['Terms'],
			"billingaddress" => array(
					"address1" => $inv['Billing_Address1'],
					"address2" => $inv['Billing_Address2'],
					"address3" => $inv['Billing_Address3'],
					"city" => $inv['Billing_City'],
					"state" => $inv['Billing_State'],
					"zip" => $inv['Billing_zip']
				),
			"shippingaddress" => array(
					"address1" => $inv['Shipping_Address1'],
					"address2" => $inv['Shipping_Address2'],
					"address3" => $inv['Shipping_Address3'],
					"city" => $inv['Shipping_City'],
					"state" => $inv['Shipping_State'],
					"zip" => $inv['Shipping_zip']
				),
			"lineitem" => array(),
			"salesrep" => array("name" => $inv['Sales_Rep']),
			"memo" => $inv['Memo']
		);
		return $tempArr;
	}
	
	function prepareLineItems($item) {
		$tempArr = array(
			"id" => $item['Line_ID'],
			"quantity" => $item['Quantity'],
			"rate" => $item['Rate'],
			"amount" => ($item['Rate'] * $item['Quantity']),
			"itemname" => $item['Item_Name'],
			"description" => $item['Item_Description'],
			"other1" => $item['Other_1'],
			"other2" => $item['Other_2'],
			"other3" => "",
			"servicedate" => $item['Service_Date'],
			"displayorder" => $item['Display_Order']
		);
		return $tempArr;
	}
	
	function addInvoice($xml, $invID) {
		$response = \Httpful\Request::post($this->uri)
			->body($xml)
			->sendsXml()
			->expectsJson()
			->send();
		$body = json_decode($response, true);
		// check for error
		if (isset($body['biller']['invoiceadd']['error'])) {
			$sql = "UPDATE " . $this->db . ".Bill_and_Pay.Invoice_Header SET Is_Active = 0 WHERE Invoice_Header_ID = " . $invID;
			$this->conn->exec('SET ANSI_PADDING ON');
			$prepare = $this->conn->prepare($sql);
			$prepare->execute();
			$prepare->closeCursor();
			// log event
			$this->logEvent($xml, $response, $body, "", $invID, $body['biller']['invoiceadd']['error']['number'], 'addInvoice', 0, 1);
		} else { // we have a successful addition
			$this->conn->exec('SET ANSI_WARNINGS OFF');
			$this->conn->exec('SET ANSI_PADDING ON');
			$params = array(
				1, // set is_sent
				0, // set is_error
				$body['biller']['invoiceadd']['internalid'] // set customer_id/internal_id
			);
			$sql = "UPDATE " . $this->db . ".Bill_and_Pay.Invoice_Header SET Is_Sent = ?, Is_Error = ?, Internal_ID = ? WHERE Invoice_Header_ID = " . $invID;
			
			$prepare = $this->conn->prepare($sql);
			$prepare->execute($params);
			$prepare->closeCursor();
			// log event
			$this->logEvent($xml, $response, $body, "", $invID, "null", 'addInvoice', 1, 0);
		}
		//print_r($body); //exit;
	}
	
	function getPaymentsPull() {
		$sql = "SELECT MAX(Created_Date) as cd FROM " . $this->db . ".Bill_and_Pay.Payment_Transactions WHERE Is_Processed=1 AND Is_From_BNP=1";
		$last_update = $this->conn->prepare($sql);
		$last_update->execute();
		$dt = $last_update->fetch(PDO::FETCH_ASSOC);
		$ld = $dt['cd'];
		$last_update->closeCursor();
		$bsql = "SELECT MAX(Batch_ID) as bi FROM " . $this->db . ".Bill_and_Pay.Payment_Transactions WHERE Is_Processed=1 AND Is_From_BNP=1";
		$last_batch = $this->conn->prepare($bsql);
		$last_batch->execute();
		$bt = $last_batch->fetch(PDO::FETCH_ASSOC);
		$bi = $bt['bi'];
		$last_batch->closeCursor();
		$this->batchID = $bi+1;
		
		$xml = '<?xml version="1.0"?>
<request>
  <response>
    <type>json</type>
  </response>
  <biller>
    <authenticate>
      <id>' . $this->user . '</id>
      <password>' . $this->pass . '</password>
    </authenticate>
    <paymentinfo>
      <where>
        <updatedsince>' . $ld . '</updatedsince>
      </where>
    </paymentinfo>
  </biller>
</request>
		';
		$response = \Httpful\Request::post($this->uri)
			->body($xml)
			->sendsXml()
			->expectsJson()
			->send();
		$transactions = json_decode($response, true);
		//echo print_r($transactions['biller']);
		if (($transactions && isset($transactions['biller']['paymentinfo']['payment']))) {
			$this->logEvent($xml, $response, $transactions, "", "", "null", 'getPayments', 0, 1);
			return $transactions['biller']['paymentinfo']['payment'];
		} else {
			$this->logEvent($xml, $response, $transactions, "", "", "null", 'getPayments', 1, 0);
			return false;
		}
		
	}
	
	function addPaymentsFromPull($payment) {
		$params = array(
			$payment['internalid'],
			$this->batchID,
			date("Y-m-d\TH:i:s", strtotime($payment['updateddatetime'])),
			$payment['creditmemo'],
			$payment['customer']['internalid'],
			$payment['customer']['id'],
			$payment['customer']['accountnumber'],
			$payment['amount'],
			date("Y-m-d", strtotime($payment['date'])),
			$payment['referencenumber'],
			$payment['method'],
			$payment['unappliedbalance'],
			$payment['appliedto']['invoice'][0]['internalid'],
			$payment['appliedto']['invoice'][0]['id'],
			$payment['appliedto']['invoice'][0]['number'],
			$payment['appliedto']['invoice'][0]['amount']
		);
		$sql = "INSERT INTO " . $this->db . ".Bill_and_Pay.Payment_Transactions (Batch_Internal_ID, Batch_ID, Batch_Updated_Date, Batch_Is_Credit_Memo, Customer_Internal_ID, Customer_Number, Customer_Account_Number, Payment_Amount, Payment_Date, Payment_Reference_Number, Payment_Method, Unapplied_Balance, Invoice_Internal_ID, Invoice_ID, Invoice_Number, Invoice_Amount) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		
		$prepare = $this->conn->prepare($sql);
		$prepare->execute($params);
		//$prepare->debugDumpParams();
		$prepare->closeCursor();
	}
	
	function getPaymentsToAdd() {
		$sql = "SELECT * FROM " . $this->db . ".Bill_and_Pay.Payment_Transactions WHERE  Is_Active = 1 AND Is_From_GP = 1 AND Is_Processed = 0";
		$getPayments = $this->conn->prepare($sql);
		$getPayments->execute();
		$payments = $getPayments->fetchAll(PDO::FETCH_ASSOC);
		$getPayments->closeCursor();
		return $payments;
	}
	
	function addPayment($payment) {
		$xml = '<?xml version="1.0"?>
<request>
    <response>
        <type>json</type>
    </response>
    <biller>
        <authenticate>
            <id>' . $this->user . '</id>
            <password>' . $this->pass . '</password>
        </authenticate>
        <paymentadd>
            <id>' . $payment['Payment_Transaction_ID'] . '</id>
            <customer>
                <id>' . $payment['Customer_Number'] . '</id>
            </customer>
            <date>' . $payment['Payment_Date'] . '</date>
            <amount>' . $payment['Payment_Amount'] . '</amount>
            <referencenumber>' . $payment['Invoice_ID'] . '</referencenumber>
            <method>' . $payment['Paymet_Method'] . '</method>
            <appliedto>
                <invoice>
                    <id>' . $payment['Invoice_ID'] . '</id>
                    <amount>' . $payment['Payment_Amount'] . '</amount>
                </invoice>
            </appliedto>
        </paymentadd>
    </biller>
</request>';

		$response = \Httpful\Request::post($this->uri)
			->body($xml)
			->sendsXml()
			->expectsJson()
			//->autoParse(false)
			->send();
		//print_r($response);
		$body = json_decode($response, true);
		// check for error
		if (isset($body['biller']['paymentadd']['error'])) {
			//echo 'error for transaction: ' . $payment['Payment_Transaction_ID'] . PHP_EOL;
			$sql = "UPDATE " . $this->db . ".Bill_and_Pay.Payment_Transactions SET Is_Processed = 1, Is_Sent = 1 WHERE Payment_Transaction_ID=" . $payment['Payment_Transaction_ID'];
			$this->conn->exec('SET ANSI_PADDING ON');
			$this->conn->exec('SET ANSI_WARNINGS OFF');
			$prepare = $this->conn->prepare($sql);
			$prepare->execute();
			$prepare->closeCursor();
			// log event
			$this->logEvent($xml, $response, $body, $payment['Payment_Transaction_ID'], "", $body['biller']['paymentadd']['error']['number'], 'addPayment', 0, 1);
		} else { // we have a successful addition
			//echo 'transaction added: ' . $payment['Payment_Transaction_ID'] . PHP_EOL;
			$this->conn->exec('SET ANSI_WARNINGS OFF');
			$this->conn->exec('SET ANSI_PADDING ON');
			$params = array(
				1, // set is_processed
				1, // set is_sent
				0, // set is_error
				$body['biller']['paymentadd']['internalid'] // set internal_id
			);
			$sql = "UPDATE " . $this->db . ".Bill_and_Pay.Payment_Transactions SET Is_Processed = ?, Is_Sent = ?, Is_Error = ?, Invoice_Internal_ID = ? WHERE Payment_Transaction_ID=" . $payment['Payment_Transaction_ID'];
			
			$prepare = $this->conn->prepare($sql);
			$prepare->execute($params);
			$prepare->closeCursor();
			// log event
			$this->logEvent($xml, $response, $body, $customer['Customer_ID'], "", "null", 'addPayment', 1, 0);
		}

	}
	
	function logEvent($xml, $response, $body, $customer_id="", $invoice_header_id="", $error_num="", $apiCall, $active=1, $error=0) {
		
		$params = array(
			$apiCall, // what call we are using ( addUser )
			$active, // did this go through? 0 if error
			$error, // was there an error? 1 if error
			$error_num, // error number from response if error, empty if not
			$xml, // sent xml
			$response, // response body
			$customer_id, // if error use system customer_id
			$invoice_header_id // if error for invoice use system invoice_header_id
		);
		$sql = "INSERT INTO " . $this->db . ".Bill_and_Pay.XML_Log 
		(API_Call, Is_Active, Is_Error, BNP_Status, Send_XML_Code, Return_XML_Code, Customer_ID, Invoice_Header_ID) 
		VALUES (?,?,?,?,?,?,?,?)
		";
		try {
			$this->conn->exec('SET ANSI_WARNINGS OFF');
			$this->conn->exec('SET ANSI_PADDING ON');
			$prepare = $this->conn->prepare($sql);
			$prepare->execute($params);
			$prepare->closeCursor();
		} 
		catch(PDOException $e)  
		{  
			die(print_r($e->getMessage()));  
		} 
		
	}
	
	
	
}
