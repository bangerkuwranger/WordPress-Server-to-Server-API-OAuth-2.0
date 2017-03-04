<?php

defined( 'ABSPATH' ) or die();

$default_$scopes = array(
	'post',
	'taxonomy',
	'tax_value',
	'comment',
	'media',
	'user',
	'status',
	'settings',
);

$cacwpssao_global_scopes = apply_filters( 'cacwpssao_perm_scopes', $default_scopes )

class CacwpssaoPermissions {

	private $client_id;
	private $scopes;
	private $values;
	private $operations;
	private $options = array();
	
	
	
	public function __construct( $client_id ) {
	
		$this->client_id = $client_id;
		$this->scopes = apply_filters( 'cacwpssao_perm_scopes', $this->defaultScopes() );
		$this->values = apply_filters( 'cacwpssao_perm_values', $this->defaultValues() );
		$this->operations = apply_filters( 'cacwpssao_perm_operations', $this->defaultOperations() );
		$this->setupOptions();
		update_post_meta( $this->client_id, 'server_permissions_set', true );
	
	}
	
	
	
	public function renderOptionsForm() {
	
		?>
		<form id="cAcwpssao-server-edit" method="post">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<input type="hidden" id="serverid" name="serverid" value="<?php echo $_REQUEST['serverid'] ?>" />
			<?php wp_nonce_field( 'cAcwpssao-save-perms', 'cAcwpssao-save-perms-nonce' ); ?>
			<ul>
				<?php
				foreach( $this->scopes as $scope ) {
					?>
					<li><h1>WP <?php echo ucwords( str_replace('x_value', 'xonomy Term', $scope ) ); ?></h1>
						<ul>
						<?php
						foreach( $this->values as $value => $vscope ) {
							if( $vscope == $scope ) {
								?>
								<li><h2><?php echo ucwords( str_replace( '_v', ' Term', $value ) ); ?></h2>
									<ul>
										<?php
										foreach( $this->operations as $operation => $opscopes ) {
										
											if( in_array( $scope, $opscopes, true ) ) {
											
												$option = $scope . '_' . $value . '_' . $operation;
												$values = $this->options[$option];
												?>
												<li>
												<?php
												$this->renderOptionField( $option, $values );
												?>
												</li>
												<?php
											
											}
					
										}
										?>
									</ul>
								</li>
							<?php
							}
						}
						?>
						</ul>
					</li>
					<?php
				}
				?>
			</ul>
			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save"></p>
		</form>
		<style>
			#cAcwpssao-server-edit > ul > li > ul {
				border-top: 1px solid;
			}
			
			#cAcwpssao-server-edit ul li ul li ul li {
				text-indent: 4em;
			}
			
			#cAcwpssao-server-edit ul li ul li {
				text-indent: 4em;
			}
			
			#cAcwpssao-server-edit ul li {
				text-indent: 0;
			}
			#cAcwpssao-server-edit ul li h4 {
				display: inline-block;
			}
		</style>
		<?php
	
	}
	
	
	
	public function saveOptions() {
	
		check_admin_referer( 'cAcwpssao-save-perms', 'cAcwpssao-save-perms-nonce' );
		if( !current_user_can( 'create_users' ) ) {
			wp_die('Sorry, no can do. Ask your site administrator if you need access.');
		}
		foreach( $this->options as $option => $values ) {
		
			$this->saveOptionField( $option, $values );
			
		}
	
	}
	
	
	
	protected function validateScope( $scope ) {
	
		$valid = false;
		$scope = trim( strtolower( $scope ) );
		$valid_perms = $this->getValidPerms();
		foreach( $valid_perms as $perm ) {
		
			if( strpos( $perm, $scope ) !== false ) {
			
				$valid = true;
				break;
			
			}
		
		}
		return $valid;
	
	}
	
	
	
	protected function validatePerm( $scope, $value, $operation ) {
	
		if( !($this->permExists( $scope, $value, $operation ) ) ) {
			return false;
		}
		$valid_perms = $this->getValidPerms();
		$perm = trim( strtolower( $scope ) . '_' . strtolower( $value ) . '_' . strtoupper( $operation ) );
		return array_key_exists( $perm, $valid_perms );
	
	}
	
	
	
	private function renderOptionField( $option, $values ) {
	
		$option_id = sanitize_title( strtolower( $option ) );
		switch( $values['operation'] ) {
		
			case 'GET':
				$label = 'Get ' . ucwords( str_replace( '_v', ' Term', $values['value'] ) ) . '(s)';
				break;
			case 'POST':
				$label = 'Add/Edit ' . ucwords( str_replace( '_v', ' Term', $values['value'] ) );
				break;
			case 'DELETE':
				$label = 'Delete ' . ucwords( str_replace( '_v', ' Term', $values['value'] ) );
		
		}
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo $option_id; ?>"><h4><?php echo $label; ?></h4></label>
			</th>
			<td>
				<input type="checkbox" id="<?php echo $option_id; ?>" name="<?php echo $option_id; ?>" value="true" <?php checked( true == $values['setting'] ); ?> />
			</td>
		</tr>
		<?php
	
	}
	
	
	
	private function saveOptionField( $option, $values ) {
	
		$option_id = sanitize_title( strtolower( $option ) );
		$setting = $values['setting'];
		$new_setting = ( isset( $_POST[$option_id] ) && !empty( $_POST[$option_id] ) ) ? (bool)$_POST[$option_id] : false;
		if( $new_setting !== $setting ) {
		
			update_post_meta( $this->client_id, $option, $new_setting, $setting );
			$this->options[$option]['setting'] = $new_setting;
		
		}
	
	}
	
	
	
	private function permExists( $scope, $value, $operation ) {
	
		$perm = trim( strtolower( $scope ) . '_' . strtolower( $value ) . '_' . strtoupper( $operation ) );
		return array_key_exists( $perm, $this->options );
	
	}
	
	
	
	private function getValidPerms() {
	
		$perms = array();
		foreach( $this->options as $option => $values ) {
		
			if( $value['setting'] == true ) {
			
				array_push( $perms, $option );
			
			}
		
		}
		return $perms;
	
	}
	
	
	
	private function defaultScopes() {
	
		$scopes = array(
			'post',
			'taxonomy',
			'tax_value',
			'comment',
			'media',
			'user',
			'status',
			'settings',
		);
		return $scopes;
	
	}
	
	
	
	private function defaultValues() {
	
		$values = array(
			'post'			=> 'post',
			'page'			=> 'post',
			'attachment'	=> 'post',
			'category'		=> 'taxonomy',
			'tag'			=> 'taxonomy',
			'category_v'	=> 'tax_value',
			'tag_v'			=> 'tax_value',
			'comment'		=> 'comment',
			'media'			=> 'media',
			'user'			=> 'user',
			'status'		=> 'status',
			'setting'		=> 'settings',
		);
		$post_types = $this->get_custom_post_types();
		if( is_array( $post_types ) && !empty( $post_types ) ) {
		
			foreach( $post_types as $post_type ) {
			
				$values[$post_type] = 'post';
			
			}
		
		}
		$taxonomies = $this->get_custom_taxonomies();
		if( is_array( $taxonomies ) && !empty( $taxonomies ) ) {
		
			foreach( $taxonomies as $taxonomy ) {
			
				$values[$taxonomy] = 'taxonomy';
				$values[$taxonomy . '_v'] = 'tax_value';
			
			}
		
		}
		return $values;
	
	}
	
	
	
	private function defaultOperations() {
	
		$ops = array(
			'GET'	=> array(
				'post',
				'taxonomy',
				'tax_value',
				'comment',
				'media',
				'user',
				'status',
			
			),
			'POST'	=> array(
				'post',
				'tax_value',
				'comment',
				'media',
				'user',
				'settings',
				
			),
			'DELETE'=> array(
				'post',
				'tax_value',
				'comment',
				'media',
				'user',
			),
		);
		return $ops;
	
	}
	
	
	
	private function get_custom_post_types() {
	
		$args = array(
			'show_in_rest'	=> true,
			'_builtin'		=> false,
		);
		$types = get_post_types( $args );
		return $types;
	
	}
	
	
	
	private function get_custom_taxonomies() {
	
		$args = array(
			'show_in_rest'	=> true,
			'_builtin'		=> false,
		);
		$taxonomies = get_taxonomies( $args );
		return $taxonomies;
	
	}
	
	
	
	private function setupOptions() {
		
		foreach( $this->scopes as $scope ) {
		
			foreach( $this->values as $value => $vscope ) {
			
				if( $scope == $vscope ) {
				
					foreach( $this->operations as $op => $opscopes ) {
					
						if( in_array( $scope, $opscopes, true ) ) {
						
							$option = $scope . '_' . $value . '_' . $op;
							add_post_meta( $this->client_id, $option, $op == 'GET' ? true : false, true );
							$opvalue = get_post_meta( $this->client_id, $option, true );
							$this->options[$option] = array(
								'setting'	=> $opvalue,
								'scope'		=> $scope,
								'value'		=> $value,
								'operation'	=> $op,
							);
						
						}
					
					}
				
				}
			
			}
		
		}
	
	}

} 
