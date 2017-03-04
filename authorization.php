<?php

defined( 'ABSPATH' ) or die();

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\AuthorizationServer;

function get_authorization_header() {
	if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
		return wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] );
	}

	if ( function_exists( 'getallheaders' ) ) {
		$headers = getallheaders();

		// Check for the authoization header case-insensitively
		foreach ( $headers as $key => $value ) {
			if ( strtolower( $key ) === 'authorization' ) {
				return $value;
			}
		}
	}

	return null;
}



class CacwpssaoAccessTokenEntity implements AccessTokenEntityInterface {

	use AccessTokenTrait;
	use TokenEntityTrait;
	
	private $storage_key;
	
	public function __construct() {
	
		$key = wp_generate_password( 20, false );
		$this->setIdentifier( $key );
	
	}
	
	
	
    public function getIdentifier() {
    
    	return $this->storage_key;
    
    }



    public function setIdentifier( $identifier ) {
    
    	$this->storage_key = $identifier;
    
    }

}



class CacwpssaoAccessTokenRepository implements AccessTokenRepositoryInterface {

	/**
     * Create a new access token
     *
     * @param ClientEntityInterface  $clientEntity
     * @param ScopeEntityInterface[] $scopes
     * @param mixed                  $userIdentifier
     *
     * @return AccessTokenEntityInterface
     */
    public function getNewToken( ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null ) {
    
    	$accessTokenEntity = new CacwpssaoAccessTokenEntity();
    	$accessTokenEntity->setClient( $clientEntity );
    	//scopes are validated by getIdentifier in the setter, which returns null if invalid (therefore, setting to a null key in array...)
    	foreach( $scopes as $scope ) {
    	
    		if( $clientEntity->validateScope( $scope ) ) {
    		
    			$accessTokenEntity->addScope( $scope );
    		
    		}
    	
    	}
    	$expire = new DateTime();
		$expire->add( new DateInterval( "PT1H" ) );
		$accessTokenEntity->setExpiryDateTime( $expire );
		$client_name = $clientEntity->getName();
		$accessTokenEntity->setUserIdentifier( $client_name );
		return $accessTokenEntity;
    	
    }

    /**
     * Persists a new access token to permanent storage.
     *
     * @param AccessTokenEntityInterface $accessTokenEntity
     */
    public function persistNewAccessToken( AccessTokenEntityInterface $accessTokenEntity ) {
    
    	$key = $accessTokenEntity->getStorageKey();
    	
    	$token = array(
    	
    		'key'       	=> $key,
			'client'		=> $accessTokenEntity->getClient(),
			'expiration' 	=> $accessTokenEntity->getExpiryDateTime(),
			'callback'   	=> null,
			'scopes'		=> $accessTokenEntity->getScopes(),
			'is_revoked'	=> false,
    	
    	);
    	
    	add_option( '_cacwpssao_token' . $key, $token, null, 'no' );
    
    }

    /**
     * Revoke an access token.
     *
     * @param string $tokenId
     */
    public function revokeAccessToken( $tokenId ) {
    
    	$token = maybe_unserialize( get_option( '_cacwpssao_token' . $tokenId ) );
    	$token['is_revoked'] = true;
    	update_option( '_cacwpssao_token' . $tokenId, $token );
    
    }

    /**
     * Check if the access token has been revoked.
     *
     * @param string $tokenId
     *
     * @return bool Return true if this token has been revoked
     */
    public function isAccessTokenRevoked( $tokenId ) {
    
    	$token = maybe_unserialize( get_option( '_cacwpssao_token' . $tokenId ) );
    	return $token['is_revoked'];
    
    }

}



class CacwpssaoScopeEntity implements ScopeEntityInterface {

	private $id;
	private $valid = false;
	
	
	
	public function __construct( $id ) {
	
		$this->id = $id;
		global $cacwpssao_global_scopes;
		if( in_array( $this->id, $cacwpssao_global_scopes, true ) ) {
		
			$this->valid = true;
		
		}
	
	}
	
	
	
	public function getIdentifier() {
	
		if( $this->valid ) {
			return $this->id;
		}
		return null;
	
	}
	
	
	
	public function jsonSerialize() {
	
		$serializable = array(
		
			'id'	=> $this->id,
			'valid'	=> $this->valid,
		
		);
		
		return $serializable;
	
	}

}



class CacwpssaoScopeRepository implements ScopeRepositoryInterface {

	public function getScopeEntityByIdentifier( $identifier ) {
	
		$scopeEntity = new CacwpssaoScopeEntity( $identifier );
		$is_valid = $scopeEntity->getIdentifier( $identifier );
		if( null !== $is_valid && $is_valid ) {
		
			return $scopeEntity;
		
		}
		return false;
	
	}
	
	
	
	public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
    
    	$final_scopes = array();
    	//only allows client_credentials grants
	 	if( 'client_credentials' !== $grantType ) {
	 	
	 		return $final_scopes;
	 	
	 	}
	 	
	 	foreach( $scopes as $scope ) {
	 	
	 		if( null !== $clientEntity->validateScope( $scope->getIdentifier() ) ) {
	 		
	 			array_push( $final_scopes, $scope );
	 		
	 		}
	 	
	 	}
	 	return $final_scopes;
    
    }

}



class CacwpssaoClientEntity implements ClientEntityInterface {

	use ClientTrait;
	
	
	
	private $id;
	private $secret = null;
	private $desc;
	private $post_id;
	private $permissions = null;
	
	
	
	public function __construct( $client_id ) {
	
		$this->id = $client_id;
		$post = $this->getClientPost();
		$this->post_id = $post->ID;
		$this->name = get_post_meta( $this->post_id, 'server_name', true );
		$this->desc = get_post_meta( $this->post_id, 'server_description', true );
		$this->redirectUri = null;
		if( false !== $this->post_id ) {
			$this->secret = get_post_meta( $this->post_id, 'server_client_secret', true );
			$this->permissions = new CacwpssaoPermissions( $this->post_id );
		}
	
	}
	
	
	public function clientExists() {
	
		return $this->post_id ? true : false;
	
	}
	
	
	public function validateSecret( $secret ) {
	
		return $secret === strval( $this->secret );
	
	}
	
	
	
    public function getIdentifier() {
    
    	return $this->id;	
    
    }
    
    
    
    public function validateScope( $scope_id ) {
    
    	return $this->permissions->validateScope( $scope_id );
    
    }
    
    
    
    private function getClientPost() {
    
    	$args = array(
			'post_type'     => 'cacwpssaoserver',
			'meta_query' 	=> array(
				array(
					'key' 	=> 'server_client_id',
					'value'	=> $this->id,
				)
			)
		);

		$getPost = get_posts($args);
		return ( isset( $getPost[0] ) && !empty( $getPost[0] ) ) ? $getPost[0] : false;
    
    }

}



class CacwpssaoClientRepository implements ClientRepositoryInterface {
	 
	 public function getClientEntity( $clientIdentifier, $grantType, $clientSecret = null, $mustValidateSecret = true ) {
	 	
	 	//only allows client_credentials grants
	 	if( 'client_credentials' !== $grantType ) {
	 	
	 		return false;
	 	
	 	}
	 	
	 	$clientEntity = new CacwpssaoClientEntity( $clientIdentifier );
	 	$client_exists = $clientEntity->clientExists();
	 	if( !$client_exists ) {
	 	
	 		return false;
	 	
	 	}
	 	
	 	if( null !== $clientSecret && is_string( $clientSecret ) && $mustValidateSecret ) {
	 	
	 		if( !$clientEntity->validateSecret( $clientSecret ) ) {
	 		
	 			return false;
	 		
	 		}
	 	
	 	}
	 	
	 	return $clientEntity;
	 	
	 }

}


$rsa_key_service = new CacwpssaoKey;
$rsa_keys = $rsa_key_service->paths();

// Init our repositories
$clientRepository = new CacwpssaoClientRepository();
$scopeRepository = new CacwpssaoScopeRepository();
$accessTokenRepository = new CacwpssaoAccessTokenRepository();

// Setup the authorization server
// $oauth_server = new \League\OAuth2\Server\AuthorizationServer(
//     $clientRepository,
//     $accessTokenRepository,
//     $scopeRepository,
//     $rsa_keys['private'],
//     $rsa_keys['public']
// );
// 
// Enable the client credentials grant on the server
// $oauth_server->enableGrantType(
//     new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
//     new \DateInterval('PT1H') // access tokens will expire after 1 hour
// );
