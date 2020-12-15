<?php
/**
 * This file runs when the plugin in uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Ideally you will add all your clean-up scripts here
 * that will clean-up unused meta, options, etc. in the database.
 *
 * @package WordPress Plugin Template/Uninstall
 */

// If plugin is not being uninstalled, exit (do nothing).
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option("mm-pdf-email-test-email");
delete_option("mm-pdf-email-from");
delete_option("mm-pdf-email-subject");
delete_option("mm-pdf-email-body");
delete_option("mm-pdf-email-billing-custom-field-id");
delete_option("mm-pdf-business-name");
delete_option("mm-pdf-business-address");
delete_option("mm-pdf-business-tax-label");
delete_option("mm-pdf-business-tax-id");
delete_option("mm-pdf-footer-section-1");
delete_option("mm-pdf-footer-section-2");
?>