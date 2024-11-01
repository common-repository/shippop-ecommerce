<?php

class SPECM_Parcel_Shipping {
	public $wp_table = null;

	function __construct( $init = false ) {
		require_once SPECM_PLUGIN_PATH . '/page-table/parcel-shipping-table.php';
		$this->wp_table = new SPECM_Parcel_Shipping_List_Table();
		if ( $init ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'specm_init_scripts_style' ) );
		}
	}

	function specm_init_scripts_style() {
		wp_register_style( 'jquery-ui-css', plugins_url( '/shippop-ecommerce/assets/css/smoothness/jquery-ui.min.css' ), false, filemtime( SPECM_PLUGIN_PATH . 'assets/css/smoothness/jquery-ui.min.css' ), 'all' );
		wp_enqueue_style( 'jquery-ui-css' );

		wp_enqueue_script(
			'parcel-shipping-js',
			plugins_url( '/shippop-ecommerce/assets/js/parcel-shipping.js' ),
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-position' ),
			filemtime( SPECM_PLUGIN_PATH . 'assets/js/parcel-shipping.js' ),
			true
		);

		wp_localize_script(
			'parcel-shipping-js',
			'ps_js_object',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( SPECM_AJAX_NONCE ),
				'confirm_cancel' => esc_html__( 'Confirm order cancellation ?', 'shippop-ecommerce' ),
			)
		);
	}

	function specm_index_page() {
		$this->wp_table->prepare_items();
		?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2><?php echo esc_html__( 'Parcel List', 'shippop-ecommerce' ); ?></h2>

			<form id="wp-list-table-parcel-shipping-form" class="shippop-wp-list-table" method="post">
				<?php
					$this->wp_table->display();
				?>
			</form>

			<!-- Modal HTML embedded directly into document -->
			<div id="shippop-ecommerce-modal" class="modal modal-shippop" style="max-width: 50%;">
				<div>
					<div class="modal-title"><?php echo esc_html__( 'Parcel tracking', 'shippop-ecommerce' ); ?></div>
					<div class="modal-close">
						<a href="javascript:void(0)" rel="modal:close">
							<span rel="modal:close">X</span>
						</a>
					</div>
				</div>
			
				<div class="modal-content">
				
				</div>
			</div>
		</div>
		<?php
	}
}
