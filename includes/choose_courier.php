<?php

class SPECM_Choose_Courier {

	public $wp_table = null;

	function __construct( $init = false ) {
		require_once SPECM_PLUGIN_PATH . '/page-table/shippop-ecommerce-table.php';
		$this->wp_table = new SPECM_Shippop_Ecommerce_List_Table();
		if ( $init ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'specm_init_scripts_style' ) );
		}
	}

	function specm_init_scripts_style() {
		wp_register_style( 'jquery-ui-css', plugins_url( '/shippop-ecommerce/assets/css/smoothness/jquery-ui.min.css' ), false, '1.0.0', 'all' );
		wp_enqueue_style( 'jquery-ui-css' );

		wp_enqueue_script(
			'shippop-ecommerce-js',
			plugins_url( '/shippop-ecommerce/assets/js/shippop-ecommerce.js' ),
			array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-position' ),
			filemtime( SPECM_PLUGIN_PATH . 'assets/js/shippop-ecommerce.js' ),
			true
		);

		wp_localize_script(
			'shippop-ecommerce-js',
			'js_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( SPECM_AJAX_NONCE ),
			)
		);
	}

	function specm_index_page() {
		$this->wp_table->prepare_items();
		?>
		<div class="wrap">
			<div id="icon-users" class="icon32"></div>
			<h2><?php echo esc_html__( 'Choose Courier', 'shippop-ecommerce' ); ?></h2>
			<p style="margin: 0px;font-style: italic;"><?php echo esc_html__( 'Orders will appeared if order status is processing or order payment method is COD', 'shippop-ecommerce' ); ?></p>
			<form id="wp-list-table-shippop-ecommerce-form" class="shippop-wp-list-table" method="post">
				<?php
				$this->wp_table->display();
				?>
			</form>

			<!-- Modal HTML embedded directly into document -->
			<div id="shippop-ecommerce-modal" class="modal modal-shippop">
				<div style="height: 50px;">
					<div class="modal-title"><?php echo esc_html__( 'Choose Courier', 'shippop-ecommerce' ); ?></div>
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
