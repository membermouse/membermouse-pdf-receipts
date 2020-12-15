<?php
/**
 * Main plugin class file.
 *
 * @package WordPress Plugin Template/Includes
 */
if (! defined('ABSPATH')) {
    exit();
}

/**
 * Main plugin class.
 */
class MemberMouse_PDF_Invoices
{

    /**
     * The single instance of MemberMouse_PDF_Invoices.
     *
     * @var object
     * @access private
     * @since 1.0.0
     */
    private static $_instance = null;

    // phpcs:ignore

    /**
     * Settings class object
     *
     * @var object
     * @access public
     * @since 1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $_version;

    // phpcs:ignore

    /**
     * The token.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $_token;

    // phpcs:ignore

    /**
     * The main plugin file.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $assets_url;

    /**
     * Suffix for JavaScripts.
     *
     * @var string
     * @access public
     * @since 1.0.0
     */
    public $script_suffix;

    /**
     * Constructor funtion.
     *
     * @param string $file
     *            File constructor.
     * @param string $version
     *            Plugin version.
     */
    public function __construct($file = '', $version = '1.0.0')
    {
        $this->_version = $version;
        $this->_token = 'membermouse_pdf_invoices';

        // Load plugin environment variables.
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        $this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        // Load admin JS & CSS
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
        
        register_activation_hook($this->file, array(
            $this,
            'install'
        ));
    }

    // End __construct ()
    
    /**
     * Admin enqueue style.
     *
     * @param string $hook Hook parameter.
     *
     * @return void
     */
    public function admin_enqueue_styles( $hook = '' ) {
        wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
        wp_enqueue_style( $this->_token . '-admin' );
    } // End admin_enqueue_styles ()
    
    /**
     * Main MemberMouse_PDF_Invoices Instance
     *
     * Ensures only one instance of MemberMouse_PDF_Invoices is loaded or can be loaded.
     *
     * @param string $file
     *            File instance.
     * @param string $version
     *            Version parameter.
     *            
     * @return Object MemberMouse_PDF_Invoices instance
     * @see MemberMouse_PDF_Invoices()
     * @since 1.0.0
     * @static
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }

        return self::$_instance;
    }

    // End instance ()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of MemberMouse_PDF_Invoices is forbidden')), esc_attr($this->_version));
    }

    // End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of MemberMouse_PDF_Invoices is forbidden')), esc_attr($this->_version));
    }

    // End __wakeup ()

    /**
     * Installation.
     * Runs on activation.
     *
     * @access public
     * @return void
     * @since 1.0.0
     */
    public function install()
    {
        $this->_log_version_number();
    }

    // End install ()

    /**
     * Log the plugin version number.
     *
     * @access public
     * @return void
     * @since 1.0.0
     */
    private function _log_version_number()
    { // phpcs:ignore
        update_option($this->_token . '_version', $this->_version);
    } // End _log_version_number ()
}
