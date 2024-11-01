<?php

class SPECM_Shippop_api {

	public $Utility = null;

	function __construct() {
		$this->Utility = new SPECM_Utility();
	}

	public function specm_AuthMemberService( $data ) {
		$url       = '/pricelist/';
		$post_data = array(
			'data' => $data,
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_GetPriceOrder( $data ) {
		$url       = '/pricelist/';
		$post_data = array(
			'data' => $data,
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_BookingOrder( $data , $hash ) {
		$url       = '/booking/';
		$post_data = array(
			'data' => $data,
			'url'  => array(
				'success' => admin_url( 'admin.php?page=shippop-ecommerce&success=1&hash=' . $hash ),
				'fail'    => admin_url( 'admin.php?page=shippop-ecommerce&fail=1&hash=' . $hash ),
			),
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_ConfirmOrder( $purchase_id ) {
		$url       = '/confirm/';
		$post_data = array(
			'purchase_id' => $purchase_id,
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_TrackingPurchase( $purchase_id ) {
		$url       = '/tracking_purchase/';
		$post_data = array(
			'purchase_id' => $purchase_id,
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_LabelPrinting( $tracking_code, $size, $type ) {
		$url      = '/label_tracking_code/';
		$settings = get_option( 'specm_settings' );
		$option   = [];
		if ( isset( $settings['label'] ) ) {
			$name = (isset($settings['label']['name'])) ? $settings['label']['name'] : '';
			$tel = (isset($settings['label']['tel'])) ? $settings['label']['tel'] : '';
			$address = (isset($settings['label']['address'])) ? $settings['label']['address'] : '';
			if ( $name && $tel && $address ) {
				preg_match('/\d\d\d\d\d/', $address, $output_array);
				if ( empty($output_array)) {
					$postcode = "00000";
				} else {
					$postcode = end( $output_array );
				}
				$option = [
					"options" => []
				];

				foreach ($tracking_code as $barcode) {
					if ( $postcode == "00000" ) {
						$post_id = specm_helper_getId_by_key_value( '_booking_tracking_code' , $barcode );
						if ( $post_id ) {
							$postcode = get_post_meta( $post_id , '_shipping_postcode' , true );
						}
					}
					$option['options'][$barcode] = [
						"replaceOrigin" => [
							"name" => $name,
							"address" => $address,
							"district" => "-",
							"state" => "-",
							"province" => "-",
							"postcode" => $postcode,
							"tel" => $tel
						]
					];
				}
			}
		}

		$post_data = array(
			'tracking_code' => implode( ',', $tracking_code ),
			'size'          => $size,
			'type'          => $type,
		);
		if ( $size == 'sticker4x6_x_product' ) {
			$post_data['size'] = 'sticker4x6';
			$post_data['showproduct'] = 1;
		}
		$post_data = array_merge( $post_data , $option );
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_GetTrackingOrder( $tracking_code ) {
		$url       = '/tracking/';
		$post_data = array(
			'tracking_code' => $tracking_code,
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_PurchaseCancel( $tracking_code ) {
		$url       = '/cancel/';
		$post_data = array(
			'tracking_code' => $tracking_code,
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_ReportDelivered( $start_date, $end_date ) {
		$url       = '/report-delivered/';
		$post_data = array(
			'start_date' => $start_date,
			'end_date'   => $end_date,
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_ReportCOD( $start_date, $end_date, $filter_date ) {
		$url       = '/report-cod/';
		$post_data = array(
			'start_date'  => $start_date,
			'end_date'    => $end_date,
			'filter_date' => $filter_date,
		);
		return $this->Utility->specm_post( $url, $post_data );
	}

	public function specm_Pyadc( $address ) {
		$Return    = $this->Utility->specm_post( 'https://www1.shippop.com/address/collection/', [ 'inputText' => $address ] , 'form' );
		$response = array(
			'status' => false,
		);
		if ( $Return['status'] ) {
			$Pyadc = $response['data'] = $Return['address'];
			if ( $Pyadc['status'] == 1 ) {
				$response['status']     = true;
				$response['type']       = '1';
				$response['suggestion'] = $this->specm_prepare_address_corrector( $Pyadc['data'] );
			} elseif ( $Pyadc['status'] == 0 && ! empty( $Pyadc['data'] ) ) {
				$response['status']     = true;
				$response['type']       = '2';
				$response['suggestion'] = $this->specm_prepare_address_corrector( $Pyadc['data'] );
				// $response["html"] = $this->specm_corrector_address_html( $Pyadc["data"] );
			} else {
				$msg = ( !empty($response["message"]) ) ? $response["message"] : '-';
				$response['message'] = esc_html__( 'Incorrect Address', 'shippop-ecommerce' ) . " [ " . $msg . " ] ";
			}
		}

		return $response;
	}

	private function specm_prepare_address_corrector( $data ) {
		$args                = array();
		$prefix_sub_district = esc_html( 'เขต/อำเภอ' );
		$prefix_district     = esc_html( 'แขวง/ตำบล' );
		$prefix_province     = esc_html( 'จังหวัด' );
		$prefix_zipcode      = esc_html( 'รหัสไปรษณีย์' );

		// $prefix_sub_district = esc_html__("State", "shippop-ecommerce");
		// $prefix_district = esc_html__("District", "shippop-ecommerce");
		// $prefix_province = esc_html__("Province", "shippop-ecommerce");
		// $prefix_zipcode = esc_html__("Zipcode", "shippop-ecommerce");
		foreach ( $data as $key => $value ) {
			// $args[$key]['full'] = $value["cleaned_address"] . ' ' . $prefix_sub_district . ' ' . $value["subdistrict"]["replacer"] . ' ' . $prefix_district . ' ' . $value["district"]["replacer"] . ' ' . $prefix_province . ' ' . $value["province"]["replacer"] . ' ' . $prefix_zipcode . ' ' . $value["zipcode"]["replacer"];
			$clear = ( empty($value['address']) ) ? "" : $value['address'];
			$args[ $key ]['full']     = $clear . ' ' . $value['subdistrict']['replacer'] . ' ' . $value['district']['replacer'] . ' ' . $value['province']['replacer'] . ' ' . $value['zipcode']['replacer'];
			$args[ $key ]['state']    = $value['subdistrict']['replacer'];
			$args[ $key ]['district'] = $value['district']['replacer'];
			$args[ $key ]['province'] = $value['province']['replacer'];
			$args[ $key ]['postcode'] = $value['zipcode']['replacer'];
		}

		return $args;
	}
}
