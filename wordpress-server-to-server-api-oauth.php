<?php
/**
 * Plugin Name: WordPress Server to Server API OAuth 2.0
 * Plugin URI: http://www.github.com/bangerkuwranger/WordPress-Server-to-Server-API-OAuth-2.0
 * Description: SKU management platform for Sundial Brands.
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
			echo '<pre>' . print_r( $request, true ) . '</pre>';
	
	}
	
	if( 'auth' === $allow ) {
	
		$allow = cacwpssao_checkApiAuth( $result, $server, $request );
		add_filter('rest_authentication_errors', function() {
			return true;
		});
		
	}
	
	if( false === $allow ) {
	
		$result = new WP_Error( 'not-logged-in', 'API Requests are only supported for authenticated requests<br/><pre>' . print_r( $request, true ) . '</pre>', array( 'status' => 401 ) );
	
	}
	
	return $result;

}
add_filter( 'rest_pre_dispatch', 'cacwpssao_req_auth', 10, 3 );
