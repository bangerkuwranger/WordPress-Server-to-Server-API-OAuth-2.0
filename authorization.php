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
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Server\RequestEvent;



/**
 * Function borrowed from MIT licensed implementation in
 * guzzle/psr7
 * Copyright (c) 2015 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>
 * https://github.com/guzzle/psr7
 *
 * Copy the contents of a stream into another stream until the given number
 * of bytes have been read.
 *
 * @param StreamInterface $source Stream to read from
 * @param StreamInterface $dest   Stream to write to
 * @param int             $maxLen Maximum number of bytes to read. Pass -1
 *                                to read the entire stream.
 *
 * @throws \RuntimeException on error.
 */
function copy_to_stream(
    StreamInterface $source,
    StreamInterface $dest,
    $maxLen = -1
) {
    $bufferSize = 8192;
    if ($maxLen === -1) {
        while (!$source->eof()) {
            if (!$dest->write($source->read($bufferSize))) {
                break;
            }
        }
    } else {
        $remaining = $maxLen;
        while ($remaining > 0 && !$source->eof()) {
            $buf = $source->read(min($bufferSize, $remaining));
            $len = strlen($buf);
            if (!$len) {
                break;
            }
            $remaining -= $len;
            $dest->write($buf);
        }
    }
}



/**
 * This class borrowed from MIT licensed implementation in
 * guzzle/psr7
 * Copyright (c) 2015 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>
 * https://github.com/guzzle/psr7
 *
 * PSR-7 response implementation.
 */
class CacwpssaoResponse implements ResponseInterface {
    
    /** @var array Map of all registered headers, as original name => array of values */
    private $headers = [];
    /** @var array Map of lowercase header name => original name at registration */
    private $headerNames  = [];
    /** @var string */
    private $protocol = '1.1';
    /** @var StreamInterface */
    private $stream;

    /** @var array Map of standard HTTP status code/reason phrases */
    private static $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];
    /** @var string */
    private $reasonPhrase = '';
    /** @var int */
    private $statusCode = 200;
    
    
    
    public function getProtocolVersion() {
    
        return $this->protocol;
    }
    
    
    
    public function withProtocolVersion( $version ) {
    
        if( $this->protocol === $version ) {
        
            return $this;
       
        }
        $new = clone $this;
        $new->protocol = $version;
        return $new;
    
    }
    
    
    
    public function getHeaders() {
    
        return $this->headers;
    
    }
    
    
    
    public function hasHeader( $header ) {
    
        return isset( $this->headerNames[strtolower( $header )] );
    
    }
    
    
    
    public function getHeader( $header ) {
    
        $header = strtolower( $header );
        
        if( !isset( $this->headerNames[$header] ) ) {
        
            return [];
        
        }
        $header = $this->headerNames[$header];
        return $this->headers[$header];
   
    }
    
    
    
    public function getHeaderLine( $header ) {
    
        return implode( ', ', $this->getHeader( $header ) );
    
    }
    
    
    
    public function withHeader( $header, $value ) {
        if( !is_array( $value ) ) {
        
            $value = [$value];
        
        }
        $value = $this->trimHeaderValues( $value );
        $normalized = strtolower( $header );
        $new = clone $this;
        if( isset( $new->headerNames[$normalized] ) ) {
        
            unset( $new->headers[$new->headerNames[$normalized]] );
        
        }
        $new->headerNames[$normalized] = $header;
        $new->headers[$header] = $value;
        return $new;
    
    }
    
    
    
    public function withAddedHeader( $header, $value ) {
    
        if( !is_array( $value ) ) {
        
            $value = [$value];
        
        }
        $value = $this->trimHeaderValues( $value );
        $normalized = strtolower($header);
        $new = clone $this;
        if( isset( $new->headerNames[$normalized] ) ) {
        
            $header = $this->headerNames[$normalized];
            $new->headers[$header] = array_merge( $this->headers[$header], $value );
        
        }
        else {
        
            $new->headerNames[$normalized] = $header;
            $new->headers[$header] = $value;
        
        }
        return $new;
    }
    
    
    
    public function withoutHeader( $header ) {
    
        $normalized = strtolower( $header );
        if( !isset( $this->headerNames[$normalized] ) ) {
        
            return $this;
        
        }
        $header = $this->headerNames[$normalized];
        $new = clone $this;
        unset( $new->headers[$header], $new->headerNames[$normalized] );
        return $new;
    
    }
    
    
    
    public function getBody() {
    
        if( !$this->stream ) {
    	
            $this->stream = new CacwpssaoStream( '' );
        
        }
        return $this->stream;
    
    }
    
    
    public function withBody( StreamInterface $body ) {
    
        if( $body === $this->stream ) {
        
            return $this;
        
        }
        $new = clone $this;
        $new->stream = $body;
        return $new;
    
    }
    
    
    
    private function setHeaders( array $headers ) {
    
        $this->headerNames = $this->headers = [];
        foreach( $headers as $header => $value ) {
        
            if( !is_array( $value ) ) {
            
                $value = [$value];
            
            }
            $value = $this->trimHeaderValues( $value );
            $normalized = strtolower( $header );
            if( isset( $this->headerNames[$normalized] ) ) {
            
                $header = $this->headerNames[$normalized];
                $this->headers[$header] = array_merge( $this->headers[$header], $value );
            
            }
            else {
            
                $this->headerNames[$normalized] = $header;
                $this->headers[$header] = $value;
            
            }
        
        }
    
    }
    /**
     * Trims whitespace from the header values.
     *
     * Spaces and tabs ought to be excluded by parsers when extracting the field value from a header field.
     *
     * header-field = field-name ":" OWS field-value OWS
     * OWS          = *( SP / HTAB )
     *
     * @param string[] $values Header values
     *
     * @return string[] Trimmed header values
     *
     * @see https://tools.ietf.org/html/rfc7230#section-3.2.4
     */
    private function trimHeaderValues( array $values ) {
    
        return array_map( function( $value ) {
        
            return trim( $value, " \t" );
        
        }, $values);
    
    }
    
    
    
    /**
     * @param int                                  $status  Status code
     * @param array                                $headers Response headers
     * @param string|null|resource|StreamInterface $body    Response body
     * @param string                               $version Protocol version
     * @param string|null                          $reason  Reason phrase (when empty a default will be used based on the status code)
     */
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    ) {
    
        $this->statusCode = (int) $status;
        if( $body !== '' && $body !== null ) {
        
            $this->stream = new CacwpssaoStream( $body );
        
        }
        $this->setHeaders( $headers );
        if( $reason == '' && isset( self::$phrases[$this->statusCode] ) ) {
        
            $this->reasonPhrase = self::$phrases[$this->statusCode];
        
        }
        else {
        
            $this->reasonPhrase = (string) $reason;
        
        }
        $this->protocol = $version;
        $this->body = file_get_contents('php://input');
        if( is_scalar( $this->body ) ) {
		
       		$stream = fopen( 'php://temp', 'r+' );
			if( $this->body !== '' ) {
				fwrite( $stream, $this->body );
				fseek( $stream, 0 );
			}
			$this->stream =  new CacwpssaoStream( $stream );
		
		}
		else{ 
		
			switch( gettype( $this->body ) ) {
		
				case 'resource':
					$this->stream = new CacwpssaoStream( $this->body );
					break;
				
				case 'object':
					if( $this->body instanceof CacwpssaoStream ) {
				
						$this->stream =  $this->body;
				
					}
					break;
			}
		
		}
    }
    
    
    
    public function getStatusCode() {
    
        return $this->statusCode;
    
    }
    
    
    
    public function getReasonPhrase() {
    
        return $this->reasonPhrase;
    
    }
    
    
    
    public function withStatus( $code, $reasonPhrase = '' ) {
    
        $new = clone $this;
        $new->statusCode = (int) $code;
        if( $reasonPhrase == '' && isset( self::$phrases[$new->statusCode] ) ) {
        
            $reasonPhrase = self::$phrases[$new->statusCode];
        
        }
        $new->reasonPhrase = $reasonPhrase;
        return $new;
    
    }

}



/**
* Methods for this class borrowed from MIT licensed implementation in
* guzzle/psr7
* Copyright (c) 2015 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>
* https://github.com/guzzle/psr7
**/
class CacwpssaoStream implements StreamInterface {

	private $stream;
    private $size;
    private $seekable;
    private $readable;
    private $writable;
    private $uri;
    private $customMetadata;
    
    
    
    /** @var array Hash of readable and writable stream types */
    private static $readWriteHash = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true
        ]
    ];
    
    
    
    public function __construct( $stream, $options = [] ) {
    
        if( !is_resource( $stream ) ) {
        
            throw new \InvalidArgumentException( 'Stream must be a resource' );
        
        }
        if( isset( $options['size'] ) ) {
        
            $this->size = $options['size'];
        
        }
        $this->customMetadata = isset( $options['metadata'] ) ? $options['metadata'] : [];
        $this->stream = $stream;
        $meta = stream_get_meta_data( $this->stream );
        $this->seekable = $meta['seekable'];
        $this->readable = isset( self::$readWriteHash['read'][$meta['mode']] );
        $this->writable = isset (self::$readWriteHash['write'][$meta['mode']] );
        $this->uri = $this->getMetadata( 'uri' );
    
    }
    
    
    
    public function __get( $name ) {
    
        if( $name == 'stream' ) {
        
            throw new \RuntimeException('The stream is detached');
        
        }
        throw new \BadMethodCallException( 'No value for ' . $name );
    
    }
    
    
    
    /**
     * Closes the stream when the destructed
     */
    public function __destruct() {
     
        $this->close();
    
    }
	
	/**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString() {
    
    	try {
    	
            $this->seek( 0 );
            return (string) stream_get_contents( $this->stream );
        
        }
        catch ( \Exception $e ) {
        
            return '';
        
        }
    
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close() {
    
		if( isset( $this->stream ) ) {
		
            if( is_resource( $this->stream ) ) {
            
                fclose( $this->stream );
            
            }
            $this->detach();
       
        }
    
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach() {
    
    	if( !isset( $this->stream ) ) {
    	
            return null;
        
        }
        $result = $this->stream;
        unset( $this->stream );
        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;
        return $result;
    
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize() {
    
    	if( $this->size !== null ) {
            return $this->size;
        }
        if (!isset($this->stream)) {
            return null;
        }
        // Clear the stat cache if the stream has a URI
        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }
        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }
        return null;
    
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell() {
    
    	$result = ftell( $this->stream );
        if( $result === false ) {
            throw new \RuntimeException( 'Unable to determine stream position' );
        }
        return $result;
    
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof() {
    
    	return !$this->stream || feof( $this->stream );
    
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable() {
    
    	return $this->seekable;
    
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek( $offset, $whence = SEEK_SET ) {
    
    	if( !$this->seekable ) {
    	
            throw new \RuntimeException( 'Stream is not seekable' );
        
        }
        elseif( fseek( $this->stream, $offset, $whence ) === -1 ) {
        
            throw new \RuntimeException( 'Unable to seek to stream position '  . $offset . ' with whence ' . var_export( $whence, true ) );
        
        }
    
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind() {
    
    	$this->seek(0);
    
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable() {
    
    	return $this->writable;
    	
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write( $string ) {
    
    	if( !$this->writable ) {
    
            throw new \RuntimeException( 'Cannot write to a non-writable stream' );
    
        }
        // We can't know the size after writing anything
        $this->size = null;
        $result = fwrite( $this->stream, $string );
        if( $result === false ) {
        
            throw new \RuntimeException( 'Unable to write to stream' );
        
        }
        return $result;
    
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable() {
    
    	return $this->readable;
    
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read( $length ) {
    
    	if( !$this->readable ) {
    	
            throw new \RuntimeException( 'Cannot read from non-readable stream' );
        
        }
        if( $length < 0 ) {
        
            throw new \RuntimeException( 'Length parameter cannot be negative' );
        
        }
        if( 0 === $length ) {
        
            return '';
        
        }
        $string = fread( $this->stream, $length );
        if( false === $string ) {
        
            throw new \RuntimeException('Unable to read from stream');
        
        }
        return $string;
    
    }



    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents() {
    
    	$contents = stream_get_contents( $this->stream );
        if( $contents === false ) {
        
            throw new \RuntimeException( 'Unable to read stream contents' );
        
        }
        return $contents;
    
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata( $key = null ) {
    
    	if( !isset( $this->stream ) ) {
    	
            return $key ? null : [];
        
        }
        elseif( !$key ) {
        
            return $this->customMetadata + stream_get_meta_data( $this->stream );
        
        }
        elseif( isset( $this->customMetadata[$key] ) ) {
        
            return $this->customMetadata[$key];
        
        }
        $meta = stream_get_meta_data( $this->stream );
        return isset( $meta[$key] ) ? $meta[$key] : null;
    
    }

}



class CacwpssaoUri implements UriInterface {

	private $scheme = '';
	private $authority = '';
	private $user_info = '';
	private $host = '';
	private $port = null;
	private $path = '';
	private $query = '';
	private $fragment = '';
	
	
	
	public function __construct( $with = null ) {
	
		if( isset( $_SERVER['REQUEST_SCHEME'] ) ) {
		
			$this->scheme = strtolower( $_SERVER['REQUEST_SCHEME'] );
		
		}
		
		if( isset( $_SERVER['SERVER_PORT'] ) ) {
		
			$port = intval( $_SERVER['SERVER_PORT'] );
			
			if( '' !== $this->scheme ) {
			
				if( 'http' === $this->scheme && (int)80 === $port ) {
				
					$port = null;
				
				}
				
				if( 'https' === $this->scheme && (int)443 === $port ) {
				
					$port = null;
				
				}
			
			}
			$this->port = $port;
		
		}
		
		if( isset( $_SERVER['HTTP_HOST'] ) ) {
		
			$this->host = strtolower( $_SERVER['HTTP_HOST'] );
		
		}
		
		if( isset( $_SERVER['PHP_AUTH_USER'] ) ) {
		
			$user = $_SERVER['PHP_AUTH_USER'];
			if( isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			
				$user .= ':' . $_SERVER['PHP_AUTH_PW'];
			
			}
			$this->user_info = $user;
			$this->authority = $user . '@' . $this->host;
			if( null !== $this->port ) {
			
				$this->authority .= ':' . $this->port;
			
			}
		
		}
		
		if( isset ( $_SERVER['REQUEST_URI'] ) ) {
		
			$uri = $_SERVER['REQUEST_URI'];
			if( strpos( $_SERVER['REQUEST_URI'], '?' ) !== false ) {
			
				$uri_path = explode( '?', $uri );
				$this->path = $uri_path[0];
				$this->query = $uri_path[1];
			
			}
			else {
			
				$this->path = $uri;
			
			}
		
		}
		
		if( null !== $with && is_array( $with ) & isset( $with['what'] ) && isset( $with['value'] ) ) {
		
			if( empty( $with['value'] ) ) {
			
				$with['value'] = '';
			
			}
			
			switch( $with['what'] ) {
			
				case 'scheme':
					$this->scheme = $with['value'];
					break;
					
				case 'user_info':
					$this->user_info = $with['value'];
					$this->authority = $user . '@' . $this->host;
					if( null !== $this->port ) {
		
						$this->authority .= ':' . $this->port;
		
					}
					break;
				case 'host':
					$this->host = $with['value'];
					break;
				case 'port':
					$this->port = ( '' === $with['value'] ) ? null : intval( $with['value'] );
					break;
				case 'path':
					$this->path = $with['value'];
					break;
				case 'query':
					$this->query = $with['value'];
					break;
				case 'fragment':
					$this->fragment = $with['value'];
					break;
			
			}
		
		}
	
	}
	
	
	
	/**
     * Retrieve the scheme component of the URI.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.1.
     *
     * The trailing ":" character is not part of the scheme and MUST NOT be
     * added.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string The URI scheme.
     */
    public function getScheme() {
    
    	return $this->scheme;
    
    }

    /**
     * Retrieve the authority component of the URI.
     *
     * If no authority information is present, this method MUST return an empty
     * string.
     *
     * The authority syntax of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority() {
    
    	return $this->authority;
    
    }

    /**
     * Retrieve the user information component of the URI.
     *
     * If no user information is present, this method MUST return an empty
     * string.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * The trailing "@" character is not part of the user information and MUST
     * NOT be added.
     *
     * @return string The URI user information, in "username[:password]" format.
     */
    public function getUserInfo() {
    
    	return $this->user_info;
    
    }

    /**
     * Retrieve the host component of the URI.
     *
     * If no host is present, this method MUST return an empty string.
     *
     * The value returned MUST be normalized to lowercase, per RFC 3986
     * Section 3.2.2.
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost() {
    
    	return $this->host;
    
    }

    /**
     * Retrieve the port component of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The URI port.
     */
    public function getPort() {
    
    	return $this->port;
    
    }

    /**
     * Retrieve the path component of the URI.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * Normally, the empty path "" and absolute path "/" are considered equal as
     * defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
     * do this normalization because in contexts with a trimmed base path, e.g.
     * the front controller, this difference becomes significant. It's the task
     * of the user to handle both "" and "/".
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.3.
     *
     * As an example, if the value should include a slash ("/") not intended as
     * delimiter between path segments, that value MUST be passed in encoded
     * form (e.g., "%2F") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     * @return string The URI path.
     */
    public function getPath() {
    
    	return $this->path;
    
    }

    /**
     * Retrieve the query string of the URI.
     *
     * If no query string is present, this method MUST return an empty string.
     *
     * The leading "?" character is not part of the query and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.4.
     *
     * As an example, if a value in a key/value pair of the query string should
     * include an ampersand ("&") not intended as a delimiter between values,
     * that value MUST be passed in encoded form (e.g., "%26") to the instance.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     * @return string The URI query string.
     */
    public function getQuery() {
    
    	return $this->query;
    
    }

    /**
     * Retrieve the fragment component of the URI.
     *
     * If no fragment is present, this method MUST return an empty string.
     *
     * The leading "#" character is not part of the fragment and MUST NOT be
     * added.
     *
     * The value returned MUST be percent-encoded, but MUST NOT double-encode
     * any characters. To determine what characters to encode, please refer to
     * RFC 3986, Sections 2 and 3.5.
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     * @return string The URI fragment.
     */
    public function getFragment() {
    
    	return $this->fragment;
    
    }

    /**
     * Return an instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified scheme.
     *
     * Implementations MUST support the schemes "http" and "https" case
     * insensitively, and MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme( $scheme ) {
    
    	$with = array(
    		'what'	=> 'scheme',
    		'value'	=> strtolower( $scheme ),
    	);
    	$newUri = new CacwpssaoUri( $with );
    	return $newUri;
    
    }



    /**
     * Return an instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo( $user, $password = null )  {
    
    	$with = array(
    		'what'	=> 'user_info',
    		'value'	=> $user,
    	);
    	if( null !== $password ) {
    	
    		$with['value'] .= ':' . $password;
    	
    	}
    	$newUri = new CacwpssaoUri( $with );
    	return $newUri;
    
    }



    /**
     * Return an instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host The hostname to use with the new instance.
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost( $host ) {
    
    	$with = array(
    		'what'	=> 'host',
    		'value'	=> strtolower( $host ),
    	);
    	$newUri = new CacwpssaoUri( $with );
    	return $newUri;
    
    }
    
    
    
    /**
     * Return an instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort( $port )  {
    
    	$with = array(
    		'what'	=> 'port',
    		'value'	=> $port,
    	);
    	if( null !== $port && ( 0 > intval( $port ) || 65536 < intval( $port ) ) ) {
    		throw new \InvalidArgumentException( 'Port is outside of established TCP/UDP port range.');
    	}
    	$newUri = new CacwpssaoUri( $with );
    	return $newUri;
    
    }

    /**
     * Return an instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified path.
     *
     * The path can either be empty or absolute (starting with a slash) or
     * rootless (not starting with a slash). Implementations MUST support all
     * three syntaxes.
     *
     * If the path is intended to be domain-relative rather than path relative then
     * it must begin with a slash ("/"). Paths not starting with a slash ("/")
     * are assumed to be relative to some base path known to the application or
     * consumer.
     *
     * Users can provide both encoded and decoded path characters.
     * Implementations ensure the correct encoding as outlined in getPath().
     *
     * @param string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath( $path ) {
    
    	$with = array(
    		'what'	=> 'path',
    		'value'	=> strtolower( $path ),
    	);
    	$newUri = new CacwpssaoUri( $with );
    	return $newUri;
    
    }
    /**
     * Return an instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified query string.
     *
     * Users can provide both encoded and decoded query characters.
     * Implementations ensure the correct encoding as outlined in getQuery().
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery( $query ) {
    
    	$with = array(
    		'what'	=> 'query',
    		'value'	=> $query,
    	);
    	$newUri = new CacwpssaoUri( $with );
    	return $newUri;
    
    }

    /**
     * Return an instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the specified URI fragment.
     *
     * Users can provide both encoded and decoded fragment characters.
     * Implementations ensure the correct encoding as outlined in getFragment().
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment( $fragment ) {
    
    	$with = array(
    		'what'	=> 'fragment',
    		'value'	=> $fragment,
    	);
    	$newUri = new CacwpssaoUri( $with );
    	return $newUri;
    
    }

    /**
     * Return the string representation as a URI reference.
     *
     * Depending on which components of the URI are present, the resulting
     * string is either a full URI or relative reference according to RFC 3986,
     * Section 4.1. The method concatenates the various components of the URI,
     * using the appropriate delimiters:
     *
     * - If a scheme is present, it MUST be suffixed by ":".
     * - If an authority is present, it MUST be prefixed by "//".
     * - The path can be concatenated without delimiters. But there are two
     *   cases where the path has to be adjusted to make the URI reference
     *   valid as PHP does not allow to throw an exception in __toString():
     *     - If the path is rootless and an authority is present, the path MUST
     *       be prefixed by "/".
     *     - If the path is starting with more than one "/" and no authority is
     *       present, the starting slashes MUST be reduced to one.
     * - If a query is present, it MUST be prefixed by "?".
     * - If a fragment is present, it MUST be prefixed by "#".
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     * @return string
     */
    public function __toString() {
    
    	$string = '';
    	$pathHasRootCheck = strpos( $this->path, '/' );
    	$pathHasRoot = ( false !== $pathHasRootCheck && 0 === $pathHasRootCheck );
    	//warning: replaces multiple slashes with a single slash EVERYWHERE in the path!
    	$clean_path = preg_replace( '#/+#','/', $this->path );
    	
    	if( '' !== $this->scheme ) {
    	
    		$string .= $this->scheme . '://';
    		if( '' !== $this->authority ) {
    		
    			$string .= $this->authority;
    			if( !$pathHasRoot ) {
    				$string .= '/';
    			}
    		
    		}
    		else {
    		
    			$string .= $this->host;
				if( null !== $this->port ) {
			
					$string .= ':' . $this->port;
			
				}
    		
    		}
    	
    	}
    	elseif( '' !== $this->authority ) {
		
			$string .= $this->authority;
			if( !$pathHasRoot ) {
    				$string .= '/';
    			}
		
		}
		else {
		
			$string .= $this->host;
			if( null !== $this->port ) {
			
				$string .= ':' . $this->port;
			
			}
			if( !$pathHasRoot ) {
				$string .= '/';
			}
		
		}
		
		$string .= $clean_path;
		if( '' !== $this->query ) {
		
			$string .= '?' . $this->query;
		
		}
		
		if( '' !== $this->fragment ) {
		
			$string .= '#' . $this->fragment;
		
		}
    
    }

}


/**
* Methods for this class borrowed from MIT licensed implementation in
* guzzle/psr7
* Copyright (c) 2015 Michael Dowling, https://github.com/mtdowling <mtdowling@gmail.com>
* https://github.com/guzzle/psr7
**/
class CacwpssaoUploadedFile implements UploadedFileInterface {
    /**
     * @var int[]
     */
    private static $errors = [
        UPLOAD_ERR_OK,
        UPLOAD_ERR_INI_SIZE,
        UPLOAD_ERR_FORM_SIZE,
        UPLOAD_ERR_PARTIAL,
        UPLOAD_ERR_NO_FILE,
        UPLOAD_ERR_NO_TMP_DIR,
        UPLOAD_ERR_CANT_WRITE,
        UPLOAD_ERR_EXTENSION,
    ];
    /**
     * @var string
     */
    private $clientFilename;
    /**
     * @var string
     */
    private $clientMediaType;
    /**
     * @var int
     */
    private $error;
    /**
     * @var null|string
     */
    private $file;
    /**
     * @var bool
     */
    private $moved = false;
    /**
     * @var int
     */
    private $size;
    /**
     * @var StreamInterface|null
     */
    private $stream;
    /**
     * @param StreamInterface|string|resource $streamOrFile
     * @param int $size
     * @param int $errorStatus
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     */
    public function __construct(
        $streamOrFile,
        $size,
        $errorStatus,
        $clientFilename = null,
        $clientMediaType = null
    ) {
        $this->setError( $errorStatus );
        $this->setSize( $size );
        $this->setClientFilename( $clientFilename );
        $this->setClientMediaType( $clientMediaType );
        if( $this->isOk() ) {
            $this->setStreamOrFile( $streamOrFile );
        }
    }
    
    
    
    /**
     * Depending on the value set file or stream variable
     *
     * @param mixed $streamOrFile
     * @throws InvalidArgumentException
     */
    private function setStreamOrFile( $streamOrFile ) {
    
        if( is_string( $streamOrFile ) ) {
        
            $this->file = $streamOrFile;
        
        }
        elseif( is_resource( $streamOrFile ) ) {
        
            $this->stream = new CacwpssaoStream($streamOrFile);
        
        }
        elseif( $streamOrFile instanceof StreamInterface ) {
        
            $this->stream = $streamOrFile;
        
        }
        else {
        
            throw new InvalidArgumentException( 'Invalid stream or file provided for UploadedFile' );
        
        }
    
    }
    
    
    
    /**
     * @param int $error
     * @throws InvalidArgumentException
     */
    private function setError( $error ) {
    
        if( false === is_int( $error ) ) {
        
            throw new InvalidArgumentException( 'Upload file error status must be an integer' );
        
        }
        if( false === in_array( $error, UploadedFile::$errors ) ) {
        
            throw new InvalidArgumentException( 'Invalid error status for UploadedFile' );
       
        }
        $this->error = $error;
    
    }
    
    
    
    /**
     * @param int $size
     * @throws InvalidArgumentException
     */
    private function setSize( $size ) {
    
        if( false === is_int( $size ) ) {
        
            throw new InvalidArgumentException( 'Upload file size must be an integer' );
        
        }
        $this->size = $size;
    
    }
    
    
    
    /**
     * @param mixed $param
     * @return boolean
     */
    private function isStringOrNull( $param ) {
    
        return in_array( gettype( $param ), ['string', 'NULL'] );
    
    }
    
    
    
    /**
     * @param mixed $param
     * @return boolean
     */
    private function isStringNotEmpty( $param ) {
    
        return is_string( $param ) && false === empty( $param );
    
    }
    
    
    
    /**
     * @param string|null $clientFilename
     * @throws InvalidArgumentException
     */
    private function setClientFilename( $clientFilename ) {
        if( false === $this->isStringOrNull( $clientFilename ) ) {
        
            throw new InvalidArgumentException( 'Upload file client filename must be a string or null' );
        
        }
        $this->clientFilename = $clientFilename;
    
    }
    
    
    
    /**
     * @param string|null $clientMediaType
     * @throws InvalidArgumentException
     */
    private function setClientMediaType( $clientMediaType ) {
    
        if( false === $this->isStringOrNull( $clientMediaType ) ) {
        
            throw new InvalidArgumentException( 'Upload file client media type must be a string or null' );
        
        }
        $this->clientMediaType = $clientMediaType;
    
    }
    
    
    
    /**
     * Return true if there is no upload error
     *
     * @return boolean
     */
    private function isOk() {
    
        return $this->error === UPLOAD_ERR_OK;
    
    }
    
    
    
    /**
     * @return boolean
     */
    public function isMoved() {
    
        return $this->moved;
    
    }
    
    
    
    /**
     * @throws RuntimeException if is moved or not ok
     */
    private function validateActive() {
    
        if( false === $this->isOk() ) {
        
            throw new RuntimeException( 'Cannot retrieve stream due to upload error' );
        
        }
        if( $this->isMoved() ) {
        
            throw new RuntimeException( 'Cannot retrieve stream after it has already been moved' );
       
        }
    
    }
    
    
    
    /**
     * {@inheritdoc}
     * @throws RuntimeException if the upload was not successful.
     */
    public function getStream() {
    
        $this->validateActive();
        if( $this->stream instanceof StreamInterface ) {
        
            return $this->stream;
        
        }
        return new CacwpssaoStream( $this->file, 'r+' );
    
    }
    
    
    
    /**
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws RuntimeException if the upload was not successful.
     * @throws InvalidArgumentException if the $path specified is invalid.
     * @throws RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo( $targetPath ) {
    
        $this->validateActive();
        if( false === $this->isStringNotEmpty( $targetPath ) ) {
        
            throw new InvalidArgumentException( 'Invalid path provided for move operation; must be a non-empty string' );
        
        }
        if( $this->file ) {
        
            $this->moved = php_sapi_name() == 'cli' ? rename( $this->file, $targetPath ) : move_uploaded_file( $this->file, $targetPath );
        
        }
        else {
        
            copy_to_stream( $this->getStream(), new CacwpssaoStream($targetPath, 'w') );
            $this->moved = true;
        
        }
        if( false === $this->moved ) {
        
            throw new RuntimeException( sprintf( 'Uploaded file could not be moved to %s', $targetPath ) );
        
        }
    
    }
    
    
    
    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize() {
    
        return $this->size;
    
    }
    
    
    
    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError() {
    
        return $this->error;
    
    }
    
    
    
    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename() {
    
        return $this->clientFilename;
    
    }
    
    
    
    /**
     * {@inheritdoc}
     */
    public function getClientMediaType() {
    
        return $this->clientMediaType;
    
    }

}


class CacwpssaoServerRequest implements ServerRequestInterface {

	private $protocol_version = '1.1';
	private $headers;
	private $body;
	private $stream;
	private $request_target;
	private $method;
	private $uri;
	private $server_params;
	private $cookie_params;
	private $query_params;
	private $uploaded_files;
	private $parsed_body;
	private $attributes = null;
	
	
	
	public function __construct( WP_REST_Request $wp_request = null, $with = null ) {
	
		//set up RequestInterface vars	
	
		if( isset( $_SERVER['SERVER_PROTOCOL'] ) ) {
		
			$prot = explode( '/', $_SERVER['SERVER_PROTOCOL'] );
			$this->protocol_version = $prot[1];
	
		}
		
		
		if( isset( $_SERVER['REQUEST_URI'] ) ) {
		
			$this->request_target = $_SERVER['REQUEST_URI'];
	
		}
		else {
		
			$this->request_target = '/';
		
		}
		
		$this->uri = new CacwpssaoUri();
		
		
		if( null !== $wp_request && is_object( $wp_request ) && ( $wp_request instanceof WP_REST_Request ) ) {
		
			$this->headers = $wp_request->get_headers();
			$this->body = $wp_request->get_body();
			$this->method = $wp_request->get_method();
			$this->query_params = $wp_request->get_query_params();
			$this->uploaded_files = $wp_request->get_file_params();
			$this->parsed_body = $wp_request->get_body_params();
		
		}
		else {
		
			if ( function_exists( 'getallheaders' ) ) {
			
				$all_headers = getallheaders();
				foreach( $all_headers as $header => $value ) {
				
					$this->headers[$header] = array( $value );
				
				}
			
			}
			
			$this->body = file_get_contents('php://input');
			
			if( isset( $_SERVER['REQUEST_METHOD'] ) ) {
		
				$this->method = $_SERVER['REQUEST_METHOD'];
	
			}
			if( isset( $_SERVER['QUERY_STRING'] ) ) {
			
				parse_str( $_SERVER['QUERY_STRING'], $this->$query_params );
			
			}
			if( isset( $_FILES ) ) {
		
				$this->uploaded_files = $_FILES;
	
			}
			if( isset( $_SERVER['CONTENT_TYPE'] ) && $this->method === 'POST' &&  ( 'application/x-www-form-urlencoded' === $_SERVER['CONTENT_TYPE'] || 'multipart/form-data' === $_SERVER['CONTENT_TYPE'] ) ) {
			
				parse_str( $this->body, $this->$parsed_body );
			
			}
		
		}
		
		if( is_scalar( $this->body ) ) {
		
       		$stream = fopen( 'php://temp', 'r+' );
			if( $this->body !== '' ) {
				fwrite( $stream, $this->body );
				fseek( $stream, 0 );
			}
			$this->stream =  new CacwpssaoStream( $stream );
		
		}
		else{ 
		
			switch( gettype( $this->body ) ) {
		
				case 'resource':
					$this->stream = new CacwpssaoStream( $this->body );
					break;
				
				case 'object':
					if( $this->body instanceof CacwpssaoStream ) {
				
						$this->stream =  $this->body;
				
					}
					break;
			}
		
		}
		
		
		//set up addtl ServerRequestInterface vars
		$this->server_params = $_SERVER;
		$this->cookie_params = $_COOKIE;
		
		
		//process data for 'with' methods that spawn new instances
		if( null !== $with && is_array( $with ) & isset( $with['what'] ) && isset( $with['value'] ) ) {
		
			switch( $with['what'] ) {
			
				case('protocol_version'):
					if( is_numeric( $with['value'] ) ) {
					
						$this->protocol_version = $with['value'];
					
					}
					break;
				case('header'):
					if( is_array( $with['value'] ) && isset( $with['value']['name'] ) && isset( $with['value']['value'] ) ) {
					
						$headerName = $with['value']['name'];
						$headerValue = $with['value']['value'];
						if( $this->hasHeader( $headerName ) ) {
						
							$this->headers[$headerName] = array( $headerValue );
						
						}
					
					}
					break;
				case('added_header'):
					if( is_array( $with['value'] ) && isset( $with['value']['name'] ) && isset( $with['value']['value'] ) ) {
					
						$headerName = $with['value']['name'];
						$headerValue = $with['value']['value'];
						if( $this->hasHeader( $headerName ) ) {
						
							array_push( $this->headers[$headerName], $headerValue );
						
						}
						else {
						
							$this->headers[$headerName] = array( $headerValue );
						
						}
					
					}
					break;
				case('removed_header'):
					if( $this->hasHeader( $headerName ) ) {
					
						unset( $this->headers[$headerName] );
					
					}
					
					break;
				case('body'):
					$body = $with['value'];
					if( is_scalar( $body ) ) {
		
						$stream = fopen( 'php://temp', 'r+' );
						if( $body !== '' ) {
							fwrite( $stream, $body );
							fseek( $stream, 0 );
						}
						$this->stream =  new CacwpssaoStream( $stream );
		
					}
					else{ 
		
						switch( gettype( $body ) ) {
		
							case 'resource':
								$this->stream = new CacwpssaoStream( $body );
								break;
				
							case 'object':
								if( $body instanceof CacwpssaoStream ) {
				
									$this->stream =  $body;
				
								}
								break;
						}
		
					}
					break;
				case('request_target'):
					$this->request_target = $with['value'];
					break;
				case('method'):
					$this->method = $with['value'];
					break;
				case('uri'):
					if( is_array( $with['value'] ) && isset( $with['value']['uri'] ) && isset( $with['value']['preserve_host'] ) ) {
					
						$uri = $with['value']['uri'];
						$preserve_host = $with['value']['preserve_host'];
						$orig_host = $this->get_header( 'host' );
						$new_host = $uri->getHost();
						if( $preserve_host ) {
						
							if( empty( $orig_host ) && !empty( $new_host ) ) {
							
								$this->headers['host'] = array( $new_host );
							
							}
						
						}
						else {
						
							$this->headers['host'] = ( empty( $new_host ) ) ? array( $orig_host ) : array( $new_host );
							
						
						}
						$this->uri = $uri;
					
					}
					break;
				case('cookies'):
					$this->cookie_params = $with['value'];
					break;
				case('query_params'):
					$this->query_params = $with['value'];
					break;
				case('uploaded_files'):
					$this->uploaded_files = $with['value'];
					break;
				case('parsed_body'):
					$this->parsed_body = $with['value'];
					break;
				case('attribute'):
					if( is_array( $with['value'] ) && isset( $with['value']['name'] ) && isset( $with['value']['value'] ) ) {
					
						$attributeName = $with['value']['name'];
						$attributeValue = $with['value']['value'];
						$this->attributes[$attributeName] = array( $attributeValue );
					
					}
					break;
				case('remove_attribute'):
					unset( $this->attribute['value'] );
					break;
			
			}
		
		}
		
	}
	
	
	
	/**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion() {
    
    	return $this->protocol_version;
    
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion( $version ) {
    
    	$with = array(
    		'what'	=> 'protocol_version',
    		'value'	=> $version,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders() {
    
    	return $this->headers;
    
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader( $name ) {
    
    	$headerKeys = array_keys( $this->headers );
    	$ciHeaders = array();
		foreach( $headerKeys as $key ) {
		
			 $ciHeaders[strtolower( $key )] = $key;
		
		}
		$ciName = strtolower( $name );
		return array_key_exists( $ciName, $ciHeaders );
    
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader( $name ) {
    
    	$headerValue = array();
    	$ciHeaders = array();
    	$ciName = strtolower( $name );
    	foreach( $this->headers as $header => $values ) {
    	
    		$ciHeaders[strtolower( $header )] = $values;
    	
    	}
    	if( isset( $ciHeaders[$ciName] ) ) {
    	
    		$headerValue = $ciHeaders[$ciName];
    	
    	}
    	return $headerValue;
    
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine( $name ) {
    
    	$values = $this->getHeader( $name );
    	$headerLine = implode( ",", $values );
    	return $headerLine;
    
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader( $name, $value ) {
    
    	$with = array(
    		'what'	=> 'header',
    		'value'	=> array(
    			'name'	=> $name,
    			'value'	=> $value,
    		),
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader( $name, $value ) {
    
    	$with = array(
    		'what'	=> 'added_header',
    		'value'	=> array(
    			'name'	=> $name,
    			'value'	=> $value,
    		),
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader( $name ) {
    
    	$with = array(
    		'what'	=> 'removed_header',
    		'value'	=> $name,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody() {
    
    	if( !$this->stream ) {
    	
            $this->stream = new CacwpssaoStream( '' );
        
        }
        return $this->stream;
    
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody( StreamInterface $body ) {
    
    	$with = array(
    		'what'	=> 'body',
    		'value'	=> $body,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }
    
    
     /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget() {
    
    	return $this->request_target;
    
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget( $requestTarget ) {
    
    	$with = array(
    		'what'	=> 'request_target',
    		'value'	=> $requestTarget,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod() {
    
    	return $this->method;
    
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod( $method ) {
    
    	$with = array(
    		'what'	=> 'method',
    		'value'	=> $method,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri() {
    
    	return $this->uri;
    
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri( UriInterface $uri, $preserveHost = false ) {
    
    	$with = array(
    		'what'	=> 'uri',
    		'value'	=> array(
    			'uri'			=> $uri,
    			'preserve_host'	=> $preserveHost,
    		),
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }
    
    
    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams() {
    
    	return $this->server_params;
    
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams() {
    
    	return $this->cookie_params;
    
    }

    /**
     * Return an instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST NOT update the related Cookie header of the request
     * instance, nor related values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams( array $cookies ) {
    
    	$with = array(
    		'what'	=> 'cookies',
    		'value'	=> $cookies,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }


    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URI or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the query string from `getUri()->getQuery()`
     * or from the `QUERY_STRING` server param.
     *
     * @return array
     */
    public function getQueryParams() {
    
    	return $this->query_params;
    
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URI stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams( array $query ) {
    
    	$with = array(
    		'what'	=> 'query_params',
    		'value'	=> $query,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Retrieve normalized file upload data.
     *
     * This method returns upload metadata in a normalized tree, with each leaf
     * an instance of Psr\Http\Message\UploadedFileInterface.
     *
     * These values MAY be prepared from $_FILES or the message body during
     * instantiation, or MAY be injected via withUploadedFiles().
     *
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles() {
    
    
    	if( empty( $this->uploaded_files ) ) {
    	
    		return array();
    	
    	}
    	$normalized = [];
        foreach( $this->uploaded_files as $key => $value ) {
        
            if( $value instanceof CacwpssaoUploadedFile ) {
            
                $normalized[$key] = $value;
            
            }
            elseif( is_array( $value ) && isset( $value['tmp_name'] ) ) {
            
                if( is_array ($value['tmp_name'] ) ) {
                
					$normalizedFiles = [];
					foreach( array_keys( $value['tmp_name'] ) as $key ) {
				
						$spec = [
							'tmp_name' => $value['tmp_name'][$key],
							'size'     => $value['size'][$key],
							'error'    => $value['error'][$key],
							'name'     => $value['name'][$key],
							'type'     => $value['type'][$key],
						];
						$normalizedFiles[$key] = new CacwpssaoUploadedFile( $spec['tmp_name'], (int) $spec['size'], (int) $spec['error'], $spec['name'], $spec['type'] );
				
					}
					$normalized[$key] = $normalizedFiles;
				
				}
				else {
				
					$normalized[$key] = new CacwpssaoUploadedFile( $value['tmp_name'], (int) $value['size'], (int) $value['error'], $value['name'], $value['type'] );
				}
            
            }
            else {
            
                throw new InvalidArgumentException( 'Invalid value in files specification' );
            
            }
        
        }
        $this->uploaded_files = $normalized;
    	
    	return $this->uploaded_files;
    
    }



    /**
     * Create a new instance with the specified uploaded files.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param array $uploadedFiles An array tree of UploadedFileInterface instances.
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles( array $uploadedFiles ) {
    
    	$with = array(
    		'what'	=> 'uploaded_files',
    		'value'	=> $uploadedFiles,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, this method MUST
     * return the contents of $_POST.
     *
     * Otherwise, this method may return any results of deserializing
     * the request body content; as parsing returns structured content, the
     * potential types MUST be arrays or objects only. A null value indicates
     * the absence of body content.
     *
     * @return null|array|object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody() {
    
    	return $this->parsed_body;
    
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * These MAY be injected during instantiation.
     *
     * If the request Content-Type is either application/x-www-form-urlencoded
     * or multipart/form-data, and the request method is POST, use this method
     * ONLY to inject the contents of $_POST.
     *
     * The data IS NOT REQUIRED to come from $_POST, but MUST be the results of
     * deserializing the request body content. Deserialization/parsing returns
     * structured data, and, as such, this method ONLY accepts arrays or objects,
     * or a null value if nothing was available to parse.
     *
     * As an example, if content negotiation determines that the request data
     * is a JSON payload, this method could be used to create a request
     * instance with the deserialized parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated body parameters.
     *
     * @param null|array|object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody( $data ) {
    
    	$with = array(
    		'what'	=> 'parsed_body',
    		'value'	=> $data,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes() {
    
    	return $this->attributes;
    
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute( $name, $default = null ) {
    
    	if( isset( $this->attributes[$name] ) ) {
    	
    		return $this->attributes[$name];
    	
    	}
    	return $default;
    
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute( $name, $value ) {
    
    	$with = array(
    		'what'	=> 'attribute',
    		'value'	=> array(
    			'name'	=> $name,
    			'value'	=> $value
    		),
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute( $name ) {
    
    	$with = array(
    		'what'	=> 'remove_attribute',
    		'value'	=> $name,
    	);
    	$newRequest = new CacwpssaoUri( $this->wp_request, $with );
    	return $newRequest;
    
    }

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
    	
    		$scope_id = $scope->getIdentifier();
    		if( $clientEntity->validateScope( $scope_id ) ) {
    		
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
    
    	$key = $accessTokenEntity->getIdentifier();
    	
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
    
    
    
    public function validateScope( $scope ) {
    
    	return $this->permissions->validateScope( $scope );
    
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



class CacwpssaoWpRequestEvent extends RequestEvent {

	private $wp_rest_request;
	
	
	
	public function __construct( $name, WP_REST_Request $wp_request ) {
	
		
		$this->wp_rest_request = $wp_request;
		$request = $this->convert_wp_rest_request();
		parent::__construct( $name, $request );
	
	}
	
	
	
	private function convert_wp_rest_request() {
	
		
	
	}

}



function cacwpssaoServer() {

	$rsa_key_service = new CacwpssaoKey;
	$rsa_keys = $rsa_key_service->paths();

	// Init our repositories
	$clientRepository = new CacwpssaoClientRepository();
	$scopeRepository = new CacwpssaoScopeRepository();
	$accessTokenRepository = new CacwpssaoAccessTokenRepository();

	// Setup the authorization server
	$oauth_server = new \League\OAuth2\Server\AuthorizationServer(
		$clientRepository,
		$accessTokenRepository,
		$scopeRepository,
		$rsa_keys['private'],
		$rsa_keys['public']
	);

	// Enable the client credentials grant on the server
	$oauth_server->enableGrantType(
		new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
		new \DateInterval('PT1H') // access tokens will expire after 1 hour
	);
	
	return $oauth_server;

}
