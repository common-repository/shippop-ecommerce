<?php

class SPECM_Shippop_Setting {

	public $settings     = null;
	public $is_correct   = false;
	public $Utility      = null;
	public $member       = null;
	public $Specm_Bearer = null;
	public $billing_state_message		 = null;

	function __construct( $init = false ) {
		$this->Utility      = new SPECM_Utility();
		$this->settings     = get_option( 'specm_settings' );
		$this->Specm_Bearer = get_option( 'specm_bearer' );

		if ( $init || ( ! empty( $_GET['page'] ) && sanitize_text_field( $_GET['page'] ) == 'shippop-ecommerce-setting' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'specm_init_scripts_style' ) );
			$member       = $this->Utility->specm_post( '/member/', [] );
			$this->member = ( ! empty( $member ) && $member['status'] ) ? $member : array();
		}

		add_action( 'admin_init', array( $this, 'specm_admin_init' ) );
		if ( isset( $this->settings['address']['address'] ) && ! empty( $this->settings['address']['address'] ) ) {
			$this->is_correct = true;
		}
	
		if ( $this->is_correct === false ) {
			add_action( 'admin_menu', array( $this, 'specm_admin_menu' ) );
			add_action( 'admin_notices', array( $this, 'specm_setting_notice' ) );
			add_action( 'admin_notices', array( $this, 'specm_setting_webhook_notice' ) );
		} else {
			if ( $init ) {
				add_action( 'admin_notices', array( $this, 'specm_setting_webhook_notice' ) );
				if ( ! empty( $_REQUEST['settings-updated'] ) && sanitize_text_field( 'settings-updated' ) ) {
					add_action( 'admin_notices', array( $this, 'specm_setting_updated' ) );

					$specm_billing_state = get_option('specm_billing_state');
					if ( $specm_billing_state !== false && $specm_billing_state['status'] === false ) {
						$this->billing_state_message = $specm_billing_state['message'];
						add_action( 'admin_notices', array( $this, 'specm_setting_updated_fail' ) );
					}
			
					if ( !empty($specm_billing_state['member']) ) {
						$this->member = $specm_billing_state['member'];
					}
				}

				$relogin = get_option( 'specm_version');
				$shippop_ecommerce_data = get_plugin_data( SPECM_PLUGIN_PATH . "shippop-ecommerce.php", false, false );
				if ( $relogin === false || $shippop_ecommerce_data['Version'] !== $relogin ) {
					add_action( 'admin_notices', array( $this, 'specm_please_relogin_notice' ) );	
				}
			}
		}
	}

	function specm_init_scripts_style() {
		wp_register_style( 'jquery-ui-css', plugins_url( '/shippop-ecommerce/assets/css/smoothness/jquery-ui.min.css' ), false, '1.0.0', 'all' );
		wp_enqueue_style( 'jquery-ui-css' );

		wp_enqueue_script(
			'shippop_setting_js',
			plugins_url( '/shippop-ecommerce/assets/js/settings.js' ),
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-position' ),
			filemtime( SPECM_PLUGIN_PATH . 'assets/js/settings.js' ),
			true
		);

		wp_localize_script(
			'shippop_setting_js',
			'shippop_setting_js',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( SPECM_AJAX_NONCE ),
			)
		);
	}

	function specm_admin_menu() {
		add_menu_page(
			esc_html__( 'SHIPPOP', 'shippop-ecommerce' ),
			esc_html__( 'SHIPPOP', 'shippop-ecommerce' ),
			'manage_options',
			'shippop-ecommerce-setting',
			array( $this, 'specm_index_page' ),
			plugins_url( 'shippop-ecommerce/assets/images/logo.png' ),
			56
		);
	}

	function specm_setting_notice() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p> <?php echo esc_html__( 'Please make sure that all required information are filled and validated before using SHIPPOP', 'shippop-ecommerce' ); ?> </p>
		</div>
		<?php
	}

	function specm_please_relogin_notice() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p> <?php echo esc_html__( 'Please logout and login SHIPPOP plugin again for a new content in plugin', 'shippop-ecommerce' ); ?> </p>
		</div>
		<?php
	}

	function specm_setting_webhook_notice() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p> <?php echo esc_html__( 'Please make sure your webhook has been set in SHIPPOP via Sales team, close this if done', 'shippop-ecommerce' ); ?> </p>
		</div>
		<?php
	}

	function specm_setting_updated() {
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<div style="color: green;"><i class="fa fa-check-circle" aria-hidden="true"></i> <?php echo esc_html__( 'Information updated', 'shippop-ecommerce' ); ?></div>
			</p>
		</div>
		<?php
	}

	function specm_setting_updated_fail() {
		delete_option('specm_billing_state');
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<div style="color: black;"><i class="fa fa-check-circle" aria-hidden="true"></i> <?php echo esc_html__( 'Billing and invoice addresses', 'shippop-ecommerce' ) . " [ " . esc_html__( $this->billing_state_message, 'shippop-ecommerce' ) . " ] "; ?></div>
			</p>
		</div>
		<?php
	}

	function specm_index_page() {
		$logout = admin_url( 'admin.php?page=shippop-ecommerce-setting-logout' );
		?>
		<div>
			<button style='color: red;border: solid 1px red;position: absolute;right: 20px;margin-top: 30px;' type='button' class='button button-danger specm-logout-btn'><?php echo esc_html__( 'Logout', 'shippop-ecommerce' ); ?></button>
			<input id='specm-logout-url' type='hidden' value='<?php echo esc_attr( $logout ); ?>' />
		</div>
		<div class="clearfix"></div>
		<div class="wrap">
			<form method="post" name="settings" action="options.php">
				<?php
				settings_fields( 'specm_settings' );
				do_settings_sections( 'shippop-settings' );
				do_settings_sections( 'shippop-settings-address' );
				do_settings_sections( 'shippop-settings-billing' );
				?>
				<!-- Modal HTML embedded directly into document -->
				<div id="shippop-settings-address-modal" class="modal modal-shippop">
					<div style="height: 50px;">
						<div class="modal-title"><?php echo esc_html__( "Please choose correct store's address for pick-up service", 'shippop-ecommerce' ); ?></div>
						<div class="modal-close">
							<a href="javascript:void(0)" rel="modal:close">
								<span rel="modal:close">X</span>
							</a>
						</div>
					</div>

					<div class="modal-content">

					</div>
				</div>
			</form>
		</div>
		<?php
	}

	public function specm_admin_init() {
		register_setting(
			'specm_settings', // Option group
			'specm_settings', // Option name
			array( $this, 'sanitize' ) // Sanitize
		);

		add_settings_section(
			'setting_section_id', // ID
			esc_html__( 'Settings', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_print_section_info' ), // Callback
			'shippop-settings' // Page
		);

		add_settings_field(
			'email_token', // ID
			esc_html__( 'Email', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_email_token_callback' ), // Callback
			'shippop-settings', // Page
			'setting_section_id' // Section
		);

		// add_settings_field(
		// 	'member_information', // ID
		// 	esc_html__( 'Type', 'shippop-ecommerce' ), // Title
		// 	array( $this, 'specm_member_information_callback' ), // Callback
		// 	'shippop-settings', // Page
		// 	'setting_section_id' // Section
		// );

		add_settings_field(
			'server', // ID
			esc_html__( 'Choose country', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_server_callback' ), // Callback
			'shippop-settings', // Page
			'setting_section_id' // Section
		);

		add_settings_field(
			'sandbox', // ID
			esc_html__( 'Mode', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_sandbox_callback' ), // Callback
			'shippop-settings', // Page
			'setting_section_id' // Section
		);

		add_settings_field(
			'product_remark', // ID
			esc_html__( 'Show product name in remark', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_product_remark_callback' ), // Callback
			'shippop-settings', // Page
			'setting_section_id' // Section
		);

		add_settings_field(
			'advance_setting', // ID
			esc_html__( 'Show advance setting', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_advance_setting_callback' ), // Callback
			'shippop-settings', // Page
			'setting_section_id' // Section
		);

		// --------------------------------

		add_settings_section(
			'setting_section_id', // ID
			esc_html__( "Store's address for pick-up service", 'shippop-ecommerce' ), // Title
			array( $this, 'specm_print_section_info' ), // Callback
			'shippop-settings-address' // Page
		);

		add_settings_field(
			'address_name', // ID
			esc_html__( 'Name and Surname', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_address_name_callback' ), // Callback
			'shippop-settings-address', // Page
			'setting_section_id' // Section
		);

		add_settings_field(
			'address_tel', // ID
			esc_html__( 'Contact number', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_address_tel_callback' ), // Callback
			'shippop-settings-address', // Page
			'setting_section_id' // Section
		);

		add_settings_field(
			'address_address', // ID
			esc_html__( 'Address', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_address_address_callback' ), // Callback
			'shippop-settings-address', // Page
			'setting_section_id' // Section
		);

		add_settings_section(
			'setting_section_label_id', // ID
			esc_html__( "Store's address for label", 'shippop-ecommerce' ), // Title
			array( $this, 'specm_print_section_info' ), // Callback
			'shippop-settings-address' // Page
		);

		add_settings_field(
			'label_address_name', // ID
			esc_html__( 'Name and Surname', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_label_address_name_callback' ), // Callback
			'shippop-settings-address', // Page
			'setting_section_label_id' // Section
		);

		add_settings_field(
			'label_address_tel', // ID
			esc_html__( 'Contact number', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_label_address_tel_callback' ), // Callback
			'shippop-settings-address', // Page
			'setting_section_label_id' // Section
		);

		add_settings_field(
			'label_address_addressss', // ID
			esc_html__( 'Address', 'shippop-ecommerce' ), // Title
			array( $this, 'specm_label_address_address_callback' ), // Callback
			'shippop-settings-address', // Page
			'setting_section_label_id' // Section
		);

		$specm_server = get_option( 'specm_server', 'TH' );
		if ( strtoupper( $specm_server ) === 'MY' ) {

			add_settings_field(
				'address_state', // ID
				esc_html__( 'State', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_address_state_callback' ), // Callback
				'shippop-settings-address', // Page
				'setting_section_id' // Section
			);

			add_settings_field(
				'address_district', // ID
				esc_html__( 'District', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_address_district_callback' ), // Callback
				'shippop-settings-address', // Page
				'setting_section_id' // Section
			);

			add_settings_field(
				'address_province', // ID
				esc_html__( 'Province', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_address_province_callback' ), // Callback
				'shippop-settings-address', // Page
				'setting_section_id' // Section
			);

			add_settings_field(
				'address_postcode', // ID
				esc_html__( 'Zipcode', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_address_postcode_callback' ), // Callback
				'shippop-settings-address', // Page
				'setting_section_id' // Section
			);
		}

		if ( !empty( $this->member['billing'] ) ) {
			add_settings_field(
				'address_billing_clone', // ID
				'', // Title
				array( $this, 'specm_address_billing_clone_callback' ), // Callback
				'shippop-settings-address', // Page
				'setting_section_id' // Section
			);

			// --------------------------------

			add_settings_section(
				'setting_section_id', // ID
				esc_html__( 'Billing and invoice addresses', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_print_section_info' ), // Callback
				'shippop-settings-billing' // Page
			);

			add_settings_field(
				'billing_name', // ID
				esc_html__( 'Name and Surname', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_billing_name_callback' ), // Callback
				'shippop-settings-billing', // Page
				'setting_section_id' // Section
			);

			add_settings_field(
				'billing_tax_id', // ID
				esc_html__( 'Tax ID', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_billing_tax_id_callback' ), // Callback
				'shippop-settings-billing', // Page
				'setting_section_id' // Section
			);

			add_settings_field(
				'billing_tel', // ID
				esc_html__( 'Contact number', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_billing_tel_callback' ), // Callback
				'shippop-settings-billing', // Page
				'setting_section_id' // Section
			);

			add_settings_field(
				'billing_address', // ID
				esc_html__( 'Address', 'shippop-ecommerce' ), // Title
				array( $this, 'specm_billing_address_callback' ), // Callback
				'shippop-settings-billing', // Page
				'setting_section_id' // Section
			);

			add_settings_field(
				'submit_address', // ID
				'', // Title
				array( $this, 'specm_submit_callback' ), // Callback
				'shippop-settings-billing', // Page
				'setting_section_id' // Section
			);
		} else {
			add_settings_section(
				'setting_section_lasted_id', // ID
				"", // Title
				"", // Callback
				'shippop-settings-address' // Page
			);
	
			add_settings_field(
				'submit_address', // ID
				'', // Title
				array( $this, 'specm_submit_callback' ), // Callback
				'shippop-settings-address', // Page
				'setting_section_lasted_id' // Section
			);
		}
	}

	public function sanitize( $input ) {
		// if (is_email(sanitize_text_field($input['email_token'])) === false) {
		// return wp_redirect( admin_url("shippop-ecommerce-setting") );
		// wp_die();
		// }
		$new_input                       = array();
		$new_input['product_remark']     = sanitize_text_field( $input['product_remark'] );
		$new_input['choose_parcel_status'] = sanitize_text_field( $input['choose_parcel_status'] );
		$new_input['address']['name']    = sanitize_text_field( $input['address']['name'] );
		$new_input['address']['address'] = sanitize_textarea_field( $input['address']['address'] );
		$new_input['address']['tel']     = sanitize_text_field( $input['address']['tel'] );

		$new_input['label']['name']    = sanitize_text_field( $input['label']['name'] );
		$new_input['label']['address'] = sanitize_textarea_field( $input['label']['address'] );
		$new_input['label']['tel']     = sanitize_text_field( $input['label']['tel'] );

		$new_input['address']['state']    = sanitize_text_field( $input['address']['state'] );
		$new_input['address']['district'] = sanitize_text_field( $input['address']['district'] );
		$new_input['address']['province'] = sanitize_text_field( $input['address']['province'] );
		$new_input['address']['postcode'] = sanitize_text_field( $input['address']['postcode'] );

		$specm_advance_setting = [];
		if ( !empty($new_input['choose_parcel_status']) ) {
			$specm_advance_setting['choose_parcel_status'] = sanitize_text_field( $new_input['choose_parcel_status'] );
		} else {
			$specm_advance_setting['choose_parcel_status'] = "wc-processing";
		}

		update_option( 'specm_advance_setting' , $specm_advance_setting );
		update_option( 'specm_product_remark' , (empty($new_input['product_remark'])) ? "N" : "Y" );

		if ( isset($input['billing']) && !empty($input['billing']) ) {
			$billing_name_title = sanitize_text_field( $input['billing']['name_title'] );
			$billing_name       = sanitize_text_field( $input['billing']['name'] );
			$billing_tax_id     = sanitize_text_field( $input['billing']['tax_id'] );
			$billing_tel        = sanitize_text_field( $input['billing']['tel'] );
			$billing_address    = sanitize_textarea_field( $input['billing']['address'] );
	
			if ( ! empty( $billing_name ) && ! empty( $billing_tax_id ) && ! empty( $billing_tel ) && ! empty( $billing_address ) ) {
				$member   = $this->Utility->specm_post(
					'/billing/update/',
					$data = array(
						'data' => array(
							'name_title' => $billing_name_title,
							'name'       => $billing_name,
							'tax_id'     => $billing_tax_id,
							'phone'      => $billing_tel,
							'address'    => $billing_address,
						),
					)
				);
	
				if ( $member['status'] ) {
					$this->member['data']['billing'] = $member['data'];
					update_option('specm_billing_state', [ 'status' => true, 'message' => '', 'member' => $member ]);
				} else {
					update_option('specm_billing_state', [ 'status' => false, 'message' => $member['message'], 'member' => [] ]);
				}
			}
		}

		return $new_input;
	}

	public function specm_print_section_info() {
		echo '<hr />';
	}

	public function specm_email_token_callback() {
		$specm_email_account = get_option( 'specm_email_account', '' );
		printf(
			'<input type="email" class="specm-settings-input" id="email_token" name="specm_settings[email_token]" value="%s" readonly />',
			esc_attr( $specm_email_account )
		);
	}

	// public function specm_member_information_callback() {
	// 	$specm_member_information = get_option( 'specm_member_information', [] );
	// 	printf(
	// 		'<input type="text" class="specm-settings-input" id="member_info" name="specm_settings[member_info]" value="%s" readonly />',
	// 		esc_attr( (isset($specm_member_information['clientType'])) ? $specm_member_information['clientType'] : "Prepaid" )
	// 	);
	// }

	public function specm_server_callback() {
		$specm_server = get_option( 'specm_server', 'TH' );
		printf(
			'<input type="text" class="specm-settings-input" value="%s" readonly />',
			( strtoupper( $specm_server ) === 'TH' ) ? esc_attr__( 'Thailand', 'shippop-ecommerce' ) : esc_attr__( 'Malaysia', 'shippop-ecommerce' )
		);
	}

	public function specm_sandbox_callback() {
		$specm_is_sandbox = get_option( 'specm_is_sandbox', 'N' );
		printf(
			'<input type="text" class="specm-settings-input" value="%s" readonly />',
			( strtoupper( $specm_is_sandbox ) === 'Y' ) ? esc_attr__( 'Testing', 'shippop-ecommerce' ) : esc_attr__( 'Live', 'shippop-ecommerce' )
		);
	}
	
	public function specm_product_remark_callback() {
		$specm_product_remark = get_option( 'specm_product_remark', 'N' );
		printf(
			'<input type="checkbox" id="product_remark" name="specm_settings[product_remark]" class="specm-settings-input" %s/>',
			( strtoupper( $specm_product_remark ) === 'Y' ) ? "checked" : ""
		);
	}

	public function specm_advance_setting_callback() {
		printf(
			'<input type="checkbox" id="advance_setting" class="specm-settings-input" %s/>',
			""
		);

		printf(
			'<div class="clearfix"></div><hr />',
			""
		);
		
		$specm_advance_setting = get_option( 'specm_advance_setting', [] );
		printf(
			'<div class="hide-advance-setting">%s</div>
			<textarea name="specm_settings[choose_parcel_status]" class="specm-settings-input hide-advance-setting" id="choose_parcel_status" cols="20"  style="resize: none;" rows="2">%s</textarea>',
			esc_attr__( 'Status filter in choose courier page', 'shippop-ecommerce' ),
			isset( $specm_advance_setting['choose_parcel_status'] ) ? esc_attr( $specm_advance_setting['choose_parcel_status'] ) : 'wc-processing'
		);
	}

	public function specm_address_name_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="address_name" name="specm_settings[address][name]" value="%s" placeholder="%s" required />',
			isset( $this->settings['address']['name'] ) ? esc_attr( $this->settings['address']['name'] ) : '',
			esc_html__( 'Name and Surname', 'shippop-ecommerce' )
		);
	}

	public function specm_address_tel_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="address_tel" name="specm_settings[address][tel]" pattern="[0-9]{10}" value="%s" placeholder="%s" required />',
			isset( $this->settings['address']['tel'] ) ? esc_attr( $this->settings['address']['tel'] ) : '',
			esc_html__( 'Contact number', 'shippop-ecommerce' )
		);
	}

	public function specm_address_address_callback() {
		printf(
			'<p style="color: red;"></p><textarea class="specm-settings-input" name="specm_settings[address][address]" id="address_address" cols="20"  style="resize: none;" rows="5" placeholder="%s" required>%s</textarea>',
			esc_html__( 'Please fill in your address', 'shippop-ecommerce' ),
			isset( $this->settings['address']['address'] ) ? esc_attr( $this->settings['address']['address'] ) : ''
		);
	}

	public function specm_label_address_name_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="label_address_name" name="specm_settings[label][name]" value="%s" placeholder="%s" />',
			isset( $this->settings['label']['name'] ) ? esc_attr( $this->settings['label']['name'] ) : '',
			esc_html__( 'Name and Surname', 'shippop-ecommerce' )
		);
	}

	public function specm_label_address_tel_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="label_address_tel" name="specm_settings[label][tel]" value="%s" placeholder="%s" />',
			isset( $this->settings['label']['tel'] ) ? esc_attr( $this->settings['label']['tel'] ) : '',
			esc_html__( 'Contact number', 'shippop-ecommerce' )
		);
	}

	public function specm_label_address_address_callback() {
		printf(
			'<textarea class="specm-settings-input" name="specm_settings[label][address]" id="label_address_address" cols="20"  style="resize: none;" rows="5" placeholder="%s">%s</textarea>',
			esc_html__( 'Please fill in your address', 'shippop-ecommerce' ),
			isset( $this->settings['label']['address'] ) ? esc_attr( $this->settings['label']['address'] ) : ''
		);
	}

	public function specm_address_billing_clone_callback() {
		printf(
			'<input type="checkbox" id="address_billing_clone" value="%s"><label for="address_billing_clone">%s</label>',
			'clone',
			esc_html__( "Copy store's address intro billing address", 'shippop-ecommerce' )
		);
	}

	public function specm_address_state_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="address_state" name="specm_settings[address][state]" value="%s" required />',
			isset( $this->settings['address']['state'] ) ? esc_attr( $this->settings['address']['state'] ) : ''
		);
	}

	public function specm_address_district_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="address_district" name="specm_settings[address][district]" value="%s" required />',
			isset( $this->settings['address']['district'] ) ? esc_attr( $this->settings['address']['district'] ) : ''
		);
	}

	public function specm_address_province_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="address_province" name="specm_settings[address][province]" value="%s" required />',
			isset( $this->settings['address']['province'] ) ? esc_attr( $this->settings['address']['province'] ) : ''
		);
	}

	public function specm_address_postcode_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="address_postcode" name="specm_settings[address][postcode]" value="%s" required/>',
			isset( $this->settings['address']['postcode'] ) ? esc_attr( $this->settings['address']['postcode'] ) : ''
		);
	}

	public function specm_billing_name_callback() {
		printf(
			'<input type="hidden" class="specm-settings-input" id="billing_name_title" name="specm_settings[billing][name_title]" value="%s" />',
			isset( $this->member['data']['billing']['name_title'] ) ? esc_attr( $this->member['data']['billing']['name_title'] ) : ''
		);

		printf(
			'<input type="text" class="specm-settings-input" id="billing_name" name="specm_settings[billing][name]" value="%s"/>',
			isset( $this->member['data']['billing']['name'] ) ? esc_attr( $this->member['data']['billing']['name'] ) : ''
		);
	}

	public function specm_billing_tax_id_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="billing_tax_id" name="specm_settings[billing][tax_id]" value="%s"/>',
			isset( $this->member['data']['billing']['tax_id'] ) ? esc_attr( $this->member['data']['billing']['tax_id'] ) : ''
		);
	}

	public function specm_billing_tel_callback() {
		printf(
			'<input type="text" class="specm-settings-input" id="billing_tel" name="specm_settings[billing][tel]" pattern="[0-9]{10}" value="%s"/>',
			isset( $this->member['data']['billing']['phone'] ) ? esc_attr( $this->member['data']['billing']['phone'] ) : ''
		);
	}

	public function specm_billing_address_callback() {
		printf(
			'<p style="color: red;"></p><textarea name="specm_settings[billing][address]" class="specm-settings-input" id="billing_address" cols="20"  style="resize: none;" rows="5">%s</textarea>',
			isset( $this->member['data']['billing']['address'] ) ? esc_attr( $this->member['data']['billing']['address'] ) : ''
		);
	}

	public function specm_submit_callback() {
		$specm_server = get_option( 'specm_server', 'TH' );
		if ( strtoupper( $specm_server ) === 'TH' ) {
			$address_corrector_class = 'specm-addr-corrector';
		} else {
			$address_corrector_class = 'specm-addr-corrector-auto';
		}
		printf(
			'<button type="button" class="button button-primary primary %s">%s</button>',
			$address_corrector_class,
			esc_html__( 'Save', 'shippop-ecommerce' )
		);
		submit_button( esc_html__( 'Save', 'shippop-ecommerce' ), 'primary specm-elm-hide' );
	}
}
