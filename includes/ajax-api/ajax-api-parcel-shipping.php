<?php

class SPECM_Ajax_api_Parcel_Shipping {

	public $Shippop_API = null;
	public $Crud        = null;

	function __construct() {
		$this->Shippop_API = new SPECM_Shippop_API();
		$this->Crud        = new SPECM_Crud_Function();

		add_action( 'wp_ajax_parcel_shipping_tracking', array( $this, 'parcel_shipping_tracking' ) );
		add_action( 'wp_ajax_parcel_shipping_tracking_multiple', array( $this, 'parcel_shipping_tracking_multiple' ) );
		add_action( 'wp_ajax_parcel_shipping_count_data_status', array( $this, 'parcel_shipping_count_data_status' ) );
		add_action( 'wp_ajax_parcel_shipping_purchase_cancel', array( $this, 'parcel_shipping_purchase_cancel' ) );
		add_action( 'wp_ajax_parcel_shipping_print_label', array( $this, 'parcel_shipping_print_label' ) );
	}

	function parcel_shipping_tracking() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response      = array(
			'status' => false,
		);
		$tracking_code = ( ! empty( $_POST['tracking_code'] ) ) ? sanitize_text_field( $_POST['tracking_code'] ) : false;
		$order_id      = ( ! empty( $_POST['order_id'] ) ) ? sanitize_text_field( $_POST['order_id'] ) : false;
		if ( $tracking_code ) {
			$getTrackingOrder = $this->Shippop_API->specm_GetTrackingOrder( $tracking_code );
			$response['getTrackingOrder'] = $getTrackingOrder;
			if ( $getTrackingOrder && $getTrackingOrder['status'] ) {
				$response['status']    = true;
				$response['html']      = $this->specm_shipping_history_html( $getTrackingOrder , $order_id );
				$response['order_ids'] = $this->Crud->specm_crud_update_shipping_status( $tracking_code , $getTrackingOrder['order_status'] , $getTrackingOrder , false );
			} else {
				$response['message'] = $getTrackingOrder['message'];
			}
		}

		specm_helper_set_header_output( $response );
	}

	function parcel_shipping_tracking_multiple() {
		global $specm_shippop_status;
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response = array(
			'status'  => false,
			'message' => ''
		);
		$countStatus = [
			'booking' => 0,
			'shipping' 	=> 0,
			'complete' 	=> 0,
			'return' 	=> 0,
			'cancel' 	=> 0
		];
		$bulk_tracking_codes = isset( $_POST['bulk_ids'] ) ? (array) $_POST['bulk_ids'] : array(); // phpcs:ignore WordPress.Security.NonceVerification
		foreach ($bulk_tracking_codes as $order_id => $tracking_code) {
			if ( $tracking_code ) {
				$getTrackingOrder = $this->Shippop_API->specm_GetTrackingOrder( $tracking_code );
				$response['getTrackingOrder'][] = $getTrackingOrder;
				if ( $getTrackingOrder && $getTrackingOrder['status'] ) {
					$response['status'] = true;
					$response['order_ids'][$order_id] = $this->Crud->specm_crud_update_shipping_status( $tracking_code , $getTrackingOrder['order_status'] , $getTrackingOrder , false );
				
					$countStatus[ $getTrackingOrder['order_status'] ] = $countStatus[ $getTrackingOrder['order_status'] ] + 1;
				} else {
					$response['message'] .= $tracking_code . ",";
				}
			}
		}
		$response['countStatus'] = $countStatus;
		foreach ($specm_shippop_status as $key => $text) {
			$response['message'] .= $text . " : " . $countStatus[ $key ];
			if ( end( $specm_shippop_status ) != $text ) {
				$response['message'] .= " <br /> ";
			}
		}
		specm_helper_set_header_output( $response );
	}

	function parcel_shipping_print_label() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response = array(
			'status'  => false,
			'message' => esc_html__( 'Error', 'shippop-ecommerce' ),
		);

		$bulk_ids = isset( $_POST['bulk_id'] ) ? array_map( 'absint', (array) $_POST['bulk_id'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
		if ( count( $bulk_ids ) > 0 ) {
			$tracking_code = array();
			foreach ( $bulk_ids as $bulk_id ) {
				if ( $bulk_id ) {
					$tracking_code[] = get_post_meta( esc_attr( $bulk_id ), '_booking_tracking_code', true );
				}
			}
			$tracking_code = array_unique( array_filter( $tracking_code ) );
			$response      = specm_helper_preprint_label( $tracking_code, sanitize_text_field( $_POST['printlabel_size'] ), 'html' );
		}

		specm_helper_set_header_output( $response );
	}

	function parcel_shipping_count_data_status() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response = array(
			'status' => false,
		);
		global $specm_shippop_status;
		$data_count = array();
		$all        = 0;
		$specm_advance_setting = get_option( 'specm_advance_setting', [] );
		$statuses = isset( $specm_advance_setting['choose_parcel_status'] ) ? explode( "," , esc_attr( $specm_advance_setting['choose_parcel_status'] ) ) : [ 'wc-processing', 'wc-canceled', 'wc-completed', 'wc-refunded' ];
		foreach ( $specm_shippop_status as $status => $status_name ) {
			$statuses     = array_merge( $statuses , array( 'wc-processing', 'wc-canceled', 'wc-completed', 'wc-refunded' ) );
			$statuses	  = array_unique( $statuses );
			$meta_query   = array(
				'relation' => 'AND',
			);
			$meta_query[] = array(
				'key'     => '_use_shippop_shipping',
				'value'   => 'Y',
				'compare' => '=',
			);

			$meta_query[] = array(
				'key'     => '_confirm_purchase_success',
				'value'   => '1',
				'compare' => '=',
			);

			$meta_query[] = array(
				'key'     => '_shippop_status',
				'value'   => $status,
				'compare' => '=',
			);

			$args                  = array(
				'post_status' => $statuses,
				'post_type'   => 'shop_order',
				'fields'      => 'ids',
				'meta_query'  => $meta_query,
				'posts_per_page' => -1
			);
			$query                 = new WP_Query( $args );
			$data_count[ $status ] = $query->found_posts;
			$all                  += $query->found_posts;

		}
		$data_count['all']      = $all;
		$response['data_count'] = $data_count;
		$response['status']     = true;

		specm_helper_set_header_output( $response );
	}

	function parcel_shipping_purchase_cancel() {
		$nonce = sanitize_text_field( $_POST['nonce'] );
		if ( ! wp_verify_nonce( $nonce, SPECM_AJAX_NONCE ) ) {
			die( 'Busted!' );
		}
		$response      = array(
			'status' => false,
		);
		$tracking_code = ( ! empty( $_POST['tracking_code'] ) ) ? sanitize_text_field( $_POST['tracking_code'] ) : false;
		$order_id      = ( ! empty( $_POST['order_id'] ) ) ? sanitize_text_field( $_POST['order_id'] ) : false;
		if ( $tracking_code && $order_id ) {
			$purchaseCancel         = $this->Shippop_API->specm_PurchaseCancel( $tracking_code );
			$purchaseCancel['data'] = $purchaseCancel;
			if ( $purchaseCancel && $purchaseCancel['status'] ) {
				// $this->Crud->specm_crud_remove_option_order($order_id);
				update_post_meta( $order_id, '_shippop_status', 'cancel' );
				$response['status']  = true;
				$response['message'] = ( ! empty( $purchaseCancel['message'] ) ) ? $purchaseCancel['message'] : esc_html__( 'Success', 'shippop-ecommerce' );
			} else {
				$response['message'] = $purchaseCancel['code'] . ' - ' . $purchaseCancel['message'];
			}
		}

		specm_helper_set_header_output( $response );
	}

	function specm_shipping_history_html( $TrackingOrder, $order_id ) {
		ob_start();
		global $specm_shippop_status;
		global $specm_shippop_order_statuses_color;

		// Fix for show status
		$specm_shippop_status['wait'] = esc_html__( 'Pending', 'shippop-ecommerce' );

		$logo = specm_helper_get_courier_logo( $TrackingOrder['courier_code'] );
		?>
		<style>
			.block-tab-content {
				border: 1px solid #ccc;
			}

			.tracking-state {
				border-top: 1px solid #e3e3e3;
			}

			.tracking-state {
				background: #fff;
				color: #555;
				min-height: 150px;
				position: relative;
				padding: 20px 0;
			}

			.tracking-state .line {
				position: absolute;
				width: 1px;
				height: 100%;
				background: #e3e3e3;
				top: 0;
				left: 313px;
			}

			.tracking-state .state {
				position: relative;
				padding: 10px;
				display: block;
			}

			.tracking-state .state-marker {
				position: absolute;
				left: 285px;
				font-size: 20px;
				color: #e3e3e3;
				height: 33px;
				line-height: 19px;
				margin: 0 20px;
			}

			.tracking-state .state-datetime {
				text-align: right;
				float: left;
			}

			.tracking-state .state-description {
				float: left;
				margin-left: 60px;
				margin-top: -2px;
				width: 45%;
				text-align: left;
			}

			.tracking-state .state-datetime .vertical-middle {
				width: 270px;
			}

			.tracking-state .state-datetime .vertical-middle .inner .date, .tracking-state .state-datetime .vertical-middle .inner .time, .tracking-state .state-datetime .vertical-middle .inner .hr-time {
				display: inline-block;
			}

			.clear-float {
				clear: both;
			}

			.main-color {
				color: #0b9dd1;
			}
		</style>
		<div style="padding: 15px;overflow-y: auto;">
			<div class="block-tab-content">
				<table class="tbl-shipping-history-list" style="width:100%;padding: 5px;font-size: 12px;">
					<tr>
						<td style="width: 50%;">
							<img src="<?php echo esc_attr( $logo ); ?>" style="display: block;margin: 0 auto;width: 120px;">
						</td>
						<td style="width: 50%;">
							<div style="margin-top: 5px;"> <span><?php echo esc_html__( 'Order', 'shippop-ecommerce' ); ?></span> : #<?php echo esc_html( $order_id ); ?> </div>
							<div style="margin-top: 5px;"> <span><?php echo esc_html__( "SHIPPOP's tracking number", 'shippop-ecommerce' ); ?></span> : <?php echo esc_html( $TrackingOrder['tracking_code'] ); ?> </div>
							<div style="margin-top: 5px;"> <span><?php echo esc_html__( "Courier's tracking number", 'shippop-ecommerce' ); ?></span> : <?php echo esc_html( $TrackingOrder['courier_tracking_code'] ); ?> </div>
							<div style="margin-top: 5px;"> <span><?php echo esc_html__( 'Status', 'shippop-ecommerce' ); ?></span> : <span style="font-size:10px;padding: 5px;background-color: <?php echo esc_attr( $specm_shippop_order_statuses_color[ $TrackingOrder['order_status'] ][0] ); ?>;color: <?php echo esc_attr( $specm_shippop_order_statuses_color[ $TrackingOrder['order_status'] ][1] ); ?>"> <?php echo esc_html__( $specm_shippop_status[ $TrackingOrder['order_status'] ], 'shippop-ecommerce' ); ?> </span> </div>
						</td>
					</tr>
				</table>
				<div class="tracking-state">
					<?php if ( count( $TrackingOrder['state'] ) == 0 ) : ?>
						<p style="text-align: center;"> <?php echo __( 'Waiting shipping information update.', 'shippop-ecommerce' ); ?> </p>
					<?php else : ?>
						<div class="line"></div>
					<?php endif; ?>
					<?php foreach ( $TrackingOrder['state'] as $state ) : ?>
						<?php
						$dt = specm_helper_dateThai( $state['datetime'] );
						?>
						<div class="state">
							<div class="state-marker">
								<i class="fa fa-circle <?php echo ( $state === end( $TrackingOrder['state'] ) ) ? 'main-color ' : ''; ?>"></i>
							</div>
							<div class="state-datetime">
								<div class="vertical-middle">
									<div class="inner">
										<div class="date"><?php echo esc_html( $dt['date'] ); ?></div>
										<div class="hr-time">|</div>
										<div class="time main-color"><?php echo esc_html( $dt['time'] ); ?></div>
									</div>
								</div>
							</div>
							<div class="space-responsive"></div>
							<div class="state-description">
								<div class="vertical-middle">
									<div class="inner">
										<div class="line-1 main-color"><?php echo esc_html( $state['description'] ); ?></div>
										<div class="line-2"><?php echo esc_html( $state['location'] ); ?></div>
									</div>
								</div>
							</div>
							<div class="clear-float"></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}
}

return new SPECM_Ajax_api_Parcel_Shipping();
