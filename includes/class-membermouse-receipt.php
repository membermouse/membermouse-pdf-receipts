<?php
if (! defined('ABSPATH')) {
    exit();
}

// domPDF and dependencies
require_once ('lib/vendor/autoload.php');
use Dompdf\Dompdf;

class MemberMouse_Receipt
{
    private $isTest = false;
    private $additionalCCEmail = "";
    private $testEmail = "";
    private $eventType = "";
    private $member_id = "";
    private $fname = "";
    private $lname = "";
    private $email = "";
    private $ccEmail = "";
    private $address1 = "";
    private $address2 = "";
    private $city = "";
    private $state = "";
    private $zip = "";
    private $country = "";
    private $extra_info = "";
    private $product_name = "";
    private $order_currency = "";
    private $order_subtotal = "";
    private $order_discount = "";
    private $order_shipping = "";
    private $order_total = "";
    private $order_number = "";
    private $message = "";

    /**
     * A reference to an instance of this class.
     */
    private static $instance;

    /**
     * Returns an instance of this class.
     */
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new MemberMouse_Receipt();
        }
        return self::$instance;
    }

    /**
     * Initializes the plugin by setting filters and administration functions.
     */
    public function __construct()
    {
        $this->plugin_name = 'membermouse-pdf-receipts';
        $this->today = date('M. j, Y');
    }

    /**
     * Process Payment Received Hook
     *
     * - Check if payment_received is only triggered on initial purchase.
     * - Check if rebill is only triggered on rebill
     * - Check if rebill is triggered on declines
     */
    public function process_payment_received($data)
    {
        try { 
            // verify all required data has been configured
            $businessName = get_option("mm-pdf-business-name", false);
            $businessAddress = get_option("mm-pdf-business-address", false);
            $emailFromId = get_option("mm-pdf-email-from", false);
            $emailSubject = get_option("mm-pdf-email-subject", false);
            $emailBody = get_option("mm-pdf-email-body", false);
            
            $emailTemplateCheck = (!empty($emailSubject) && !empty($emailBody) && !empty($emailFromId)) ? true : false;
            $pdfConfigCheck = (!empty($businessName) && !empty($businessAddress)) ? true : false;
            $pdfInvoicingActive = ($emailTemplateCheck && $pdfConfigCheck) ? true : false;
            
            if($pdfInvoicingActive)
            {
                $this->setData($data);
        
                $pdfPath = false;
                $pdfPath = $this->createPDF();
        
                if ($pdfPath !== false) 
                {
                    $this->sendEmail($pdfPath);
        
                    // remove file
                    unlink($pdfPath);
                }
            }
        } catch (Error $e) {
            // PDF generation and emailing failed for some reason. Catching error so that it doesn't
            // interfere with the order process
        }
    }

    public function sendTest($toEmail)
    {
        $this->isTest = true;
        $this->testEmail = $toEmail;
        
        global $wpdb;
        
        $orderData = array();
        
        $transactionsTable = MM_TABLE_TRANSACTION_LOG;
        $ordersTable = MM_TABLE_ORDERS;
        
        $desiredTransactionTypes = implode(",",array(MM_TransactionLog::$TRANSACTION_TYPE_PAYMENT,MM_TransactionLog::$TRANSACTION_TYPE_RECURRING_PAYMENT));
        
        $masterQuery = "SELECT o.id as orderId, t.transaction_type as type, t.transaction_date as date ".
            "FROM {$transactionsTable} t ".
            "LEFT JOIN {$ordersTable} o on (t.order_id = o.id) ".
            "WHERE (t.transaction_type IN ({$desiredTransactionTypes})) ".
            "ORDER BY t.transaction_date DESC LIMIT 1";
        $results = $wpdb->get_results($masterQuery);
        
        if(count($results) > 0)
        {
            $orderId = $results[0]->orderId;
            $transactionType = $results[0]->type;
            $isRebill = ($transactionType == 4) ? true:false;
            
            $order = new MM_Order($orderId);
            
            if($order->isValid())
            {
                $orderData = MM_Event::packageOrderData($order->getCustomer()->getId(), $order->getId(), null, null, $isRebill);
                $orderData["event_type"] = ($isRebill == true) ? MM_Event::$PAYMENT_REBILL : MM_Event::$PAYMENT_RECEIVED;
                $this->process_payment_received($orderData);
            }
        }
        
        if(count($orderData) == 0)
        {
            $response = new MM_Response();
            $response->type = MM_Response::$ERROR;
            $response->message = "There must be at least one payment processed in MemberMouse in order to run a test.";
            return $response;
        }
        
        return new MM_Response();
    }
    
    public function resendReceipt($data, $additionalCCEmail)
    {   
        $this->additionalCCEmail = $additionalCCEmail;
        $this->process_payment_received($data);
        
        return new MM_Response();
    }
    
    /**
     * Set Data
     */
    protected function setData($data)
    {
        $this->eventType = $data["event_type"];
        $this->member_id = $data['member_id'];
        $this->fname = $data['first_name'];
        $this->lname = $data['last_name'];
        $this->email = $data['email'];
        $this->address1 = $data['billing_address'];
        $this->address2 = $data['billing_address2'];
        $this->city = $data['billing_city'];
        $this->state = $data['billing_state'];
        $this->zip = $data['billing_zip_code'];
        $this->country = $data['billing_country'];
        $order_products = json_decode($data['order_products'], true)[0];
        $this->product_name = $order_products['name'];
        $this->order_subtotal = $data['order_subtotal'];
        $this->order_discount = $data['order_discount'];
        $this->order_shipping = $data['order_shipping'];
        $this->order_total = $data['order_total'];
        $this->order_number = $data['order_number'];
        $this->order_currency = isset($data['order_currency']) ? $data['order_currency'] : "";
        
        $billingCustomFieldId = get_option("mm-pdf-email-billing-custom-field-id", false);
        
        if(!empty($billingCustomFieldId) && isset($data['cf_'.$billingCustomFieldId]))
        {
            $this->extra_info = nl2br($data['cf_'.$billingCustomFieldId]);
        }
        
        $emailCCFieldId = get_option("mm-pdf-email-cc-field-id", false);
        
        if(!empty($emailCCFieldId) && isset($data['cf_'.$emailCCFieldId]))
        {
            $this->ccEmail = trim($data['cf_'.$emailCCFieldId]);
            
            // validate email
            if (!filter_var($this->ccEmail, FILTER_VALIDATE_EMAIL)) 
            {
                // invalid email. clear it. 
                $this->ccEmail = "";
            }
        }
    }

    /**
     * Create PDF
     */
    private function createPDF()
    {
        $pdfName = ($this->isTest) ? "test_billing_receipt_" : "billing_receipt_";
        $pdfName .= $this->order_number."_";
        $tmp_prefix = tempnam(sys_get_temp_dir(), $pdfName);

        // php functions don't provide a way to add extension, so we use them to find a writeable dir and then
        // add the extension ourselves
        $full_path = $tmp_prefix . ".pdf";
        $renameReturnValue = rename($tmp_prefix, $full_path);
        if ($renameReturnValue) {
            $dompdf = new Dompdf();

            // Load HTML
            $dompdf->loadHtml($this->generatePDFHtml());

            // Render the HTML as PDF
            $dompdf->render();

            // Output the generated PDF to variable
            $pdf_gen = $dompdf->output();

            if (file_put_contents($full_path, $pdf_gen) !== false) {
                // Saved PDF to file
                return $full_path;
            }
        }
        return false;
    }

    /**
     * Generates PDF HTML
     */
    protected function generatePDFHtml()
    {
        ob_start();
        
        $businessName = get_option("mm-pdf-business-name", false);
        $businessAddress = get_option("mm-pdf-business-address", false);
        $businessTaxId = get_option("mm-pdf-business-tax-id", false);
        $headerImageUri = get_option("mm-pdf-header-image-uri", false);
        $headerImageAlign = get_option("mm-pdf-header-image-align", false);
        $borderColor = get_option("mm-pdf-border-color", false);
        $receiptFooterSection1 = get_option("mm-pdf-footer-section-1", false);
        $receiptFooterSection2 = get_option("mm-pdf-footer-section-2", false);
        ?>
<!DOCTYPE html>
<html>

<head>
<meta charset='utf-8'>
<title><?php echo $businessName; ?> Receipt</title>
<link
	href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap"
	rel="stylesheet">
<link rel="stylesheet"
	href="<?php echo plugin_dir_path(__FILE__) .'css/receipt.css'; ?>">
</head>

<body>
	<div class="pdf-container">
		<?php if(!empty($headerImageUri)) { ?>
		<div style="margin-bottom: 10px; <?php if(!empty($headerImageAlign)) { ?>text-align:<?php echo $headerImageAlign; ?>;<?php } ?>">
    		<img src="<?php echo $headerImageUri; ?>" alt="" />
    	</div>
    	<?php } ?>
		<div class="row title-row" <?php if(!empty($borderColor)) { ?>style="border-top: 4px solid <?php echo $borderColor; ?>"<?php } ?>>
			<p>
				<?php if($this->isTest) { ?>
				<strong><span style="color:#c00"><?php echo _mmpdft("TEST RECEIPT"); ?></span></strong><br/>
				<?php } ?>
				<strong><?php echo $businessName; ?></strong><br /> 
				<?php echo $businessAddress; ?><br/>
				<?php if(!empty($businessTaxId)) { ?>
				<?php echo _mmpdft("Tax ID"); ?>: <?php echo $businessTaxId; ?>
				<?php } ?>
			</p>
		</div>

		<div class="row receipt-table">
			<div class="receipt-top">
				<div class="receipt-info">
					<div>
						<strong><?php echo _mmpdft("MEMBER ID"); ?>:</strong> <?php echo $this->member_id; ?></div>
					<br /> <br />
                <?php if($this->extra_info) : ?>
                  <div><?php echo $this->extra_info; ?></div>
                <?php else: ?>
                 	<div><?php echo $this->fname; ?> <?php echo $this->lname; ?></div>
					<div><?php echo $this->email; ?></div>
					<div><?php echo $this->address1; ?></div>
                	<?php if($this->address2) : ?>
                  	<div><?php echo $this->address2; ?></div>
                	<?php endif; ?>
                	<div><?php echo $this->city; ?><?php echo ($this->city && $this->state)?",":""; ?> <?php echo $this->state; ?> <?php echo $this->address1?$this->zip:""; ?></div>
                <?php endif; ?>
              </div>
				<div class="receipt-date">
					<div>
						<strong><?php echo _mmpdft("DATE PAID"); ?>:</strong> <?php echo $this->today; ?></div>
				</div>
			</div>
            <?php if(!empty($this->order_currency)) { ?>
            <div class="receipt-top-extra">
				<p>
					<em><?php echo _mmpdft("All prices in"); ?> <?php echo $this->order_currency; ?></em>
				</p>
			</div>
			<?php } ?>
			
            <table>
				<thead>
					<tr>
						<th class="left-align"><?php echo _mmpdft("Service Description"); ?></th>
						<th class="right-align"><?php echo _mmpdft("Order"); ?> #</th>
						<th></th>
						    <th class="right-align"><?php echo _mmpdft("Amount"); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo $this->product_name; ?></td>
						<td class="right-align"><?php echo $this->order_number; ?></td>
						<td class="right-align"><?php echo _mmpdft("Subtotal"); ?></td>
						<td class="right-align"><?php echo _mmf($this->order_subtotal, $this->order_currency); ?></td>
					</tr>
				<?php if(isset($this->order_shipping) && floatval($this->order_shipping) > 0) : ?>
                	<tr>
						<td></td>
						<td></td>
						<td class="right-align"><?php echo _mmpdft("Shipping"); ?></td>
						<td class="right-align"><?php echo _mmf($this->order_shipping, $this->order_currency); ?></td>
					</tr>
                <?php endif; ?>
                <?php if(isset($this->order_discount) && floatval($this->order_discount) > 0) : ?>
                	<tr>
						<td></td>
						<td></td>
						<td class="right-align"><?php echo _mmpdft("Discount"); ?></td>
						<td class="right-align"><?php echo _mmf($this->order_discount, $this->order_currency); ?></td>
					</tr>
                <?php endif; ?>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
                	<tr>
						<td></td>
						<td></td>
						<td class="total-paid-td right-align first"><strong><?php echo _mmpdft("TOTAL PAID"); ?></strong></td>
						<td class="total-paid-td right-align"><strong><?php echo _mmf($this->order_total, $this->order_currency); ?></strong></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="receipt-bottom">
			<?php echo $receiptFooterSection1; ?>
		</div>
		<div class="receipt-footer" <?php if(!empty($borderColor)) { ?>style="border-bottom: 4px solid <?php echo $borderColor; ?>"<?php } ?>>
			<?php echo $receiptFooterSection2; ?>
		</div>

	</div>
</body>

</html>
<?php
        return ob_get_clean();
    }

    /**
     * Send email to member
     */
    private function sendEmail($pdfPath)
    {
        $emailFromId = get_option("mm-pdf-email-from", false);
        $emailSubject = get_option("mm-pdf-email-subject", false);
        $emailBody = get_option("mm-pdf-email-body", false);

        if (empty($emailFromId) || empty($emailSubject) || empty($emailBody)) {
            return false;
        }
        
        $fromEmployee = new MM_Employee($emailFromId);
        $user = new MM_User($this->member_id);
        $order = MM_Order::getDataByOrderNumber($this->order_number);
        $context = new MM_Context($user, $fromEmployee, $order);

        if ($this->eventType == MM_Event::$PAYMENT_REBILL) {
            $orderAttributes = array(
                "is_rebill" => true
            );
        } else {
            $orderAttributes = array(
                "is_rebill" => false
            );
        }

        $context->setOrderAttributes($orderAttributes);

        $email = new MM_Email();
        $email->setContext($context);
        $email->setBody($emailBody);
        $email->setFromName($fromEmployee->getDisplayName());
        $email->setFromAddress($fromEmployee->getEmail());
        
        if(!empty($this->ccEmail))
        {
            $email->addCC($this->ccEmail);
        }
        
        if(!empty($this->additionalCCEmail))
        {
            $email->addCC($this->additionalCCEmail);  
        }
        
        $email->setAttachments(array(
            $pdfPath
        ));
        $email->setToName($this->fname);
        
        if($this->isTest)
        {
            $email->setSubject("["._mmpdft("TEST")."] ".$emailSubject);
            $email->setToAddress($this->testEmail);
            $email->disableLogging();
        }
        else 
        {   
            $email->setToAddress($this->email);
            $email->setSubject($emailSubject);
        }

        $email->send();
    }
}
