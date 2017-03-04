<?php

defined( 'ABSPATH' ) or die();

function cacwpssao_grant_request( $request ) {

	$params = $request->get_params();
	if( !isset( $params['grant_type'] ) || empty( $params['grant_type'] ) || !isset( $params['client_id'] ) || empty( $params['client_id'] ) || !isset( $params['client_secret'] ) || empty( $params['client_secret'] ) || !isset( $params['scope'] ) || empty( $params['scope'] ) ) {
	
		return new WP_Error( 'malformed-request', 'Token requests require grant_type, client_id, client_secret, and scope.' . print_r( $request, true ), array( 'status' => 401 ) );
	
	}
	return $params;

}

add_action( 'rest_api_init', function () {
  register_rest_route( 'cacoauth/v1', '/token', array(
    'methods' => 'GET, POST',
    'callback' => 'cacwpssao_grant_request',
  ) );
} );


