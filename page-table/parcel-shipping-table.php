<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class SPECM_Parcel_Shipping_List_Table extends WP_List_Table {

	public $total_data = 0;
	public $per_page;
	public $option_name = 'shippop_page_shippop-ecommerce-parcel';

	public function __construct() {
		// Utilize the parent constructor to build the main class properties.
		parent::__construct(
			array(
				'singular' => 'form',
				'plural'   => 'forms',
				'ajax'     => false,
			)
		);

		// Default number of forms to show per page.
		$this->per_page = (int) apply_filters( 'specm_spect_overview_per_page', 20 );
		add_filter( 'manage_' . $this->option_name . '_columns', array( $this, 'get_columns' ), 0 );
	}

	public function prepare_data( $sortable = array(), $perPage = 10, $currentPage = 1 ) {
		$orderby       = ( isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $sortable ) ) ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'id';
		$order         = ( isset( $_REQUEST['order'] ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'desc';
		$tracking_code = ( isset( $_REQUEST['tracking_code'] ) ) ? sanitize_text_field( $_REQUEST['tracking_code'] ) : '';
		$status   = ( isset( $_REQUEST['status'] ) ) ? sanitize_text_field( $_REQUEST['status'] ) : '';

		// $statuses = array( 'wc-processing', 'wc-canceled', 'wc-completed', 'wc-refunded' );
		if ( $status == '' ) {
			// $specm_advance_setting = get_option( 'specm_advance_setting', [] );
			// $statuses = isset( $specm_advance_setting['choose_parcel_status'] ) ? explode( "," , esc_attr( $specm_advance_setting['choose_parcel_status'] ) ) : [ 'wc-processing' ];
			$specm_advance_setting = get_option( 'specm_advance_setting', [] );
			$statuses = isset( $specm_advance_setting['choose_parcel_status'] ) ? explode( "," , esc_attr( $specm_advance_setting['choose_parcel_status'] ) ) : [ 'wc-processing', 'wc-canceled', 'wc-completed', 'wc-refunded' ];
			$statuses = array_merge( $statuses , array( 'wc-processing', 'wc-canceled', 'wc-completed', 'wc-refunded' ) );
			$statuses = array_unique( $statuses );
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
			'value'   => '1',
			'compare' => '=',
		);

		if ( ! empty( $_GET['shippop_status'] ) ) {
			$_meta_query[] = array(
				'key'     => '_shippop_status',
				'value'   => sanitize_text_field( $_GET['shippop_status'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $_POST['courier_code'] ) ) {
			$_meta_query[] = array(
				'key'     => '_booking_courier_code',
				'value'   => sanitize_text_field( $_POST['courier_code'] ),
				'compare' => '=',
			);
		}

		if ( ! empty( $tracking_code ) ) {
			$__meta_query[] = array(
				'relation' => 'OR',
			);

			$meta_key_search = array(
				// "_booking_courier_tracking_code",
				'_booking_tracking_code',
			);
			foreach ( $meta_key_search as $search ) {
				$__meta_query[] = array(
					'key'     => $search,
					'value'   => $tracking_code,
					'compare' => 'LIKE',
				);
			}
		}

		$meta_query[] = $_meta_query;
		$meta_query[] = $__meta_query;

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
		$totalItems = $this->total_data = $query->found_posts;

		$query     = new WP_Query(
			array_merge(
				$args,
				array(
					'orderby'        => $orderby,
					'order'          => $order,
					'posts_per_page' => $perPage,
					'paged'          => $currentPage,
				)
			)
		);
		$order_ids = $query->posts;

		return array( $order_ids, $totalItems );
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
		$this->process_bulk_action();

		// $per_page = 10;
		$per_page    = $this->get_items_per_page( 'specm_spect_per_page', $this->per_page );
		$currentPage = $this->get_pagenum();

		list($order_ids, $totalItems) = $this->prepare_data( $sortable, $per_page, $currentPage );
		$data                         = specm_helper_convert_wp_object_to_order_array( $order_ids );

		$this->set_pagination_args(
			array(
				'total_items' => $totalItems,
				'per_page'    => $per_page,
				'total_pages' => ceil( $totalItems / $per_page ),
			)
		);

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns() {
		$columns = array(
			'cb'                    => '<input type="checkbox" />',
			'order'                 => esc_html__( 'Order', 'shippop-ecommerce' ),
			'date_created'          => esc_html__( 'Order date', 'shippop-ecommerce' ),
			'date_booking'          => esc_html__( 'Booking date', 'shippop-ecommerce' ),
			'tracking_code'         => esc_html__( "SHIPPOP's tracking number", 'shippop-ecommerce' ),
			'courier_tracking_code' => esc_html__( "Courier's tracking number", 'shippop-ecommerce' ),
			'courier_name'          => esc_html__( 'Couriers', 'shippop-ecommerce' ),
			'shippop_status'        => esc_html__( 'Status', 'shippop-ecommerce' ),
			'actually_weight'       => esc_html__( 'Real weight', 'shippop-ecommerce' ),
			'actually_price'        => esc_html__( 'Real charge', 'shippop-ecommerce' ),
		);

		$specm_server = get_option( 'specm_server', 'TH' );
		if ( strtoupper( $specm_server ) === 'TH' ) {
			$columns['cod'] = esc_html__( 'COD', 'shippop-ecommerce' );
		}

		if ( ! empty( $_GET['shippop_status'] ) && sanitize_text_field( $_GET['shippop_status'] ) == 'booking' ) {
			$columns['purchase_cancel'] = esc_html__( 'Cancel order', 'shippop-ecommerce' );
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

			$action = $this->current_action();
			switch ( $action ) {
				case 'bulk_preprint_label':
					// $bulk_ids = isset( $_POST['bulk_id'] ) ? array_map( 'absint', (array) $_POST['bulk_id'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
					// if ( count($bulk_ids) > 0 ) {
					// $tracking_code = [];
					// foreach ($bulk_ids as $bulk_id) {
					// if ($bulk_id) {
					// $tracking_code[] = get_post_meta(esc_attr($bulk_id), "_booking_tracking_code", true);
					// }
					// }
					// $tracking_code = array_unique( array_filter($tracking_code) );
					// specm_helper_preprint_label($tracking_code, sanitize_text_field($_POST["printlabel_size"]), "html");
					// }
					break;
				case 'export_to_csv':
						$sortable                     = $this->get_sortable_columns();
						list($order_ids, $totalItems) = $this->prepare_data( $sortable, -1, 1 );
						$data                         = specm_helper_convert_wp_object_to_order_array( $order_ids, true );

					if ( ! empty( $data ) ) {
						usort( $data, array( &$this, 'sort_data' ) );
						$columns = $this->get_columns();
						unset( $columns['cb'] );
						if ( ! empty( $columns['purchase_cancel'] ) ) {
							unset( $columns['purchase_cancel'] );
						}
						specm_helper_export_data_to_csv( $data, 'SHIPPOP_Shipping_Parcel_' . time(), $columns );
						die;
					}
					break;
				default:
					// do nothing or something else
					return;
					break;
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
			'date_booking' => array( 'date_booking' , false )
		);
	}

	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk_id[]" value="%s" />',
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
			case 'date_booking':
				$_shippop_purchase_confirm = get_post_meta( $item['order_id'] , '_shippop_purchase_confirm' , true );
				if ( $_shippop_purchase_confirm === false || empty( $_shippop_purchase_confirm ) ) {
					$_shippop_purchase_confirm = "";
				}
				return $_shippop_purchase_confirm;
			case 'courier_tracking_code':
			case 'tracking_code':
				return "<a href='javascript:void(0);' class='check-tracking-code' data-tracking-code='" . $item['tracking_code'] . "' data-order-id='" . $item['order_id'] . "' > " . $item[ $column_name ] . ' </a>';
			case 'shippop_status':
				global $specm_shippop_status;
				global $specm_shippop_order_statuses_color;
				return "<span style='font-size:10px;padding: 5px;background-color: " . $specm_shippop_order_statuses_color[ $item['shippop_status'] ][0] . ';color: ' . $specm_shippop_order_statuses_color[ $item['shippop_status'] ][1] . "'> " . esc_html__( $specm_shippop_status[ $item[ $column_name ] ], 'shippop-ecommerce' ) . ' </span>';
			case 'preprint':
				return "<button type='button' class='button'>" . esc_html__( 'Print waybill', 'shippop-ecommerce' ) . '</button>';
			case 'purchase_cancel':
				return "<button type='button' data-order-id='" . $item['order_id'] . "' data-tracking-code='" . $item['tracking_code'] . "' class='button purchase-cancel'>" . esc_html__( 'Cancel order', 'shippop-ecommerce' ) . '</button>';
			case 'cod':
			case 'actually_price':
			case 'actually_weight':
			case 'courier_name':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	public function extra_tablenav( $which ) {
		global $specm_shippop_status;
		global $specm_printlabel_size;
		list($start_date, $end_date) = $this->get_start_end_date( 'd/m/Y' );
		$courier_list_avalible       = get_option( 'courier_list_avalible', array() );
		$specm_server = get_option( 'specm_server', 'TH' );

		$specm_advance_setting = get_option( 'specm_advance_setting', [] );
		$statuses = isset( $specm_advance_setting['choose_parcel_status'] ) ? explode( "," , esc_attr( $specm_advance_setting['choose_parcel_status'] ) ) : [ 'wc-processing' ];
		if ( $which == 'top' ) :
			?>
			<ul class="subsubsub" style="margin-bottom: 10px;">
				<li class="<?php echo ( empty( $_GET['shippop_status'] ) ) ? 'specm-current' : ''; ?> specm-sp sp-all"><a href="<?php echo admin_url( 'admin.php?page=shippop-ecommerce-parcel' ); ?>" <?php echo ( empty( $_GET['shippop_status'] ) ) ? esc_attr( 'class="current" aria-current="page"' ) : ''; ?> ><?php echo esc_html__( 'All', 'shippop-ecommerce' ); ?> <span class="count">(0)</span></a> </li>
				<?php foreach ( $specm_shippop_status as $status => $name ) : ?>
					<li class="<?php echo ( ! empty( $_GET['shippop_status'] ) && sanitize_text_field( $_GET['shippop_status'] ) == $status ) ? 'specm-current' : ''; ?> specm-sp sp-<?php echo esc_attr( $status ); ?>"><a href="<?php echo admin_url( "admin.php?page=shippop-ecommerce-parcel&shippop_status=$status" ); ?>" <?php echo ( ! empty( $_GET['shippop_status'] ) && sanitize_text_field( $_GET['shippop_status'] ) == $status ) ? esc_attr( 'class="current" aria-current="page"' ) : ''; ?> ><?php echo esc_html__( $name, 'shippop-ecommerce' ); ?> <span class="count">(0)</span></a> </li>
				<?php endforeach; ?>
			</ul>
			<div class="clearfix"></div>

			<!-- <input type="hidden" value="" name="action"> -->
			<div class="alignleft">
				<select name="courier_code" id="courier_code" class="specm_on_change_submit specm-input-style">
					<option value=""><?php echo esc_html__( 'All Courier', 'shippop-ecommerce' ); ?></option>
					<?php foreach ( $courier_list_avalible as $courier_code => $courier ) : ?>
						<?php
							if ( strpos( $courier['courier_name'] , "Seventree" ) !== false ) {
								$courier['courier_name'] = "EMS";
							}
						?>
						<option value="<?php echo esc_attr( $courier_code ); ?>" <?php echo ( ! empty( $_REQUEST['courier_code'] ) && sanitize_text_field( $_REQUEST['courier_code'] ) == $courier_code ) ? esc_attr( 'selected' ) : ''; ?> ><?php echo esc_html( $courier['courier_name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="alignleft" style="margin-left: 5px;margin-bottom: 5px;">
				<input style="width: 130px;" class="specm-input-style" type="text" name="daterange_start" data-start-date="" value="<?php echo esc_attr( $start_date ); ?>" placeholder="<?php echo esc_attr__( 'Search from date', 'shippop-ecommerce' ); ?>" autocomplete="off" />
				<input style="width: 130px;" class="specm-input-style" type="text" name="daterange_end" data-end-date="" value="<?php echo esc_attr( $end_date ); ?>" placeholder="<?php echo esc_attr__( 'To date', 'shippop-ecommerce' ); ?>" autocomplete="off" />
			</div>

			<div class="alignleft" style="margin-left: 5px;margin-bottom: 5px;">
				<input style="width: 200px;" name="tracking_code" value="<?php echo ( ! empty( $_REQUEST['tracking_code'] ) ) ? esc_attr( $_REQUEST['tracking_code'] ) : ''; ?>" class="specm-input-style" type="text" placeholder="<?php echo esc_attr__( 'Search by Tracking number', 'shippop-ecommerce' ); ?>" autocomplete="off" />
				<button type="submit" class="specm-search-with-input" ><i class="fa fa-search"></i></button>
			</div>

			<div class="alignleft" style="margin-left: 5px;">
				<select name="status" id="status" class="specm_on_change_submit specm-input-style" style="width: 160px;">
					<option value=""><?php echo esc_html__( 'Status', 'shippop-ecommerce' ); ?></option>

					<?php foreach ( $statuses as $status ) : ?>
						<option value="<?php echo esc_attr( $status ); ?>" <?php echo ( ! empty( $_REQUEST['status'] ) && sanitize_text_field( $_REQUEST['status'] ) == $status ) ? 'selected' : ''; ?>><?php echo esc_html( wc_get_order_status_name($status) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="alignright" style="margin-left: 5px;margin-bottom: 5px;">
				<label> <?php echo esc_html__( 'Please select order(s)', 'shippop-ecommerce' ); ?> </label>
				<input type="hidden" value="" name="action">
				<select name="printlabel_size" id="printlabel_size" class="specm-input-style printing-label" style="cursor: not-allowed;" disabled="disabled">
					<option value=""><?php echo esc_html__( 'Print waybill', 'shippop-ecommerce' ); ?></option>
					<?php foreach ( $specm_printlabel_size as $size => $name ) : ?>
						<?php if ( in_array( $size , ['sticker' , 'sticker4x6'] ) && $specm_server == "MY" ) { continue; } ?>
						<option value="<?php echo esc_attr( $size ); ?>" <?php echo ( ! empty( $_REQUEST['printlabel_size'] ) && sanitize_text_field( $_REQUEST['printlabel_size'] ) == $size ) ? esc_attr( 'selected' ) : ''; ?> ><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
				<button type="button" class="button action" id="export_to_csv"> <i class="fa fa-download" aria-hidden="true"></i> <?php echo esc_html__( 'Export', 'shippop-ecommerce' ); ?></button>
				<button type="button" class="button" id="manual_tracking" style="cursor: not-allowed;" disabled="disabled"> <i class="fa fa-truck" aria-hidden="true"></i> <?php echo esc_html__( 'Update Tracking', 'shippop-ecommerce' ); ?></button>
			</div>

			<div class="clearfix"></div>
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
					<option value="<?php echo esc_attr( $option ); ?>" <?php echo ( $per_page == $option ) ? esc_attr( 'selected' ) : ''; ?>><?php echo esc_html( $option ); ?></option>
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
