<?php

class SPECM_Shippop_Login {

	public $Specm_Bearer = null;
	public $Utility      = null;
	public $is_correct   = false;

	function __construct() {
		$this->Utility      = new SPECM_Utility();
		$this->Specm_Bearer = get_option( 'specm_bearer' );
		if ( isset( $this->Specm_Bearer ) && ! empty( $this->Specm_Bearer ) ) {
			$this->is_correct = true;
		}

		if ( $this->is_correct === false ) {
			add_action( 'admin_menu', array( $this, 'specm_admin_menu' ) );
			
			add_action( 'admin_enqueue_scripts', array( $this, 'specm_init_scripts_style' ) );
			add_action( 'wp_ajax_specm_shippop_register', array( $this, 'specm_shippop_register' ) );
		}
	}

	private function _genLoginEnvironment( $shippop_testing_mode = true ) {
		if ( $shippop_testing_mode ) {
			/* TST */
			$is_sandbox = 'Y';
			$key = 'tdiG06240HFAwCFOrVRxzbzuRCgMmpx1';
			$iv = 'UJrkONI192qEmaBk';
			// $courier_info_endpoint = 'https://api.shippop.dev/v1/couriers';
			$member_service_endpoint = 'https://member.shippop.dev/auth';
		} else {
			/* PRD */
			$is_sandbox = "N";
			$key = "Jfkd0i20r0eif32dFis94dsafb920DKa";
			$iv = "djowr1Aj234fd0aD";
			// $courier_info_endpoint = 'https://api.shippop.com/v1/couriers';
			$member_service_endpoint = 'https://member.shippop.com/auth';
		}

		// return [ 'is_sandbox' => $is_sandbox , 'key' => $key , 'iv' => $iv , 'courier_info_endpoint' => $courier_info_endpoint , 'member_service_endpoint' => $member_service_endpoint ];
		return [ 'is_sandbox' => $is_sandbox , 'key' => $key , 'iv' => $iv , 'member_service_endpoint' => $member_service_endpoint ];
	} 

	function specm_shippop_register() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response        = array(
			'status'  => false,
			'message' => '',
		);
		$shippop_company = ( ! empty( $_POST['shippop_company'] ) ) ? sanitize_text_field( $_POST['shippop_company'] ) : esc_html__( 'Optional' );
		$shippop_name    = ( ! empty( $_POST['shippop_name'] ) ) ? sanitize_text_field( $_POST['shippop_name'] ) : false;
		$shippop_tel     = ( ! empty( $_POST['shippop_tel'] ) ) ? sanitize_text_field( $_POST['shippop_tel'] ) : false;
		$shippop_email   = ( ! empty( $_POST['shippop_email'] ) ) ? sanitize_text_field( $_POST['shippop_email'] ) : false;
		$shippop_courier = ( ! empty( $_POST['shippop_courier'] ) ) ? sanitize_text_field( $_POST['shippop_courier'] ) : false;
		$shippop_server  = ( ! empty( $_POST['shippop_server'] ) ) ? sanitize_text_field( $_POST['shippop_server'] ) : false;
		if ( $shippop_company && $shippop_name && $shippop_tel && $shippop_email && $shippop_courier && $shippop_server ) {
			update_option( 'specm_server', strtoupper( ( $shippop_server == 'MY' ) ? 'MY' : 'TH' ) );
			update_option( 'specm_is_sandbox', 'N' ); // FIX FOR TO PROD
			$response = $this->Utility->specm_post(
				'/register/wordpress/',
				$data = array(
					'company' => $shippop_company,
					'name'    => $shippop_name,
					'email'   => $shippop_email,
					'phone'   => $shippop_tel,
					'courier' => $shippop_courier,
					'detail'  => array(
						'domain'   => site_url(),
						'webhooks' => site_url( 'wp-json/shippop/v1/update-status' ),
					),
					'channel' => (strpos( site_url() , "zaviago") !== false) ? "zaviago" : "woocommerce"
				)
			);
			delete_option( 'specm_is_sandbox' );
			$response['dataa'] = $data;
			if ( $response['status'] ) {
				$response['message']  = esc_html__( 'Thank you for your interest in SHIPPOP service. We already received your information', 'shippop-ecommerce' );
				$response['message2'] = esc_html__( 'Our team will contact you within 1-2 business days', 'shippop-ecommerce' );
			} else {
				foreach ( $response['message']['form_error'] as $value ) {
					$response['message'] = $value;
					break;
				}
			}
		}

		header( 'Content-type: application/json; charset=utf-8' );
		echo json_encode( $response );
		die;
	}

	function specm_init_scripts_style() {
		wp_register_style( 'jquery-login-css', plugins_url( '/shippop-ecommerce/assets/css/login.css' ), false, '1.0.0', 'all' );
		wp_enqueue_style( 'jquery-login-css' );

		wp_enqueue_script(
			'shippop_login_js',
			plugins_url( '/shippop-ecommerce/assets/js/login.js' ),
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-position' ),
			filemtime( SPECM_PLUGIN_PATH . 'assets/js/login.js' ),
			true
		);

		wp_localize_script(
			'shippop_login_js',
			'shippop_login_js',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( SPECM_AJAX_NONCE ),
			)
		);
	}

	public function specm_admin_menu() {
		add_menu_page(
			esc_html__( 'SHIPPOP', 'shippop-ecommerce' ),
			esc_html__( 'SHIPPOP', 'shippop-ecommerce' ),
			'manage_options',
			'shippop-ecommerce-setting-login',
			array( $this, 'specm_index_page' ),
			plugins_url( 'shippop-ecommerce/assets/images/logo.png' ),
			56
		);
	}

	private function specm_encode( $text, $key, $iv ) {
		return base64_encode( openssl_encrypt( $text, 'aes-256-cbc', $key, 0, $iv ) );
	}


	private function specm_jwt_decode( $content , $shippop_email) {
		$content = explode("." , $content);
		if ( count($content) == 3 && isset($content[1]) ) {
			$data = base64_decode( $content[1] );
			$data = json_decode( $data , JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			update_option('specm_member_information' , $data);
		} else {
			update_option('specm_member_information' , ['mem_type' => 'B2C' , 'clientType' => 'PREPAID' , 'marketId' => 21 , 'email' => $shippop_email]);
		}

		return true;
	}

	// private function specm_updateCourierInformation( $courier_info_endpoint , $email , $password ) {
	// 	$this->createTempFileCourier( [] );
	// 	try {
	// 		// $info = $this->Utility->specm_get( $courier_info_endpoint . '/api/information/th', $email , $password );
	// 		$info = $this->Utility->specm_get( $courier_info_endpoint . '/api/courier/information/th', $email , $password );
	// 		if (is_array( $info )) {
	// 			$content = [];
	// 			if ( !empty($info['data']) ) {
	// 				foreach ($info['data'] as $value) {
	// 					$courier_code = $value['courier_code'];
	// 					$content[ $courier_code ] = [
	// 						'logo' => $value['image'],
	// 						'courier_service' => $value['courier_service'],
	// 						'services' => (isset($value['information']['services'])) ? $value['information']['services'] : '',
	// 						'term_of_usages' => (isset($value['information']['term_of_usages'])) ? $value['information']['term_of_usages'] : ''
	// 					];
	// 				}
	// 			}
	// 			$this->createTempFileCourier( $content );
	// 		}
	// 	} catch (\Exception $e) {
	// 		return false;
	// 	}

	// 	return true;
	// }

	private function specm_updateCourierInformation() {
		$this->createTempFileCourier( [] );
		try {
			$info = $this->Utility->specm_get( 'https://api.shippop.com/v1/couriers/admin/courier/api/information/th/' );
			if (is_array( $info )) {
				$content = [];
				if ( !empty($info['data']) ) {
					foreach ($info['data'] as $value) {
						$courier_code = $value['courier_code'];
						$content[ $courier_code ] = [
							'logo' => $value['image'],
							'courier_name' => $value['courier_name'],
							'courier_service' => $value['courier_service'],
							'services' => (isset($value['information']['service_type'])) ? $value['information']['service_type'] : '',
							'term_of_usages' => (isset($value['information']['term_of_usages_html'])) ? $value['information']['term_of_usages_html'] : ''
						];
					}
				}
				$this->createTempFileCourier( $content );
			}
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	private function createTempFileCourier( $content ) {
		$fh = fopen(WP_CONTENT_DIR . "/shippop_courier_info.json", "w");
		fputs( $fh, json_encode( $content , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); 
		fclose( $fh );

		return true;
	}

	private function specmAuthMemberServiceSHIPPOP ( $specm_server , $shippop_testing_mode , $shippop_email , $shippop_password ) {
		$_genLoginEnvironment = $this->_genLoginEnvironment( $shippop_testing_mode );
		$is_sandbox = $_genLoginEnvironment['is_sandbox'];
		$key = $_genLoginEnvironment['key'];
		$iv = $_genLoginEnvironment['iv'];
		// $courier_info_endpoint = $_genLoginEnvironment['courier_info_endpoint'];
		$member_service_endpoint = $_genLoginEnvironment['member_service_endpoint'];

		update_option( 'specm_is_sandbox', $is_sandbox );
		update_option( 'specm_email_account', $shippop_email );
		update_option( 'specm_server', $specm_server );

		$sign = $this->specm_encode(
			json_encode(
				array(
					'email'    => $shippop_email,
					'password' => $shippop_password,
				)
			),
			$key,
			$iv
		);

		if ( $specm_server == 'TH' ) {
			$response = $this->Utility->specm_post(
				$member_service_endpoint . '/wp/login',
				array(
					'sign'			=> $sign,
					'clientName' 	=> 'SHIPPOP_WP',
					'clientType'	=> 'PREPAID'
				),
				'json',
				false
			);
		} else {
			$response = $this->Utility->specm_post(
				'/auth/login',
				array(
					'sign'			=> $sign,
					'clientName' 	=> 'SHIPPOP_WP',
					'clientType'	=> 'POSTPAID'
				),
				'json',
				false
			);
		}

		if ( $specm_server == 'TH' && $response['status'] ) {
			// $this->specm_jwt_decode( $response['data']['token'] , $shippop_email );
			// $this->specm_updateCourierInformation( $courier_info_endpoint , $shippop_email , $shippop_password );
			$this->specm_updateCourierInformation();
		}

		return $response;
	}

	function specm_index_page() {
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! empty( $_POST['shippop_email'] ) && ! empty( $_POST['shippop_password'] ) && ! empty( $_POST['shippop_server'] ) ) {
			if ( ! isset( $_POST['specm_login_form'] ) || ! wp_verify_nonce( $_POST['specm_login_form'], 'specm_login_form' ) ) {
				print 'Sorry, your nonce did not verify.';
				exit;
			} else {
				$shippop_email    = sanitize_email( $_POST['shippop_email'] );
				$shippop_password = sanitize_text_field( $_POST['shippop_password'] );
				$shippop_server   = sanitize_text_field( $_POST['shippop_server'] );
				$shippop_testing_mode  = ( !empty( $_POST['shippop_testing_mode'] ) && $_POST['shippop_testing_mode']  == 'on' )  ? true : false;
				$specm_server = ( $shippop_server == 'MY' ) ? 'MY' : 'TH';

				$loginResult = $this->specmAuthMemberServiceSHIPPOP( $specm_server , $shippop_testing_mode , $shippop_email , $shippop_password );

				if ( $loginResult['status'] ) {
					update_option( 'specm_version', get_plugin_data( SPECM_PLUGIN_PATH . "shippop-ecommerce.php", false, false )['Version'] );
					update_option( 'specm_bearer', $loginResult['data']['token'] );
					return wp_redirect( admin_url( 'admin.php?page=shippop-ecommerce-setting' ) );
					die;
				} else {
					$message = ( ! empty( $loginResult['message'] ) ) ? $loginResult['message'] : '';
					?>
					<div class="notice notice-error" style="box-shadow: 0 1px 5px 0 rgb(0 0 0 / 10%);">
						<p style="color: #dc3232;"> <?php echo esc_html__( 'Can’t connect. Please try again later', 'shippop-ecommerce' ); ?> [<?php echo esc_html__( $message, 'shippop-ecommerce' ); ?>] </p>
					</div>
					<?php
				}
			}
		}
		$logo = plugins_url( 'shippop-ecommerce/assets/images/logo_shippop.png' );
		?>
		<div class="clearfix"></div>
		<div class="wrap">
			<div class="specm-wrapper-login">
				<h1 class="login-title"><?php echo esc_html__( 'Login to SHIPPOP', 'shippop-ecommerce' ); ?></h1>
				<form method="post" action="" style="padding: 0px 50px;">
					<?php wp_nonce_field( 'specm_login_form', 'specm_login_form' ); ?>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Email', 'shippop-ecommerce' ); ?></label>
						<input type="email" name="shippop_email" required>
					</div>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Password', 'shippop-ecommerce' ); ?></label>
						<input type="password" name="shippop_password" required>
					</div>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Choose country', 'shippop-ecommerce' ); ?></label>
						<select name="shippop_server" required>
							<option value="TH"><?php echo esc_html__( 'Thailand', 'shippop-ecommerce' ); ?></option>
							<option value="MY"><?php echo esc_html__( 'Malaysia', 'shippop-ecommerce' ); ?></option>
						</select>
					</div>
					<div class="specm-frm-group">
						<label class="specm-checkbox-container"><?php echo esc_html__("Mode", "shippop-ecommerce") . " " . esc_html__("Testing", "shippop-ecommerce"); ?>
							<input type="checkbox" name="shippop_testing_mode">
							<span class="checkmark"></span>
						</label>
					</div>
					<div class="specm-frm-group">
						<button type="submit" name="shippop_login_submit"><?php echo esc_html__( 'Login', 'shippop-ecommerce' ); ?></button>
					</div>
					<div class="specm-frm-group">
						<div style="width: 49%;display: inline-block;text-align: right;">
							<p style="margin-right: 5px;"><?php echo esc_html__( 'Do not have account?', 'shippop-ecommerce' ); ?></p>
						</div>
						<div style="width: 50%;display: inline-block;text-align: left;">
							<button type="button" class="specm-register-btn"><?php echo esc_html__( 'Interested in our services', 'shippop-ecommerce' ); ?></button>
						</div>
					</div>
				</form>
			</div>
			<div class="specm-wrapper-register" style="display: none;">
				<div style="text-align: center;padding: 15px 10px;">
					<img src="<?php echo esc_attr( $logo ); ?>" style="width: 80px;">
				</div>
				<span class="back-to-login"><i class="fa fa-arrow-left" aria-hidden="true"></i></span>
				<h1 class="login-title"><?php echo esc_html__( "Interested in our services? \n Fill this form, and our team will contact you back", 'shippop-ecommerce' ); ?></h1>
				<form method="post" action="" class="specm-form-register" style="padding: 10px 80px;">
					<?php wp_nonce_field( 'specm_register_form', 'specm_register_form' ); ?>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Company name (Optional)', 'shippop-ecommerce' ); ?></label>
						<input type="text" name="shippop_company">
					</div>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Name and Surname', 'shippop-ecommerce' ); ?><span class="register-red-star">★</span></label>
						<input type="text" name="shippop_name" required />
					</div>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Telephone number', 'shippop-ecommerce' ); ?><span class="register-red-star">★</span></label>
						<input type="text" name="shippop_tel" pattern="[0-9]{10}" value="" required />
					</div>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Email', 'shippop-ecommerce' ); ?><span class="register-red-star">★</span></label>
						<input type="email" name="shippop_email" required />
					</div>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Interested courier services', 'shippop-ecommerce' ); ?><span class="register-red-star">★</span></label>
						<input type="text" name="shippop_courier" placeholder="Flash Express , Kerry Express , Ems, J&T Express" required />
					</div>
					<div class="specm-frm-group">
						<label><?php echo esc_html__( 'Choose country', 'shippop-ecommerce' ); ?></label>
						<select name="shippop_server" required>
							<option value="TH"><?php echo esc_html__( 'Thailand', 'shippop-ecommerce' ); ?></option>
							<option value="MY"><?php echo esc_html__( 'Malaysia', 'shippop-ecommerce' ); ?></option>
						</select>
					</div>
					<div class="specm-frm-group">
						<button type="submit" name="shippop_register_submit"><?php echo esc_html__( 'Submit', 'shippop-ecommerce' ); ?></button>
					</div>
				</form>
			</div>
			<div class="specm-wrapper-copyright">
				<?php
					printf(
						"© Copyright 2015-%s All Right Reserved By <a href='%s' target='_blank'>SHIPPOP</a>",
						esc_attr( date( 'Y' ) ),
						esc_url( 'https://www.shippop.com' )
					);
				?>
			</div>
		</div>
		<?php
	}
}
