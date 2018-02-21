<?php
/**
 * Plugin Name: WordPress Server to Server API OAuth 2.0
 * Plugin URI: http://www.github.com/bangerkuwranger/WordPress-Server-to-Server-API-OAuth-2.0
 * Description: WP REST API OAuth2 support for server-to-server authentication only.
 * Version: 1.0.0
 * Author: Chad A. Carino
 * Author URI: http://www.chadacarino.com
 * License: MIT
 */
/*
The MIT License (MIT)
Copyright (c) 2017 Chad A. Carino

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

defined( 'ABSPATH' ) or die();

define( 'CACWPSSAO_PREFIX', 'cAcWpSSAO_' );
define( 'CACWPSSAO_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'CACWPSSAO_DIR_URI', plugin_dir_url( __FILE__ ) );

require_once CACWPSSAO_DIR_PATH . 'vendor/autoload.php';

require_once CACWPSSAO_DIR_PATH . 'rsa.php';

require_once CACWPSSAO_DIR_PATH . 'permissions.php';

require_once CACWPSSAO_DIR_PATH . 'admin.php';

function cacwpssao_activate() {

    $rsakeys = new CacwpssaoKey();
}
register_activation_hook( __FILE__, 'cacwpssao_activate' );

require_once CACWPSSAO_DIR_PATH . 'authorization.php';

require_once CACWPSSAO_DIR_PATH . 'endpoints.php';
require_once CACWPSSAO_DIR_PATH . 'cron.php';

use League\OAuth2\Server\Exception\OAuthServerException;


function cacwpssao_checkApiAuth( $result, $server, $request ){
    
    $user_checks_out = false;
    
    
    if($user_checks_out) {
        $result = true;
    } else {
        $result = null;
    }

//     return $result;
return false;
            
}
// add_filter('rest_authentication_errors', 'cacwpssao_checkApiAuth');

function cacwpssao_req_auth( $result, $server, $request ) {
     // if ( ! is_user_logged_in() ) {
//         return new WP_Error( 'not-logged-in', 'API Requests are only supported for authenticated requests', array( 'status' => 401 ) );
//     }

	$force_get_auth = get_option( 'cacwpssao_is_force_auth' );
	$op = $request->get_method();
	$route = $request->get_route();
	if( '/cacoauth/v1/token' == $route && ( 'GET' == $op || 'POST' == $op ) ) {
		return null;
	}
	$allow = false;
	switch( $op ) {
	
		case 'HEAD':
			$allow = true;
			break;
		case 'GET':
			if( $force_get_auth ) {
			
				$allow = 'auth';
			
			}
			else {
			
				$allow = true;
			
			}
			break;
		case 'POST':
		case 'DELETE':
			$allow = 'auth';
			break;
		default:
			$allow = false;
	
	}
	
	if( 'auth' === $allow ) {
	
// 		$allow = cacwpssao_checkApiAuth( $result, $server, $request );
// 		add_filter('rest_authentication_errors', function() {
// 			return true;
// 		});
		$resource_server = cacwpssaoResourceServer();
		$auth_request = new CacwpssaoServerRequest( $request );
		if( !$auth_request->hasHeader( 'Authorization' ) ) {
		
			return new WP_Error( 'not-authorized', 'API Requests are only supported for authenticated requests', array( 'status' => 401 ) );
		
		}
		$response =  new CacwpssaoResponse( 200, array(), 'stuff' );
// 		return print_r( $_SERVER, true );
		try {
		
			$auth_result = $resource_server->validateAuthenticatedRequest( $auth_request );
			$perm_error = cacwpssao_validateRequestPermissions( $auth_result );
			if( null !== $perm_error ) {
			
				return $perm_error;
			
			}
			$result = $auth_result;
			$allow = true;
			
		}
		catch( OAuthServerException $exception ) {
		
            $result = $exception->generateHttpResponse($response)->getWpResponse();
            // @codeCoverageIgnoreStart
        
        } 
        catch( \Exception $exception ) {
        
            $result = new OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500);
            $result = $result->generateHttpResponse($response)->getWpResponse();
        
        }
        
		
	}
	
	if( false === $allow ) {
	
		$result = new WP_Error( 'not-logged-in', 'API Requests are only supported for authenticated requests<br/><pre>' . print_r( $request, true ) . '</pre>', array( 'status' => 401 ) );
	
	}
	if( true === $allow ) { return null; };
	
	return $result;

}
add_filter( 'rest_pre_dispatch', 'cacwpssao_req_auth', 10, 3 );



function cacwpssao_validateRequestPermissions( $request ) {

	$client_id = $request->getAttribute( 'oauth_client_id', null );
	if( null === $client_id ) {
	
		return new WP_Error( 'no-client', 'Could not get client id.', array( 'status' => 401 ) );
	
	}
	if( is_array( $client_id ) ) {
	
		$client_id = $client_id[0];
	
	}
	$client_post_id = cacwpssao_getClientByClientId( $client_id );
	if( !$client_post_id ) {
	
		return new WP_Error( 'no-client', 'Client does not exist.', array( 'status' => 401 ) );
	
	}
	$permissions = new CacwpssaoPermissions( $client_post_id );
	//get scope, value and operation from request / uri
	$scopes_att = $request->getAttribute( 'oauth_scopes' );
	if( isset( $scopes_att[0] ) ) {
	
		$scopes = $scopes_att[0];
	
	}
	else {
	
		return new WP_Error( 'cannot-validate', 'Could not validate scopes ' . $scopes_att, array( 'status' => 401 ) );
	
	}
	$uri_obj = $request->getUri();
	$uri_path = $uri_obj->getPath();
	$op = strtoupper( $request->getMethod() );
	$uri_array = explode( '/', $uri_path );
	if( isset( $uri_array[4] ) ) {
	
		$value = $uri_array[4];
	
	}
	else {
	
		return new WP_Error( 'cannot-validate', 'Could not validate path ' . $uri_path, array( 'status' => 401 ) );
	
	}
	$valid_request = $permissions->validatePerm( $scopes, $value, $op );
	if( $valid_request ) {
	
		return null;
	
	}
	return new WP_Error( 'client-not-authorized', 'Client not authorized to ' . $op . ' ' . $value . '.', array( 'status' => 401 ) );

}


//return false if not found, or post id of client
function cacwpssao_getClientByClientId( $client_id ) {

	global $wpdb;
	$results = $wpdb->get_results( "select post_id, meta_key from $wpdb->postmeta where meta_value = '" . $client_id . "'", ARRAY_A );
	if( empty( $results ) ) {
	
		return false;
	
	}
	return $results[0]['post_id'];

}

