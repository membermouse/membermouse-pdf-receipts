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

    private $error = "";
    private $mmPluginCheck = false;
    private $mmVersionCheck = false;
    private $emailTemplateCheck = false;
    private $pdfConfigCheck = false;
    private $pdfReceiptsActive = false;
    private $emailCCFieldId = "";
    private $emailFromId = "";
    private $billingCustomFieldId = "";
    private $businessName = "";
    private $businessAddress = "";
    private $borderColor = "";
    private $hederImageAlign = "";
    private $headerImageUri = "";
    private $receiptFooterSection1 = "";
    private $receiptFooterSection2 = "";

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
        add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
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
     * Load settings JS & CSS
     *
     * @return void
     */
    public function settings_assets() {
        
        // the farbtastic script & styles are needed for the color picker
        wp_enqueue_style( 'farbtastic' );
        wp_enqueue_script( 'farbtastic' );
        
        wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings.js', array( 'farbtastic', 'jquery' ), '1.0.0', true );
        wp_enqueue_script( $this->parent->_token . '-settings-js' );
    }

    /**
     * Load settings page content.
     *
     * @return void
     */
    public function settings_page()
    {
        $selectedTab = 'config';
        
        if(isset($_GET['tab']) && $_GET['tab'])
        {
            $selectedTab = $_GET['tab'];
        }
        
        // Reset Plugin
        if (isset($_POST["mm_pdf_reset_plugin"]) && $_POST["mm_pdf_reset_plugin"] == "1")
        {
            delete_option("mm-pdf-email-test-email");
            delete_option("mm-pdf-email-from");
            delete_option("mm-pdf-email-cc-field-id");
            delete_option("mm-pdf-email-subject");
            delete_option("mm-pdf-email-body");
            delete_option("mm-pdf-email-billing-custom-field-id");
            delete_option("mm-pdf-business-name");
            delete_option("mm-pdf-business-address");
            delete_option("mm-pdf-header-image-uri");
            delete_option("mm-pdf-header-image-align");
            delete_option("mm-pdf-border-color");
            delete_option("mm-pdf-business-tax-id");
            delete_option("mm-pdf-footer-section-1");
            delete_option("mm-pdf-footer-section-2");
            
            $this->error = _mmpdft("The plugin has been reset to its initial state");
        }
        
        // Send Test
        if (isset($_POST["mm_pdf_email_test"]) && $_POST["mm_pdf_email_test"] == "1") {
            include (plugin_dir_path(__FILE__) . 'class-membermouse-receipt.php');
            
            if (empty($_POST["mm_pdf_email_test_email"])) {
                $this->error = "Test email address required";
            } else {
                $this->testEmail = $_POST["mm_pdf_email_test_email"];
                update_option("mm-pdf-email-test-email", $this->testEmail);
                $pdfReceiptGenerator = MemberMouse_Receipt::get_instance();
                $response = $pdfReceiptGenerator->sendTest($this->testEmail);
                
                if (MM_Response::isSuccess($response)) {
                    $this->error = _mmpdft("Test sent successfully");
                } else {
                    $this->error = $response->message;
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
            
            $this->error = _mmpdft("Email template saved successfully");
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
            
            if (isset($_POST["mm_business_tax_id"])) {
                update_option("mm-pdf-business-tax-id", stripslashes($_POST["mm_business_tax_id"]));
            }
            
            if (isset($_POST["mm_border_color"])) {
                update_option("mm-pdf-border-color", stripslashes($_POST["mm_border_color"]));
            }
            
            if (isset($_POST["mm_header_image_uri"])) {
                update_option("mm-pdf-header-image-uri", stripslashes($_POST["mm_header_image_uri"]));
            }
            
            if (isset($_POST["mm_header_image_align"])) {
                update_option("mm-pdf-header-image-align", stripslashes($_POST["mm_header_image_align"]));
            }
            
            if (isset($_POST["mm_footer_section_1"])) {
                update_option("mm-pdf-footer-section-1", stripslashes($_POST["mm_footer_section_1"]));
            }
            
            if (isset($_POST["mm_footer_section_2"])) {
                update_option("mm-pdf-footer-section-2", stripslashes($_POST["mm_footer_section_2"]));
            }
            
            $this->error = _mmpdft("PDF configuration saved successfully");
        }
        
        // get data
        $this->billingCustomFieldId = get_option("mm-pdf-email-billing-custom-field-id", false);
        $this->businessName = get_option("mm-pdf-business-name", false);
        $this->businessAddress = get_option("mm-pdf-business-address", false);
        $this->businessTaxId = get_option("mm-pdf-business-tax-id", false);
        $this->borderColor = get_option("mm-pdf-border-color", false);
        $this->hederImageAlign = get_option("mm-pdf-header-image-align", false);
        $this->headerImageUri = get_option("mm-pdf-header-image-uri", false);
        $this->receiptFooterSection1 = get_option("mm-pdf-footer-section-1", false);
        $this->receiptFooterSection2 = get_option("mm-pdf-footer-section-2", false);
        
        $this->emailFromId = get_option("mm-pdf-email-from", false);
        $this->emailCCFieldId = get_option("mm-pdf-email-cc-field-id", false);
        
        // set default email from ID
        if (empty($this->emailFromId)) {
            $dfltEmployee = MM_Employee::getDefault();
            update_option("mm-pdf-email-from", $dfltEmployee->getId());
        }
        
        if(empty($this->hederImageAlign))
        {
            $this->hederImageAlign = "center";
            update_option("mm-pdf-header-image-align", $this->hederImageAlign);
        }
        
        if(empty($this->borderColor))
        {
            $this->borderColor = "#066cd2";
            update_option("mm-pdf-border-color", $this->borderColor);
        }
        
        $this->emailSubject = get_option("mm-pdf-email-subject", false);
        $this->emailBody = get_option("mm-pdf-email-body", false);
        $this->testEmail = get_option("mm-pdf-email-test-email", false);
        
        // Activate PDF Invoicing
        $this->mmPluginCheck = is_plugin_active("membermouse/index.php");
        $this->mmVersionCheck = false;
        $this->phpVersionCheck = ((double)phpversion() >= 7.1);
        
        if($this->mmPluginCheck)
        {
            $crntVersion = MemberMouse::getPluginVersion();
            $this->mmVersionCheck = version_compare($crntVersion, '2.4.0', '>=');
        }
        
        $this->emailTemplateCheck = (!empty($this->emailSubject) && !empty($this->emailBody) && !empty($this->emailFromId)) ? true : false;
        $this->pdfConfigCheck = (!empty($this->businessName) && !empty($this->businessAddress)) ? true : false;
        $this->pdfReceiptsActive = ($this->mmPluginCheck && $this->mmVersionCheck && $this->emailTemplateCheck && $this->pdfConfigCheck && $this->phpVersionCheck) ? true : false;        
        ?>
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
	
	<?php if ($this->mmPluginCheck && $this->mmVersionCheck && $this->phpVersionCheck) { ?>
	
	<?php if($this->pdfReceiptsActive) { ?>
	<div class="mm-navbar" style="margin-bottom:10px;"><ul>
        <li> 
		<a href="<?php echo add_query_arg(array('tab' => 'config')); ?>" class='<?php echo ($selectedTab == 'config' ? "active":""); ?>'>
			<i class="fa fa-cog"></i>
			<?php echo _mmpdft("Configure");?>
		</a>
		</li>
		<li> 
		<a href="<?php echo add_query_arg(array('tab' => 'test')); ?>" class='<?php echo ($selectedTab == 'test' ? "active":""); ?>'>
			<i class="fa fa-flask"></i>
			<?php echo _mmpdft("Send Test");?>
		</a>
		</li>
		<li> 
		<a href="<?php echo add_query_arg(array('tab' => 'resend')); ?>" class='<?php echo ($selectedTab == 'resend' ? "active":""); ?>'>
			<i class="fa fa-send"></i>
			<?php echo _mmpdft("Resend Receipt");?>
		</a>
		</li>
		<li> 
		<a href="<?php echo add_query_arg(array('tab' => 'info')); ?>" class='<?php echo ($selectedTab == 'info' ? "active":""); ?>'>
			<i class="fa fa-info-circle"></i> <?php echo _mmpdft("Info"); ?>
		</a>
		</li>
		<li> 
		<a href="https://support.membermouse.com/support/solutions/articles/9000197357-send-pdf-receipts-to-members" target="_blank">
			<i class="fa fa-life-ring"></i> <?php echo _mmpdft("Help"); ?>
		</a>
		</li>
	</ul></div>
	<?php } ?>
	
	<?php if (!$this->pdfReceiptsActive) { ?>
	<div style="margin-top: 20px;">
		<div style="margin-left: 10px; width: 700px;">
			<div class="updated" style="padding: 10px; border-left-color: #690">
				<div style="margin-left: 20px;">
					<h2>
						<i class="fa fa-times" style="color: #c00"></i> <?php echo _mmpdft("PDF Receipts Not Active"); ?>
					</h2>
					
					<p style="font-size:14px;">
						<strong><?php echo _mmpdft("Complete the following in order to activate PDF receipts"); ?>:</strong>
					</p>
					
					<p style="font-size:14px;">
					<?php if ($this->pdfConfigCheck) { ?>
					<i class="fa fa-check-square-o" style="color: #690"></i>
					<?php } else { ?>
					<i class="fa fa-square-o"></i> 
					<?php } ?>
					<?php echo _mmpdft("Configure the PDF content below"); ?> or <a href="javascript:insertPDFConfigTemplate(true);"><?php echo _mmpdft("use the default configuration"); ?></a>.
					</p>
					
					<p style="font-size:14px;">
					<?php if ($this->emailTemplateCheck) { ?>
					<i class="fa fa-check-square-o" style="color: #690"></i>
					<?php } else { ?>
					<i class="fa fa-square-o"></i> 
					<?php } ?>
					<?php echo _mmpdft("Configure the email template below"); ?> or <a href="javascript:insertEmailTemplate(true);"><?php echo _mmpdft("use the default template"); ?></a>.
					</p>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>
     
    <?php   
        switch($selectedTab)
        {
            case 'config':
                $this->render_config_tab();
                break;
                
            case 'test':
                $this->render_test_tab();
                break;
                
            case 'resend':
                $this->render_resend_tab();
                break;
                
            case 'info':
                $this->render_info_tab();
                break;
        }
        ?>
        
	<script type='text/javascript'>
    <?php if(!empty($this->error)){ ?>
    alert('<?php echo $this->error; ?>');
    <?php  } ?>
    </script>
    
    <?php } else { 
        // clear data so plugin won't run
        update_option("mm-pdf-business-name", "");
        update_option("mm-pdf-business-address", "");
    ?>
	<div class="error" style="padding: 10px; width: 600px;">
	<?php if(!$this->phpVersionCheck) { ?> 
		Your webserver is running PHP <?php echo phpversion(); ?>. This plugin requires a minimum PHP version of 7.1. Please contact your hosting provider and request to be upgraded to a more recent version of PHP.
	<?php } else if(!$this->mmPluginCheck) { ?>
		The <a href="https://membermouse.com">MemberMouse plugin</a> must be active in order to use this plugin.
	<?php } else if(!$this->mmVersionCheck) { ?>
		MemberMouse 2.4.0 or above is required to use this plugin. <a href="https://hub.membermouse.com/download.php">Download the latest version</a> and 
		<a href="https://support.membermouse.com/support/solutions/articles/9000020393-manually-upgrading-membermouse" target="_blank">follow these instructions</a> to upgrade.
	<?php } ?>
	</div>
	<?php } ?>
        
	</div>
	<?php
    }
    
    /** 
     * This function renders the configuration and testing tab content
     */
    public function render_config_tab()
    {
        ?>
		<script>
		function insertPDFConfigTemplate(doSubmit)
        {
        	jQuery("#mm-business-name").val("ABC Company, Inc.");
        	
        	var str = "123 Pine St.<br/>\n";
        	str += "Seattle, WA 98122<br/>\n";
        	str += "United States";
        	
        	jQuery("#mm-business-address").val(str);
        	
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
		
		<div style="margin-top: 20px;">
		<div style="margin-left: 10px; width: 700px;">
			
			<!-- PDF CONFIG -->
			<div class="updated"
				style="padding: 10px; border-left-color: #066cd2">
				<form id="mm-pdf-config-form"  method='post'>
					<div style="margin-left: 20px;">
						<h2><?php echo _mmpdft("PDF Receipt Configuration"); ?></h2>
						<input name="mm_pdf_configuration" type="hidden" value="1" />
						
						<p><a href="javascript:insertPDFConfigTemplate(false);"><?php echo _mmpdft("Insert the Default Configuration"); ?></a></p>
						
						<p><strong><?php echo _mmpdft("Business Information"); ?></strong></p>
						<div style="margin-left:10px;">
						<p>
							<input id="mm-business-name" name="mm_business_name" type="text"
								style="width: 300px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($this->businessName, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="<?php echo _mmpdft("Business Name"); ?>" />
						</p>
						<p>
							<?php echo MM_Utils::getIcon('info-circle', 'blue', '1.3em', '2px'); ?> <em>use <code>&lt;br/&gt;</code> tags for new lines.</em>
						</p>
						<p>
						<textarea id="mm-business-address" name="mm_business_address"
							style="width: 300px; font-family: courier; font-size: 11px;"
							rows="5"><?php echo htmlentities($this->businessAddress, ENT_QUOTES, 'UTF-8', true); ?></textarea>
                		</p>
						<p>
							<input id="mm-business-tax-id" name="mm_business_tax_id" type="text"
								style="width: 300px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($this->businessTaxId, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="<?php echo _mmpdft("Business Tax ID"); ?>" /> (<em>optional</em>)
						</p>
						</div>
						
						<div style="margin-top:20px;">
						<p><strong><?php echo _mmpdft("Design Settings"); ?></strong></p>
						<div style="margin-left:10px;">
						<p><?php echo _mmpdft("Header Logo/Image"); ?></p>
						<div style="margin-left:10px;">
						<p>
						<a href="https://ezgif.com/image-to-datauri" target="_blank">Generate a Data URI</a> for your logo and paste it below.<br/>
						It starts with <code>data:image</code>. Don't include any HTML tags. <a href="javascript:jQuery('#mm-pdf-logo-instructions').toggle()">view instructions</a>
						</p>
						<div id="mm-pdf-logo-instructions" style="margin-bottom: 10px; display:none;">
                    		<img
                    			src="<?php echo plugins_url("../assets/images/", __FILE__)."/pdf-logo-instructions.png"; ?>"
                    			style="width:600px; vertical-align: middle;" />
                    	</div>
						<textarea id="mm-header-image-uri" name="mm_header_image_uri"
							style="width: 500px; font-family: courier; font-size: 11px;"
							rows="4"><?php echo htmlentities($this->headerImageUri, ENT_QUOTES, 'UTF-8', true); ?></textarea>
						<?php if(!empty($this->headerImageUri)) { ?>
                        <div style="margin-top:10px; margin-bottom: 10px;">
                        	Preview:<br/>
                        	<img src="<?php echo $this->headerImageUri; ?>" alt="" />
                        </div>
                        <?php } ?>
                        </div>
                        
                        <p><?php echo _mmpdft("Header Logo/Image Alignment"); ?></p>
                        <div style="margin-bottom:20px;">
                        <input type="radio" id="mm-header-image-align-left" name="mm_header_image_align" value="left" <?php if($this->hederImageAlign == "left") { echo "checked"; }?>>
                        <label for="male">Left</label>
                        &nbsp;
                        <input type="radio" id="mm-header-image-align-center" name="mm_header_image_align" value="center" <?php if($this->hederImageAlign == "center") { echo "checked"; }?>>
                        <label for="female">Center</label>
                        &nbsp;
                        <input type="radio" id="mm-header-image-align-right" name="mm_header_image_align" value="right" <?php if($this->hederImageAlign == "right") { echo "checked"; }?>>
                        <label for="other">Right</label>
                        </div>
                        
                        <p><?php echo _mmpdft("Border Color"); ?></p>
						
						<div class="color-picker" style="position:relative;">
                			<input type="text" name="mm_border_color" class="color" value="<?php esc_attr_e( $this->borderColor ); ?>" />
                			<div style="position:absolute;background:#FFF;z-index:99;border-radius:100%;" class="colorpicker"></div>
                		</div>
                		</div>
                		</div>
						
                		<div style="margin-top:20px;">
						<p><strong><?php echo _mmpdft("Receipt Footer"); ?></strong></p>
						<div style="margin-left:10px;">
						<p><?php echo _mmpdft("Area"); ?> 1</p>
						<p>
						<textarea id="mm-footer-section-1" name="mm_footer_section_1"
							style="width: 500px; font-family: courier; font-size: 11px;"
							rows="8"><?php echo htmlentities($this->receiptFooterSection1, ENT_QUOTES, 'UTF-8', true); ?></textarea>
                		</p>
                		
                		<p><?php echo _mmpdft("Area"); ?> 2</p>
						<p>
						<textarea id="mm-footer-section-2" name="mm_footer_section_2"
							style="width: 500px; font-family: courier; font-size: 11px;"
							rows="6"><?php echo htmlentities($this->receiptFooterSection2, ENT_QUOTES, 'UTF-8', true); ?></textarea>
                		</p>
                		</div>
                		</div>
						
                		<div style="margin-top:20px;">
                		<strong><?php echo _mmpdft("Manually Entered Billing Details"); ?></strong></div>
                		<div style="margin-left:10px;">
                		<p><?php echo _mmpdft("You can allow the customer to manually enter their own billing details. To do this, select a custom field 
                		below that will store billing details. When generating the PDF, if the customer has entered data into this field, 
                		then it will be used instead of the Name, Email Address and Billing Address present on the order."); ?></p>
                		<?php
                		    $customFieldsList = $this->getCustomFields("text");
                            $customFieldsURL = MM_ModuleUtils::getUrl(MM_MODULE_CHECKOUT_SETTINGS, MM_MODULE_CUSTOM_FIELDS);
                            
                            if (count($customFieldsList) > 0) {
                                $customFieldValues = MM_HtmlUtils::generateSelectionsList($customFieldsList, $this->billingCustomFieldId);
                        ?>
                		<p>
                			<select name="mm_billing_custom_field" class="medium-text">
                			<?php echo $customFieldValues ?>
                			</select>
                			&nbsp;
                    		<a href="<?php echo $customFieldsURL ?>" target="_blank"><?php echo _mmpdft("configure custom fields"); ?></a>
						</p>
						<p>
                			<?php echo MM_Utils::getIcon('info-circle', 'blue', '1.3em', '2px'); ?> NOTE: <em><?php echo _mmpdft("only"); ?> 'Long Text' <?php echo _mmpdft("custom fields will appear in the dropdown above"); ?></em>
						</p>
						<?php } else { ?>
						<p>
							<?php echo MM_Utils::getIcon('info-circle', 'yellow', '1.3em', '2px'); ?>
							<?php echo _mmpdft("No custom fields with the type <em>Long Text</em> have been created."); ?> 
							<a href="<?php echo $customFieldsURL ?>" target="_blank"><?php echo _mmpdft("Configure custom fields");?></a>.
						</p>
						<?php } ?>
						</div>
						
						<div style="margin-top:20px;">
							<input type='submit' value='<?php echo _mmpdft("Save PDF Configuration"); ?>'
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
						<h2><?php echo _mmpdft("Email Template Configuration"); ?></h2>
						<input name="mm_pdf_email_template" type="hidden" value="1" />
						<p><a href="javascript:insertEmailTemplate(false);"><?php echo _mmpdft("Insert the Default Email Template"); ?></a></p>
						
						<p>
		<?php echo _mmpdft("From"); ?>
		<select name="mm_from_email" class="medium-text">
		<?php echo MM_HtmlUtils::getEmployees($this->emailFromId); ?>
		</select>
		<?php
            $employeesUrl = MM_ModuleUtils::getUrl(MM_MODULE_GENERAL_SETTINGS, MM_MODULE_EMPLOYEES);
            ?>
		<a href="<?php echo $employeesUrl ?>" target="_blank"><?php echo _mmpdft("manage employees");?></a>
						</p>
						<p>
						<?php echo _mmpdft("CC"); ?>
                		<div style="margin-left:10px;">
                		<p><?php echo _mmpdft("You can allow the customer to enter an email address to CC on PDF receipt emails. To do this, select a custom field 
                		below that will store the email address to CC."); ?></p>
                		<?php
                		    $customFieldsList = $this->getCustomFields("input");
                            $customFieldsURL = MM_ModuleUtils::getUrl(MM_MODULE_CHECKOUT_SETTINGS, MM_MODULE_CUSTOM_FIELDS);
                            
                            if (count($customFieldsList) > 0) {
                                $customFieldValues = MM_HtmlUtils::generateSelectionsList($customFieldsList, $this->emailCCFieldId);
                        ?>
                		<p>
                			<select name="mm_email_cc_id" class="medium-text">
                			<?php echo $customFieldValues ?>
                			</select>
                			&nbsp;
                    		<a href="<?php echo $customFieldsURL ?>" target="_blank"><?php echo _mmpdft("configure custom fields");?></a>
						</p>
						<p>
                			<?php echo MM_Utils::getIcon('info-circle', 'blue', '1.3em', '2px'); ?> NOTE: <em><?php echo _mmpdft("only"); ?> 'Short Text' <?php echo _mmpdft("custom fields will appear in the dropdown above"); ?></em>
						</p>
						<?php } else { ?>
						<p>
							<?php echo MM_Utils::getIcon('info-circle', 'yellow', '1.3em', '2px'); ?>
							<?php echo _mmpdft("No custom fields with the type <em>Short Text</em> have been created."); ?>
							<a href="<?php echo $customFieldsURL ?>" target="_blank"><?php echo _mmpdft("Configure custom fields");?></a>.
						</p>
						<?php } ?>
						</p>
						<p>
							<input id="mm-email-subject" name="mm_email_subject" type="text"
								style="width: 510px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($this->emailSubject, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="<?php echo _mmpdft("Email Subject"); ?>" />
						</p>
						<p>
		<?php echo MM_SmartTagLibraryView::smartTagLibraryButtons("mm-email-body"); ?>
		&nbsp;
		<span style="font-size: 11px; color: #666666;">
		<?php
            $validSmartTags = _mmpdft("Only the following SmartTags can be used here") . ":\n";
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
		<em><?php echo _mmpdft("Note: Only certain SmartTags can be used here"); ?></em><?php echo MM_Utils::getInfoIcon($validSmartTags); ?>
		</span>
						</p>
						<textarea id="mm-email-body" name="mm_email_body"
							style="width: 515px; font-family: courier; font-size: 11px;"
							rows="15"><?php echo htmlentities($this->emailBody, ENT_QUOTES, 'UTF-8', true); ?></textarea>
						<p>
                			<?php echo MM_Utils::getIcon('paperclip', 'gray', '1.3em', '2px'); ?> <?php echo _mmpdft("PDF receipt will be attached to the email automatically"); ?>
                		</p>

						<input type='submit' value='<?php echo _mmpdft("Save Email Template"); ?>'
							class="mm-ui-button orange" />
					</div>
				</form>
			</div>
			<!-- END EMAIL TEMPLATE -->

			</div>
		</div>
	<?php
    }
    
    /**
     * This function renders the send test tab content
     */
    public function render_test_tab()
    {
        ?>
		<div style="margin-top: 20px;">
		<div style="margin-left: 10px; width: 700px;">
			
			<!-- TESTING SECTION -->
			<div class="updated" style="padding: 10px; border-left-color: #066cd2">
				
					<div style="margin-left: 20px;">
						<form method='post'>
						<input name="mm_pdf_email_test" type="hidden" value="1" />
						<p>
							<strong><?php echo _mmpdft("Send test to the following email address"); ?>:</strong>
						</p>
						<p>
							<input name="mm_pdf_email_test_email" type="text"
								style="width: 251px; font-family: courier; font-size: 11px;"
								value="<?php echo htmlentities($this->testEmail, ENT_COMPAT | ENT_HTML401, "UTF-8"); ?>"
								placeholder="<?php echo _mmpdft("Enter email address"); ?>" />
						</p>
						<p>
                			<?php echo MM_Utils::getIcon('info-circle', 'yellow', '1.3em', '2px'); ?> WordPress Mail (i.e. <code>wp_mail()</code>
                			) is used to send emails. Be sure it is configured correctly by using a plugin like <a
                				href="https://wordpress.org/plugins/wp-mail-smtp/"
                				target="_blank">WP Mail SMTP</a>.
                		</p>
						<input type='submit' value='Send Test' class="mm-ui-button blue" />
						</form>
					</div>
			</div>
			<!-- END TESTING SECTION -->

			</div>
		</div>
	<?php
    }
    
    /**
     * This function renders the resend receipt tab content
     */
    public function render_resend_tab()
    {
        $order = null;
        $orderNumber = "";
        $crntStep = 1;
        $orderData = array();
        
        // Resend receipt step 1
        if (isset($_POST["mm_pdf_resend_email"]) && $_POST["mm_pdf_resend_email"] == "1") 
        {
            if (empty($_POST["mm_pdf_resend_order_num"])) {
                $this->error = _mmpdft("Order number required");
            } 
            else 
            {
                $orderNumber = $_POST["mm_pdf_resend_order_num"];
                $orderData = $this->get_order_data($orderNumber);
                
                if(count($orderData) == 0)
                {
                    $this->error = $orderNumber." "._mmpdft("is not a valid order number");
                }
                else 
                {   
                    $crntStep = 2;
                }
            }
        }
        
        if (isset($_POST["mm_pdf_resend_email"]) && $_POST["mm_pdf_resend_email"] == "2") {
            include (plugin_dir_path(__FILE__) . 'class-membermouse-receipt.php');
            
            if (empty($_POST["mm_pdf_resend_order_num"])) 
            {
                $this->error = _mmpdft("Order number required");
            } 
            else 
            {
                $orderNumber = $_POST["mm_pdf_resend_order_num"];
                $orderData = $this->get_order_data($orderNumber);
                
                if (count($orderData) > 0)
                {
                    $addlCCEmail = "";
                    
                    if(!empty($_POST["mm_pdf_addl_cc_email"]))
                    {
                        $addlCCEmail = $_POST["mm_pdf_addl_cc_email"];
                    }
                    
                    $pdfReceiptGenerator = MemberMouse_Receipt::get_instance();
                    $response = $pdfReceiptGenerator->resendReceipt($orderData, $addlCCEmail);
                    
                    if (MM_Response::isSuccess($response)) {
                        $this->error = "Receipt sent successfully";
                    } else {
                        $this->error = $response->message;
                    }
                }
                else
                {
                    $this->error = $orderNumber." "._mmpdft("is not a valid order number");
                }
            }
        }
        ?>
		
		<div style="margin-top: 20px;">
		<div style="margin-left: 10px; width: 700px;">
			
			<div class="updated" style="padding: 10px; border-left-color: #066cd2">
				
					<div style="margin-left: 20px; <?php echo (intval($crntStep) == 1) ? '':'display: none;'; ?>">
						<form method='post'>
						<input name="mm_pdf_resend_email" type="hidden" value="1" />
						<p>
							<a href="<?php echo MM_ModuleUtils::getUrl(MM_MODULE_MANAGE_TRANSACTIONS); ?>" target="_blank" class="mm-ui-button" style="padding:5px; font-size:14px;"><i class="fa fa-search"></i> <?php echo _mmpdft("Lookup Order #");?></a>
						</p>
						<p>
							<strong><?php echo _mmpdft("Enter order number to resend receipt for"); ?>:</strong>
						</p>
						<p>
							<input id="mm_pdf_resend_order_num" name="mm_pdf_resend_order_num" type="text"
								style="width: 100px; font-family: courier; font-size: 11px;" placeholder="<?php echo _mmpdft("Order"); ?> #" />
						</p>
						<p>
							<input type='submit' value='<?php echo _mmpdft("Preview"); ?>' class="mm-ui-button blue" />
						</p>
						</form>
					</div>
					
					<div style="margin-left: 20px; <?php echo (intval($crntStep) == 2) ? '':'display: none;'; ?>">
						<form method='post'>
						<input name="mm_pdf_resend_email" type="hidden" value="2" />
						<input name="mm_pdf_resend_order_num" type="hidden" value="<?php echo $orderNumber; ?>" />
						<p>
							<a href="<?php echo add_query_arg(array('tab' => 'resend')); ?>" class="mm-ui-button" style="padding:5px; font-size:14px;"><?php echo _mmpdft("Start Over");?></a>
						</p>
						<?php 
						if(count($orderData) > 0) { 
						    $ccEmail = "";
						    
						    $emailCCFieldId = get_option("mm-pdf-email-cc-field-id", false);
						    
						    if(!empty($emailCCFieldId) && isset($orderData['cf_'.$emailCCFieldId]))
						    {
						        $ccEmail = trim($orderData['cf_'.$emailCCFieldId]);
						        
						        // validate email
						        if (!filter_var($ccEmail, FILTER_VALIDATE_EMAIL))
						        {
						            // invalid email. clear it.
						            $ccEmail = "";
						        }
						    }
						?>
						<p>
							<strong><?php echo _mmpdft("Recipient Information"); ?></strong><br/>
							<?php echo _mmpdft("Name"); ?>: <?php echo $orderData['first_name']." ".$orderData['last_name']; ?><br/>
							<?php echo _mmpdft("To"); ?>: <?php echo $orderData['email']; ?><br/>
							<?php echo _mmpdft("CC"); ?>: <?php if(!empty($ccEmail)) { echo $ccEmail."<br/>"; } ?>
						</p>
						<p>
							<input name="mm_pdf_addl_cc_email" type="text" style="width: 251px; font-family: courier; 
							     font-size: 11px;" placeholder="<?php echo _mmpdft("Additional CC Email Address"); ?>" />
						</p>
						<p>
							<strong><?php echo _mmpdft("Order Information"); ?></strong><br/>
							<?php echo _mmpdft("Order"); ?> #: <?php echo $orderData['order_number']; ?><br/>
							<?php echo _mmpdft("Product"); ?>: <?php echo json_decode($orderData['order_products'], true)[0]['name'] ?><br/>
							<?php echo _mmpdft("Order Total"); ?>: <?php echo _mmf($orderData["order_total"], $orderData["order_currency"]); ?>
						</p>
						<p>
							<input type='submit' value='<?php echo _mmpdft("Confirm & Send Receipt"); ?>' class="mm-ui-button blue" />
						</p>
						<?php } else { ?>
						<p>
							<?php echo _mmpdft("No order specified. Click above to start over."); ?>
						</p>
						<?php } ?>
						</form>
					</div>
				</div>
			</div>
		</div>
		
		<script>
			jQuery("#mm_pdf_resend_order_num").focus();
		</script>
	<?php 
    }
    
    private function get_order_data($orderNumber)
    {
        global $wpdb;
        
        $orderData = array();
        
        $transactionsTable = MM_TABLE_TRANSACTION_LOG;
        $ordersTable = MM_TABLE_ORDERS;
        
        $desiredTransactionTypes = implode(",",array(MM_TransactionLog::$TRANSACTION_TYPE_PAYMENT,MM_TransactionLog::$TRANSACTION_TYPE_RECURRING_PAYMENT));
        
        $masterQuery = "SELECT o.id as orderId, t.transaction_type as type, t.transaction_date as date ".
            "FROM {$transactionsTable} t ".
            "LEFT JOIN {$ordersTable} o on (t.order_id = o.id) ".
            "WHERE (t.transaction_type IN ({$desiredTransactionTypes})) AND (o.order_number = %s) ".
            "ORDER BY t.transaction_date DESC";
        $masterQuery = $wpdb->prepare($masterQuery,$orderNumber);
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
            }
        }
        
        return $orderData;
    }
    
    /**
     * This function renders the info tab content
     */
    public function render_info_tab()
    {
        if ($this->pdfReceiptsActive) 
        {
        ?>
        <div style="margin-top: 20px;">
        <div style="margin-left: 10px; width: 700px;">
        <div class="updated" style="padding: 10px; border-left-color: #690">
        <div style="margin-left: 20px;">
			<h2>
				<i class="fa fa-check" style="color: #690"></i> <?php echo _mmpdft("PDF Receipts Active"); ?>
			</h2>
			
			<?php $activityLogURL = MM_ModuleUtils::getUrl(MM_MODULE_LOGS, MM_MODULE_ACTIVITY_LOG); ?>
			
			<p><?php echo _mmpdft("An email with a PDF receipt attached will be sent to MemberMouse members when an initial or rebill payment occurs.
			All emails sent by this plugin will be logged in the MemberMouse"); ?> <a href="<?php echo $activityLogURL; ?>" target="_blank">activity log</a>.</p>
			
			<p>
    			<?php echo MM_Utils::getIcon('info-circle', 'yellow', '1.3em', '2px'); ?> WordPress Mail (i.e. <code>wp_mail()</code>
				) is used to send emails. Be sure it is configured correctly by using a plugin like <a
					href="https://wordpress.org/plugins/wp-mail-smtp/"
					target="_blank">WP Mail SMTP</a>.
			</p>
			
			<script>
            function confirmReset() {
              if (confirm("<?php echo _mmpdft("Are you sure you want to reset the plugin to its initial state?"); ?>")) {
                jQuery("#mm_pdf_reset_plugin_form").submit();
              }
            }
            </script>
			<h3><?php echo _mmpdft("Reset Plugin to Initial State"); ?></h3>
			<p>
			<?php echo _mmpdft("Click the button below to reset the plugin to its initial state. This will delete the current configuration settings."); ?>
			</p>
			<form id="mm_pdf_reset_plugin_form" method='post' action='<?php echo remove_query_arg('tab'); ?>'>
				<input name="mm_pdf_reset_plugin" type="hidden" value="1" />
				<a onClick="confirmReset()" class="mm-ui-button red" style="padding:5px;"><?php echo _mmpdft("Reset Plugin"); ?></a>
			</form>
		</div>
		</div>
		</div>
	</div>
	<?php
        }
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
