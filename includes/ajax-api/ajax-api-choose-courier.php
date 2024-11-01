<?php

class SPECM_Ajax_api_Choose_Courier {


	public $Shippop_API = null;
	public $Crud        = null;

	function __construct() {
		$this->Shippop_API = new SPECM_Shippop_API();
		$this->Crud        = new SPECM_Crud_Function();

		add_action( 'wp_ajax_shippop_ecommerce_choose_courier', array( $this, 'shippop_ecommerce_choose_courier' ) );
		add_action( 'wp_ajax_shippop_ecommerce_booking_courier', array( $this, 'shippop_ecommerce_booking_courier' ) );
		add_action( 'wp_ajax_shippop_ecommerce_tracking_purchase', array( $this, 'shippop_ecommerce_tracking_purchase' ) );
	}

	function shippop_ecommerce_choose_courier() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response  = array(
			'status' => false,
		);
		$order_ids = ( ! empty( $_POST['order_ids'] ) ) ? sanitize_text_field( $_POST['order_ids'] ) : false;
		$cc = ( ! empty( $_POST['cc'] ) ) ? sanitize_text_field( $_POST['cc'] ) : false;
		if ( $order_ids ) {
			$specm_product_remark = get_option( 'specm_product_remark', 'N' );
			$from        = specm_helper_get_store_address();
			$data_object = array();
			foreach ( explode( ',', $order_ids ) as $order_id ) {
				if ( get_post_type( $order_id ) != 'shop_order' ) {
					$response['message'] = 'Fail to get order data';
					specm_helper_set_header_output( $response );
				}

				$shippop_server = get_option( 'specm_server', 'TH' );
				$_shipping_country = get_post_meta( $order_id , '_shipping_country' , true );
				if ( strtoupper( $shippop_server ) !== strtoupper( $_shipping_country ) ) {
					$response['message'] = esc_html__( "Can't shipping cross country right now, Please waiting for new update or using SHIPPOP Inter instead" , 'shippop-ecommerce' );
					specm_helper_set_header_output( $response );
				}

				$_order_width  = get_post_meta( $order_id, '_order_width', true );
				$_order_length = get_post_meta( $order_id, '_order_length', true );
				$_order_height = get_post_meta( $order_id, '_order_height', true );

				if ( empty( $_order_width ) ) {
					update_post_meta( $order_id, '_order_width', SPECM_ORDER_WIDTH );
				}

				if ( empty( $_order_length ) ) {
					update_post_meta( $order_id, '_order_length', SPECM_ORDER_LENGTH );
				}

				if ( empty( $_order_height ) ) {
					update_post_meta( $order_id, '_order_height', SPECM_ORDER_HEIGHT );
				}

				$order_data    = specm_helper_get_order_data_object( $order_id );
				$from = apply_filters( 'shippop_ecommerce_hook_change_origin_address', $from, $order_id, $order_data );
				$args          = array(
					'from'    => $from,
					'to'      => $order_data['to'],
					'parcel'  => $order_data['parcel'],
					'showall' => 1,
					'remark'  => esc_html__( 'Order', 'shippop-ecommerce' ) . " : " . $order_id
				);

				if ( $cc ) {
					$args['courier_code'] = $cc;
				}
				if ( $specm_product_remark === 'Y' ) {
					$args['remark'] .= ' [ ' . $order_data['products_name'] . ' ] ';
				}

				$data_object[] = $args;
			}

			$response['data_object'] = $data_object;
			$getPriceOrder           = $this->Shippop_API->specm_GetPriceOrder( $data_object );
			$response['data']        = $getPriceOrder;
			if ( $getPriceOrder && $getPriceOrder['status'] ) {
				$response['status']       = true;
				$response['courier_list'] = $courier_list = $getPriceOrder['data'];
				$response['prepare']      = $prepare_obj = specm_helper_prepare_courier_obj( $courier_list , $order_ids , $cc );
				$response['html']         = $this->choose_courier_html( $prepare_obj , $order_ids , ( $cc ) ? 'on_demand' : 'normal' );
			} else {
				$response['message'] = esc_html__( strtoupper( $getPriceOrder['message'] ), 'shippop-ecommerce' );
				specm_helper_set_header_output( $response );
			}
		}

		specm_helper_set_header_output( $response );
	}

	function shippop_ecommerce_booking_courier() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response       = array(
			'status' => false,
		);
		$order_ids      = ( ! empty( $_POST['order_ids'] ) ) ? sanitize_text_field( $_POST['order_ids'] ) : false;
		$select_courier = ( ! empty( $_POST['select_courier'] ) ) ? sanitize_text_field( $_POST['select_courier'] ) : false;

		if ( ! empty( $order_ids ) && ! empty( $select_courier ) ) {
			$from        = specm_helper_get_store_address();
			$data_object = [];
			$_order_ids = explode( ',', $order_ids );
			$specm_product_remark = get_option( 'specm_product_remark', 'N' );
			foreach ( $_order_ids as $key => $order_id ) {
				if ( get_post_type( $order_id ) != 'shop_order' ) {
					$response['message'] = esc_html__( 'Fail to get order data', 'shippop-ecommerce' );
					specm_helper_set_header_output( $response );
				}

				$_order_width  = get_post_meta( $order_id, '_order_width', true );
				$_order_length = get_post_meta( $order_id, '_order_length', true );
				$_order_height = get_post_meta( $order_id, '_order_height', true );

				if ( empty( $_order_width ) || empty( $_order_length ) || empty( $_order_height ) ) {
					$msg                 = esc_html__( 'Please specify parcel dimension first', 'shippop-ecommerce' );
					$response['message'] = " <a target='_blank' href='" . get_edit_post_link( $order_id ) . "'>#$order_id</a> . ' ' . $msg";
					specm_helper_set_header_output( $response );
				}

				$order_data = specm_helper_get_order_data_object( $order_id );
				$from = apply_filters( 'shippop_ecommerce_hook_change_origin_address', $from, $order_id, $order_data );
				$args       = array(
					'from'         => $from,
					'to'           => $order_data['to'],
					'parcel'       => $order_data['parcel'],
					'product'      => $order_data['products'],
					'courier_code' => $select_courier,
					'order_id'     => $order_id,
					'remark'	   => esc_html__( 'Order', 'shippop-ecommerce' ) . " : " . $order_id
				);

				if ( $specm_product_remark === 'Y' ) {
					$args['remark'] .= ' [ ' . $order_data['products_name'] . ' ]';
				}

				if ( $order_data['cod_amount'] ) {
					$args['cod_amount'] = $order_data['cod_amount'];
				}

				$data_object[] = $args;
			}

			$hash = specm_base64_encode_hash_url( implode("," , $_order_ids) );

			$specm_is_sandbox = get_option( 'specm_is_sandbox', 'Y' );
			$response['data_object']  = $data_object;
			$bookingOrder             = $this->Shippop_API->specm_BookingOrder( $data_object , $hash );
			$response['bookingOrder'] = $bookingOrder;
			if ( $bookingOrder && $bookingOrder['status'] ) {
				$response['status'] = true;
				$purchase_id        = ( empty( $bookingOrder['purchase_id'] ) ) ? 0 : $bookingOrder['purchase_id'];
				$payment_url        = ( empty( $bookingOrder['payment_url'] ) ) ? false : $bookingOrder['payment_url'];
				foreach ( $_order_ids as $key => $order_id ) {
					if ( empty( $bookingOrder['data'][ $key ] ) ) {
						continue;
					}
					$content_data                    		= $bookingOrder['data'][ $key ];
					$content_data['purchase_id']     		= $purchase_id;
					$content_data['request_confirm'] 		= ( $payment_url ) ? false : true;
					$content_data['server']          		= get_option( 'specm_server', 'TH' );
					$content_data['environment_sandbox']    = $specm_is_sandbox;

					foreach ( $content_data as $field => $field_data ) {
						update_post_meta( $order_id, '_booking_' . $field, $field_data );
					}
					update_post_meta( $order_id, '_shippop_purchase_confirm', current_time( 'mysql' ) );
					// For customer export Order custom
					update_post_meta( $order_id, 'shippop_tracking_number', $content_data['tracking_code'] );
					update_post_meta( $order_id, 'shippop_courier_tracking_number', ( isset( $content_data['courier_tracking_code'] ) ) ? $content_data['courier_tracking_code'] : '' );
				}

				if ( $payment_url ) {
					$response['redirect'] = $payment_url;
				} else {
					$response['purchase_id']  = $purchase_id;
					$confirmOrder             = $this->Shippop_API->specm_ConfirmOrder( $purchase_id );
					$response['confirmOrder'] = $confirmOrder;
					if ( $confirmOrder && $confirmOrder['status'] ) {
						$trackingPurchase = $this->Shippop_API->specm_TrackingPurchase( $purchase_id );
						if ( $trackingPurchase && $trackingPurchase['status'] ) {
							$response['TrackingPurchase'] = $this->Crud->specm_crud_update_tracking_purchase_courier_tracking_code( $trackingPurchase['data'] );
						}

						$response['status']   = true;
						$response['message']  = esc_html__( 'Booking confirmed and Payment completed', 'shippop-ecommerce' );
						$response['message2'] = esc_html__( 'Please print waybill', 'shippop-ecommerce' );
						$response['order_ids'] = $_order_ids;
					} else {
						$response['status']  = false;
						$response['message'] = $confirmOrder['message'];
					}
				}
			} else {
				$skip_message = array( 'optional' );
				$tmp_message  = '';
				if ( ! empty( $bookingOrder['message'] ) ) {
					$tmp_message = $bookingOrder['message'];
				} else {
					foreach ( $bookingOrder['data'] as $courier ) {
						foreach ( $courier as $value ) {
							if ( ! in_array( $value['remark'], $skip_message ) ) {
								$tmp_message = $value['courier_name'] . ' : ' . $value['remark'];
								break;
							}
						}
					}
				}

				$response['message'] = $tmp_message;
			}
		}

		specm_helper_set_header_output( $response );
	}
	
	function shippop_ecommerce_tracking_purchase() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response       = array(
			'status' => false,
		);
		$purchaseValidate = [];
		$hash = ( ! empty( $_POST['hash'] ) ) ? sanitize_text_field( $_POST['hash'] ) : false;
		if ( $hash ) {
			$decode = specm_base64_decode_hash_url( $hash );
			$response['decode'] = $decode;
			$response['message']  = esc_html__( 'Decode Fail', 'shippop-ecommerce' );
			if ( $decode !== false && $decode !== '' ) {
				$order_ids = explode("," , $decode);
				$response['order_ids'] = $order_ids;
				$response['message']  = esc_html__( 'Not found orders', 'shippop-ecommerce' );
				if ( !empty($order_ids) ) {
					foreach ($order_ids as $order_id) {
						$purchase_id = get_post_meta( $order_id , '_booking_purchase_id' , true );
						$response['purchase_id'][] = $purchase_id;
						if ( !empty($purchase_id) && !array_key_exists( $purchase_id , $purchaseValidate) ) {
							$trackingPurchase = $this->Shippop_API->specm_TrackingPurchase( $purchase_id );
							if ( $trackingPurchase && $trackingPurchase['status'] && strtoupper($trackingPurchase['purchase_status']) === 'PAID' ) {
								$purchaseValidate[ $purchase_id ] = 'PAID';

								$response['status'] = true;
								$response['message']  = esc_html__( 'Booking confirmed and Payment completed', 'shippop-ecommerce' );
								$response['message2'] = esc_html__( 'Please print waybill', 'shippop-ecommerce' );
								$response['purchaseValidate'] = $purchaseValidate;
								$response['TrackingPurchase'] = $this->Crud->specm_crud_update_tracking_purchase_courier_tracking_code( $trackingPurchase['data'] );
							}
						}
					}
				}
			}
		}

		specm_helper_set_header_output( $response );
	}

	private function specm_error_translate( $msg ) {
		$msg = strtoupper( $msg );
		if ( $msg === 'OPTIONAL' ) {
			return '';
		}
		if ( strpos( $msg, 'MINIMUM' ) !== false && strpos( $msg, 'ORDER' ) !== false ) {
			$min = trim( str_replace( array( 'MINIMUM', 'ORDER' ), '', $msg ) );
			$msg = sprintf( esc_html__( 'MINIMUM %s ORDER', 'shippop-ecommerce' ), $min );
		} else {
			$msg = esc_html__( $msg, 'shippop-ecommerce' );
		}
		// If ENG
		if ( preg_match( '/[^A-Za-z0-9]+/', $msg ) ) {
			return esc_html__( ucfirst( strtolower( $msg ) ), 'shippop-ecommerce' );
		}
		return esc_html__( $msg, 'shippop-ecommerce' );
	}

	function choose_courier_html( $couriers , $order_ids , $courier_type = 'normal' ) {
		global $specm_courier_my_service_type;
		$shippop_server = get_option( 'specm_server', 'TH' );
		$currency = ($shippop_server == 'TH') ? "THB" : "MYR" ;
		$orderFail = $couriers['orderFail'];
		ob_start();
		?>
		<style>
			table.tbl-courier-list {
				border-collapse: collapse;
				width: 100%;
			}

			table.tbl-courier-list td,
			table.tbl-courier-list th {
				border: 1px solid #f9f9f9;
				text-align: left;
				padding: 8px;
			}

			table.tbl-courier-list tr:nth-child(even) {
				background-color: #f9f9f9;
			}
		</style>
		<div class="specm-modal-content">
			<table class="tbl-courier-list modal-table-style">
				<tr class="specm-background-list-column">
					<th style="width: 15%;text-align: center;"><?php echo esc_html__( 'Couriers list', 'shippop-ecommerce' ); ?></th>
					<th style="width: 15%;text-align: center;"><?php echo esc_html__( 'Conditions for pick-up service', 'shippop-ecommerce' ); ?></th>
					<th style="width: 20%;text-align: center;"><?php echo esc_html__( 'Service type', 'shippop-ecommerce' ); ?></th>
					<th style="width: 20%;text-align: center;"><?php echo esc_html__( 'Estimated time', 'shippop-ecommerce' ); ?></th>
					<th style="width: 15%;text-align: center;"><?php echo esc_html__( 'Estimated charge', 'shippop-ecommerce' ); ?> <span style="font-size: 10px;display: block;"> (<?php echo esc_html($currency) ?> )</span> </th>
					<th style="width: 15%;text-align: center;"><?php echo esc_html__( 'Choose', 'shippop-ecommerce' ); ?></th>
				</tr>
				<?php foreach ( $couriers[ $courier_type ] as $courier_code => $courier ) : ?>
					<tr>
						<td style="text-align: center;">
							<img data-courier-code="<?php echo esc_attr( $courier_code ); ?>" src="<?php echo esc_attr( $courier['logo'] ); ?>" style="width: 120px;">
							<div class="clearfix"></div>
								<?php if ($shippop_server == 'TH') : ?>
									<span style="font-size: 10px;"><?php echo esc_html__( $courier['courier_name'] ); ?></span>
								<?php else : ?>
									<span style="font-size: 10px;"><?php echo esc_html__( $courier['courier_name'] ); ?> <br /> (<?php echo esc_html( $courier['pick_up_mode'] ); ?>)</span>
								<?php endif; ?>
							<div class="clearfix"></div>
						</td>
						<td style="text-align: center;">
							<?php if ( ! empty( $courier['notice'] ) ) : ?>
								<span style="font-size: 10px;color: red;"><?php echo wp_kses_data( $courier['notice'] ); ?></span>
							<?php endif; ?>
						</td>
						<?php if ($shippop_server == 'TH') : ?>
							<td style="text-align: center;"><?php echo esc_html( $courier['pick_up_mode'] ); ?></td>
						<?php else : ?>
							<td style="text-align: center;"><?php echo esc_html( isset($specm_courier_my_service_type[$courier_code]) ? $specm_courier_my_service_type[$courier_code] : $courier['pick_up_mode'] ); ?></td>
						<?php endif; ?>
						<td style="text-align: center;"><?php echo esc_html( isset($courier['estimate_time']) ? $courier['estimate_time'] : '' ); ?></td>
						<?php if ( $courier['available'] ) : ?>
							<td style="text-align: center;" class="txt-shippop-main-color"><?php echo wc_price( esc_html( $courier['price'] ) , [ 'currency' => $currency ] ); ?></td>
							<td style="text-align: center;">
								<button type="button" data-courier-code="<?php echo esc_attr( $courier['courier_code'] ); ?>" class="button txt-shippop-main-color bd-shippop-main-color button-select-courier" style="width: 100px;height: 40px;background-color: white;border-radius: 10px;"> <?php echo esc_html__( 'Select', 'shippop-ecommerce' ); ?> </button>
							</td>
						<?php else : ?>
							<td style="text-align: center;">
							<p style="color: red;"> <?php echo $this->specm_error_translate( $courier['remark'] ); ?> </p>
							<?php if ( isset( $orderFail[ $courier_code ] ) ) : ?>
								<p style="color: red;font-style: italic;font-size: 8px;word-break: break-word;"> <?php echo esc_html__( 'Order', 'shippop-ecommerce' ); ?> ( <?php echo esc_html( implode("," , $orderFail[ $courier_code ]) ); ?> ) </p>
							<?php endif; ?>	
						</td>
							<td style="text-align: center;">
								<button type="button" title="<?php echo esc_attr( $courier['remark'] ); ?>" class="button txt-shippop-main-color" style="cursor: not-allowed;width: 100px;height: 40px;background-color: white;border-radius: 10px;" disabled> <?php echo esc_html__( 'Select', 'shippop-ecommerce' ); ?> </button>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</table>

			<!-- <table class="tbl-courier-list modal-table-style" style="width:100%;margin-top: 20px;display: none;">
				<tr class="specm-background-list-column">
					<th style="width: 20%;text-align: center;"><?php echo esc_html__( 'Couriers list', 'shippop-ecommerce' ); ?></th>
					<th style="width: 20%;text-align: center;"><?php echo esc_html__( 'Conditions for pick-up service', 'shippop-ecommerce' ); ?></th>
					<th style="width: 20%;text-align: center;"><?php echo esc_html__( 'Service type', 'shippop-ecommerce' ); ?></th>
					<th style="width: 20%;text-align: center;"><?php echo esc_html__( 'Estimated charge', 'shippop-ecommerce' ); ?></th>
					<th style="width: 20%;text-align: center;"><?php echo esc_html__( 'Choose', 'shippop-ecommerce' ); ?></th>
				</tr>
				<?php foreach ( $couriers['on_demand'] as $courier_code => $courier ) : ?>
					<?php
						$logo = specm_helper_get_courier_logo( $courier_code );
					?>
					<tr>
						<td style="text-align: center;">
							<img src="<?php echo esc_attr( $logo ); ?>" style="width: 120px;">
							<div class="clearfix"></div>
							<?php if ( ! empty( $courier['notice'] ) ) : ?>
								<span style="font-size: 10px;color: red;"><?php echo wp_kses_data( $courier['notice'] ); ?></span>
							<?php endif; ?>
						</td>
						<td style="text-align: center;"><?php echo esc_html( $courier['remark'] ); ?></td>
						<td style="text-align: center;"><?php echo esc_html( $courier['pick_up_mode'] ); ?></td>
						<?php if ( $courier['available'] ) : ?>
							<td style="text-align: center;" class="txt-shippop-main-color"><?php echo wc_price( esc_html( $courier['price'] ) ); ?></td>
							<td style="text-align: center;">
								<button type="button" data-courier-code="<?php echo esc_attr( $courier['courier_code'] ); ?>" class="button button-select-courier" style="width: 100px;"> <?php echo esc_html__( 'Select', 'shippop-ecommerce' ); ?> </button>
							</td>
						<?php else : ?>
							<td style="text-align: center;">-</td>
							<td style="text-align: center;">
								<button type="button" title="<?php echo esc_attr( $courier['remark'] ); ?>" class="button" style="cursor: not-allowed;width: 100px;"> <i class="fa fa-info-circle" aria-hidden="true"></i> </button>
							</td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</table> -->
		</div>
		<div class="specm-modal-footer" style="width: 100%;border-top: solid 2px #b4b9be;display: none;">
			<table style="width: 100%;margin-top: 5px;">
				<tr>
					<td style="width: 20%;padding-left: 10px;"><?php echo count( explode("," , $order_ids) ) . " " . esc_html__( 'Orders', 'shippop-ecommerce' ); ?></td>
					<td style="width: 20%;">&nbsp;</td>
					<td style="width: 20%;">&nbsp;</td>
					<td style="width: 40%;text-align: right;padding-right: 15px;">
					<form id="wp-shippop-ecommerce-booking" method="post">
						<input type="hidden" id="select_courier" name="select_courier" required>
						<input type="hidden" id="order_ids" name="order_ids" value="<?php echo esc_attr( $order_ids ); ?>">
						<button type="button" style="margin: 0px !important;height: 37px !important;width: 80px !important;" class="button alert-modal-button-close"><?php echo esc_html__( 'Cancel', 'shippop-ecommerce' ); ?></button>
						<button type="submit" class="button button-shippop-submit"><?php echo esc_html__( 'Confirm', 'shippop-ecommerce' ); ?></button>
					</form>
					</td>
				</tr>
			</table>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}
}

return new SPECM_Ajax_api_Choose_Courier();
