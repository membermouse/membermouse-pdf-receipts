<?php
/**
 * Plugin Name: MemberMouse PDF Receipts
 * Version: 1.0.0
 * Plugin URI: https://membermouse.com
 * Description: Sends an email receipt with PDF attachment on initial and rebill payments.
 * Author: MemberMouse, LLC
 * Author URI: https://membermouse.com
 * Requires at least: 4.0
 * Tested up to: 5.6
 *
 * Text Domain: membermouse-pdf-receipts
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
require_once 'includes/class-membermouse-pdf-receipts.php';
require_once 'includes/class-membermouse-pdf-receipts-settings.php';
require 'plugin-update-checker/plugin-update-checker.php';

function _mmpdft($str)
{
    if (function_exists("__"))
    {
        return __($str, "membermouse-pdf-receipts");
    }
    return $str;
}

/**
 * Returns the main instance of MemberMouse_PDF_Receipts to prevent the need to use globals.
 *
 * @since 1.0.0
 * @return object MemberMouse_PDF_Receipts
 */
function membermouse_pdf_receipts()
{
    $instance = MemberMouse_PDF_Receipts::instance(__FILE__, '1.0.0');

    if (is_null($instance->settings)) {
        $instance->settings = MemberMouse_PDF_Receipts_Settings::instance($instance);
    }

    add_action('mm_payment_received', 'membermouse_receipt_pdf_action_handler');
    add_action('mm_payment_rebill', 'membermouse_receipt_pdf_action_handler');

    return $instance;
}

function membermouse_receipt_pdf_action_handler($data)
{
    // Includes for Classes
    include (plugin_dir_path(__FILE__) . 'includes/class-membermouse-receipt.php');
    $pdfReceiptGenerator = MemberMouse_Receipt::get_instance();
    $pdfReceiptGenerator->process_payment_received($data);
}

membermouse_pdf_receipts();

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://membermouse.com/resources/mm-pdf-receipts.json',
    __FILE__,
    'membermouse-pdf-receipts'
);