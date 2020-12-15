<?php
/**
 * Plugin Name: MemberMouse PDF Invoices
 * Version: 1.0.0
 * Plugin URI: https://membermouse.com
 * Description: Sends an invoice email with PDF attachment on initial payment and rebill payment.
 * Author: MemberMouse, LLC
 * Author URI: https://membermouse.com
 * Requires at least: 4.0
 * Tested up to: 5.6
 *
 * Text Domain: membermouse-pdf-invoices
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author MemberMouse, LLC
 * @since 1.0.0
 */
if (! defined('ABSPATH')) {
    exit();
}

// Load plugin class files.
require_once 'includes/class-membermouse-pdf-invoices.php';
require_once 'includes/class-membermouse-pdf-invoices-settings.php';

/**
 * Returns the main instance of MemberMouse_PDF_Invoices to prevent the need to use globals.
 *
 * @since 1.0.0
 * @return object MemberMouse_PDF_Invoices
 */
function membermouse_pdf_invoices()
{
    $instance = MemberMouse_PDF_Invoices::instance(__FILE__, '1.0.0');

    if (is_null($instance->settings)) {
        $instance->settings = MemberMouse_PDF_Invoices_Settings::instance($instance);
    }

    add_action('mm_payment_received', 'membermouse_invoice_pdf_action_handler');
    add_action('mm_payment_rebill', 'membermouse_invoice_pdf_action_handler');

    return $instance;
}

function membermouse_invoice_pdf_action_handler($data)
{
    // Includes for Classes
    include (plugin_dir_path(__FILE__) . 'includes/class-membermouse-invoice.php');
    $pdfInvoicer = MemberMouse_Invoice::get_instance();
    $pdfInvoicer->process_payment_received($data);
}

membermouse_pdf_invoices();
