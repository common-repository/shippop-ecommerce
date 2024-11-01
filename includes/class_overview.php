<?php

class SPECM_Overview {

	public function __construct() {
		// Setup screen options. Needs to be here as admin_init hook it too late.
		add_action( 'load-toplevel_page_shippop-ecommerce', array( $this, 'screen_options' ) );
		add_action( 'load-shippop_page_shippop-ecommerce-parcel', array( $this, 'screen_options' ) );
		add_action( 'load-shippop_page_shippop-ecommerce-report-cod', array( $this, 'screen_options' ) );

		add_filter( 'set-screen-option', array( $this, 'screen_options_set' ), 10, 3 );
		add_filter( 'set_screen_option_specm_spect_per_page', array( $this, 'screen_options_set' ), 10, 3 );
	}

	/**
	 * Add per-page screen option to the Forms table.
	 *
	 * @since 1.0.0
	 */
	public function screen_options() {
		$screen  = get_current_screen();
		$screens = array(
			'toplevel_page_shippop-ecommerce',
			'shippop_page_shippop-ecommerce-parcel',
			'shippop_page_shippop-ecommerce-report-cod',
		);

		// print_r($screen);
		// die;

		if ( null === $screen || ! in_array( $screen->id, $screens ) ) {
			return;
		}

		add_screen_option(
			'per_page',
			array(
				'label'   => esc_html__( 'Number of data per page:', 'shippop-ecommerce' ),
				'option'  => 'specm_spect_per_page',
				'default' => apply_filters( 'specm_spect_overview_per_page', 20 ),
			)
		);
	}

	/**
	 * Form table per-page screen option value.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $keep   Whether to save or skip saving the screen option value. Default false.
	 * @param string $option The option name.
	 * @param int    $value  The number of rows to use.
	 *
	 * @return mixed
	 */
	public function screen_options_set( $keep, $option, $value ) {
		if ( 'specm_spect_per_page' === $option ) {
			return $value;
		}

		return $keep;
	}
}

new SPECM_Overview();
