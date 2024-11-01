<?php

class SPECM_Report_COD {
	public $_data    = array();
	public $wp_table = null;

	function __construct( $init = false ) {
		require_once SPECM_PLUGIN_PATH . '/page-table/report-cod-table.php';
		$this->wp_table = new SPECM_Report_COD_Table();
		if ( $init ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'specm_init_scripts_style' ) );
		}
	}

	function specm_init_scripts_style() {
		wp_register_style( 'jquery-ui-css', plugins_url( '/shippop-ecommerce/assets/css/smoothness/jquery-ui.min.css' ), false, SPECM_PLUGIN_PATH . 'assets/css/smoothness/jquery-ui.min.css', 'all' );
		wp_enqueue_style( 'jquery-ui-css' );

		wp_enqueue_script(
			'report-cod-js',
			plugins_url( '/shippop-ecommerce/assets/js/report-cod.js' ),
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-position' ),
			filemtime( SPECM_PLUGIN_PATH . 'assets/js/report-cod.js' ),
			true
		);

		wp_localize_script(
			'report-cod-js',
			'js_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( SPECM_AJAX_NONCE ),
			)
		);
	}

	function specm_index_page() {
		$this->_data = $this->wp_table->prepare_items();
		list($_total, $_summary_total, $_fee, $_pending_tranfer, $_cancelled, $_tranfered) = $this->wp_table->summary_data( $this->_data );
		?>
	<style>
		#specm-summary-table {
			border-collapse: collapse;
			width: 50%;
			float: right;
		}

		#specm-summary-table th {
			padding: 8px;
		}

		#specm-summary-table td {
			border: 1px solid #ddd;
			padding: 8px;
		}
	</style>

		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2><?php echo esc_html__( 'COD Report', 'shippop-ecommerce' ); ?></h2>

			<form id="wp-list-table-report-cod-form" class="shippop-wp-list-table" method="post">
				<?php
					$this->wp_table->display();
				?>
			</form>
			<table id="specm-summary-table" style="display: none;">
				<tr>
					<th style="float: right;"><?php echo esc_html__( 'Total', 'shippop-ecommerce' ); ?></th>
					<td style="text-align: center;"><?php echo esc_html( $_total ); ?></td>
					<td style="text-align: center;"><?php echo esc_html( $_fee ); ?></td>
					<td style="text-align: center;"><?php echo esc_html( $_summary_total ); ?></td>
				</tr>
				<tr>
					<th style="float: right;"><?php echo esc_html__( 'Summary total', 'shippop-ecommerce' ); ?></th>
					<td style="text-align: center;font-weight: bold;"><?php echo esc_html( $_total ); ?></td>
					<td style="text-align: center;font-weight: bold;"><?php echo esc_html( $_fee ); ?></td>
					<td style="text-align: center;font-weight: bold;"><?php echo esc_html( $_summary_total ); ?></td>
				</tr>
				<tr>
					<th style="float: right;"><?php echo esc_html__( 'Pending transfer', 'shippop-ecommerce' ); ?></th>
					<td></td>
					<td style="text-align: center;font-weight: bold;"><?php echo esc_html( $_pending_tranfer ); ?></td>
					<td></td>
				</tr>
				<tr>
					<th style="float: right;"><?php echo esc_html__( 'Cancelled', 'shippop-ecommerce' ); ?></th>
					<td></td>
					<td style="text-align: center;font-weight: bold;"><?php echo esc_html( $_cancelled ); ?></td>
					<td></td>
				</tr>
				<tr>
					<th style="float: right;"><?php echo esc_html__( 'Transfered', 'shippop-ecommerce' ); ?></th>
					<td></td>
					<td style="text-align: center;font-weight: bold;"><?php echo esc_html( $_tranfered ); ?></td>
					<td></td>
				</tr>
			</table>

		</div>
		<?php
	}
}
