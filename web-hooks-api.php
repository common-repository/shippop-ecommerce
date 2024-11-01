<?php

class SPECM_Web_Hooks_API {

	public $Crud = null;

	public function __construct() {
		$this->Crud = new SPECM_Crud_Function();
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'shippop/v1',
					'/update-status',
					array(
						'methods'  => WP_REST_Server::CREATABLE,
						'callback' => array( $this, 'specm_web_hooks' ),
						'permission_callback' => array( $this , 'specm_get_private_data_permissions_check' )
					)
				);
			}
		);
	}

	function specm_get_private_data_permissions_check() {
		return true;
	}

	function specm_web_hooks( WP_REST_Request $request ) {
		$parameters    = $request->get_params();
		$tracking_code = ( ! empty( $parameters['tracking_code'] ) ) ? $parameters['tracking_code'] : false;
		$order_status  = ( ! empty( $parameters['order_status'] ) ) ? $parameters['order_status'] : false;
		$data          = ( ! empty( $parameters['data'] ) ) ? $parameters['data'] : [];
		// {
			// "tracking_code": "",
			// "order_status": "",
			// "data": {
			// "weight": "",
			// "price": "",
			// "datetime": "",
			// "width": "",
			// "height": "",
			// "length": ""
		// }

		if ( ( $tracking_code && $order_status ) || $data ) {
			$query    = $this->Crud->specm_crud_update_shipping_status( $tracking_code, $order_status, $data, true );
			$response = array(
				'success' => 0,
			);
			if ( $query ) {
				$response = array(
					'success' => 1,
				);
			}
			return new WP_REST_Response( $response );
		} else {
			return new WP_Error( 403, 'missing data.', array( 'message' => 'missing data.' ) );
		}
	}
}

return new SPECM_Web_Hooks_API();
