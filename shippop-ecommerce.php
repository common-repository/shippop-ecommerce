<?php

/**
 * SHIPPOP
 *
 * @package           SHIPPOP
 * @author            SHIPPOP
 * @copyright         SHIPPOP
 * @license           GPL-2.0
 *
 * @wordpress-plugin
 * Plugin Name:       SHIPPOP
 * Description:       SHIPPOP
 * Version:           4.5
 * Requires at least: 4.8
 * Author:            SHIPPOP
 * Author URI:        https://shippop.com
 * Text Domain:       shippop-ecommerce
 * Domain Path:       /languages/
 * License:           GPL v2
 * Endpoint           wp-json/shippop/v1/update-status
 */

define( 'SPECM_ECOMMERCE_REQUIRED_PHP_VERSION', '5.5' );
define( 'SPECM_ECOMMERCE_REQUIRED_WP_VERSION', '4.8' );
define( 'SPECM_ECOMMERCE_REQUIRED_WC_VERSION', '3.1' );

class Shippop_eCommerce {

	public $settings = null;

	function __construct() {
		global $specm_parcel_delivery;
		global $specm_parcel_logo;
		global $specm_courier_my_service_type;
		global $specm_on_demand;
		global $specm_ignore_cc;
		global $specm_shippop_status;
		global $specm_shippop_cod_status;
		global $specm_shippop_error_code;
		global $specm_printlabel_size;
		global $specm_shippop_order_statuses_color;
		global $specm_woocommerce_order_statuses_color;

		$specm_parcel_delivery = array(
			'drop_off' => array( 'THP', 'TP2', 'JNTD' , 'POSD' , 'DHL' ),
			'pick_up'  => array( 'APF', 'KRY', 'RSB', 'SKT', 'SCG', 'SCGC', 'SCGF', 'NJV', 'LLM', 'CJE', 'FLE', 'JNTP' ),
		);

		$specm_parcel_logo = array(
			'APF'   => 'shippop-ecommerce/assets/images/logistic_logo/APF.png',
			'BEE'   => 'shippop-ecommerce/assets/images/logistic_logo/BEE.png',
			'ARM'   => 'shippop-ecommerce/assets/images/logistic_logo/ARM.png',
			'CJE'   => 'shippop-ecommerce/assets/images/logistic_logo/CJLX.png',
			'CJLX'  => 'shippop-ecommerce/assets/images/logistic_logo/CJLX.png',
			'CJL'   => 'shippop-ecommerce/assets/images/logistic_logo/CJLX.png',
			'FLE'   => 'shippop-ecommerce/assets/images/logistic_logo/FLE.png',
			'JNTP'  => 'shippop-ecommerce/assets/images/logistic_logo/JNTP.png',
			'JNTPF' => 'shippop-ecommerce/assets/images/logistic_logo/JNTP.png',
			'JNTD'  => 'shippop-ecommerce/assets/images/logistic_logo/JNTP.png',
			'JWDC'  => 'shippop-ecommerce/assets/images/logistic_logo/JWDC.png',
			'JWDF'  => 'shippop-ecommerce/assets/images/logistic_logo/JWDC.png',
			'LLM'   => 'shippop-ecommerce/assets/images/logistic_logo/LLM.png',
			'NJV'   => 'shippop-ecommerce/assets/images/logistic_logo/NJV.png',
			'NJVE'  => 'shippop-ecommerce/assets/images/logistic_logo/NJV.png',
			'SCG'   => 'shippop-ecommerce/assets/images/logistic_logo/SCG.png',
			'SCGC'  => 'shippop-ecommerce/assets/images/logistic_logo/SCGC.png',
			'SCGF'  => 'shippop-ecommerce/assets/images/logistic_logo/SCGC.png',
			'SEN'   => 'shippop-ecommerce/assets/images/logistic_logo/SEN.png',
			'EMST'   => 'shippop-ecommerce/assets/images/logistic_logo/THP.png',
			'THP'   => 'shippop-ecommerce/assets/images/logistic_logo/THP.png',
			'TP2'   => 'shippop-ecommerce/assets/images/logistic_logo/TP2.png',
			'ECP'   => 'shippop-ecommerce/assets/images/logistic_logo/ECP.png',
			'SKT'   => 'shippop-ecommerce/assets/images/logistic_logo/SKT.png',
			'KRY'   => 'shippop-ecommerce/assets/images/logistic_logo/KRY.png',
			'KRYP'  => 'shippop-ecommerce/assets/images/logistic_logo/KRY.png',
			'KRYD'  => 'shippop-ecommerce/assets/images/logistic_logo/KRY.png',
			'BEST'  => 'shippop-ecommerce/assets/images/logistic_logo/BEST.png',
			'TRUE'  => 'shippop-ecommerce/assets/images/logistic_logo/TRUE.png',
			'ZTO'   => 'shippop-ecommerce/assets/images/logistic_logo/ZTO.png',
			'SPE'   => 'shippop-ecommerce/assets/images/logistic_logo/SPE.png',

			'ZPT'   => 'shippop-ecommerce/assets/images/logistic_logo/ZPT.png',
			'ZPTE'  => 'shippop-ecommerce/assets/images/logistic_logo/ZPT.png',
			'SKY'   => 'shippop-ecommerce/assets/images/logistic_logo/SKY.png',
			'POS'   => 'shippop-ecommerce/assets/images/logistic_logo/POS.png',
			'POSD'  => 'shippop-ecommerce/assets/images/logistic_logo/POS.png',
			'DHL'   => 'shippop-ecommerce/assets/images/logistic_logo/DHL.png',
			'NTW'   => 'shippop-ecommerce/assets/images/logistic_logo/NTW.jpg',
			'DROPC'   => 'shippop-ecommerce/assets/images/logistic_logo/DROP.png',
			'DROPB'   => 'shippop-ecommerce/assets/images/logistic_logo/DROP.png',
		);

		$specm_courier_my_service_type = [
			'POS' => 'Standard',
			'POSD' => 'Standard',
			'JNTP' => 'Standard',
			'JNTD' => 'Standard',
			'DHL' => 'Standard',
			'CJL' => 'Standard',
			'ARM' => 'Standard',
			'NJV' => 'Standard',
			'NJVE' => 'Standard',
			'NTW' => 'Standard',
			'SKY' => 'Standard',
			'ZPT' => 'Next Day',
			'ZPTE' => 'Same Day',
			'DROPB' => 'Same Day',
			'DROPC' => 'Same Day',
			'DROPBND' => 'Next Day'
		];

		$specm_ignore_cc                        = array( 'KRYQ', 'KRYS' );
		$specm_on_demand                        = array( 'LLM', 'SKT' );
		$specm_shippop_status                   = array(
			'booking'  => esc_html__( 'Confirmed', 'shippop-ecommerce' ),
			'shipping' => esc_html__( 'During delivery', 'shippop-ecommerce' ),
			'complete' => esc_html__( 'Success', 'shippop-ecommerce' ),
			'cancel'   => esc_html__( 'Failed/Cancelled', 'shippop-ecommerce' ),
			'return'   => esc_html__( 'Returned', 'shippop-ecommerce' ),
		);
		$specm_shippop_cod_status               = array(
			'wait_transfer'    => esc_html__( 'Confirmation pending', 'shippop-ecommerce' ),
			'pending_transfer' => esc_html__( 'Pending transfer', 'shippop-ecommerce' ),
			'transferred'      => esc_html__( 'Transfered', 'shippop-ecommerce' ),
			'cancel_transfer'  => esc_html__( 'Cancelled', 'shippop-ecommerce' ),
		);
		$specm_printlabel_size                  = array(
			// 'receipt'    => esc_html__( 'Receipt ( Default )', 'shippop-ecommerce' ),
			'letter'     => esc_html__( 'Letter ( Default )', 'shippop-ecommerce' ),
			'letter4x6'  => esc_html__( 'Letter 4*6', 'shippop-ecommerce' ),
			'A4'         => esc_html__( 'A4', 'shippop-ecommerce' ),
			'A5'         => esc_html__( 'A5', 'shippop-ecommerce' ),
			'A6'         => esc_html__( 'A6', 'shippop-ecommerce' ),
			'sticker'    => esc_html__( 'Sticker size 8x8 cm', 'shippop-ecommerce' ),
			'sticker4x6' => esc_html__( 'Sticker size 4x6 in', 'shippop-ecommerce' ),
			'sticker4x6_x_product' => esc_html__( 'Sticker size 4x6 in (Show products)', 'shippop-ecommerce' ),
		);
		$specm_shippop_error_code               = array(
			'SERVICE_MAINTENANCE'         => esc_html__( 'SERVICE MAINTENANCE', 'shippop-ecommerce' ),
			'ERR_MAINTENANCE'             => esc_html__( 'SYSTEM MAINTENANCE', 'shippop-ecommerce' ),
			'ERR_ORIGIN'                  => esc_html__( 'INVALID ORIGIN AREA', 'shippop-ecommerce' ),
			'ERR_DEST'                    => esc_html__( 'INVALID DESTINATION AREA', 'shippop-ecommerce' ),
			'ERR_DEFAULT'                 => esc_html__( 'ERROR MSG', 'shippop-ecommerce' ),
			'ERR_REALTIME_CHECKPRICE'     => esc_html__( 'COURIER NOT CALCULATE', 'shippop-ecommerce' ),
			'ERR_REVERSE_GEOCODE_FAILURE' => esc_html__( 'REVERSE GEOCODE FAILURE', 'shippop-ecommerce' ),
			'ERR_COD_AMOUNT_EXCEED'       => esc_html__( 'COD AMOUNT EXCEED', 'shippop-ecommerce' ),
			'ERR_NOT_SUPPORT_COD'         => esc_html__( 'NOT SUPPORT COD', 'shippop-ecommerce' ),
			'NOT_SUPPORT_COD'             => esc_html__( 'COURIER SERVICE NOT SUPPORT COD', 'shippop-ecommerce' ),
			'INVALID_WEIGHT'              => esc_html__( 'INVALID WEIGHT', 'shippop-ecommerce' ),
			'ERR_OVER_WEIGHT'             => esc_html__( 'OVER WEIGHT', 'shippop-ecommerce' ),
			'ERR_OUT_OF_SERVICE_TIME'     => esc_html__( 'OUT OF SERVICE TIME', 'shippop-ecommerce' ),
			'ERR_OUT_OF_AREA'             => esc_html__( 'SERVICE UNAVAILABLE', 'shippop-ecommerce' ),
			'ERR_SIZE'                    => esc_html__( 'INVALID SIZE', 'shippop-ecommerce' ),
			'ERR_OVER_SIZE'               => esc_html__( 'OVER SIZE', 'shippop-ecommerce' ),
			'DAY_OFF'                     => esc_html__( 'HOLIDAY', 'shippop-ecommerce' ),
			'ERR_POSTCODE'                => esc_html__( 'INVALID POSTCODE', 'shippop-ecommerce' ),
			'ERR_LAT_LNG'                 => esc_html__( 'INVALID LAT , LNG DATA', 'shippop-ecommerce' ),
			'UNAVILABLE'                  => esc_html__( 'UNAVILABLE', 'shippop-ecommerce' ),
		);
		$specm_shippop_order_statuses_color     = array(
			'wait'     => array( 'gray', 'black' ),
			'booking'  => array( '#f9dea7', 'black' ),
			'shipping' => array( '#cad7e1', '#546877' ),
			'complete' => array( '#c6e2c7', '#5e861f' ),
			'cancel'   => array( '#eba4a3', '#933d3c' ),
			'return'   => array( '#e5e5e5', '#a7a7a7' ),
		);
		$specm_woocommerce_order_statuses_color = array(
			'pending'    => array( 'gray', 'black' ),
			'processing' => array( 'green', 'white' ),
			'on-hold'    => array( 'orange', 'white' ),
			'completed'  => array( 'blue', 'white' ),
			'cancelled'  => array( 'gray', 'black' ),
			'refunded'   => array( 'gray', 'black' ),
			'failed'     => array( 'red', 'black' ),
		);

		define( 'SPECM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
		define( 'SPECM_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/shippop_waybills' );
		define( 'SPECM_UPLOAD_URL', wp_upload_dir()['baseurl'] . '/shippop_waybills' );

		define( 'SPECM_TRACKING_FILE', 'shippop_tracking' );
		define( 'SPECM_WEBHOOK_LOG', 'shippop_webhook_log' );
		define( 'SPECM_AJAX_NONCE', 'shippop-ajax-nonce' );

		define( 'SPECM_ORDER_WIDTH', 1 );
		define( 'SPECM_ORDER_HEIGHT', 1 );
		define( 'SPECM_ORDER_LENGTH', 1 );

		define( 'SHIPPOP_TH', 'https://www.shippop.com' );
		define( 'SHIPPOP_MY', 'https://www.shippop.my' );

		$this->init();
	}

	function specm_lang_plugin_init() {
		load_plugin_textdomain( 'shippop-ecommerce', false, 'shippop-ecommerce/languages' );
	}

	function init() {
		add_action( 'init', array( $this, 'specm_lang_plugin_init' ) );
		if ( ! $this->settings() ) {
			return;
		}
		$this->run();
	}

	function settings() {
		// for style settings page
		add_action( 'admin_enqueue_scripts', array( $this, 'specm_init_scripts_style' ) );
		// for logout menu page
		add_action( 'admin_menu', array( $this, 'specm_admin_menu_logout' ) );
		// for init popup
		add_action( 'admin_footer', array( $this, 'specm_admin_footer' ) );

		require_once 'libs/utility.php';

		require_once 'includes/shippop_login.php';
		require_once 'includes/shippop_setting.php';

		require_once 'libs/shippop-api.php';
		require_once 'includes/ajax-api/ajax-api-address-corrector.php';

		$Shippop_Auth = new SPECM_Shippop_Login();
		if ( ! $Shippop_Auth->is_correct ) {
			return false;
		}

		$Shippop_Setting = new SPECM_Shippop_Setting();
		if ( ! $Shippop_Setting->is_correct ) {
			return false;
		}

		$this->settings = $Shippop_Setting->settings;
		return true;
	}

	function run() {
		$this->init_include_file();
		$this->init_frontend();
		$this->init_ajax();
		$this->init_admin();
	}

	function init_frontend() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_my_scripts' ) );

		add_action( 'woocommerce_new_order', array( $this, 'specm_add_post_meta_after_place_order' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'specm_add_post_meta_after_place_order' ) );
		add_action( '__experimental_woocommerce_blocks_checkout_update_order_meta', array( $this, 'specm_add_post_meta_after_place_order' ) );

		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'specm_woocommerce_my_account_my_orders_actions' ), 10, 2 );
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'specm_woocommerce_get_order_item_totals' ), 10, 3 );
	}

	function init_ajax() {
		require_once 'includes/ajax-api/ajax-api-choose-courier.php';
		require_once 'includes/ajax-api/ajax-api-parcel-shipping.php';
	}

	function init_include_file() {
		require_once 'libs/crud-function.php';
		require_once 'includes/class_overview.php';
		require_once 'helper.php';

		require_once 'web-hooks-api.php';

		require_once 'includes/choose_courier.php';
		require_once 'includes/parcel_shipping.php';
		require_once 'includes/report_cod.php';
	}

	function init_admin() {
		add_action( 'admin_menu', array( $this, 'specm_adding_admin_menu' ) );
		add_action( 'admin_menu', array( $this, 'specm_change_post_menu_label' ) );

		add_action( 'add_meta_boxes_shop_order', array( $this, 'specm_add_boxes_parcel' ) );
		add_action( 'save_post_shop_order', array( $this, 'specm_add_boxes_parce_save' ) );
	}

	function specm_init_scripts_style() {
		wp_register_style( 'font-awesome-css', plugins_url( '/shippop-ecommerce/assets/css/font-awesome.min.css' ), array(), filemtime( SPECM_PLUGIN_PATH . 'assets/css/font-awesome.min.css' ), 'all' );
		wp_enqueue_style( 'font-awesome-css' );

		wp_enqueue_script(
			'loadingoverlay-js',
			plugins_url( '/shippop-ecommerce/assets/js/loadingoverlay.min.js' ),
			array( 'jquery' ),
			filemtime( SPECM_PLUGIN_PATH . 'assets/js/loadingoverlay.min.js' ),
			true
		);

		wp_enqueue_script(
			'shippop-main-js',
			plugins_url( '/shippop-ecommerce/assets/js/shippop-main.js' ),
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-position', 'jquery-ui-datepicker' ),
			filemtime( SPECM_PLUGIN_PATH . 'assets/js/shippop-main.js' ),
			true
		);

		wp_register_style( 'jquery-modal-css', plugins_url( '/shippop-ecommerce/assets/css/jquery.modal.min.css' ), false, filemtime( SPECM_PLUGIN_PATH . 'assets/css/jquery.modal.min.css' ), 'all' );
		wp_enqueue_style( 'jquery-modal-css' );

		wp_register_style(
			'shippop-main-css',
			plugins_url( '/shippop-ecommerce/assets/css/shippop-main.css' ),
			false,
			filemtime( SPECM_PLUGIN_PATH . 'assets/css/shippop-main.css' ),
			'all'
		);
		wp_enqueue_style( 'shippop-main-css' );

		wp_localize_script(
			'shippop-main-js',
			'shippop_main_js',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'list_parcel_url' => admin_url( 'admin.php?page=shippop-ecommerce-parcel&shippop_status=booking' ),
				'nonce'           => wp_create_nonce( SPECM_AJAX_NONCE ),
				'translate'       => array(
					'confirm_cancel'      => esc_html__( 'Confirm order cancellation ?', 'shippop-ecommerce' ),
					'ok'                  => esc_html__( 'OK', 'shippop-ecommerce' ),
					'cancel'              => esc_html__( 'Cancel', 'shippop-ecommerce' ),
					'alert'               => esc_html__( 'Notice', 'shippop-ecommerce' ),
					'error'               => esc_html__( 'Error', 'shippop-ecommerce' ),
					'confirm'             => esc_html__( 'Confirm', 'shippop-ecommerce' ),
					'confirm_success'     => esc_html__( 'Booking confirmed and Payment completed', 'shippop-ecommerce' ),
					'close'               => esc_html__( 'Close', 'shippop-ecommerce' ),
					'print_label'         => esc_html__( 'Print waybill', 'shippop-ecommerce' ),
					'please_select_order' => esc_html__( 'Please select order(s)', 'shippop-ecommerce' ),
					'confirm_logout'      => esc_html__( 'Are you sure you want to logout ?', 'shippop-ecommerce' ),
					'logout'              => esc_html__( 'Logout', 'shippop-ecommerce' ),
				),
			)
		);
	}

	function load_my_scripts() {
		wp_enqueue_script(
			'shippop-frontend-js',
			plugins_url( '/shippop-ecommerce/assets/js/shippop-frontend.js' ),
			array( 'jquery' ),
			filemtime( SPECM_PLUGIN_PATH . 'assets/js/shippop-frontend.js' ),
			true
		);
	}

	function specm_admin_footer() {
		?>
		<div id="specm-dialog-message" class="modal modal-shippop">
			<div style="height: 50px;">
				<div class="modal-title" style="text-align: center;"><?php echo esc_html__( 'Notice', 'shippop-ecommerce' ); ?></div>
				<div class="modal-close">
					<a href="javascript:void(0)" rel="modal:close">
						<span rel="modal:close">X</span>
					</a>
				</div>
			</div>
			<div class="modal-content" style="padding: 10px;">
				<div class="md-success" style="text-align: center;color: green;font-size: 50px;display: none;">
					<i class="fa fa-check-circle" aria-hidden="true"></i>
				</div>
				<div class="md-fail" style="text-align: center;color: red;font-size: 50px;display: none;">
					<i class="fa fa-times-circle" aria-hidden="true"></i>
				</div>
				<div class="modal-content-body" style="text-align: center;margin-top: 10px;">

				</div>
			</div>
		</div>

		<div id="specm-dialog-confirm" class="modal modal-shippop">
			<div style="height: 50px;">
				<div class="modal-title" style="text-align: center;"><?php echo esc_html__( 'Notice', 'shippop-ecommerce' ); ?></div>
				<div class="modal-close">
					<a href="javascript:void(0)" rel="modal:close">
						<span rel="modal:close">X</span>
					</a>
				</div>
			</div>
			<div class="modal-content" style="padding: 10px;">
				<div class="modal-content-body" style="text-align: center;margin-top: 10px;">

				</div>
			</div>
		</div>

		<div id="specm-dialog-confirm-logout" class="modal modal-shippop">
			<div style="height: 50px;">
				<div class="modal-title" style="text-align: center;"><?php echo esc_html__( 'Logout', 'shippop-ecommerce' ); ?></div>
				<div class="modal-close">
					<a href="javascript:void(0)" rel="modal:close">
						<span rel="modal:close">X</span>
					</a>
				</div>
			</div>
			<div class="modal-content" style="padding: 10px;">
				<div class="modal-content-body" style="text-align: center;margin-top: 10px;">

				</div>
			</div>
		</div>

		<div id="specm_overlay"></div>
		<?php
	}

	function specm_add_post_meta_after_place_order( $order_id ) {
		if ( !is_numeric( $order_id ) ) {
			$order_id = $order_id->get_id();
		}
		update_post_meta( $order_id, '_use_shippop_shipping', 'Y' );
		// $_order_width  = SPECM_ORDER_WIDTH;
		// $_order_length = SPECM_ORDER_LENGTH;
		// $_order_height = SPECM_ORDER_HEIGHT;

		// update_post_meta( $order_id, '_order_width', ( is_numeric( $_order_width ) ) ? (float) $_order_width : 1 );
		// update_post_meta( $order_id, '_order_length', ( is_numeric( $_order_length ) ) ? (float) $_order_length : 1 );
		// update_post_meta( $order_id, '_order_height', ( is_numeric( $_order_height ) ) ? (float) $_order_height : 1 );
		update_post_meta( $order_id, '_order_width', SPECM_ORDER_WIDTH );
		update_post_meta( $order_id, '_order_length', SPECM_ORDER_LENGTH );
		update_post_meta( $order_id, '_order_height', SPECM_ORDER_HEIGHT );
	}

	function specm_woocommerce_my_account_my_orders_actions( $actions, $order ) {
		$post_id                   = $order->get_id();
		$_confirm_purchase_success = get_post_meta( $post_id, '_confirm_purchase_success', true );
		$_booking_tracking_code    = get_post_meta( $post_id, '_booking_tracking_code', true );
		$_booking_server           = get_post_meta( $post_id, '_booking_tracking_code', true );
		if ( ! empty( $_booking_server ) && strtoupper( $_booking_server ) === 'MY' ) {
			$endpoint = SHIPPOP_MY;
		} else {
			$endpoint = SHIPPOP_TH;
		}

		if ( ! empty( $_confirm_purchase_success ) && $_confirm_purchase_success && $_booking_tracking_code ) {
			$actions['shippop_tracking_code'] = array(
				'url'  => $endpoint . '/tracking/?tracking_code=' . $_booking_tracking_code,
				'name' => esc_html__( 'Parcel tracking', 'shippop-ecommerce' ),
			);
		}

		return $actions;
	}

	function specm_woocommerce_get_order_item_totals( $total_rows, $instants, $tax_display ) {
		$post_id                   = $instants->get_id();
		$_confirm_purchase_success = get_post_meta( $post_id, '_confirm_purchase_success', true );
		$_booking_tracking_code    = get_post_meta( $post_id, '_booking_tracking_code', true );
		$_booking_server           = get_post_meta( $post_id, '_booking_tracking_code', true );
		if ( ! empty( $_booking_server ) && strtoupper( $_booking_server ) === 'MY' ) {
			$endpoint = SHIPPOP_MY;
		} else {
			$endpoint = SHIPPOP_TH;
		}

		if ( ! empty( $_confirm_purchase_success ) && $_confirm_purchase_success && $_booking_tracking_code ) {
			$total_rows[ '_booking_tracking_code_' . $post_id ] = array(
				'label' => esc_html__( 'SHIPPOP Tracking', 'shippop-ecommerce' ) . ':',
				'value' => " <a href='" . $endpoint . '/tracking/?tracking_code=' . $_booking_tracking_code . "' target='_blank'> <button type='button' class='button'>" . esc_html__( 'Parcel tracking', 'shippop-ecommerce' ) . '</button> </a>',
			);
		}

		return $total_rows;
	}

	private function specm_check_is_page( $pagename ) {
		return ( ! empty( $_GET['page'] ) && sanitize_text_field( $_GET['page'] ) == $pagename ) ? true : false;
	}

	function specm_adding_admin_menu() {
		$choose_courier  = new SPECM_Choose_Courier( $this->specm_check_is_page( 'shippop-ecommerce' ) );
		$parcel_shipping = new SPECM_Parcel_Shipping( $this->specm_check_is_page( 'shippop-ecommerce-parcel' ) );
		$setting         = new SPECM_Shippop_Setting( $this->specm_check_is_page( 'shippop-ecommerce-setting' ) );
		$shippop_server = get_option( 'specm_server', 'TH' );

		add_menu_page(
			esc_html__( 'SHIPPOP', 'shippop-ecommerce' ),
			esc_html__( 'SHIPPOP', 'shippop-ecommerce' ),
			'manage_woocommerce',
			'shippop-ecommerce',
			array( $choose_courier, 'specm_index_page' ),
			plugins_url( 'shippop-ecommerce/assets/images/logo.png' ),
			50
		);

		add_submenu_page(
			'shippop-ecommerce',
			esc_html__( 'Parcel List', 'shippop-ecommerce' ),
			esc_html__( 'Parcel List', 'shippop-ecommerce' ),
			'manage_woocommerce',
			'shippop-ecommerce-parcel',
			array( $parcel_shipping, 'specm_index_page' )
		);

		if ( 'TH' === strtoupper( $shippop_server ) ) {
			$report_cod = new SPECM_Report_COD( $this->specm_check_is_page( 'shippop-ecommerce-report-cod' ) );
			add_submenu_page(
				'shippop-ecommerce',
				esc_html__( 'COD Report', 'shippop-ecommerce' ),
				esc_html__( 'COD Report', 'shippop-ecommerce' ),
				'manage_woocommerce',
				'shippop-ecommerce-report-cod',
				array( $report_cod, 'specm_index_page' )
			);
		}

		add_submenu_page(
			'shippop-ecommerce',
			esc_html__( 'Settings', 'shippop-ecommerce' ),
			esc_html__( 'Settings', 'shippop-ecommerce' ),
			'manage_options',
			'shippop-ecommerce-setting',
			array( $setting, 'specm_index_page' )
		);
	}

	function specm_admin_menu_logout() {
		add_submenu_page(
			'-',
			esc_html__( 'Logout', 'shippop-ecommerce' ),
			esc_html__( 'Logout', 'shippop-ecommerce' ),
			'manage_woocommerce',
			'shippop-ecommerce-setting-logout',
			array( $this, 'specm_logout' )
		);
	}

	function specm_logout() {
		delete_option( 'specm_bearer' );
		delete_option( 'specm_is_sandbox' );
		delete_option( 'specm_email_account' );
		delete_option( 'specm_settings' );
		delete_option( 'specm_member_information' );
		return wp_redirect( admin_url( 'admin.php?page=shippop-ecommerce-setting-login' ) );
		die;
	}

	function specm_change_post_menu_label() {
		global $submenu;

		$submenu['shippop-ecommerce'][0][0] = esc_html__( 'Choose Courier', 'shippop-ecommerce' );
	}

	function specm_add_boxes_parcel( $post ) {
		$post_id = $post->ID;
		// $_shippop_status = get_post_meta($post_id, '_shippop_status', true);
		$_confirm_purchase_success = get_post_meta( $post_id, '_confirm_purchase_success', true );
		$_booking_tracking_code    = get_post_meta( $post_id, '_booking_tracking_code', true );

		add_meta_box( 'meta-box-id', esc_html__( 'Parcel dimensions', 'shippop-ecommerce' ), array( $this, 'specm_add_boxes_parcel_callback' ), '', 'side', 'high' );

		// if ($_shippop_status != "booking" && $_booking_tracking_code) {
		if ( ! empty( $_confirm_purchase_success ) && $_confirm_purchase_success && $_booking_tracking_code ) {
			add_meta_box( 'meta-box-tracking-id', esc_html__( 'SHIPPOP Tracking', 'shippop-ecommerce' ), array( $this, 'specm_add_boxes_tracking_code_callback' ), '', 'side', 'high' );
		}
	}

	function specm_add_boxes_parcel_callback( $post ) {
		$post_id             = $post->ID;
		$_order_total_weight = get_post_meta( $post_id, '_order_total_weight', true );
		if ( empty( $_order_total_weight ) ) {
			$order        = wc_get_order( $post_id );
			$total_weight = 0;
			foreach ( $order->get_items() as $item_id => $product_item ) {
				$product_data 	= $product_item->get_data();
				$quantity       = $product_data['quantity']; // get quantity
				$product_id		= $product_data['product_id'];
				$variation_id	= $product_data['variation_id'];
				if ( $variation_id !== 0 ) {
					$product = new WC_Product_Variation( $variation_id );
					$product_weight = $product->get_weight();
				} else {
					$product = new WC_Product( $product_id );
					$product_weight = $product->get_weight();
				}

				// $quantity       = $product_item->get_quantity(); // get quantity
				// $product        = $product_item->get_product(); // get the WC_Product object
				// $product_weight = $product->get_weight(); // get the product weight
				// Add the line item weight to the total weight calculation
				if ( empty($product_weight) ) {
					$product_weight = 1;
				}
				$total_weight += floatval( $product_weight * $quantity );
			}
			$_order_total_weight = $total_weight;
		}

		$_order_width  = get_post_meta( $post_id, '_order_width', true );
		$_order_length = get_post_meta( $post_id, '_order_length', true );
		$_order_height = get_post_meta( $post_id, '_order_height', true );
		?>
		<div class="wrap">
			<div style="display: block;margin-bottom: 5px;">
				<label style="display: block;"><?php echo esc_html__( 'Weight (kg)', 'shippop-ecommerce' ); ?>: </label>
				<input type="number" min="0.1" step="0.01" class="form-control" name="_order_total_weight" value="<?php echo esc_attr( $_order_total_weight ); ?>">
			</div>

			<div style="display: block;margin-bottom: 5px;">
				<label style="display: block;"><?php echo esc_html__( 'Width (cm)', 'shippop-ecommerce' ); ?>: </label>
				<input type="number" min="1" step="0.01" class="form-control" required name="_order_width" value="<?php echo esc_attr( $_order_width ); ?>">
			</div>

			<div style="display: block;margin-bottom: 5px;">
				<label style="display: block;"><?php echo esc_html__( 'Length (cm)', 'shippop-ecommerce' ); ?>: </label>
				<input type="number" min="1" step="0.01" class="form-control" required name="_order_length" value="<?php echo esc_attr( $_order_length ); ?>">
			</div>

			<div style="display: block;margin-bottom: 5px;">
				<label style="display: block;"><?php echo esc_html__( 'Height (cm)', 'shippop-ecommerce' ); ?>: </label>
				<input type="number" min="1" step="0.01" class="form-control" required name="_order_height" value="<?php echo esc_attr( $_order_height ); ?>">
			</div>
		</div>
		<?php
	}

	function specm_add_boxes_tracking_code_callback( $post ) {
		$post_id                   = $post->ID;
		$_confirm_purchase_success = get_post_meta( $post_id, '_confirm_purchase_success', true );
		$_booking_tracking_code    = get_post_meta( $post_id, '_booking_tracking_code', true );
		$_booking_server           = get_post_meta( $post_id, '_booking_tracking_code', true );
		if ( ! empty( $_booking_server ) && strtoupper( $_booking_server ) === 'MY' ) {
			$endpoint = SHIPPOP_MY;
		} else {
			$endpoint = SHIPPOP_TH;
		}

		if ( ! empty( $_confirm_purchase_success ) && $_confirm_purchase_success && $_booking_tracking_code ) {
			?>
			<a href='<?php echo esc_url( $endpoint ); ?>/tracking/?tracking_code=<?php echo esc_attr( $_booking_tracking_code ); ?>' target='blank'>
				<button type='button' class='button'><?php echo esc_html__( 'Parcel tracking', 'shippop-ecommerce' ); ?></button>
			</a>
			<?php
		}
	}

	function specm_add_boxes_parce_save( $post_id ) {
		$_order_total_weight = ( ! empty( $_POST['_order_total_weight'] ) ) ? sanitize_text_field( $_POST['_order_total_weight'] ) : false;
		$_order_width        = ( ! empty( $_POST['_order_width'] ) ) ? sanitize_text_field( $_POST['_order_width'] ) : false;
		$_order_length       = ( ! empty( $_POST['_order_length'] ) ) ? sanitize_text_field( $_POST['_order_length'] ) : false;
		$_order_height       = ( ! empty( $_POST['_order_height'] ) ) ? sanitize_text_field( $_POST['_order_height'] ) : false;

		if ( $_order_total_weight ) {
			update_post_meta( $post_id, '_order_total_weight', ( is_numeric( $_order_total_weight ) ) ? (float) $_order_total_weight : '' );
		}

		if ( $_order_width ) {
			update_post_meta( $post_id, '_order_width', ( is_numeric( $_order_width ) ) ? (float) $_order_width : '' );
		}

		if ( $_order_length ) {
			update_post_meta( $post_id, '_order_length', ( is_numeric( $_order_length ) ) ? (float) $_order_length : '' );
		}

		if ( $_order_height ) {
			update_post_meta( $post_id, '_order_height', ( is_numeric( $_order_height ) ) ? (float) $_order_height : '' );
		}
	}
}

if ( specm_check_requirements_met() ) {
	return new Shippop_eCommerce();
} else {
	add_action( 'admin_notices', 'specm_requirements_error' );
}

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 */
function specm_check_requirements_met() {
	global $wp_version;
	require_once ABSPATH . '/wp-admin/includes/plugin.php';  // to get is_plugin_active() early

	if ( version_compare( PHP_VERSION, SPECM_ECOMMERCE_REQUIRED_PHP_VERSION, '<' ) ) {
		return false;
	}

	if ( version_compare( $wp_version, SPECM_ECOMMERCE_REQUIRED_WP_VERSION, '<' ) ) {
		return false;
	}

	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		return false;
	}

	$woocommer_data = get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php', false, false );

	if ( version_compare( $woocommer_data['Version'], SPECM_ECOMMERCE_REQUIRED_WC_VERSION, '<' ) ) {
		return false;
	}

	return true;
}

function specm_requirements_error() {
	global $wp_version;
	?>
	<div class="notice notice-warning is-dismissible">
		<p> <?php echo esc_html__( 'Can’t use SHIPPOP Plugin. Please check the following', 'shippop-ecommerce' ); ?> </p>
		<ul style="list-style: inside;">
			<li><?php echo esc_html__( 'WordPress Version >= ', 'shippop-ecommerce' ) . SPECM_ECOMMERCE_REQUIRED_WP_VERSION . ' ( ' . $wp_version . ' ) '; ?></li>
			<li><?php echo esc_html__( 'PHP Version >= ', 'shippop-ecommerce' ) . SPECM_ECOMMERCE_REQUIRED_PHP_VERSION . ' ( ' . PHP_VERSION . ' ) '; ?></li>
			<li><?php echo esc_html__( 'Install and active Woocommerce Version >= ', 'shippop-ecommerce' ) . SPECM_ECOMMERCE_REQUIRED_WC_VERSION; ?></li>
		</ul>
	</div>
	<?php
}
