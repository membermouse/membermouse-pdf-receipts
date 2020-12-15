<?php
if (! defined('ABSPATH')) {
    exit();
}

// domPDF and dependencies
require_once ('lib/vendor/autoload.php');
use Dompdf\Dompdf;

class MemberMouse_Invoice
{
    private $isTest = false;
    
    private $testEmail = "";
    
    private $eventType = "";

    private $member_id = "";

    private $fname = "";

    private $lname = "";

    private $email = "";

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
            self::$instance = new MemberMouse_Invoice();
        }
        return self::$instance;
    }

    /**
     * Initializes the plugin by setting filters and administration functions.
     */
    public function __construct()
    {
        $this->plugin_name = 'membermouse-pdf-invoices';
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
        $this->setData($data);

        $pdfPath = false;

        // Create PDF
       $pdfPath = $this->createPDF();

        if ($pdfPath !== false) {
            // Created PDF - Send Email
            $this->sendEmail($pdfPath);

            // remove file
            unlink($pdfPath);
        }
    }

    public function sendTest($toEmail)
    {
        $this->isTest = true;
        $this->testEmail = $toEmail;
        
        global $wpdb;
        
        $sql = "SELECT id FROM ".MM_TABLE_ORDERS." ORDER BY date_added DESC LIMIT 1;";
        $orderId = $wpdb->get_var($wpdb->prepare($sql));
        
        if (!is_null($orderId))
        {
            $order = new MM_Order($orderId);
            $user = new MM_User($order->getUserIdByOrderId($orderId));
            $data = MM_Event::packageOrderData($user->getId(), $order->getId());
            $this->process_payment_received($data);
        }
        else
        {
            $response = new MM_Response();
            $response->type = MM_Response::$ERROR;
            $response->message = "There must be at least one order placed in MemberMouse in order to run a test.";
            return $response;
        }
        
        return new MM_Response();
    }
    
    /**
     * Set Data
     */
    private function setData($data)
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
        $this->order_subtotal = $data['order_total'];
        $this->order_discount = $data['order_discount'];
        $this->order_total = $data['order_total'];
        $this->order_number = $data['order_number'];
        $this->order_currency = isset($data['order_currency']) ? $data['order_currency'] : "";
        
        $billingCustomFieldId = get_option("mm-pdf-email-billing-custom-field-id", false);
        
        if(!empty($billingCustomFieldId) && isset($data['cf_'.$billingCustomFieldId]))
        {
            $this->extra_info = nl2br($data['cf_'.$billingCustomFieldId]);
        }
    }

    /**
     * Create PDF
     */
    private function createPDF()
    {
        $pdfName = ($this->isTest) ? "test_billing_receipt_" : "billing_receipt_";
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
    private function generatePDFHtml()
    {
        ob_start();
        
        $businessName = get_option("mm-pdf-business-name", false);
        $businessAddress = get_option("mm-pdf-business-address", false);
        $businessTaxLabel = get_option("mm-pdf-business-tax-label", false);
        $businessTaxId = get_option("mm-pdf-business-tax-id", false);
        $invoiceFooterSection1 = get_option("mm-pdf-footer-section-1", false);
        $invoiceFooterSection2 = get_option("mm-pdf-footer-section-2", false);
        ?>
<!DOCTYPE html>
<html>

<head>
<meta charset='utf-8'>
<title><?php echo $businessName; ?> Invoice</title>
<link
	href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap"
	rel="stylesheet">
<link rel="stylesheet"
	href="<?= plugin_dir_path(__FILE__) .'css/invoice.css'; ?>">
</head>

<body>
	<div class="pdf-container">
		<div class="row title-row">
			<p>
				<?php if($this->isTest) { ?>
				<strong><span style="color:#c00">TEST INVOICE</span></strong><br/>
				<?php } ?>
				<strong><?php echo $businessName; ?></strong><br /> 
				<?php echo $businessAddress; ?><br/>
				<?php if(!empty($businessTaxId)) { ?>
				<?php echo $businessTaxLabel; ?> <?php echo $businessTaxId; ?>
				<?php } ?>
			</p>
		</div>

		<div class="row invoice-table">
			<div class="invoice-top">
				<div class="invoice-info">
					<div>
						<strong>MEMBER ID:</strong> <?= $this->member_id; ?></div>
					<br /> <br />
                <?php if($this->extra_info) : ?>
                  <div><?= $this->extra_info; ?></div>
                <?php else: ?>
                 	<div><?= $this->fname; ?> <?= $this->lname; ?></div>
					<div><?= $this->email; ?></div>
					<div><?= $this->address1; ?></div>
                	<?php if($this->address2) : ?>
                  	<div><?= $this->address2; ?></div>
                	<?php endif; ?>
                	<?php
            // 1. If "extra_info" is present, replace the name, email, and entire address with the contents of extra_info
            // 2. Only show a comma if city and state are both present. This corrects the issue where both are missing and a comma is left floating by itself
            // 3. Only show zip code if billing address is present
            ?>
                	<div><?= $this->city; ?> <?= ($this->city && $this->state)?",":""; ?> <?= $this->state; ?> <?= $this->address1?$this->zip:""; ?></div>
                <?php endif; ?>
              </div>
				<div class="invoice-date">
					<div>
						<strong>DATE PAID:</strong> <?= $this->today; ?></div>
				</div>
			</div>
            <?php if(!empty($this->order_currency)) { ?>
            <div class="invoice-top-extra">
				<p>
					<em>All prices in <?php echo $this->order_currency; ?></em>
				</p>
			</div>
			<?php } ?>
			
            <table>
				<thead>
					<tr>
						<th class="left-align">Service Description</th>
						<th class="right-align">Order Number</th>
						<th></th>
						<th class="right-align">Amount Billed</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?= $this->product_name; ?></td>
						<td class="right-align"><?= $this->order_number; ?></td>
						<td></td>
						<td class="right-align">$<?= $this->order_subtotal; ?></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
					</tr>
                <?php if($this->order_discount) : ?>
                <tr>
						<td></td>
						<td></td>
						<td class="right-align">Discount</td>
						<td class="right-align">$<?= $this->order_discount; ?></td>
					</tr>
                <?php endif; ?>
                <tr>
						<td></td>
						<td></td>
						<td class="total-paid-td right-align first"><strong>TOTAL PAID</strong></td>
						<td class="total-paid-td right-align"><strong>$<?= $this->order_total; ?></strong></td>
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

		<div class="invoice-bottom">
			<?php echo $invoiceFooterSection1; ?>
		</div>
		<div class="invoice-footer">
			<?php echo $invoiceFooterSection2; ?>
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
        $email->setAttachments(array(
            $pdfPath
        ));
        $email->setToName($this->fname);
        
        if($this->isTest)
        {
            $email->setSubject("[TEST] ".$emailSubject);
            $email->setToAddress($this->testEmail);
        }
        else 
        {   
            $email->setToAddress($this->email);
            $email->setSubject($emailSubject);
        }

        $email->send();
    }
}