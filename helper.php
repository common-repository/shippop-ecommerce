<?php

function specm_helper_convert_wp_object_to_order_array( $order_ids, $export = false ) {
	$new_orders      = array();
	$countries_obj   = new WC_Countries();
	$countries_array = $countries_obj->get_countries();

	foreach ( $order_ids as $order_id ) {
		$order    = wc_get_order( $order_id );
		$get_data = $order->get_data();

		$shipping      = $get_data['shipping'];
		$billing       = $get_data['billing'];
		$customer_name = $shipping['first_name'] . ' ' . $shipping['last_name'];

		$country_code   = $shipping['country'];
		$country        = $countries_array[ $country_code ];
		$country_states = $countries_obj->get_states( $country_code );
		if ( strtoupper( $country_code ) === 'TH' ) {
			$state = ( specm_helper_get_state_thai( $shipping['state'] ) == '' ) ? $country_states[ $shipping['state'] ] : specm_helper_get_state_thai( $shipping['state'] );
		} else {
			$state = $country_states[ $shipping['state'] ];
		}
		$_booking_courier_code = get_post_meta( $get_data['id'], '_booking_courier_code', true );
		$total_weight          = 0;
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
			if (!$product->has_weight()) {
				$product_weight = 0.001;
			}
			$total_weight += floatval( $product_weight * $quantity );
		}

		$_order_total_weight = get_post_meta( $order_id, '_order_total_weight', true );
		if ( ! empty( $_order_total_weight ) ) {
			$total_weight = $_order_total_weight;
		}

		$sub_city = get_post_meta($order_id,'_shipping_sub_city', true); // plugin Designil - Woo Thai Address
		// $_billing_sub_city = get_post_meta($order_id,'_billing_sub_city', true); // plugin Designil - Woo Thai Address
		if ( empty( $sub_city ) ) {
			$sub_city = '';
		}

		$new_orders[ $order_id ] = array(
			'order'                 => $order_id,
			'order_id'              => $order_id,
			'billing'               => $billing,
			'shipping'              => $shipping,
			'shipping_method'       => $order->get_shipping_method(),
			'payment_method'        => $order->get_payment_method(),
			'payment_method_title'  => $order->get_payment_method_title(),
			'phone_number'          => ! empty( $shipping['phone'] ) ? $shipping['phone'] : $billing['phone'],
			'status'                => $get_data['status'],
			'shippop_status'        => get_post_meta( $get_data['id'], '_shippop_status', true ),
			'total'                 => $get_data['total'],
			'cod'                   => ( $order->get_payment_method() == 'cod' ) ? $get_data['total'] : '-',
			'customer_note'         => $get_data['customer_note'],
			'total_weight'          => $total_weight,
			'date_created'          => specm_helper_dateThai( $order->get_date_created()->date( 'Y-m-d H:i:s' ), $export ),
			'customer_name'         => $customer_name,
			'courier_tracking_code' => get_post_meta( $get_data['id'], '_booking_courier_tracking_code', true ),
			'tracking_code'         => get_post_meta( $get_data['id'], '_booking_tracking_code', true ),
			'courier_name'          => specm_helper_get_courier_name( $_booking_courier_code ),
			'destination'           => $shipping['company'] . ' ' . $shipping['address_1'] . ' ' . $shipping['address_2'] . ' ' . $sub_city . ' ' . $shipping['city'] . ' ' . $state . ' ' . $shipping['postcode'] . ' ' . $country,
		);

		if ( $actually_price = get_post_meta( $order_id, '_actually_price', true ) ) {
			$new_orders[ $order_id ]['actually_price'] = $actually_price;
		} elseif ( $_booking_price = get_post_meta( $order_id, '_booking_price', true ) ) {
			$new_orders[ $order_id ]['actually_price'] = $_booking_price;
		} else {
			$new_orders[ $order_id ]['actually_price'] = '-';

		}

		if ( $_actually_weight = get_post_meta( $order_id, '_actually_weight', true ) ) {
			$new_orders[ $order_id ]['actually_weight'] = ( ! empty( $_actually_weight ) ) ? $_actually_weight / 1000 : 0;
		} elseif ( $_order_total_weight = get_post_meta( $order_id, '_order_total_weight', true ) ) {
			$new_orders[ $order_id ]['actually_weight'] = $_order_total_weight;
		} else {
			$new_orders[ $order_id ]['actually_weight'] = '-';
		}
	}

	return $new_orders;
}

function specm_helper_get_order_data_object( $order_id, $order = false ) {
	$countries_obj = new WC_Countries();

	if ( $order === false ) {
		$order = wc_get_order( $order_id );
	}
	$is_cod   = ( $order->get_payment_method() == 'cod' ) ? true : false;
	$get_data = $order->get_data(); // The Order data

	// "name": "ชื่อผู้รับ นามสกุล",
	// "address": "522 ซอยรัชดาภิเษก 26 ถนนรัชดาภิเษก  แขวงสามเสนนอก เขตห้วยขวาง กรุงเทพมหานคร",
	// "district": "-",
	// "state": "-",
	// "province": "-",
	// "postcode": "10310",
	// "tel": "0929053355"
	$shipping     = $get_data['shipping'];
	$billing      = $get_data['billing'];
	$country_code = $shipping['country'];

	$country_states = $countries_obj->get_states( $country_code );
	// $countries_array = $countries_obj->get_countries();

	if ( strtoupper( $country_code ) === 'TH' ) {
		$state = ( specm_helper_get_state_thai( $shipping['state'] ) == '' ) ? $country_states[ $shipping['state'] ] : specm_helper_get_state_thai( $shipping['state'] );
	} else {
		$state = $country_states[ $shipping['state'] ];
	}

	$sub_city = get_post_meta($order_id,'_shipping_sub_city', true); // plugin Designil - Woo Thai Address
	// $_billing_sub_city = get_post_meta($order_id,'_billing_sub_city', true); // plugin Designil - Woo Thai Address
	if ( empty( $sub_city ) ) {
		$sub_city = '-';
	}

	$to = array(
		'name'     => $shipping['first_name'] . ' ' . $shipping['last_name'],
		// 'address'  => $shipping['company'] . ' ' . $shipping['address_1'] . ' ' . $shipping['address_2'] . ' ' . $shipping['city'] . ' ' . $shipping['postcode'],
		'address'  => $shipping['company'] . ' ' . $shipping['address_1'] . ' ' . $shipping['address_2'],
		'district' => $sub_city, // Fix Switch For SHIPPOP
		'state'    => $shipping['city'], // Fix Switch For SHIPPOP
		'province' => $state,
		'postcode' => $shipping['postcode'],
		'tel'      => ! empty( $shipping['phone'] ) ? $shipping['phone'] : $billing['phone'],
	);

	// "name": "-",
	// "weight": "1",
	// "width": "1",
	// "length": "1",
	// "height": "1"
	$total_weight = 0;
	$products     = array();
	$product_name = "";
	$tt = count($order->get_items());
	$last = 1;
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
		if (!$product->has_weight()) {
			$product_weight = 0.001;
		}
		$total_weight += floatval( $product_weight * $quantity );

		$products[] = array(
			'product_code' => ( empty( $product->get_sku() ) ) ? 'generate-' . time() : $product->get_sku(),
			// 'name'         => $product->get_name(),
			'name'         => "",
			'weight'       => ( $product_weight == '' ) ? 0 : $product_weight,
			'amount'	   => $quantity
		);

		$product_name .= $product->get_name() . " #" . $product->get_sku()  . " (" . $quantity . " " . esc_html__( 'Items', 'shippop-ecommerce' ) . ")";
		if ( $last != $tt ) {
			$product_name .= " , ";
		}
		$last++;
	}
	$total_weight = $total_weight * 1000;

	$_order_total_weight = get_post_meta( $order_id, '_order_total_weight', true );
	if ( ! empty( $_order_total_weight ) ) {
		$total_weight = $_order_total_weight * 1000; // convert to grams
	}

	$_order_width  = get_post_meta( $order_id, '_order_width', true );
	$_order_length = get_post_meta( $order_id, '_order_length', true );
	$_order_height = get_post_meta( $order_id, '_order_height', true );

	$parcel = array(
		'name'   => '-',
		'weight' => $total_weight,
		'width'  => ($_order_width) ? $_order_width : 1,
		'length' => ($_order_length) ? $_order_length : 1,
		'height' => ($_order_height) ? $_order_height : 1,
	);

	return array(
		'to'         => $to,
		'parcel'     => $parcel,
		'products'   => $products,
		'products_name' => $product_name,
		'cod_amount' => ( $is_cod ) ? $order->get_total() : false
	);
}

function specm_get_courier_information_file() {
	$shippop_server = get_option( 'specm_server', 'TH' );
	if ( $shippop_server == 'TH' && file_exists( WP_CONTENT_DIR . "/shippop_courier_info.json" ) ) {
		$file = file_get_contents(WP_CONTENT_DIR . "/shippop_courier_info.json");
		if ( $file === false ) {
			return false;
		}
		$information = json_decode($file, true);
		if ( is_array( $information ) ) {
			return $information;
		}
	}
	return false;
}

function specm_helper_prepare_courier_obj( $courier_list , $order_ids , $cc = false ) {
	global $specm_parcel_delivery;
	global $specm_on_demand;
	global $specm_ignore_cc;
	if ( count( $courier_list ) == 0 ) {
		return false;
	}

	$courierInformation = specm_get_courier_information_file();
	$new_courier = $courier_list[0];
	$order_ids = explode("," , $order_ids);
	$order_index = 0;
	$orderFail = [];
	if ( $cc === false ) {
		// save Courier List to option
		update_option( 'courier_list_avalible', $new_courier );
	}
	foreach ( $courier_list as $k => $courier ) {
		foreach ( $courier as $courier_code => $courier_code_value ) {
			specm_helper_fill_courier_args( $new_courier[ $courier_code ] );
			specm_helper_fill_courier_args( $courier_code_value );

			// fix it to key 1
			if ( $k != 0 ) {
				if ( $new_courier[ $courier_code ]['available'] != $courier_code_value['available'] ) {
					if ( $new_courier[ $courier_code ]['available'] ) {
						$new_courier[ $courier_code ] = $courier_code_value;
					}
				} elseif ( ( $new_courier[ $courier_code ]['available'] ) && ( $courier_code_value['available'] ) ) {
					$new_courier[ $courier_code ]['price'] += $courier_code_value['price'];
				}
			}

			if ( $courierInformation !== false ) {
				$new_courier[ $courier_code ]['logo'] = isset( $courierInformation[ $courier_code ]['logo'] ) ? $courierInformation[ $courier_code ]['logo'] : specm_helper_get_courier_logo( $courier_code );
				$new_courier[ $courier_code ]['pick_up_mode'] = '';
				if ( isset( $courierInformation[ $courier_code ]['courier_service'] ) &&  $courierInformation[ $courier_code ]['courier_service'] == "Drop Off" ) {
					$new_courier[ $courier_code ]['pick_up_mode'] = esc_html__( 'Deliver to Drop Off point yourself', 'shippop-ecommerce' ) . ' ';
				} 
				if ( isset( $courierInformation[ $courier_code ]['courier_service'] )  && $courierInformation[ $courier_code ]['courier_service'] == "Pickup" ) {
					$new_courier[ $courier_code ]['pick_up_mode'] .= esc_html__( 'Pick-up service', 'shippop-ecommerce' );
				}
				// SET DEFAULT
				if ( $new_courier[ $courier_code ]['pick_up_mode'] == '' ) {
					$new_courier[ $courier_code ]['pick_up_mode'] = esc_html__( 'Pick-up service', 'shippop-ecommerce' );
				}
			} else {
				$new_courier[ $courier_code ]['logo'] = specm_helper_get_courier_logo( $courier_code );
				$new_courier[ $courier_code ]['pick_up_mode'] = '';
				if ( in_array( $courier_code, $specm_parcel_delivery['drop_off'] ) ) {
					$new_courier[ $courier_code ]['pick_up_mode'] = esc_html__( 'Deliver to Drop Off point yourself', 'shippop-ecommerce' ) . ' ';
				}
				if ( in_array( $courier_code, $specm_parcel_delivery['pick_up'] ) ) {
					$new_courier[ $courier_code ]['pick_up_mode'] .= esc_html__( 'Pick-up service', 'shippop-ecommerce' );
				}
				// SET DEFAULT
				if ( $new_courier[ $courier_code ]['pick_up_mode'] == '' ) {
					$new_courier[ $courier_code ]['pick_up_mode'] = esc_html__( 'Pick-up service', 'shippop-ecommerce' );
				}
			}

			// fix
			if ( strpos( $new_courier[ $courier_code ]['courier_name'] , "Seventree" ) !== false ) {
				$new_courier[ $courier_code ]['courier_name'] = 'EMS';
				$new_courier[ $courier_code ]['logo'] = specm_helper_get_courier_logo( 'THP' );;
			}

			if ( $new_courier[ $courier_code ]['available'] === false ) {
				$orderFail[ $courier_code ][] = $order_ids[ $order_index ];
			}
		}
		$order_index++;
	}

	$on_demand_args = array();
	foreach ( $new_courier as $courier_code => $courier ) {
		if ( in_array( $courier_code, $specm_on_demand ) ) {
			$on_demand_args[ $courier_code ] = $courier;
			unset( $new_courier[ $courier_code ] );
		}
		if ( in_array( $courier_code, $specm_ignore_cc ) ) {
			unset( $new_courier[ $courier_code ] );
		}
	}
	specm_helper_array_sort_by_column( $new_courier, 'price' );

	return array(
		'normal'    => $new_courier,
		'on_demand' => $on_demand_args,
		'orderFail' => $orderFail
	);
}

function specm_helper_getId_by_key_value ( $key , $value ) {
	global $wpdb;
	$sql = $wpdb->prepare( "SELECT post_id FROM `$wpdb->postmeta` WHERE meta_key = %s AND meta_value = %s" , $key , $value );
	$results = $wpdb->get_results( $sql , ARRAY_A );

	if ( !empty($results) && isset($results[0]['post_id']) ) {
		return $results[0]['post_id'];
	}

	return false;
}

function specm_helper_array_sort_by_column( &$arr, $col, $dir = SORT_ASC ) {
	$sort_col = array();
	foreach ( $arr as $key => $row ) {
		$sort_col[ $key ] = ( $row['available'] ) ? $row[ $col ] : 100000000000; // fix 100000000000 for order by
	}

	array_multisort( $sort_col, $dir, $arr );
}

function specm_helper_prepare_report_obj( &$data, $cod = false, $export = false ) {
	global $specm_shippop_status;
	global $specm_shippop_cod_status;

	foreach ( $data as $key => $obj ) {
		$order_id = specm_helper_get_post_id_by_meta_key_and_value( '_booking_tracking_code', $obj['tracking_code'] );
		if ( ! empty( $order_id ) ) {
			$data[ $key ]['order_id']       = $data[ $key ]['order'] = 'SHIPPOP000';
			$data[ $key ]['customer_name']  = '';
			$data[ $key ]['shippop_status'] = 'none';
			$data[ $key ]['courier_name']   = '-';

			if ( $cod ) {
				$data[ $key ]['datetime_shipping'] = specm_helper_dateThai( $obj['datetime_shipping'], $export );
				$data[ $key ]['datetime_transfer'] = specm_helper_dateThai( $obj['datetime_transfer'], $export );
				$data[ $key ]['datetime_complete'] = specm_helper_dateThai( $obj['datetime_complete'], $export );
				$data[ $key ]['cod_status']        = $specm_shippop_cod_status[ $obj['cod_status'] ];
				$data[ $key ]['_cod_status']       = $obj['cod_status'];
				$data[ $key ]['order_status']      = $specm_shippop_status[ $obj['order_status'] ];
				$data[ $key ]['destination_name']  = $obj['destination_name'];
				$data[ $key ]['cod_charge']        = $obj['cod_charge'];
				$data[ $key ]['cod_amount']        = $obj['cod_amount'];
				$data[ $key ]['cod_total']         = $obj['cod_total'];
				$data[ $key ]['remark']            = $obj['remark'];
				$data[ $key ]['invoice_id']        = $obj['invoice_id'];
				$data[ $key ]['invoice_id_url']    = $obj['invoice_id_url'];
				$data[ $key ]['receipt_id']        = $obj['receipt_id'];
				$data[ $key ]['receipt_id_url']    = $obj['receipt_id_url'];
			} else {
				$data[ $key ]['total']        = $obj['price'];
				$data[ $key ]['total_weight'] = $obj['weight'] / 100;
			}

			$data[ $key ]['datetime_shipping']     = specm_helper_dateThai( $obj['datetime_shipping'], $export );
			$data[ $key ]['courier_tracking_code'] = $obj['courier_tracking_code'];
			$data[ $key ]['tracking_code']         = $obj['tracking_code'];

			if ( ! empty( $obj['courier_code'] ) ) {
				$data[ $key ]['courier_name'] = specm_helper_get_courier_name( $obj['courier_code'] );
			}

			if ( $order_id ) {
				$order    = wc_get_order( $order_id );
				$get_data = $order->get_data();

				$billing       = $get_data['billing'];
				$customer_name = $billing['first_name'] . ' ' . $billing['last_name'];

				$data[ $key ]['order_id']       = $data[ $key ]['order'] = $order_id;
				$data[ $key ]['customer_name']  = $customer_name;
				$data[ $key ]['status']         = $get_data['status'];
				$data[ $key ]['shippop_status'] = get_post_meta( $order_id, '_shippop_status', true );
			}
		} else {
			unset( $data[ $key ] );
		}
	}
}

function specm_helper_get_store_address() {
	$shippop_server = get_option( 'specm_server', 'TH' );
	 $Shippop_Setting = new SPECM_Shippop_Setting();
	return array(
		'name'     => $Shippop_Setting->settings['address']['name'],
		'address'  => $Shippop_Setting->settings['address']['address'],
		// "district" => $Shippop_Setting->settings["address"]["district"],
		// "state" => $Shippop_Setting->settings["address"]["state"],
		'state'    => ($shippop_server == 'TH') ? "-" : $Shippop_Setting->settings["address"]["district"],
		'district' => ($shippop_server == 'TH') ? "-" : $Shippop_Setting->settings["address"]["state"],
		'province' => ($shippop_server == 'TH') ? "-" : $Shippop_Setting->settings["address"]["province"],
		'postcode' => $Shippop_Setting->settings['address']['postcode'],
		'tel'      => $Shippop_Setting->settings['address']['tel'],
	);
}

function specm_helper_set_header_output( $response ) {
	header( 'Content-type: application/json; charset=utf-8' );
	echo json_encode( $response );
	die;
}

function specm_helper_get_courier_name( $courier_code ) {
	$courier_list_avalible = get_option( 'courier_list_avalible', array() );
	if ( ! empty( $courier_list_avalible ) && ! empty( $courier_code ) && ! empty( $courier_list_avalible[ $courier_code ] ) ) {
		if ( strpos( $courier_list_avalible[ $courier_code ]['courier_name'] , "Seventree" ) !== false ) {
			return "EMS";
		}
		return $courier_list_avalible[ $courier_code ]['courier_name'];
	}

	return $courier_code;
}

function specm_helper_get_courier_logo( $courier_code ) {
	global $specm_parcel_logo;
	$logo = ( ! empty( $specm_parcel_logo[ $courier_code ] ) ) ? $specm_parcel_logo[ $courier_code ] : $specm_parcel_logo['SPE'];
	$logo = plugins_url( $logo );
	return $logo;
}

function specm_helper_preprint_label( $tracking_code, $size, $type = 'pdf' ) {
	$Shippop_API   = new SPECM_Shippop_API();
	$labelPrinting = $Shippop_API->specm_LabelPrinting( $tracking_code, $size, $type );
	$file_name     = time() . '_tracking_label' . '.' . $type;
	if ( $labelPrinting['status'] ) {
		// if ($type == "pdf") {
		// $response = base64_decode($labelPrinting[$type]);
		// header('Content-Description: File Transfer');
		// header('Content-Type: application/pdf');
		// header('Content-Disposition: attachment; filename=' . $file_name);
		// header('Content-Transfer-Encoding: binary');
		// header('Expires: 0');
		// header('Cache-Control: must-revalidate');
		// header('Pragma: public');
		// header('Content-Length: ' . strlen($response));
		// ob_clean();
		// flush();
		// echo $response;
		// die;
		// } else {
			specm_create_files();
		if ( $type == 'html' ) {
			$response = stripcslashes( $labelPrinting['html'] );
		} else {
			$response = base64_decode( $labelPrinting['pdf'] );
		}
			$file  = SPECM_UPLOAD_DIR . '/' . $file_name;
			$open  = fopen( $file, 'w' );
			$write = fputs( $open, $response );
			fclose( $open );

			return array(
				'status'   => true,
				'file_url' => SPECM_UPLOAD_URL . '/' . $file_name,
				'message'  => '',
			);
			// }
	}

	return $labelPrinting;
}

function specm_create_files() {
	// Install files and folders for uploading files and prevent hotlinking
	$files = array(
		array(
			'base'    => SPECM_UPLOAD_DIR,
			'file'    => 'index.html',
			'content' => '',
		),
		array(
			'base'    => SPECM_UPLOAD_DIR,
			'file'    => '.htaccess',
			'content' => '
# BEGIN WPForms
# Disable PHP and Python scripts parsing.
<Files *>
    SetHandler none
    SetHandler default-handler
    RemoveHandler .cgi .php .php3 .php4 .php5 .phtml .pl .py .pyc .pyo
    RemoveType .cgi .php .php3 .php4 .php5 .phtml .pl .py .pyc .pyo
</Files>
<IfModule mod_php5.c>
    php_flag engine off
</IfModule>
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>
<IfModule headers_module>
    Header set X-Robots-Tag "noindex"
</IfModule>
# END WPForms',
		),
		// array(
		// 'base'       => SPECM_UPLOAD_DIR,
		// 'file'       => 'index.html',
		// 'content'    => '',
		// ),
	);

	foreach ( $files as $file ) {
		if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
			if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
				fwrite( $file_handle, $file['content'] );
				fclose( $file_handle );
			}
		}
	}

	// clear file old than 3 days
	if ( wp_mkdir_p( SPECM_UPLOAD_DIR ) ) {
		$all_file = list_files( SPECM_UPLOAD_DIR );
		if ( $all_file ) {
			foreach ( $all_file as $file ) {
				$filename = explode( '/', $file );
				if ( time() - filemtime( $file ) > 72 * 3600 && end( $filename ) != 'index.html' ) {
					@unlink( $file );
				}
			}
		}
	}
}

function specm_helper_get_report( $start_date, $end_date ) {
	$Shippop_API     = new SPECM_Shippop_API();
	$reportDelivered = $Shippop_API->specm_ReportDelivered( $start_date, $end_date );
	if ( $reportDelivered['status'] ) {
		return $reportDelivered['data'];
	} else {
		return array();
	}
}

function specm_helper_get_report_cod( $start_date, $end_date, $filter_date ) {
	$Shippop_API = new SPECM_Shippop_API();
	$reportCOD   = $Shippop_API->specm_ReportCOD( $start_date, $end_date, $filter_date );
	if ( $reportCOD['status'] ) {
		return $reportCOD['data'];
	} else {
		return array();
	}
}

function specm_helper_is_date( $date ) {
	if ( preg_match( '/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $date ) ) {
		return true;
	} else {
		return false;
	}
}

function specm_helper_get_post_id_by_meta_key_and_value( $key, $value ) {
	global $wpdb;
	$meta = $wpdb->get_results( "SELECT * FROM $wpdb->postmeta WHERE meta_key='" . sanitize_text_field( $key ) . "' AND meta_value='" . sanitize_text_field( $value ) . "'" );
	if ( is_array( $meta ) && ! empty( $meta ) && isset( $meta[0] ) ) {
		$meta = $meta[0];
	}
	if ( is_object( $meta ) ) {
		return $meta->post_id;
	} else {
		return false;
	}
}

/**
 *
 * Exports an associative array into a CSV file using PHP.
 *
 * @see https://stackoverflow.com/questions/21988581/write-utf-8-characters-to-file-with-fputcsv-in-php
 *
 * @param array  $data       The table you want to export in CSV
 * @param string $filename   The name of the file you want to export
 * @param array  $header     header tab
 * @param string $delimiter  The CSV delimiter you wish to use. The default ";" is used for a compatibility with microsoft excel
 * @param string $enclosure  The type of enclosure used in the CSV file, by default it will be a quote "
 */
function specm_helper_export_data_to_csv( $data, $filename = 'export', $header = array(), $delimiter = ',', $enclosure = '"' ) {
	// Tells to the browser that a file is returned, with its name : $filename.csv
	header( "Content-disposition: attachment; filename=$filename.csv" );
	// Tells to the browser that the content is a csv file
	// header("Content-Type: text/csv");
	header( 'Content-Type: application/csv' );

	// I open PHP memory as a file
	$fp = fopen( 'php://output', 'w' );
	ob_clean(); // clean slate

	// Insert the UTF-8 BOM in the file
	fputs( $fp, $bom = ( chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ) );

	// I add the array keys as CSV headers
	$tab = ( empty( $header ) ) ? array_keys( $data[0] ) : $header;
	fputcsv( $fp, $tab, $delimiter, $enclosure );

	// Add all the data in the file
	foreach ( $data as $fields ) {
		$args = array();
		foreach ( $tab as $key => $kk ) {
			if ( ! empty( $fields[ $key ] ) ) {
				$args[ $key ] = $fields[ $key ];
			} else {
				$args[ $key ] = '-';
			}
		}
		// print_r($args);
		fputcsv( $fp, $args, $delimiter, $enclosure );
	}

	// Close the file
	ob_flush(); // dump buffer
	fclose( $fp );

	// Stop the script
	die();
}

/* For Helper */

function specm_helper_fill_courier_args( &$args ) {
	$kv = array(
		'available' => false,
		'remark'    => '',
	);

	foreach ( $kv as $key => $value ) {
		if ( empty( $args[ $key ] ) ) {
			$args[ $key ] = $value;
		}
	}
}

function specm_helper_dateThai( $strDate, $inline = false ) {
	if ( $strDate == '0000-00-00 00:00:00' ) {
		if ( $inline ) {
			return '';
		} else {
			return array(
				'date' => '',
				'time' => '',
			);
		}
	}

	if ( $strDate == '' || $strDate == null || empty( $strDate ) ) {
		if ( $inline ) {
			return '';
		} else {
			return array(
				'date' => '',
				'time' => '',
			);
		}
	}
	$strYear      = date( 'Y', strtotime( $strDate ) ) + 543;
	$strMonth     = date( 'n', strtotime( $strDate ) );
	$strDay       = date( 'j', strtotime( $strDate ) );
	$strHour      = date( 'H', strtotime( $strDate ) );
	$strMinute    = date( 'i', strtotime( $strDate ) );
	$strSeconds   = date( 's', strtotime( $strDate ) );
	$strMonthCut  = array( '', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.' );
	$strMonthThai = $strMonthCut[ $strMonth ];

	if ( $inline ) {
		return "$strDay $strMonthThai $strYear $strHour:$strMinute:$strSeconds";
	} else {
		return array(
			'date' => "$strDay $strMonthThai $strYear",
			'time' => "$strHour:$strMinute:$strSeconds",
		);
	}
}

function specm_base64_encode_hash_url ( $content ) {
	$encode = base64_encode( $content );
	$encode = str_replace("=" , "_" , $encode);
	return $encode; 
}

function specm_base64_decode_hash_url ( $content ) {
	$content = str_replace("_" , "=" , $content);
	$decode = base64_decode( $content );
	return $decode; 
}

function specm_helper_get_state_thai( $code ) {
	$states = array(
		'TH-81' => 'กระบี่',
		'TH-10' => 'กรุงเทพมหานคร',
		'TH-71' => 'กาญจนบุรี',
		'TH-46' => 'กาฬสินธุ์',
		'TH-62' => 'กำแพงเพชร',
		'TH-40' => 'ขอนแก่น',
		'TH-22' => 'จันทบุรี',
		'TH-24' => 'ฉะเชิงเทรา',
		'TH-20' => 'ชลบุรี',
		'TH-18' => 'ชัยนาท',
		'TH-36' => 'ชัยภูมิ',
		'TH-86' => 'ชุมพร',
		'TH-57' => 'เชียงราย',
		'TH-50' => 'เชียงใหม่',
		'TH-92' => 'ตรัง',
		'TH-23' => 'ตราด',
		'TH-63' => 'ตาก',
		'TH-26' => 'นครนายก',
		'TH-73' => 'นครปฐม',
		'TH-48' => 'นครพนม',
		'TH-30' => 'นครราชสีมา',
		'TH-80' => 'นครศรีธรรมราช',
		'TH-60' => 'นครสวรรค์',
		'TH-12' => 'นนทบุรี',
		'TH-96' => 'นราธิวาส',
		'TH-55' => 'น่าน',
		'TH-38' => 'บึงกาฬ',
		'TH-31' => 'บุรีรัมย์',
		'TH-13' => 'ปทุมธานี',
		'TH-77' => 'ประจวบคีรีขันธ์',
		'TH-25' => 'ปราจีนบุรี',
		'TH-94' => 'ปัตตานี',
		'TH-14' => 'พระนครศรีอยุธยา',
		'TH-56' => 'พะเยา',
		'TH-82' => 'พังงา',
		'TH-93' => 'พัทลุง',
		'TH-66' => 'พิจิตร',
		'TH-65' => 'พิษณุโลก',
		'TH-76' => 'เพชรบุรี',
		'TH-67' => 'เพชรบูรณ์',
		'TH-54' => 'แพร่',
		'TH-83' => 'ภูเก็ต',
		'TH-44' => 'มหาสารคาม',
		'TH-49' => 'มุกดาหาร',
		'TH-58' => 'แม่ฮ่องสอน',
		'TH-35' => 'ยโสธร',
		'TH-95' => 'ยะลา',
		'TH-45' => 'ร้อยเอ็ด',
		'TH-85' => 'ระนอง',
		'TH-21' => 'ระยอง',
		'TH-70' => 'ราชบุรี',
		'TH-16' => 'ลพบุรี',
		'TH-52' => 'ลำปาง',
		'TH-51' => 'ลำพูน',
		'TH-42' => 'เลย',
		'TH-33' => 'ศรีสะเกษ',
		'TH-47' => 'สกลนคร',
		'TH-90' => 'สงขลา',
		'TH-91' => 'สตูล',
		'TH-11' => 'สมุทรปราการ',
		'TH-75' => 'สมุทรสงคราม',
		'TH-74' => 'สมุทรสาคร',
		'TH-27' => 'สระแก้ว',
		'TH-19' => 'สระบุรี',
		'TH-17' => 'สิงห์บุรี',
		'TH-64' => 'สุโขทัย',
		'TH-72' => 'สุพรรณบุรี',
		'TH-84' => 'สุราษฎร์ธานี',
		'TH-32' => 'สุรินทร์',
		'TH-43' => 'หนองคาย',
		'TH-39' => 'หนองบัวลำภู',
		'TH-15' => 'อ่างทอง',
		'TH-37' => 'อำนาจเจริญ',
		'TH-41' => 'อุดรธานี',
		'TH-53' => 'อุตรดิตถ์',
		'TH-61' => 'อุทัยธานี',
		'TH-34' => 'อุบลราชธานี',
	);

	return empty( $states[ $code ] ) ? '' : $states[ $code ];
}
