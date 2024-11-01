<?php

class SPECM_Ajax_api_Address_Corrector {

	public $Shippop_API = null;

	function __construct() {
		$this->Shippop_API = new SPECM_Shippop_API();

		add_action( 'wp_ajax_shippop_address_corrector', array( $this, 'shippop_address_corrector' ) );
	}

	function shippop_address_corrector() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response        = array(
			'status' => false,
		);
		$address         = ( ! empty( $_POST['address'] ) ) ? sanitize_textarea_field( $_POST['address'] ) : false;
		$billing_address = ( ! empty( $_POST['billing_address'] ) ) ? sanitize_textarea_field( $_POST['billing_address'] ) : false;
		// $response['address'] = $address;
		// $response['billing_address'] = $billing_address;
		// if ($address && $billing_address) {
		$shippop_server              = get_option( 'specm_server', 'TH' );
		$_address_address_suggestion = array(
			'type'       => 1,
			'status'     => true,
			'suggestion' => array(
				array(
					'state'    => '',
					'district' => '',
					'province' => '',
					'postcode' => '',
				),
			),
		);
		$_billing_address_suggestion = array(
			'type'   => 1,
			'status' => true,
		);
		if ( strtoupper( $shippop_server ) === 'TH' ) {
			if ( $address ) {
				$address_address_suggestion = $this->Shippop_API->specm_Pyadc( $address );
			} else {
				$address_address_suggestion = $_address_address_suggestion;
			}

			if ( $billing_address ) {
				$billing_address_suggestion = $this->Shippop_API->specm_Pyadc( $billing_address );
			} else {
				$billing_address_suggestion = $_billing_address_suggestion;
			}

			$response = array(
				'address_address_suggestion' => $address_address_suggestion,
				'billing_address_suggestion' => $billing_address_suggestion,
				'html'                       => $this->specm_corrector_address_html( $address_address_suggestion, $billing_address_suggestion ),
				'status'                     => true,
				'shippop_server'             => $shippop_server,
			);
		} else {
			// fix response if they is malaysia user
			$response = array(
				'address_address_suggestion' => $_address_address_suggestion,
				'billing_address_suggestion' => $_billing_address_suggestion,
				'status'                     => true,
				'shippop_server'             => $shippop_server,
			);
		}

		header( 'Content-type: application/json; charset=utf-8' );
		echo json_encode( $response );
		die;
	}

	function specm_corrector_address_html( $address_address_suggestion, $billing_address_suggestion ) {
		$html = '';
		if ( $address_address_suggestion['status'] && $billing_address_suggestion['status'] ) :
			ob_start();

			?>
		<div id="address-corrector-form" style="padding: 15px;">
			<?php foreach ( $address_address_suggestion['suggestion'] as $key => $value ) : ?>
				<p> <input <?php echo ( $key == 0 ) ? esc_attr( 'checked' ) : ''; ?> type="radio" name="correct_address" value="<?php echo esc_attr( $key ); ?>" data-full="<?php echo esc_attr( $value['full'] ); ?>" data-state="<?php echo esc_attr( $value['state'] ); ?>" data-district="<?php echo esc_attr( $value['district'] ); ?>" data-province="<?php echo esc_attr( $value['province'] ); ?>" data-postcode="<?php echo esc_attr( $value['postcode'] ); ?>" /> <?php echo esc_html__( $value['full'] ); ?> </p>
			<?php endforeach; ?>
			<div class="clearfix"></div>

			<hr style="margin-top: 20px;margin-bottom: 20px;" />
			
			<?php if ( ! empty( $billing_address_suggestion['suggestion'] ) ) : ?>
				<p style="font-size: 16px;font-weight: bold;"> <?php echo esc_html__( 'Please choose correct billing address for billing service', 'shippop-ecommerce' ); ?> </p>
				<?php foreach ( $billing_address_suggestion['suggestion'] as $key => $value ) : ?>
					<p> <input <?php echo ( $key == 0 ) ? esc_attr( 'checked' ) : ''; ?> type="radio" name="correct_address_billing" value="<?php echo esc_attr( $key ); ?>" data-full="<?php echo esc_attr( $value['full'] ); ?>" data-state="<?php echo esc_attr( $value['state'] ); ?>" data-district="<?php echo esc_attr( $value['district'] ); ?>" data-province="<?php echo esc_attr( $value['province'] ); ?>" data-postcode="<?php echo esc_attr( $value['postcode'] ); ?>" /> <?php echo esc_html__( $value['full'] ); ?> </p>
				<?php endforeach; ?>
				<div class="clearfix"></div>
			<?php endif; ?>

			<div style="display: block;margin: 0 auto;text-align: center;position: relative;top: 10px;">
				<button type="button" data-form-name="settings" class="button button-primary"><?php echo esc_html__( 'Select', 'shippop-ecommerce' ); ?></button>
			</div>
		</div>
			<?php
			$html = ob_get_clean();
		endif;

		return $html;
	}

}

return new SPECM_Ajax_api_Address_Corrector();
