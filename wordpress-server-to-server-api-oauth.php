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

require_once CACWPSSAO_DIR_PATH . 'admin.php';

function cacwpssao_activate() {

    $rsakeys = new CacwpssaoKey();
}
register_activation_hook( __FILE__, 'cacwpssao_activate' );

function checkApiAuth( $result ){
    
    $user_checks_out = false;
    
    if($user_checks_out) {
        $result = true;
    } else {
        $result = null;
    }

    return $result;
            
}
// add_filter('rest_authentication_errors', 'checkApiAuth');
