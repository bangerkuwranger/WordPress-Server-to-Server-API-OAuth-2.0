<?php

defined( 'ABSPATH' ) or die();
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;

function cacwpssao_grant_request( $request ) {

	$params = $request->get_params();
	if( !isset( $params['grant_type'] ) || empty( $params['grant_type'] ) || !isset( $params['client_id'] ) || empty( $params['client_id'] ) || !isset( $params['client_secret'] ) || empty( $params['client_secret'] ) || !isset( $params['scope'] ) || empty( $params['scope'] ) ) {
	
		return new WP_Error( 'malformed-request', 'Token requests require grant_type, client_id, client_secret, and scope.' . print_r( $request, true ), array( 'status' => 401 ) );
	
	}

	$auth = array(
		'grant_type'		=> $params['grant_type'],
		'client_id'			=> $params['client_id'],
		'client_secret'		=> $params['client_secret'],
		'scope'				=> $params['scope']
	);
	
	$oauth = cacwpssaoServer();
	$token_request = new CacwpssaoServerRequest( $request );
	$response =  new CacwpssaoResponse();
// 	$response_obj = new BearerTokenResponse();
// 	$token_response = $response_obj->generateHttpResponse( $response );
	
// 	$authorizer = cacwpssaoAuthorizationServerMiddleware();
// 	
// 	$response = new BearerTokenResponse();
// 	
// 	$authorizer( $request, $response, 'cacwpssao_process_auth' );

	return $oauth->respondToAccessTokenRequest( $token_request, $response );	
	

}

add_action( 'rest_api_init', function () {
  register_rest_route( 'cacoauth/v1', '/token', array(
    'methods' => 'GET, POST',
    'callback' => 'cacwpssao_grant_request',
  ) );
} );


function cacwpssao_process_auth( $request, $response ) {

	return $request->get_headers()['authorization'];

}
