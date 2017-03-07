<?php

defined( 'ABSPATH' ) or die();

if( ! wp_next_scheduled( 'cacwpssao_cron' ) ) {

    wp_schedule_event( time(), 'hourly', 'cacwpssao_cron' );

}

add_action( 'cacwpssao_cron', 'cacwpssao_cleanup_tokens' );

function cacwpssao_cleanup_tokens() {

	$now = new DateTime();
	$tokens = maybe_unserialize( get_option( '_cacwpssao_token_expires' ) );
	foreach( $tokens as $key => $expires ) {
	
		$expire_time = new DateTime( $expires );
		$diff = $expire_time->diff( $now )->format( "%H" );
		if( 1 < intval( $diff ) ) {
		
			delete_option( '_cacwpssao_token' . $key );
		
		}
	
	}

}



