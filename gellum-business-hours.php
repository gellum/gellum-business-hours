<?php
/**
 * Plugin Name:       Gellum Business Hours
 * Plugin URI:        https://gellum.com/opensource/gellum-business-hours
 * Description:       Manage your WooCommerce store's business hours. Disable checkout and display notices when closed. Shortcode [gellum_business_hours]
 * Version:           1.3.4
 * Author:            Gellum
 * Author URI:        https://gellum.com/opensource
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gellum-business-hours
 * Domain Path:       /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.8
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Gellum_Business_Hours' ) ) :

final class Gellum_Business_Hours {

    public $version = '1.3.4'; // Version updated
    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    private function define_constants() {
        define( 'GELLUM_BUSINESS_HOURS_TEXT_DOMAIN', 'gellum-business-hours' );

        if ( ! defined('GELLUM_BUSINESS_HOURS_PLUGIN_FILE') ) {
            define( 'GELLUM_BUSINESS_HOURS_PLUGIN_FILE', __FILE__ );
        }
        if ( ! defined('GELLUM_BUSINESS_HOURS_PLUGIN_URI') ) {
            define( 'GELLUM_BUSINESS_HOURS_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
        }
        if ( ! defined('GELLUM_BUSINESS_HOURS_VERSION') ) {
            define( 'GELLUM_BUSINESS_HOURS_VERSION', $this->version );
        }
        if ( ! defined('GELLUM_BUSINESS_HOURS_SLUG') ) {
            define( 'GELLUM_BUSINESS_HOURS_SLUG', 'gellum-business-hours' );
        }
        if ( ! defined('GELLUM_BUSINESS_HOURS_OPTION_NAME') ) {
            define( 'GELLUM_BUSINESS_HOURS_OPTION_NAME', GELLUM_BUSINESS_HOURS_SLUG . '_settings' );
        }
    }

    private function init_hooks() {
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) ); // Enqueue public styles

        // Shortcode
        add_shortcode( 'gellum_business_hours', array( $this, 'render_store_status_shortcode' ) );

        // WooCommerce hooks
        add_action( 'woocommerce_check_cart_items', array( $this, 'check_store_open_status_on_checkout' ) );
        add_action( 'woocommerce_before_checkout_form', array( $this, 'display_store_closed_notice_checkout_page' ), 5 );
        add_action( 'woocommerce_before_cart', array( $this, 'display_store_closed_notice_cart_page' ), 5 );
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        $plugin_page_hook = 'woocommerce_page_' . GELLUM_BUSINESS_HOURS_SLUG . '-settings';

        if ( $hook_suffix == $plugin_page_hook ) {
            wp_enqueue_style(
                'gellum-business-hours-google-font-readex-pro-admin', // Unique handle for admin
                'https://fonts.googleapis.com/css2?family=Readex+Pro:wght@300;400;500;600;700&display=swap',
                array(), null
            );
            wp_enqueue_style(
                GELLUM_BUSINESS_HOURS_SLUG . '-admin-styles',
                GELLUM_BUSINESS_HOURS_PLUGIN_URI . 'assets/css/admin-styles.css',
                array( 'gellum-business-hours-google-font-readex-pro-admin' ),
                GELLUM_BUSINESS_HOURS_VERSION
            );
        }
    }

    /**
     * Enqueue public-facing scripts and styles.
     */
    public function enqueue_public_assets() {

        wp_enqueue_style(
            GELLUM_BUSINESS_HOURS_SLUG . '-public-styles',
            GELLUM_BUSINESS_HOURS_PLUGIN_URI . 'assets/css/public-styles.css',
            array(), 
            GELLUM_BUSINESS_HOURS_VERSION
        );
    }

    /**
     * Renders the [gellum_business_hours] shortcode.
     */
    public function render_store_status_shortcode() {
        $settings = get_option( GELLUM_BUSINESS_HOURS_OPTION_NAME, array() );
        $output_text = '';
        $css_class = '';

        if ( $this->is_store_open() ) {
            $css_class = 'gellum-business-hours-open';
            $output_text = __( 'Open', 'gellum-business-hours' );

            // Check for "closing soon" only for same-day schedules
            $timezone = $this->get_wp_timezone();
            $current_dt = new DateTime('now', $timezone);
            $current_day_key = strtolower($current_dt->format('l'));
            
            if (isset($settings[$current_day_key . '_opens']) && isset($settings[$current_day_key . '_closes'])) {
                $opens_str = $settings[$current_day_key . '_opens'];
                $closes_str = $settings[$current_day_key . '_closes'];

                // Check if it's a same-day schedule (not overnight)
                if (strtotime($closes_str) > strtotime($opens_str)) {
                    $closing_dt_today = DateTime::createFromFormat('Y-m-d H:i', $current_dt->format('Y-m-d') . ' ' . $closes_str, $timezone);
                    if ($closing_dt_today && $closing_dt_today > $current_dt) {
                        $diff_seconds = $closing_dt_today->getTimestamp() - $current_dt->getTimestamp();
                        $minutes_to_close = floor($diff_seconds / 60);

                        if ($minutes_to_close > 0 && $minutes_to_close <= 60) {
                            $output_text = sprintf(
                                // translators: %d is the number of minutes.
                                _n(
                                    'Closing in %d minute',
                                    'Closing in %d minutes',
                                    $minutes_to_close,
                                    'gellum-business-hours'
                                ),
                                $minutes_to_close
                            );
                        }
                    }
                }
            }
        } else {
            $css_class = 'gellum-business-hours-closed';
            $next_opening_message_full = $this->get_next_opening_time_message();
            
            // Attempt to make the "Closed" message more concise with next opening time
            $closed_text = __('Closed', 'gellum-business-hours');
            $prefix_to_remove = __('The store is currently closed. ', 'gellum-business-hours');
            
            if (strpos($next_opening_message_full, $prefix_to_remove) === 0) {
                $next_opening_details = substr($next_opening_message_full, strlen($prefix_to_remove));
                 // $next_opening_details will be like "Next opening: Today at HH:MM." or "Next opening: Day at HH:MM."
                $output_text = $closed_text . '. ' . $next_opening_details;
            } else {
                // For messages like "Business hours have not been configured yet." or "The store will remain closed..."
                $output_text = $closed_text . '. ' . $next_opening_message_full;
            }
        }

        if ( ! empty($output_text) ) {
            return '<span class="gellum-business-hours-status ' . esc_attr($css_class) . '">' . esc_html($output_text) . '</span>';
        }
        return ''; // Return empty if no status could be determined (should not happen ideally)
    }


    // --- EL RESTO DE TUS FUNCIONES PHP VAN AQUÍ ---
    // (declare_hpos_compatibility, on_plugins_loaded, admin_menu, register_settings, sanitize_settings,
    // render_time_select_field_html, settings_page_html, get_days_of_week, get_wp_timezone,
    // is_store_open, get_next_opening_time_message, check_store_open_status_on_checkout,
    // display_store_closed_notice_checkout_page, display_store_closed_notice_cart_page)
    // ... (Copia aquí el resto de las funciones de la versión 1.3.2) ...

    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', GELLUM_BUSINESS_HOURS_PLUGIN_FILE, true );
        }
    }

    public function on_plugins_loaded() {
        load_plugin_textdomain( 'gellum-business-hours', false, dirname( plugin_basename( GELLUM_BUSINESS_HOURS_PLUGIN_FILE ) ) . '/languages' );
    }

    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Gellum Business Hours Settings', 'gellum-business-hours' ),
            __( 'Business Hours', 'gellum-business-hours' ),
            'manage_woocommerce',
            GELLUM_BUSINESS_HOURS_SLUG . '-settings',
            array( $this, 'settings_page_html' )
        );
    }

    public function register_settings() {
        register_setting(
            GELLUM_BUSINESS_HOURS_SLUG . '_options_group',
            GELLUM_BUSINESS_HOURS_OPTION_NAME,
            array( $this, 'sanitize_settings' )
        );
    }

    public function sanitize_settings( $input ) {
        $sanitized_input = array();
        $days = array_keys($this->get_days_of_week());

        foreach ( $days as $day ) {
            $sanitized_input[ $day . '_enabled' ] = isset( $input[ $day . '_enabled' ] ) ? (bool) $input[ $day . '_enabled' ] : false;
            
            if ( isset( $input[ $day . '_opens' ] ) && preg_match( '/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/', $input[ $day . '_opens' ] ) ) {
                $sanitized_input[ $day . '_opens' ] = sanitize_text_field( $input[ $day . '_opens' ] );
            } else {
                $sanitized_input[ $day . '_opens' ] = '09:00';
            }
            if ( isset( $input[ $day . '_closes' ] ) && preg_match( '/^([01]?[0-9]|2[0-3]):([0-5][0-9])$/', $input[ $day . '_closes' ] ) ) {
                $sanitized_input[ $day . '_closes' ] = sanitize_text_field( $input[ $day . '_closes' ] );
            } else {
                $sanitized_input[ $day . '_closes' ] = '17:00';
            }
        }
        return $sanitized_input;
    }

    public function render_time_select_field_html( $options, $day_key, $type ) {
        $field_name = GELLUM_BUSINESS_HOURS_OPTION_NAME . '[' . esc_attr( $day_key ) . '_' . esc_attr( $type ) . ']';
        $current_value = isset( $options[ $day_key . '_' . $type ] ) ? $options[ $day_key . '_' . $type ] : ( $type === 'opens' ? '09:00' : '17:00' );

        $html = '<select name="' . esc_attr( $field_name ) . '" class="gellum-time-select">';
        for ( $h = 0; $h < 24; $h++ ) {
            for ( $m = 0; $m < 60; $m += 15 ) {
                $time = sprintf( '%02d:%02d', $h, $m );
                $html .= '<option value="' . esc_attr( $time ) . '" ' . selected( $current_value, $time, false ) . '>' . esc_html( $time ) . '</option>';
            }
        }
        $html .= '</select>';
        return $html;
    }

    public function settings_page_html() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        $options = get_option( GELLUM_BUSINESS_HOURS_OPTION_NAME, array() );
        $days = $this->get_days_of_week();
        ?>
        <div class="wrap gellum-business-hours-wrap gellum-business-hours-page">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p class="gellum-description"><?php _e( 'Configure the weekly opening hours for your store. When the store is closed, checkout will be disabled and customers will be notified.', 'gellum-business-hours' ); ?></p>
            
            <form action="options.php" method="post" class="gellum-settings-form">
                <?php settings_fields( GELLUM_BUSINESS_HOURS_SLUG . '_options_group' ); ?>
                
                <div class="gellum-settings-table-container">
                    <table class="form-table gellum-business-hours-table widefat">
                        <thead>
                            <tr>
                                <th scope="col" class="gellum-day-col"><?php _e( 'Day', 'gellum-business-hours' ); ?></th>
                                <th scope="col" class="gellum-status-col"><?php _e( 'Status', 'gellum-business-hours' ); ?></th>
                                <th scope="col" class="gellum-time-col"><?php _e( 'Opening', 'gellum-business-hours' ); ?></th>
                                <th scope="col" class="gellum-time-col"><?php _e( 'Closing', 'gellum-business-hours' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $days as $day_key => $day_name ) : ?>
                                <?php $is_enabled = isset( $options[ $day_key . '_enabled' ] ) ? (bool) $options[ $day_key . '_enabled' ] : false; ?>
                                <tr>
                                    <td class="gellum-day-cell"><span><?php echo esc_html( $day_name ); ?></span></td>
                                    <td class="gellum-status-cell">
                                        <span class="status-indicator <?php echo $is_enabled ? 'open' : 'closed'; ?>"></span>
                                        <select name="<?php echo GELLUM_BUSINESS_HOURS_OPTION_NAME . '[' . esc_attr( $day_key ) . '_enabled]'; ?>" class="gellum-status-select">
                                            <option value="1" <?php selected( $is_enabled, true ); ?>><?php _e( 'Open', 'gellum-business-hours' ); ?></option>
                                            <option value="0" <?php selected( $is_enabled, false ); ?>><?php _e( 'Closed', 'gellum-business-hours' ); ?></option>
                                        </select>
                                    </td>
                                    <td class="gellum-time-cell">
                                        <?php echo $this->render_time_select_field_html( $options, $day_key, 'opens' ); ?>
                                    </td>
                                    <td class="gellum-time-cell">
                                        <?php echo $this->render_time_select_field_html( $options, $day_key, 'closes' ); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="gellum-submit-button-container">
                    <?php submit_button( __( 'Save changes', 'gellum-business-hours' ), 'primary gellum-save-button' ); ?>
                </div>
            </form>
        </div>
        <?php
    }
    
    private function get_days_of_week() {
        return array(
            'monday'    => __( 'Monday', 'gellum-business-hours' ),
            'tuesday'   => __( 'Tuesday', 'gellum-business-hours' ),
            'wednesday' => __( 'Wednesday', 'gellum-business-hours' ),
            'thursday'  => __( 'Thursday', 'gellum-business-hours' ),
            'friday'    => __( 'Friday', 'gellum-business-hours' ),
            'saturday'  => __( 'Saturday', 'gellum-business-hours' ),
            'sunday'    => __( 'Sunday', 'gellum-business-hours' ),
        );
    }

    private function get_wp_timezone() {
        $timezone_string = get_option( 'timezone_string' );
        if ( $timezone_string ) {
            return new DateTimeZone( $timezone_string );
        }
        $offset  = (float) get_option( 'gmt_offset' );
        $hours   = (int) $offset;
        $minutes = ( $offset - $hours ) * 60;
        $sign    = ( $offset < 0 ) ? '-' : '+';
        $timezone_offset_string = sprintf( '%s%02d:%02d', $sign, abs( $hours ), abs( $minutes ) );
        return new DateTimeZone( $timezone_offset_string );
    }

    public function is_store_open() {
        $settings = get_option( GELLUM_BUSINESS_HOURS_OPTION_NAME, array() );
        $timezone = $this->get_wp_timezone();
        $current_time = new DateTime( 'now', $timezone );
        $current_day_key = strtolower( $current_time->format( 'l' ) );
        $current_time_str = $current_time->format( 'H:i' );
    
        if ( ! isset( $settings[ $current_day_key . '_enabled' ] ) || ! $settings[ $current_day_key . '_enabled' ] ) {
            return false;
        }
    
        $opens_str = isset( $settings[ $current_day_key . '_opens' ] ) ? $settings[ $current_day_key . '_opens' ] : '09:00';
        $closes_str = isset( $settings[ $current_day_key . '_closes' ] ) ? $settings[ $current_day_key . '_closes' ] : '17:00';
    
        if ( $opens_str === $closes_str ) {
            return $opens_str === '00:00';
        }
    
        if ( strtotime( $closes_str ) < strtotime( $opens_str ) ) {
            if ( $current_time_str >= $opens_str || $current_time_str < $closes_str ) {
                if ( $current_time_str < $closes_str ) {
                    $yesterday_dt = new DateTime( 'yesterday', $timezone );
                    $yesterday_key = strtolower( $yesterday_dt->format( 'l' ) );
    
                    if ( ! isset( $settings[ $yesterday_key . '_enabled' ] ) || ! $settings[ $yesterday_key . '_enabled' ] ) {
                        return false;
                    }
    
                    $yesterday_opens_str = isset( $settings[ $yesterday_key . '_opens' ] ) ? $settings[ $yesterday_key . '_opens' ] : '00:00';
                    $yesterday_closes_str = isset( $settings[ $yesterday_key . '_closes' ] ) ? $settings[ $yesterday_key . '_closes' ] : '23:59';
    
                    if ( !(strtotime( $yesterday_closes_str ) < strtotime( $yesterday_opens_str ) && $yesterday_closes_str === $closes_str) ) {
                        return false;
                    }
                }
                return true;
            }
            return false;
        }
    
        return ( $current_time_str >= $opens_str && $current_time_str < $closes_str );
    }

    public function get_next_opening_time_message() {
        $settings = get_option( GELLUM_BUSINESS_HOURS_OPTION_NAME, array() );
        if (empty($settings)) {
            return __( 'Business hours have not been configured yet.', 'gellum-business-hours' );
        }

        $timezone = $this->get_wp_timezone();
        $current_dt = new DateTime( 'now', $timezone );
        
        for ( $i = 0; $i < 7; $i++ ) {
            $check_dt = clone $current_dt;
            if ($i > 0) { 
                 $check_dt->modify( "+{$i} day" )->setTime(0,0,0);
            }

            $day_key = strtolower( $check_dt->format( 'l' ) ); 

            if ( isset( $settings[ $day_key . '_enabled' ] ) && $settings[ $day_key . '_enabled' ] ) {
                $opens_str = isset($settings[ $day_key . '_opens' ]) ? $settings[ $day_key . '_opens' ] : '00:00';
                $next_opening_dt = DateTime::createFromFormat('Y-m-d H:i', $check_dt->format('Y-m-d') . ' ' . $opens_str, $timezone);

                if (!$next_opening_dt) continue; 

                if ( ($i === 0 && $next_opening_dt > $current_dt) || $i > 0) {
                    $time_format = get_option( 'time_format', 'g:i a' );
                    $day_name_localized = wp_date( 'l', $next_opening_dt->getTimestamp(), $timezone );
                    $time_localized = wp_date( $time_format, $next_opening_dt->getTimestamp(), $timezone );

                    if ($check_dt->format('Y-m-d') === $current_dt->format('Y-m-d')) {
                        return sprintf(
                            __( 'The store is currently closed. Next opening: Today at %s.', 'gellum-business-hours' ),
                            $time_localized
                        );
                    } else {
                         return sprintf(
                            __( 'The store is currently closed. Next opening: %1$s at %2$s.', 'gellum-business-hours' ),
                            $day_name_localized, 
                            $time_localized      
                        );
                    }
                }
            }
        }
        return __( 'The store will remain closed for the next 7 days based on the current schedule.', 'gellum-business-hours' );
    }

    public function check_store_open_status_on_checkout() {
        if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            return;
        }

        if ( ! $this->is_store_open() ) {
            $message = $this->get_next_opening_time_message();
            if ( function_exists('wc_add_notice') && ! wc_has_notice( $message, 'error' ) ) {
                wc_add_notice( $message, 'error' );
            }
            
            add_filter( 'woocommerce_order_button_html', '__return_empty_string', 100 );
            add_filter( 'woocommerce_cart_needs_payment', '__return_false', 100 );

            remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
            add_action( 'woocommerce_proceed_to_checkout', function() { /* Message handled by wc_add_notice */ }, 20);
        }
    }

    public function display_store_closed_notice_checkout_page() {
        if ( ! $this->is_store_open() && is_checkout() && function_exists('wc_print_notice') && ! wc_has_notice( $this->get_next_opening_time_message(), 'error' ) ) {
            wc_print_notice( $this->get_next_opening_time_message(), 'notice' );
        }
    }

    public function display_store_closed_notice_cart_page() {
        if ( ! $this->is_store_open() && is_cart() && function_exists('wc_print_notice') && ! wc_has_notice( $this->get_next_opening_time_message(), 'error' ) ) {
             wc_print_notice( $this->get_next_opening_time_message(), 'notice' );
        }
    }

} // End Class Gellum_Business_Hours


function Gellum_Business_Hours_Instance() {
    return Gellum_Business_Hours::instance();
}
$GLOBALS['gellum_business_hours'] = Gellum_Business_Hours_Instance();

endif;