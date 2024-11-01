<?php

class SPECM_Crud_Function {
	
	function specm_crud_update_confirm_order_id_by_order_id( $order_id ) {
		update_post_meta( $order_id, '_confirm_purchase_success', true );
		update_post_meta( $order_id, '_shippop_status', 'booking' );

		return true;
	}

	function specm_crud_update_tracking_purchase_courier_tracking_code( $tracking_purchase_data ) {
		if ( !empty( $tracking_purchase_data ) ) {
			$order_ids = [];
			foreach ($tracking_purchase_data as $item) {
				$order_id = $order_ids[] = specm_helper_getId_by_key_value( '_booking_tracking_code' , $item['tracking_code'] );
				if ( $order_id ) {
					update_post_meta( $order_id, '_booking_courier_tracking_code', $item['courier_tracking_code'] );
					update_post_meta( $order_id, 'shippop_courier_tracking_number', $item['courier_tracking_code'] );
					$this->specm_crud_update_confirm_order_id_by_order_id( $order_id );
				}
			}

			return $order_ids;
		}

		return false;
	}

	function specm_crud_update_shipping_status( $tracking_code, $status, $data = [], $is_webhook = true ) {
		$order_id = specm_helper_getId_by_key_value( '_booking_tracking_code' , $tracking_code );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			$order_status  = $order->get_status();

			if ( ! empty( $data['courier_tracking_code'] ) ) {
				update_post_meta( $order_id, '_booking_courier_tracking_code', $data['courier_tracking_code'] );
			}
			if ( ! empty( $data['weight'] ) ) {
				update_post_meta( $order_id, '_actually_weight', $data['weight'] );
			}
			if ( ! empty( $data['price'] ) ) {
				update_post_meta( $order_id, '_actually_price', $data['price'] );
			}

			if ( $is_webhook ) {
				$order->add_order_note( esc_html__( "Update status from SHIPPOP's WebHooks", 'shippop-ecommerce' ) . " [STATUS : $status]" );
			}

			if ( $status == 'booking' ) {
				$this->specm_crud_update_confirm_order_id_by_order_id( $order_id );
			} else {
				if ( $status == 'complete' && $order_status != 'completed' ) {
					$order->update_status( 'completed' );
				}
				update_post_meta( $order_id, '_shippop_status', $status );
			}

			return true;
		}

		return false;
	}

	function specm_crud_remove_option_order( $order_id ) {
		$meta_key = array(
			'_booking_status',
			'_booking_tracking_code',
			'_booking_courier_code',
			'_booking_price',
			'_booking_discount',
			'_booking_from',
			'_booking_to',
			'_booking_courier_tracking_code',
			'_booking_purchase_id',
			'_booking_request_confirm',
			'_confirm_purchase_success',
			'_shippop_status',
		);

		foreach ( $meta_key as $key ) {
			delete_post_meta( $order_id, $key, '' );
		}

		return $order_id;
	}
}
