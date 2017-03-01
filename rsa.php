<?php

defined( 'ABSPATH' ) or die();

class CacwpssaoKey {

	private $rsa_path;
	private $priv_key;
	private $pub_key;
	
	public function __construct() {
	
		$this->rsa_path = CACWPSSAO_DIR_PATH . 'rsa/';
		if( !( file_exists( $this->rsa_path . 'prkey.rsa' ) ) ) {
			$this->genKeyPair();
		}
		$this->priv_key = $this->rsa_path . 'prkey.rsa';
		$this->pub_key = $this->rsa_path . 'prkey.rsa.pub';
	
	}
	
	protected function paths() {
		
		$paths = array(
			'private'	=> $this->priv_key,
			'public'	=> $this->pub_key
		);
		return $paths;
	
	}
	
	private function genKeyPair() {
		
		if( file_exists( $this->rsa_path . 'prkey.rsa.pub' ) ) {
			unlink( $this->rsa_path . 'prkey.rsa.pub' );
		}
		$key = fopen( $this->rsa_path . 'prkey.rsa', 'w' );
		$pkey = fopen( $this->rsa_path . 'prkey.rsa.pub', 'w' );
		$rsa = new phpseclib\Crypt\RSA();
		$keys = $rsa->createKey();
		fwrite( $key, $keys['privatekey'] );
		fwrite( $pkey, $keys['publickey'] );
		fclose( $key );
		fclose( $pkey );
		
	}
	

}
