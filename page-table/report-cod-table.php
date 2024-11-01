<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class SPECM_Report_COD_Table extends WP_List_Table {

	public $per_page;
	public $option_name = 'shippop_page_shippop-ecommerce-report-cod';

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

		$filter_date                 = ( isset( $_REQUEST['filter_date'] ) ) ? sanitize_text_field( $_REQUEST['filter_date'] ) : 'SHIPPING';
		list($start_date, $end_date) = $this->get_start_end_date();

		$data     = specm_helper_get_report_cod( $start_date, $end_date, $filter_date );
		$per_page = $this->get_items_per_page( 'specm_spect_per_page', $this->per_page );

		specm_helper_prepare_report_obj( $data, true, false );
		usort( $data, array( &$this, 'sort_data' ) );
		// $data = [];
		$totalItems  = count( $data );
		$currentPage = $this->get_pagenum();
		$this->set_pagination_args(
			array(
				'total_items' => $totalItems,
				'per_page'    => $per_page,
			)
		);

		$data = array_slice( $data, ( ( $currentPage - 1 ) * $per_page ), $per_page );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;

		return $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns() {
		$columns = array(
			'order'                 => esc_html__( 'Order', 'shippop-ecommerce' ),
			'tracking_code'         => esc_html__( "SHIPPOP's tracking number", 'shippop-ecommerce' ),
			'courier_tracking_code' => esc_html__( "Courier's tracking number", 'shippop-ecommerce' ),
			'datetime_shipping'     => esc_html__( 'Delivery date', 'shippop-ecommerce' ),
			'datetime_complete'     => esc_html__( 'Date of delivery completion', 'shippop-ecommerce' ),
			'shippop_status'        => esc_html__( 'Status', 'shippop-ecommerce' ),
			'destination_name'      => esc_html__( 'Receiver name', 'shippop-ecommerce' ),
			'datetime_transfer'     => esc_html__( 'COD transfer date', 'shippop-ecommerce' ),
			'cod_amount'            => esc_html__( 'COD amount', 'shippop-ecommerce' ),
			'cod_charge'            => esc_html__( 'Fee', 'shippop-ecommerce' ),
			'cod_total'             => esc_html__( 'Transferring amount', 'shippop-ecommerce' ),
			'receipt_id'            => esc_html__( 'Billing ID', 'shippop-ecommerce' ),
			'cod_status'            => esc_html__( 'Transferring status', 'shippop-ecommerce' ),
		);

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
				case 'export_to_csv':
					$filter_date                 = ( isset( $_REQUEST['filter_date'] ) ) ? sanitize_text_field( $_REQUEST['filter_date'] ) : 'SHIPPING';
					list($start_date, $end_date) = $this->get_start_end_date();

					$data = specm_helper_get_report_cod( $start_date, $end_date, $filter_date );
					specm_helper_prepare_report_obj( $data, true, true );
					if ( ! empty( $data ) ) {
						usort( $data, array( &$this, 'sort_data' ) );
						specm_helper_export_data_to_csv( $data, 'SHIPPOP_Report_COD_' . time(), $this->get_columns() );
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
			// 'order_status' => array('order_status', false)
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
				break;
			case 'datetime_shipping':
			case 'datetime_transfer':
			case 'datetime_complete':
				$str_date = "<span style='display: block;'> " . $item[ $column_name ]['date'] . ' </span>';
				$str_time = "<span style='display: block;font-size: 10px;color: gray;' > " . $item[ $column_name ]['time'] . ' </span>';

				if ( $item[ $column_name ]['date'] == '' && $item[ $column_name ]['time'] == '' ) {
					return '-';
				}
				return $str_date . '' . $str_time;
				break;
			case 'receipt_id':
				$receipt_id_url = ( $item['receipt_id_url'] ) ? $item['receipt_id_url'] : false;
				if ( $receipt_id_url ) {
					$a = $receipt_id_url;
				} else {
					$a = 'javascript:void(0);';
				}
				$label = ( $item['receipt_id'] != '' ) ? $item['receipt_id'] : '-';
				return "<a href='$a' target='_blank'>" . $label . '</a>';
				break;
			case 'shippop_status':
				global $specm_shippop_status;
				global $specm_shippop_order_statuses_color;
				return "<span style='font-size:10px;padding: 5px;background-color: " . $specm_shippop_order_statuses_color[ $item['shippop_status'] ][0] . ';color: ' . $specm_shippop_order_statuses_color[ $item['shippop_status'] ][1] . "'> " . esc_html__( $specm_shippop_status[ $item[ $column_name ] ], 'shippop-ecommerce' ) . ' </span>';
				break;
			case 'cod_status':
				return esc_html__( $item[ $column_name ], 'shippop-ecommerce' );
				break;
			case 'order_status':
			case 'cod_amount':
			case 'cod_charge':
			case 'cod_total':
			case 'destination_name':
			case 'courier_tracking_code':
			case 'tracking_code':
				return $item[ $column_name ];
				break;
			default:
				return print_r( $item, true );
		}
	}

	public function extra_tablenav( $which ) {
		list($start_date, $end_date) = $this->get_start_end_date( 'd/m/Y', 'd/m/Y' );
		if ( $which == 'top' ) :
			?>
				<ul class="subsubsub" style="margin-bottom: 10px;">
					<li class="<?php echo ( ! empty( $_GET['filter'] ) && sanitize_text_field( $_GET['filter'] ) == 'last_7days' ) ? 'specm-current' : ''; ?> sp-last-7day specm-sp "><a href="<?php echo admin_url( 'admin.php?page=shippop-ecommerce-report-cod&filter=last_7days' ); ?>" <?php echo ( ! empty( $_GET['filter'] ) && sanitize_text_field( $_GET['filter'] ) == 'last_7days' ) ? esc_attr( 'class="current" aria-current="page"' ) : ''; ?> > <?php echo esc_html__( 'Last Week', 'shippop-ecommerce' ); ?> </a> </li>
					<li class="<?php echo ( ! empty( $_GET['filter'] ) && sanitize_text_field( $_GET['filter'] ) == 'this_month' ) ? 'specm-current' : ''; ?> sp-this-month specm-sp "><a href="<?php echo admin_url( 'admin.php?page=shippop-ecommerce-report-cod&filter=this_month' ); ?>" <?php echo ( ! empty( $_GET['filter'] ) && sanitize_text_field( $_GET['filter'] ) == 'this_month' ) ? esc_attr( 'class="current" aria-current="page"' ) : ''; ?> > <?php echo esc_html__( 'This Month', 'shippop-ecommerce' ); ?> </a> </li>
					<li class="<?php echo ( ! empty( $_GET['filter'] ) && sanitize_text_field( $_GET['filter'] ) == 'last_month' ) ? 'specm-current' : ''; ?> sp-last-month specm-sp "><a href="<?php echo admin_url( 'admin.php?page=shippop-ecommerce-report-cod&filter=last_month' ); ?>" <?php echo ( ! empty( $_GET['filter'] ) && sanitize_text_field( $_GET['filter'] ) == 'last_month' ) ? esc_attr( 'class="current" aria-current="page"' ) : ''; ?> > <?php echo esc_html__( 'Last Month', 'shippop-ecommerce' ); ?> </a> </li>
					<li class="<?php echo ( ! empty( $_GET['filter'] ) && sanitize_text_field( $_GET['filter'] ) == 'last_year' ) ? 'specm-current' : ''; ?> sp-last-year specm-sp "><a href="<?php echo admin_url( 'admin.php?page=shippop-ecommerce-report-cod&filter=last_year' ); ?>" <?php echo ( ! empty( $_GET['filter'] ) && sanitize_text_field( $_GET['filter'] ) == 'last_year' ) ? esc_attr( 'class="current" aria-current="page"' ) : ''; ?> > <?php echo esc_html__( 'Last Year', 'shippop-ecommerce' ); ?> </a> </li>
				</ul>
				<div class="clearfix"></div>

				<div class="alignleft" style="margin-bottom: 5px;">
					<input style="width: 130px;" class="specm-input-style" type="text" name="daterange_start" data-start-date="" value="<?php echo esc_attr( $start_date ); ?>" placeholder="<?php echo esc_attr__( 'Search from date', 'shippop-ecommerce' ); ?>" autocomplete="off" />
					<input style="width: 130px;" class="specm-input-style" type="text" name="daterange_end" data-end-date="" value="<?php echo esc_attr( $end_date ); ?>" placeholder="<?php echo esc_attr__( 'To date', 'shippop-ecommerce' ); ?>" autocomplete="off" />
				</div>

				<div class="alignleft" style="margin-left: 5px;margin-bottom: 5px;">
					<select name="filter_date" id="filter_date" class="specm-input-style specm_on_change_submit" style="width: 200px;">
						<option value="SHIPPING" <?php echo ( empty( $_REQUEST['filter_date'] ) || sanitize_text_field( $_REQUEST['filter_date'] ) == 'SHIPPING' ) ? esc_attr( 'selected' ) : ''; ?> ><?php echo esc_html__( 'Search from shipping date', 'shippop-ecommerce' ); ?></option>
						<option value="TRANSFER" <?php echo ( ! empty( $_REQUEST['filter_date'] ) && sanitize_text_field( $_REQUEST['filter_date'] ) == 'TRANSFER' ) ? esc_attr( 'selected' ) : ''; ?> ><?php echo esc_html__( 'Search from COD transferring date', 'shippop-ecommerce' ); ?></option>
					</select>
				</div>

				<div class="alignright" style="margin-left: 5px;margin-bottom: 5px;">
					<input type="hidden" value="" name="action">
					<button type="button" class="button action" id="export_to_csv"> <i class="fa fa-download" aria-hidden="true"></i> <?php echo esc_html__( 'Export', 'shippop-ecommerce' ); ?></button>
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
			<!-- <div></div>
			<div class="clearfix"></div> -->
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

	private function sort_data( $a, $b ) {
		// Set defaults
		$orderby = 'order_status';
		$order   = 'asc';

		// If orderby is set, use this as the sort column
		if ( ! empty( $_GET['orderby'] ) ) {
			$orderby = sanitize_text_field( $_GET['orderby'] );
		}

		// If order is set use this as the order
		if ( ! empty( $_GET['order'] ) ) {
			$order = sanitize_text_field( $_GET['order'] );
		}

		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		// $result = ( $a[$orderby] > $b[$orderby] ) ? 1 : -1;

		if ( $order === 'asc' ) {
			return $result;
		}

		return -$result;
	}

	private function get_start_end_date( $format = 'Y-m-d', $end_format = 'Y-m-d' ) {
		$daterange_start = ( isset( $_REQUEST['daterange_start'] ) ) ? sanitize_text_field( $_REQUEST['daterange_start'] ) : false;
		$daterange_end   = ( isset( $_REQUEST['daterange_end'] ) ) ? sanitize_text_field( $_REQUEST['daterange_end'] ) : false;
		$filter          = ( isset( $_REQUEST['filter'] ) ) ? sanitize_text_field( $_REQUEST['filter'] ) : '-';

		if ( $daterange_start && $daterange_end ) {
			$start_date = date( $format, strtotime( str_replace( '/', '-', $daterange_start ) ) );
			$end_date   = date( $format, strtotime( str_replace( '/', '-', $daterange_end ) ) );

			$check_start_date = date( 'Y-m-d', strtotime( str_replace( '/', '-', $start_date ) ) );
			$check_end_date   = date( 'Y-m-d', strtotime( str_replace( '/', '-', $end_date ) ) );

			if ( specm_helper_is_date( $check_start_date ) === false && specm_helper_is_date( $check_end_date ) === false ) {
				$start_date = date( $format, strtotime( '-1 day' ) );
				$end_date   = date( $format );
			}
		} else {
			$reformat_start_date = true;
			$reformat_end_date   = true;
			switch ( $filter ) {
				case 'last_year':
					$start_date          = date( $format, strtotime( date( 'Y-01-01', strtotime( '-1 year' ) ) ) );
					$end_date            = date( $end_format, strtotime( date( 'Y-12-31', strtotime( '-1 year' ) ) ) );
					$reformat_start_date = false;
					$reformat_end_date   = false;
					break;
				case 'last_month':
					$start_date = date( '01-m-Y', strtotime( 'last month' ) );
					$end_date   = date( 't-m-Y', strtotime( 'last month' ) );
					break;
				case 'this_month':
					$day                 = date( 'd' ) - 1;
					$start_date          = date( $format, strtotime( "-$day day" ) );
					$end_date            = date( 't-m-Y', strtotime( "-$day day" ) );
					$reformat_start_date = false;
					break;
				case 'last_7days':
					$start_date          = date( $format, strtotime( '-7 day' ) );
					$end_date            = date( $end_format );
					$reformat_start_date = false;
					$reformat_end_date   = false;
					break;
				default:
					$start_date          = date( $format, strtotime( '-1 day' ) );
					$end_date            = date( $end_format, strtotime( '-1 day' ) );
					$reformat_start_date = false;
					$reformat_end_date   = false;
					break;
			}

			if ( $reformat_start_date ) {
				$start_date = date( $format, strtotime( $start_date ) );
			}
			if ( $reformat_end_date ) {
				$end_date = date( $end_format, strtotime( $end_date ) );
			}

			// $start_date = date($format, strtotime($st_con));
			// $end_date = date($end_format, strtotime($st_con));
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

	public function summary_data( $_data ) {
		$_total = $_summary_total = $_fee = $_pending_tranfer = $_cancelled = $_tranfered = 0;

		foreach ( $_data as $key => $value ) {
			$_total           += floatval( $value['cod_amount'] );
			$_summary_total   += floatval( $value['cod_total'] );
			$_fee             += floatval( $value['cod_charge'] );
			$_pending_tranfer += ( in_array( $value['_cod_status'], array( 'wait_transfer', 'pending_transfer' ) ) ) ? floatval( $value['cod_total'] ) : 0;
			$_cancelled       += ( $value['_cod_status'] == 'cancel_transfer' ) ? 1 : 0;
			$_tranfered       += ( $value['_cod_status'] == 'transferred' ) ? 1 : 0;
		}

		return array( number_format( $_total, 1 ), number_format( $_summary_total, 1 ), number_format( $_fee, 1 ), $_pending_tranfer, $_cancelled, $_tranfered );
	}
}
