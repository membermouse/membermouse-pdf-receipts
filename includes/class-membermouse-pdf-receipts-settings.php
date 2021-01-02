<?php
/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */
if (! defined('ABSPATH')) {
    exit();
}

/**
 * Settings class.
 */
class MemberMouse_PDF_Receipts_Settings
{
    /**
     * The single instance of MemberMouse_PDF_Receipts_Settings.
     *
     * @var object
     * @access private
     * @since 1.0.0
     */
    private static $_instance = null;

    // phpcs:ignore

    /**
     * The main plugin object.
     *
     * @var object
     * @access public
     * @since 1.0.0
     */
    public $parent = null;

    /**
     * Prefix for plugin settings.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $base = '';

    /**
     * Available settings for plugin.
     *
     * @var array
     * @access public
     * @since 1.0.0
     */
    public $settings = array();

    /**
     * Constructor function.
     *
     * @param object $parent
     *            Parent object.
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        $this->base = 'wpt_';

        // Add settings page to menu.
        add_action('admin_menu', array(
            $this,
            'add_menu_item'
        ));

        // Add settings link to plugins page.
        add_filter('plugin_action_links_' . plugin_basename($this->parent->file), array(
            $this,
            'add_settings_link'
        ));
    }

    /**
     * Add settings page to admin menu
     *
     * @return void
     */
    public function add_menu_item()
    {
        $args = $this->menu_settings();

        // Do nothing if wrong location key is set.
        if (is_array($args) && isset($args['location']) && function_exists('add_' . $args['location'] . '_page')) {
            switch ($args['location']) {
                case 'options':
                case 'submenu':
                    $page = add_submenu_page($args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function']);
                    break;
                case 'menu':
                    $page = add_menu_page($args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position']);
                    break;
                default:
                    return;
            }
        }
    }

    /**
     * Prepare default settings page arguments
     *
     * @return mixed|void
     */
    private function menu_settings()
    {
        $iconUrl = "";

        if (class_exists("MM_Utils")) {
            $iconUrl = MM_Utils::getImageUrl('mm-logo-svg-white');
        }

        return apply_filters($this->base . 'menu_settings', array(
            'location' => 'menu', // Possible settings: options, menu, submenu.
            'parent_slug' => 'admin.php',
            'page_title' => __('MemberMouse PDF Receipts', 'membermouse-pdf-receipts'),
            'menu_title' => __('MM PDF Receipts', 'membermouse-pdf-receipts'),
            'capability' => 'manage_options',
            'menu_slug' => $this->parent->_token . '_settings',
            'function' => array(
                $this,
                'settings_page'
            ),
            'icon_url' => $iconUrl,
            'position' => 4
        ));
    }

    /**
     * Add settings link to plugin list table
     *
     * @param array $links
     *            Existing links.
     * @return array Modified links.
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="admin.php?page=' . $this->parent->_token . '_settings">' . __('Configure', 'membermouse-pdf-receipts') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Load settings page content.
     *
     * @return void
     */
    public function settings_page()
    {
        // Send Test
        if (isset($_POST["mm_pdf_email_test"]) && $_POST["mm_pdf_email_test"] == "1") {
            include (plugin_dir_path(__FILE__) . 'class-membermouse-receipt.php');

            if (empty($_POST["mm_pdf_email_test_email"])) {
                $error = "Test email address required.";
            } else {
                $testEmail = $_POST["mm_pdf_email_test_email"];
                update_option("mm-pdf-email-test-email", $testEmail);
                $pdfReceiptGenerator = MemberMouse_Receipt::get_instance();
                $response = $pdfReceiptGenerator->sendTest($testEmail);

                if (MM_Response::isSuccess($response)) {
                    $error = "Test sent successfully.";
                } else {
                    $error = $response->message;
                }
            }
        }

        // Email Template
        if (isset($_POST["mm_pdf_email_template"]) && $_POST["mm_pdf_email_template"] == "1") {
            if (isset($_POST["mm_from_email"])) {
                update_option("mm-pdf-email-from", $_POST["mm_from_email"]);
            }
            
            if (isset($_POST["mm_email_cc_id"])) {
                update_option("mm-pdf-email-cc-field-id", $_POST["mm_email_cc_id"]);
            }
            
            if (isset($_POST["mm_email_subject"])) {
                update_option("mm-pdf-email-subject", stripslashes($_POST["mm_email_subject"]));
            }
            
            if (isset($_POST["mm_email_body"])) {
                update_option("mm-pdf-email-body", stripslashes($_POST["mm_email_body"]));
            }
            
            $error = "Email template saved successfully.";
        }
        
        // PDF Configuration
        if (isset($_POST["mm_pdf_configuration"]) && $_POST["mm_pdf_configuration"] == "1") {
            if (isset($_POST["mm_billing_custom_field"])) {
                update_option("mm-pdf-email-billing-custom-field-id", $_POST["mm_billing_custom_field"]);
            }
            
            if (isset($_POST["mm_business_name"])) {
                update_option("mm-pdf-business-name", stripslashes($_POST["mm_business_name"]));
            }
            
            if (isset($_POST["mm_business_address"])) {
                update_option("mm-pdf-business-address", stripslashes($_POST["mm_business_address"]));
            }
            
            if (isset($_POST["mm_business_tax_label"])) {
                update_option("mm-pdf-business-tax-label", stripslashes($_POST["mm_business_tax_label"]));
            }
            
            if (isset($_POST["mm_business_tax_id"])) {
                update_option("mm-pdf-business-tax-id", stripslashes($_POST["mm_business_tax_id"]));
            }
            
            if (isset($_POST["mm_footer_section_1"])) {
                update_option("mm-pdf-footer-section-1", stripslashes($_POST["mm_footer_section_1"]));
            }
            
            if (isset($_POST["mm_footer_section_2"])) {
                update_option("mm-pdf-footer-section-2", stripslashes($_POST["mm_footer_section_2"]));
            }
            
            $error = "PDF configuration saved successfully.";
        }

        // get data
        $billingCustomFieldId = get_option("mm-pdf-email-billing-custom-field-id", false);
        $businessName = get_option("mm-pdf-business-name", false);
        $businessAddress = get_option("mm-pdf-business-address", false);
        $businessTaxLabel = get_option("mm-pdf-business-tax-label", false);
        $businessTaxId = get_option("mm-pdf-business-tax-id", false);
        $receiptFooterSection1 = get_option("mm-pdf-footer-section-1", false);
        $receiptFooterSection2 = get_option("mm-pdf-footer-section-2", false);
        
        $emailFromId = get_option("mm-pdf-email-from", false);
        $emailCCFieldId = get_option("mm-pdf-email-cc-field-id", false);

        // set default email from ID
        if (empty($emailFromId)) {
            $dfltEmployee = MM_Employee::getDefault();
            update_option("mm-pdf-email-from", $dfltEmployee->getId());
        }
        
        $emailSubject = get_option("mm-pdf-email-subject", false);
        $emailBody = get_option("mm-pdf-email-body", false);
        $testEmail = get_option("mm-pdf-email-test-email", false);
        
        // Activate PDF Invoicing
        $mmPluginCheck = is_plugin_active("membermouse/index.php");
        $mmVersionCheck = false;
        
        if($mmPluginCheck)
        {
            $crntVersion = MemberMouse::getPluginVersion();
            $mmVersionCheck = version_compare($crntVersion, '2.4.0', '>=');
        }
        
        $emailTemplateCheck = (!empty($emailSubject) && !empty($emailBody) && !empty($emailFromId)) ? true : false;
        $pdfConfigCheck = (!empty($businessName) && !empty($businessAddress)) ? true : false;
        $pdfInvoicingActive = ($mmPluginCheck && $mmVersionCheck && $emailTemplateCheck && $pdfConfigCheck) ? true : false;
        ?>
		<script>
		function insertPDFConfigTemplate(doSubmit)
        {
        	jQuery("#mm-business-name").val("ABC Company, Inc.");
        	
        	var str = "123 Pine St.<br/>\n";
        	str += "Seattle, WA 98122<br/>\n";
        	str += "United States";
        	
        	jQuery("#mm-business-address").val(str);
        	jQuery("#mm-business-tax-label").val("Tax ID");
        	
        	str = "<p style=\"margin-bottom: 15px;\">\n";
        	str += "If you have any questions, please contact us at <a href=\"mailto:support@ourwebsite.com\">support@ourwebsite.com</a>.\n";
			str += "</p>\n";
			str += "<p>\n";
			str += "You can also self-service your account or modify your billing information by logging in at <a href=\"https://ourwebsite.com\">https://ourwebsite.com</a>.\n";
			str += "</p>";
			
        	jQuery("#mm-footer-section-1").val(str);
        	
        	str = "<p>\n";
        	str += "<strong>Thank you for choosing us to support your business!</strong>\n";
			str += "</p>";
			
        	jQuery("#mm-footer-section-2").val(str);
        	
        	if(doSubmit)
        	{
        		jQuery("#mm-pdf-config-form").submit();
        	}
        }
        
        function insertEmailTemplate(doSubmit)
        {
        	var str = "Receipt for your recent purchase (Order #[MM_Order_Data name='id'])";
        	jQuery("#mm-email-subject").val(str);
        
        	var str = "Hi [MM_Member_Data name='firstName'],\n\n";
        
        	str += "Thank you for your recent payment.\n\n";
        	
        	str += "Please find attached a PDF version of your receipt.\n\n";
        	
        	str += "My Company, LLC\n";
        	str += "123 Pine St.\n";
        	str += "Seattle, WA 98122\n\n";
        	
        	str += "Bill to:\n";
        	str += "[MM_Member_Data name='firstName'] [MM_Member_Data name='lastName']\n";
        	str += "[MM_Member_Data name='email']\n\n";
        	
        	str += "Product: [MM_Order_Data name='productName']\n";
        	str += "Total: [MM_Order_Data name='total' doFormat='true']\n\n";
        	
        	str += "If you have any questions, please reply to this email.";
        	jQuery("#mm-email-body").val(str);
        	
        	if(doSubmit)
        	{
        		jQuery("#mm-email-template-form").submit();
        	}
        }
        </script>
<style>
.mm-ui-button {
	padding: 10px 12px;
	font-size: 12px !important;
}
</style>
<div class="mm-wrap" id="<?php echo $this->parent->_token.'_settings' ?>">
	<div style="margin-bottom: 10px;">
		<img
			src="<?php echo plugins_url("../assets/images/", __FILE__)."/mm-pdf-receipts.png"; ?>"
			style="vertical-align: middle; margin-top: 20px;" />
	</div>
		
		<?php if ($mmPluginCheck && $mmVersionCheck) { ?>
		
		<div style="margin-top: 20px;">
		<div style="margin-left: 10px; width: 700px;">
			
			<!-- ACTIVATION SECTION -->
			<div class="updated" style="padding: 10px; border-left-color: #690">
				
					<div style="margin-left: 20px;">
						<?php if ($pdfInvoicingActive) { ?>
						<h3>
    						<i class="fa fa-check" style="color: #690"></i> PDF Receipts Active
    					</h3>
						
						<?php $activityLogURL = MM_ModuleUtils::getUrl(MM_MODULE_LOGS, MM_MODULE_ACTIVITY_LOG); ?>
						
						<p>An email with a PDF receipt attached will be sent to MemberMouse members when an initial or rebill payment occurs.
						All emails sent by this plugin will be logged in the MemberMouse <a href="<?php echo $activityLogURL; ?>" target="_blank">activity log</a>.</p>
						
						<form method='post'>
						<input name="mm_pdf_email_test" type="hidden" value="1" />
						<p>
							<strong>Send test to the following email address:</strong>
						</p>
						<p>
							<input name="mm_pdf_email_test_email" type="text"
								style="width: 251px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($testEmail, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="" />
						</p>

						<p>
                			<?php echo MM_Utils::getIcon('info-circle', 'yellow', '1.3em', '2px'); ?> WordPress Mail (i.e. <code>wp_mail()</code>
							) is used to send emails. Be sure it is configured correctly to
							send emails by using a plugin like <a
								href="https://wordpress.org/plugins/wp-mail-smtp/"
								target="_blank">WP Mail SMTP</a>.
						</p>
						
						<input type='submit' value='Send Test' class="mm-ui-button green" />
						</form>
						<?php } else { ?>
						<h3>
    						<i class="fa fa-times" style="color: #c00"></i> PDF Invoicing Not Active
    					</h3>
    					
    					<p style="font-size:14px;">
    						<strong>Complete the following in order to activate PDF invoicing:</strong>
    					</p>
    					
    					<p style="font-size:14px;">
    					<?php if ($pdfConfigCheck) { ?>
						<i class="fa fa-check-square-o" style="color: #690"></i>
						<?php } else { ?>
						<i class="fa fa-square-o"></i> 
						<?php } ?>
						Configure the PDF content below or <a href="javascript:insertPDFConfigTemplate(true);">use the default configuration</a>.
						</p>
						
    					<p style="font-size:14px;">
						<?php if ($emailTemplateCheck) { ?>
						<i class="fa fa-check-square-o" style="color: #690"></i>
						<?php } else { ?>
						<i class="fa fa-square-o"></i> 
						<?php } ?>
						Configure the email template below or <a href="javascript:insertEmailTemplate(true);">use the default template</a>.
						</p>
						<?php } ?>
					</div>
			</div>
			<!-- END ACTIVATION SECTION -->
			
			<!-- PDF CONFIG -->
			<div class="updated"
				style="padding: 10px; border-left-color: #066cd2">
				<form id="mm-pdf-config-form"  method='post'>
					<div style="margin-left: 20px;">
						<h2>PDF Configuration</h2>
						<input name="mm_pdf_configuration" type="hidden" value="1" />
						
						<p><a href="javascript:insertPDFConfigTemplate(false);">Insert the Default Configuration</a></p>
						
						<p><strong>Business Information</strong></p>
						<div style="margin-left:10px;">
						<p>
							<input id="mm-business-name" name="mm_business_name" type="text"
								style="width: 300px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($businessName, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="Business Name" />
						</p>
						<p>
							<?php echo MM_Utils::getIcon('info-circle', 'blue', '1.3em', '2px'); ?> <em>use <code>&lt;br/&gt;</code> tags for new lines.</em>
						</p>
						<p>
						<textarea id="mm-business-address" name="mm_business_address"
							style="width: 300px; font-family: courier; font-size: 11px;"
							rows="5"><?php echo htmlentities($businessAddress, ENT_QUOTES, 'UTF-8', true); ?></textarea>
                		</p>
						<p>
							<input id="mm-business-tax-label" name="mm_business_tax_label" type="text"
								style="width: 300px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($businessTaxLabel, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="Business Tax Label" /> (<em>optional</em>)
						</p>
						<p>
							<input id="mm-business-tax-id" name="mm_business_tax_id" type="text"
								style="width: 300px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($businessTaxId, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="Business Tax ID" /> (<em>optional</em>)
						</p>
						</div>
						
                		<div style="margin-top:20px;">
						<p><strong>Receipt Footer</strong></p>
						<div style="margin-left:10px;">
						<p>Area 1</p>
						<p>
						<textarea id="mm-footer-section-1" name="mm_footer_section_1"
							style="width: 500px; font-family: courier; font-size: 11px;"
							rows="8"><?php echo htmlentities($receiptFooterSection1, ENT_QUOTES, 'UTF-8', true); ?></textarea>
                		</p>
                		
                		<p>Area 2</p>
						<p>
						<textarea id="mm-footer-section-2" name="mm_footer_section_2"
							style="width: 500px; font-family: courier; font-size: 11px;"
							rows="6"><?php echo htmlentities($receiptFooterSection2, ENT_QUOTES, 'UTF-8', true); ?></textarea>
                		</p>
                		</div>
                		</div>
						
                		<div style="margin-top:20px;">
                		<strong>Manually Entered Billing Details</strong></div>
                		<div style="margin-left:10px;">
                		<p>You can allow the customer to manually enter their own billing details. To do this, select a custom field 
                		below that will store billing details. When generating the PDF, if the customer has entered data into this field, 
                		then it will be used instead of the Name, Email Address and Billing Address present on the order.</p>
                		<?php
                		    $customFieldsList = $this->getCustomFields("text");
                            $customFieldsURL = MM_ModuleUtils::getUrl(MM_MODULE_CHECKOUT_SETTINGS, MM_MODULE_CUSTOM_FIELDS);
                            
                            if (count($customFieldsList) > 0) {
                                $customFieldValues = MM_HtmlUtils::generateSelectionsList($customFieldsList, $billingCustomFieldId);
                        ?>
                		<p>
                			<select name="mm_billing_custom_field" class="medium-text">
                			<?php echo $customFieldValues ?>
                			</select>
                			&nbsp;
                    		<a href="<?php echo $customFieldsURL ?>" target="_blank"><?php echo _mmt("configure custom fields");?></a>
						</p>
						<p>
                			<?php echo MM_Utils::getIcon('info-circle', 'blue', '1.3em', '2px'); ?> NOTE: <em>only 'Long Text' custom fields will appear in the dropdown above</em>
						</p>
						<?php } else { ?>
						<p>
							<?php echo MM_Utils::getIcon('info-circle', 'yellow', '1.3em', '2px'); ?>
							No custom fields with the type <em>Long Text</em> have been created. 
							<a href="<?php echo $customFieldsURL ?>" target="_blank"><?php echo _mmt("Configure custom fields");?></a>.
						</p>
						<?php } ?>
						</div>
						
						<div style="margin-top:20px;">
							<input type='submit' value='Save PDF Configuration'
								class="mm-ui-button blue" />
						</div>
					</div>
				</form>
			</div>
			<!-- END PDF CONFIG -->

			<!-- EMAIL TEMPLATE -->
			<div class="updated" style="padding: 10px; border-left-color: #f80">
				<form id="mm-email-template-form" method='post'>
					<div style="margin-left: 20px;">
						<h2>Email Template</h2>
						<input name="mm_pdf_email_template" type="hidden" value="1" />
						<p><a href="javascript:insertEmailTemplate(false);">Insert the Default Email Template</a></p>
						
						<p>
		<?php echo _mmt("From"); ?>
		<select name="mm_from_email" class="medium-text">
		<?php echo MM_HtmlUtils::getEmployees($emailFromId); ?>
		</select>
		<?php
            $employeesUrl = MM_ModuleUtils::getUrl(MM_MODULE_GENERAL_SETTINGS, MM_MODULE_EMPLOYEES);
            ?>
		<a href="<?php echo $employeesUrl ?>" target="_blank"><?php echo _mmt("manage employees");?></a>
						</p>
						<p>
						<?php echo _mmt("CC"); ?>
                		<div style="margin-left:10px;">
                		<p>You can allow the customer to enter an email address to CC on PDF receipt emails. To do this, select a custom field 
                		below that will store the email address to CC.</p>
                		<?php
                		    $customFieldsList = $this->getCustomFields("input");
                            $customFieldsURL = MM_ModuleUtils::getUrl(MM_MODULE_CHECKOUT_SETTINGS, MM_MODULE_CUSTOM_FIELDS);
                            
                            if (count($customFieldsList) > 0) {
                                $customFieldValues = MM_HtmlUtils::generateSelectionsList($customFieldsList, $emailCCFieldId);
                        ?>
                		<p>
                			<select name="mm_email_cc_id" class="medium-text">
                			<?php echo $customFieldValues ?>
                			</select>
                			&nbsp;
                    		<a href="<?php echo $customFieldsURL ?>" target="_blank"><?php echo _mmt("configure custom fields");?></a>
						</p>
						<p>
                			<?php echo MM_Utils::getIcon('info-circle', 'blue', '1.3em', '2px'); ?> NOTE: <em>only 'Short Text' custom fields will appear in the dropdown above</em>
						</p>
						<?php } else { ?>
						<p>
							<?php echo MM_Utils::getIcon('info-circle', 'yellow', '1.3em', '2px'); ?>
							No custom fields with the type <em>Long Text</em> have been created. 
							<a href="<?php echo $customFieldsURL ?>" target="_blank"><?php echo _mmt("Configure custom fields");?></a>.
						</p>
						<?php } ?>
						</p>
						<p>
							<input id="mm-email-subject" name="mm_email_subject" type="text"
								style="width: 510px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($emailSubject, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="Email Subject" />
						</p>
						<p>
		<?php echo MM_SmartTagLibraryView::smartTagLibraryButtons("mm-email-body"); ?>
		&nbsp;
		<span style="font-size: 11px; color: #666666;">
		<?php
            $validSmartTags = _mmt("Only the following SmartTags can be used here") . ":\n";
            $validSmartTags .= "[MM_CorePage_Link]\n";
            $validSmartTags .= "[MM_CustomField_Data]\n";
            $validSmartTags .= "[MM_Employee_Data]\n";
            $validSmartTags .= "[MM_Member_Data]\n";
            $validSmartTags .= "[MM_Member_Decision]\n";
            $validSmartTags .= "[MM_Member_Link]\n";
            $validSmartTags .= "[MM_Order_Data]\n";
            $validSmartTags .= "[MM_Order_Decision]\n";
            $validSmartTags .= "[MM_Purchase_Link]";
            ?>
		<em><?php echo _mmt("Note: Only certain SmartTags can be used here"); ?></em><?php echo MM_Utils::getInfoIcon($validSmartTags); ?>
		</span>
						</p>
						<textarea id="mm-email-body" name="mm_email_body"
							style="width: 515px; font-family: courier; font-size: 11px;"
							rows="15"><?php echo htmlentities($emailBody, ENT_QUOTES, 'UTF-8', true); ?></textarea>
						<p>
                			<?php echo MM_Utils::getIcon('paperclip', 'gray', '1.3em', '2px'); ?> PDF receipt will be attached to the email automatically
                		</p>

						<input type='submit' value='Save Email Template'
							class="mm-ui-button orange" />
					</div>
				</form>
			</div>
			<!-- END EMAIL TEMPLATE -->

			</div>
		</div>


		<script type='text/javascript'>
        <?php if(!empty($error)){ ?>
        alert('<?php echo $error; ?>');
        <?php  } ?>
        </script>
	<?php } else { ?>
	<div class="error" style="padding: 10px; width: 600px;">
		<?php if(!$mmPluginCheck) { ?>
			The <a href="https://membermouse.com">MemberMouse plugin</a> must be
			active in order to use this plugin.
		<?php } else if(!$mmVersionCheck) { ?>
			MemberMouse 2.4.0 or above is required to use this plugin. <a href="https://hub.membermouse.com/download.php">Download the latest version</a> and 
			<a href="https://support.membermouse.com/support/solutions/articles/9000020393-manually-upgrading-membermouse" target="_blank">follow these instructions</a> to upgrade.
		<?php } ?>
		</div>
	<?php } ?>
	</div>

<?php
    }

    private function getCustomFields($type)
    {
        global $wpdb;

        $sql = "SELECT * FROM " . MM_TABLE_CUSTOM_FIELDS . " WHERE is_hidden = '0' AND type = '{$type}' ORDER BY id ASC;";
        $results = $wpdb->get_results($sql);

        $list = array();
        $list["0"] = "&mdash; not used &mdash;";
        
        if (is_array($results)) {
            foreach ($results as $row) {
                $list[$row->id] = $row->display_name;
            }
        }

        return $list;
    }

    /**
     * Main MemberMouse_PDF_Receipts_Settings Instance
     *
     * Ensures only one instance of MemberMouse_PDF_Receipts_Settings is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see MemberMouse_PDF_Receipts()
     * @param object $parent
     *            Object instance.
     * @return object MemberMouse_PDF_Receipts_Settings instance
     */
    public static function instance($parent)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }
        return self::$_instance;
    }

    // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of MemberMouse_PDF_Receipts_Settings is forbidden.')), esc_attr($this->parent->_version));
    }

    // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of MemberMouse_PDF_Receipts_Settings is forbidden.')), esc_attr($this->parent->_version));
    } // End __wakeup()
}
