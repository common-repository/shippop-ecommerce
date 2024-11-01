<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class SPECM_Shippop_Ecommerce_List_Table extends WP_List_Table {

	public $per_page;
	public $option_name = 'toplevel_page_shippop-ecommerce';

	public function __construct() {
		// Utilize the parent constructor to build the main class properties.
		parent::__construct(
			array(
				'singular' => 'shippop-ecommerce',
				'plural'   => 'shippop-ecommerce',
				'ajax'     => false,
			)
		);

		// Default number of forms to show per page.
		$this->per_page = (int) apply_filters( 'specm_spect_overview_per_page', 20 );
		add_filter( 'manage_' . $this->option_name . '_columns', array( $this, 'get_columns' ), 0 );
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$orderby  = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $sortable ) ) ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
		$order    = ( isset( $_REQUEST['order'] ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'desc';
		$status   = ( isset( $_REQUEST['status'] ) ) ? sanitize_text_field( $_REQUEST['status'] ) : '';

		$this->process_bulk_action();
		if ( $status == '' ) {
			$specm_advance_setting = get_option( 'specm_advance_setting', [] );
			$statuses = isset( $specm_advance_setting['choose_parcel_status'] ) ? explode( "," , esc_attr( $specm_advance_setting['choose_parcel_status'] ) ) : [ 'wc-processing' ];
		} else {
			$statuses = (array) $status;
		}
		$_meta_query = $__meta_query = array();
		$meta_query  = array(
			'relation' => 'AND',
		);

		$_meta_query[] = array(
			'relation' => 'AND',
		);
		$_meta_query[] = array(
			'key'     => '_use_shippop_shipping',
			'value'   => 'Y',
			'compare' => '=',
		);

		$_meta_query[] = array(
			'key'     => '_confirm_purchase_success',
			'value'   => 'null',
			'compare' => 'NOT EXISTS',
		);

		// if ( ! empty( $_POST['payment_type'] ) ) {
		// 	$_meta_query[] = array(
		// 		'key'     => '_payment_method',
		// 		'value'   => sanitize_text_field( $_POST['payment_type'] ),
		// 		'compare' => '=',
		// 	);
		// }

		// if ( ! empty( $s ) ) {
		// 	$__meta_query[] = array(
		// 		'relation' => 'OR',
		// 	);

		// 	$meta_key_search = array(
		// 		'_billing_phone',
		// 		'_shipping_phone',
		// 		'_billing_address_index',
		// 		'_shipping_address_index',
		// 	);
		// 	foreach ( $meta_key_search as $search ) {
		// 		$__meta_query[] = array(
		// 			'key'     => $search,
		// 			'value'   => $s,
		// 			'compare' => 'LIKE',
		// 		);
		// 	}
		// }

		$meta_query[] = $_meta_query;
		// $meta_query[] = $__meta_query;

		$args = array(
			'post_status' => $statuses,
			'post_type'   => 'shop_order',
			'fields'      => 'ids',
			'meta_query'  => $meta_query,
			// 's' => $s
		);

		list($start_date, $end_date) = $this->get_start_end_date();
		if ( $start_date && $end_date ) {
			$args['date_query'] = array(
				'after'     => $start_date,
				'before'    => $end_date,
				'inclusive' => true,
			);
		}

		$query      = new WP_Query( $args );
		$totalItems = $query->found_posts;

		$per_page    = $this->get_items_per_page( 'specm_spect_per_page', $this->per_page );
		$currentPage = $this->get_pagenum();

		$query     = new WP_Query(
			array_merge(
				$args,
				array(
					'orderby'        => $orderby,
					'order'          => $order,
					'posts_per_page' => $per_page,
					'paged'          => $currentPage,
				)
			)
		);
		$order_ids = $query->posts;

		$data                  = specm_helper_convert_wp_object_to_order_array( $order_ids );
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;

		$this->set_pagination_args(
			array(
				'total_items' => $totalItems,
				'per_page'    => $per_page,
				'total_pages' => ceil( $totalItems / $per_page ),
			)
		);
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns() {
		$columns = array(
			'cb'                   => '<input class="specm_checkbox_value" type="checkbox" />',
			'order'                => esc_html__( 'Order', 'shippop-ecommerce' ),
			'date_created'         => esc_html__( 'Order date', 'shippop-ecommerce' ),
			'phone_number'         => esc_html__( 'Contact number', 'shippop-ecommerce' ),
			'destination'          => esc_html__( 'Customer address', 'shippop-ecommerce' ),
			'status'               => esc_html__( 'Status', 'shippop-ecommerce' ),
			'total_weight'         => esc_html__( 'Weight (kg.)', 'shippop-ecommerce' ),
			'shipping_method'      => esc_html__( 'Customer selected courier', 'shippop-ecommerce' ),
			'payment_method_title' => esc_html__( 'Payment method', 'shippop-ecommerce' ),
		);

		$specm_server = get_option( 'specm_server', 'TH' );
		if ( strtoupper( $specm_server ) === 'TH' ) {
			$columns['total'] = esc_html__( 'COD', 'shippop-ecommerce' );
		}

		return $columns;
	}

	public function get_bulk_actions() {
		return array();
	}

	public function process_bulk_action() {
		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			// security check!
			if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {
				$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
				$action = 'bulk-' . $this->_args['plural'];

				if ( ! wp_verify_nonce( $nonce, $action ) ) {
					wp_die( 'Nope! Security check failed!' );
				}
			}

			// $bulk_ids = (!empty($_POST["bulk_id"])) ? $_POST["bulk_id"]: [];
			$bulk_ids = isset( $_POST['bulk_id'] ) ? array_map( 'absint', (array) $_POST['bulk_id'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
			if ( count( $bulk_ids ) > 0 ) {
				$action = $this->current_action();
				switch ( $action ) {
					case 'bulk_custom_shipping':
						foreach ( $bulk_ids as $bulk_id ) {
							if ( $bulk_id ) {
								update_post_meta( esc_attr( $bulk_id ), '_use_shippop_shipping', 'N' );
							}
						}
						break;
					default:
						// do nothing or something else
						return;
						break;
				}
			}
		}

		return;
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		$user_id = get_current_user_id();
		$hiddens = get_user_meta( $user_id, 'manage' . $this->option_name . 'columnshidden', true );
		if ( ! empty( $hiddens ) ) {
			return $hiddens;
		} else {
			return array();
		}
	}

	/**
	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {
		return array(
			'order'        => array( 'order', false ),
			'date_created' => array( 'date_created', false ),
		);
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk_id[]" class="specm_checkbox_value" value="%s" />',
			$item['order_id']
		);
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  Array  $item        Data
	 * @param  String $column_name - Current column name
	 *
	 * @return Mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'order':
				return " <a href='" . get_edit_post_link( $item['order_id'] ) . "'> #" . $item['order_id'] . ' ' . $item['customer_name'] . ' </a> ';
			case 'date_created':
				$str_date = "<span style='display: block;'> " . $item['date_created']['date'] . ' </span>';
				$str_time = "<span style='display: block;font-size: 10px;color: gray;' > " . $item['date_created']['time'] . ' </span>';
				return $str_date . '' . $str_time;
			case 'total':
				return ( $item['payment_method'] == 'cod' ) ? $item[ $column_name ] : '-';
			case 'status':
				global $specm_woocommerce_order_statuses_color;
				return "<span style='font-size:10px;padding: 5px;background-color: " . $specm_woocommerce_order_statuses_color[ $item['status'] ][0] . ';color: ' . $specm_woocommerce_order_statuses_color[ $item['status'] ][1] . "'> " . wc_get_order_status_name( $item['status'] ) . ' </span>';
			case 'total_weight':
			case 'shipping_method':
			case 'customer_note':
			case 'phone_number':
			case 'destination':
			case 'payment_method_title':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	public function extra_tablenav( $which ) {
		global $specm_on_demand;
		$courierInformation = specm_get_courier_information_file();
		$courier_list_avalible = get_option('courier_list_avalible', []);

		list($start_date, $end_date) = $this->get_start_end_date( 'd/m/Y' );
		$gateways                    = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways            = array();
		if ( $gateways ) {
			foreach ( $gateways as $gateway ) {
				if ( $gateway->enabled == 'yes' ) {
					$enabled_gateways[] = $gateway;
				}
			}
		}

		$specm_advance_setting = get_option( 'specm_advance_setting', [] );
		$statuses = isset( $specm_advance_setting['choose_parcel_status'] ) ? explode( "," , esc_attr( $specm_advance_setting['choose_parcel_status'] ) ) : [ 'wc-processing' ];
		if ( $which == 'top' ) :
			?>
				<div class="alignleft">
					<input style="width: 130px;" class="specm-input-style" type="text" name="daterange_start" data-start-date="" value="<?php echo esc_attr( $start_date ); ?>" placeholder="<?php echo esc_attr__( 'Search from date', 'shippop-ecommerce' ); ?>" autocomplete="off" />
					<input style="width: 130px;" class="specm-input-style" type="text" name="daterange_end" data-end-date="" value="<?php echo esc_attr( $end_date ); ?>" placeholder="<?php echo esc_attr__( 'To date', 'shippop-ecommerce' ); ?>" autocomplete="off" />
				</div>

				<div class="alignleft" style="margin-left: 5px;">
					<select name="payment_type" id="payment_type" class="specm_on_change_submit specm-input-style" style="width: 160px;">
						<option value=""><?php echo esc_html__( 'All payment method', 'shippop-ecommerce' ); ?></option>

						<?php foreach ( $enabled_gateways as $payment ) : ?>
							<option value="<?php echo esc_attr( $payment->id ); ?>" <?php echo ( ! empty( $_REQUEST['payment_type'] ) && sanitize_text_field( $_REQUEST['payment_type'] ) == $payment->id ) ? 'selected' : ''; ?>><?php echo esc_html( $payment->title ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="alignleft" style="margin-left: 5px;">
					<select name="status" id="status" class="specm_on_change_submit specm-input-style" style="width: 160px;">
						<option value=""><?php echo esc_html__( 'Status', 'shippop-ecommerce' ); ?></option>

						<?php foreach ( $statuses as $status ) : ?>
							<option value="<?php echo esc_attr( $status ); ?>" <?php echo ( ! empty( $_REQUEST['status'] ) && sanitize_text_field( $_REQUEST['status'] ) == $status ) ? 'selected' : ''; ?>><?php echo esc_html( wc_get_order_status_name($status) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="alignright">
					<label style="margin-right: 10px;"> <?php echo esc_html__( 'Please select order(s)', 'shippop-ecommerce' ); ?> </label>
					<button type="button" class="specm-get-list-choose-courier" style="cursor: not-allowed;" disabled="disabled"> <?php echo esc_html__( 'Choose Courier', 'shippop-ecommerce' ); ?> </button>
					<?php foreach ($specm_on_demand as $cc) : ?>
						<?php if ( $cc === "SKT" ) { continue; } ?>
						<?php if ( isset( $courier_list_avalible[ $cc ] ) ) : ?>
							<button type="button" class="specm-get-list-choose-courier" data-courier-code="<?php echo esc_attr( $cc ); ?>" style="cursor: not-allowed;" disabled="disabled"> <?php echo esc_html__( $courier_list_avalible[$cc]['courier_name'] , 'shippop-ecommerce' ); ?> </button>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>

			<?php
		elseif ( $which == 'bottom' ) :
			$per_page     = $this->get_items_per_page( 'specm_spect_per_page', $this->per_page );
			$option_value = array( 20, 50, 70, 100 );
			if ( ! in_array( $per_page, $option_value ) ) {
				$option_value[] = $per_page;
				sort( $option_value );
			}
			?>
			<?php echo esc_html__( 'View', 'shippop-ecommerce' ); ?>
			<select class="update_post_per_page specm-input-style" data-option-name="specm_spect_per_page" style="width: 50px;">
				<?php foreach ( $option_value as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>" <?php echo esc_attr( ( $per_page == $option ) ? 'selected' : '' ); ?>><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php echo esc_html__( 'items per page.', 'shippop-ecommerce' ); ?>
			<?php
		endif;
	}

	private function get_start_end_date( $format = 'Y-m-d' ) {
		$daterange_start = ( isset( $_REQUEST['daterange_start'] ) ) ? sanitize_text_field( $_REQUEST['daterange_start'] ) : false;
		$daterange_end   = ( isset( $_REQUEST['daterange_end'] ) ) ? sanitize_text_field( $_REQUEST['daterange_end'] ) : false;

		if ( $daterange_start && $daterange_end ) {
			$start_date = date( $format, strtotime( str_replace( '/', '-', $daterange_start ) ) );
			$end_date   = date( $format, strtotime( str_replace( '/', '-', $daterange_end ) ) );

			$check_start_date = date( 'Y-m-d', strtotime( str_replace( '/', '-', $start_date ) ) );
			$check_end_date   = date( 'Y-m-d', strtotime( str_replace( '/', '-', $end_date ) ) );

			if ( specm_helper_is_date( $check_start_date ) === false && specm_helper_is_date( $check_end_date ) === false ) {
				$start_date = date( $format, strtotime( '-1 days' ) );
				$end_date   = date( $format );
			}
		} else {
			$start_date = '';
			$end_date   = '';
		}

		// echo "<pre>";
		// print_r( $start_date );
		// // echo "<hr />";
		// // print_r( specm_helper_is_date($start_date ));
		// echo "<hr />";
		// print_r( $end_date );
		// // echo "<hr />";
		// // print_r( specm_helper_is_date( $end_date ));
		// echo "</pre>";
		// exit;

		return array( $start_date, $end_date );
	}
}
