<?php

defined( 'ABSPATH' ) or die();
use League\OAuth2\Server\Middleware\AuthorizationServerMiddleware;

function cacwpssao_grant_request( WP_REST_Request $request ) {

	$params = $request->get_params();
// 	return var_dump($request);
	if( !isset( $params['grant_type'] ) || empty( $params['grant_type'] ) || !isset( $params['client_id'] ) || empty( $params['client_id'] ) || !isset( $params['client_secret'] ) || empty( $params['client_secret'] ) || !isset( $params['scope'] ) || empty( $params['scope'] ) ) {
	
		return new WP_Error( 'malformed-request', 'Token requests require grant_type, client_id, client_secret, and scope.' . print_r( $request, true ), array( 'status' => 401 ) );
	
	}
	
	$oauth = cacwpssaoServer();
	$token_request = new CacwpssaoServerRequest( $request );
	$response =  new CacwpssaoResponse( 200, array(), 'stuff' );


	try {
    
        // Try to respond to the request
        $token_response = $oauth->respondToAccessTokenRequest($token_request, $response);
        return $token_response->getWpResponse();
        
    } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
    
        // All instances of OAuthServerException can be formatted into a HTTP response
        return $exception->generateHttpResponse($response)->getWpResponse();
        
    } catch (\Exception $exception) {
    
        // Unknown exception
        $body = new Stream('php://temp', 'r+');
        $body->write($exception->getMessage());
        return $response->withStatus(500)->withBody($body)->getWpResponse();
        
    }
	
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'cacoauth/v1', '/token', array(
    'methods' => 'GET, POST',
    'callback' => 'cacwpssao_grant_request',
  ) );
} );


function cacwpssao_process_auth( $request, $response ) {

	return var_dump( $response->getWpResponse() );
	
	return var_dump($response->getBody()->__toString());
	$final_response = $response->withBody($response->getBody());
	return var_dump( $final_response );

}
