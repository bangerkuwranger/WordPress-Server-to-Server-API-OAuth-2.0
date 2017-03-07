<?php
defined( 'ABSPATH' ) or die();


function cacwpssao_server() {

	$labels = array(
		'name'                  => 'Servers',
		'singular_name'         => 'Server',
		'menu_name'             => 'Post Types',
		'name_admin_bar'        => 'Post Type',
		'archives'              => 'Item Archives',
		'attributes'            => 'Item Attributes',
		'parent_item_colon'     => 'Parent Item:',
		'all_items'             => 'All Items',
		'add_new_item'          => 'Add New Item',
		'add_new'               => 'Add New',
		'new_item'              => 'New Item',
		'edit_item'             => 'Edit Item',
		'update_item'           => 'Update Item',
		'view_item'             => 'View Item',
		'view_items'            => 'View Items',
		'search_items'          => 'Search Item',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into item',
		'uploaded_to_this_item' => 'Uploaded to this item',
		'items_list'            => 'Items list',
		'items_list_navigation' => 'Items list navigation',
		'filter_items_list'     => 'Filter items list',
	);
	$args = array(
		'label'                 => 'Server',
		'description'           => 'Client servers for OAuth 2.0 authentication',
		'labels'                => $labels,
		'supports'              => array( ),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => false,
		'show_in_menu'          => false,
		'menu_position'         => 5,
		'show_in_admin_bar'     => false,
		'show_in_nav_menus'     => false,
		'can_export'            => false,
		'has_archive'           => false,		
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'query_var'             => 'cacwpssaoserver',
		'rewrite'               => false,
		'capability_type'       => 'page',
		'show_in_rest'          => false,
	);
	register_post_type( 'cacwpssaoserver', $args );

}
add_action( 'init', 'cacwpssao_server', 0 );


if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Cacwpssao_Server_List_Table extends WP_List_Table {

	public function __construct() {
        
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'server',
            'plural'    => 'servers',
            'ajax'      => false
        ) );
    
    }
    
    public function column_default( $item, $column_name ) {
    
        switch( $column_name ) {
            case 'name':
            case 'description':
            case 'client_id':
            case 'client_secret':
            case 'enabled':
            case 'permissions':
                return $item[$column_name];
            default:
                return print_r( $item, true );
        }
    
    }
    
    public function column_cb( $item ) {
    
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],
            $item['ID']
        );
    
    }
    
    public function column_enabled( $item ) {
    
    	$status = (bool)$item['enabled'];
    	$value = $status ? '&check;' : '&cross;';
    	return '<span style="font-size:1.5em;margin-left:1em;">' . $value . '</span>';
    
    }
    
    public function column_permissions( $item ) {
    
    	if( $item['permissions'] ) {
    	
    		$value = '<span style="font-size:1.5em;margin-left:1em;">&check;</span>';
    		$link = '<span class="edit"><a href="' . admin_url( 'admin.php?page=cAcwpssao_server_perms&serverid=' . $item['ID'] ) . '" title="Edit Permissions">Edit Permissions</a>';
    	
    	}
    	else {
    	
    		$value = '<span style="font-size:1.5em;margin-left:1em;">&cross;</span>';
    		$link = '<span class="edit"><a href="' . admin_url( 'admin.php?page=cAcwpssao_server_perms&serverid=' . $item['ID'] ) . '" title="Set Permissions">Set Permissions</a>';
    	
    	}
    	
    	return $value . '&nbsp;' . $link;
    
    }
    
    public function get_columns() {
    
        $columns = array(
            'cb'			=> '<input type="checkbox" />',
            'enabled'		=> 'Enabled',
            'name'			=> 'Server Name',
            'description'	=> 'Description',
            'client_id' 	=> 'Client ID',
            'client_secret'	=> 'Client Secret'
        );
        if( current_user_can( 'create_users' ) ) {
			$columns['permissions'] = 'Permissions Set';
		}
        return $columns;
    
    }
    
    public function get_sortable_columns() {
    
        $sortable_columns = array(
            'name'	=> array( 'name', true )
        );
        return $sortable_columns;
    
    }
    
    public function get_bulk_actions() {
    
        $actions = array(
        	'enable'	=> 'Enable',
        	'disable'	=> 'Disable',
            'delete'	=> 'Delete'
        );
        return $actions;
    
    }
    
    public function process_bulk_action() {
    	
		if( isset( $_REQUEST['server'] ) ) {
		
			if( !current_user_can( 'create_users' ) ) {
				wp_die('Sorry, no can do. Ask your site administrator if you need access.');
			}
			else {
		
				$server_id = ( is_array( $_REQUEST['server'] ) ) ? $_REQUEST['server'] : array( $_REQUEST['server'] );
			
				foreach ( $server_id as $id ) {
		   
					if( 'enable'===$this->current_action() ) {
						update_post_meta( $id, 'server_enabled', 1 );
					}
				
					if( 'disable'===$this->current_action() ) {
						update_post_meta( $id, 'server_enabled', 0 );
					}
				
					if( 'delete'===$this->current_action() ) {
						wp_delete_post( $id, true );
					}
			
				}
		
			}
		
		}
        
    }
    
    public function prepare_items() {
    
        global $wpdb;
        $per_page = 15;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        $this->process_bulk_action();
        
       	$server_args = array(
        	'post_type'		=> 'cacwpssaoserver',
        	'numberposts'	=> -1
        );
        $servers = get_posts( $server_args );
//         $query = new WP_Query( array( 'post_type' => 'cacwpssaoserver' ) );
        $data = array();
        $i = 0;
		if( count( $servers ) > 0 ) {
		
			foreach( $servers as $server ) {
		
				$id = $server->ID;
				$enabled = get_post_meta( $id, 'server_enabled', true );
				$name = get_post_meta( $id, 'server_name', true );
				$description = get_post_meta( $id, 'server_description', true );
				$clientid = get_post_meta( $id, 'server_client_id', true );
				$clientsecret = get_post_meta( $id, 'server_client_secret', true );
				$perms = get_post_meta( $id, 'server_permissions_set', true );
				$data[$i] = array(
					'ID'			=> $id,
					'enabled'		=> $enabled,
					'name'			=> $name,
					'description'	=> $description,
					'client_id' 	=> $clientid,
					'client_secret'	=> $clientsecret,
					'permissions'	=> $perms,
				);
				$i++;
		
			}
		
		}
        
        function usort_reorder($a,$b){
            $orderby = ( !empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'name';
            $order = ( !empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
            $result = strcmp($a[$orderby], $b[$orderby]);
            return ( $order==='asc' ) ? $result : -$result;
        }
        usort( $data, 'usort_reorder' );

        $current_page = $this->get_pagenum();
        $total_items = count( $data );
        $data = array_slice( $data, ( ( $current_page-1 ) * $per_page ), $per_page );
        $this->items = $data;
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items/$per_page )
        ) );
    
    }

}



function cAcwpssao_add_menu_items() {

    add_menu_page('OAuth 2.0', 'OAauth 2.0 Settings', 'activate_plugins', 'cAcwpssao_admin_menu', 'cAcwpssao_render_genereal_settings');
    add_submenu_page( 'cAcwpssao_admin_menu', 'Servers', 'Server Settings', 'activate_plugins', 'cAcwpssao_server_list', 'cAcwpssao_render_server_list');
    add_submenu_page( 'cAcwpssao_admin_menu', 'Add New Server', 'Add New Server', 'activate_plugins', 'cAcwpssao_add_server', 'cAcwpssao_render_add_server');
    add_submenu_page( 'admin.php', 'Server Permissions', '', 'activate_plugins', 'cAcwpssao_server_perms', 'cAcwpssao_render_server_perms' );

} 
add_action('admin_menu', 'cAcwpssao_add_menu_items');

function cAcwpssao_admin_notice__missing() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php _e( 'Both Name and Description are required. Please fill out required fields and submit again.', 'sample-text-domain' ); ?></p>
    </div>
    <?php
}


function cAcwpssao_render_genereal_settings() {

	if( isset( $_POST['submit'] ) ) {
	
		check_admin_referer( 'cAcwpssao-save-settings', 'cAcwpssao-save-settings-nonce' );
		
		if( !current_user_can( 'create_users' ) ) {
			wp_die('Sorry, no can do. Ask your site administrator if you need access.');
		}
		
		$is_force_auth = isset( $_POST['cAcwpssao-is-force-auth'] ) ? (bool)( $_POST['cAcwpssao-is-force-auth'] ) : 0;
		update_option( 'cacwpssao_is_force_auth', $is_force_auth );
		
	}
	else {
	
		$is_force_auth = get_option( 'cacwpssao_is_force_auth', 0 );
		
	}
	
	?>
	<div class="wrap">
		<h1>OAuth 2.0 Settings</h1>
		<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
			<p>General Settings for Authentication for WP JSON API</p>
		</div>
		<form id="cAcwpssao-server-add" method="post">
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<?php wp_nonce_field( 'cAcwpssao-save-settings', 'cAcwpssao-save-settings-nonce' ); ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="cAcwpssao-is-force-auth">Force GET Authorization</label>
						</th>
						<td>
							<span>Yes</span><input type="radio" name="cAcwpssao-is-force-auth" value="1" <?php checked( '1' == $is_force_auth ); ?> />
							<span>No</span><input type="radio" name="cAcwpssao-is-force-auth" value="0" <?php checked( '0' == $is_force_auth ); ?> />
							<p>Normally, WP doesn't attempt to authenticate GET requests. If set to "Yes", GET requests will require a valid AUTH token and permissions set for the client.</p>
						</td>
					</tr>
				</tbody>
			</table>
			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save"></p>
		</form>
	</div>
	<?php	

}

function cAcwpssao_render_add_server() {
	$post_type = 'cAcwpssaoserver';
	if( isset( $_POST['submit'] ) ) {
	
		check_admin_referer( 'cAcwpssao-add-server', 'cAcwpssao-add-server-nonce' );
		
		if( !current_user_can( 'create_users' ) ) {
			wp_die('Sorry, no can do. Ask your site administrator if you need access.');
		}
		
		$proceed = false;
		if( isset( $_POST['cAcwpssao-new-server-name'] ) || $_POST['cAcwpssao-new-server-name'] != '' ) {
		
			$proceed = true;
			if( !isset( $_POST['cAcwpssao-new-server-description'] ) || $_POST['cAcwpssao-new-server-description'] == '' ) {
			
				$proceed = false;
				add_action( 'admin_notices', 'cAcwpssao_admin_notice__missing' );
			
			}
		
		}
		else {
		
			$proceed = false;
			add_action( 'admin_notices', 'cAcwpssao_admin_notice__missing' );
		
		}
		
		
		if( $proceed ) {
			
			$name = sanitize_text_field( $_POST['cAcwpssao-new-server-name'] );
			$desc = sanitize_text_field( $_POST['cAcwpssao-new-server-description'] );
			$factory = new RandomLib\Factory;
			$generator = $factory->getMediumStrengthGenerator();
			$client_id = $generator->generateString( 20, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
			$client_secret = $generator->generateString( 20, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' );
			$post_array = array(
				'post_type'		=> $post_type,
				'post_name'		=> sanitize_title( $name . '-' . $client_id ),
				'meta_input'	=> array(
					'server_name'				=> $name,
					'server_description'		=> $desc,
					'server_enabled'			=> true,
					'server_client_id'			=> $client_id,
					'server_client_secret'		=> $client_secret,
					'server_permissions_set'	=> false,
				),
			);
			$new_server = wp_insert_post( $post_array );
			wp_publish_post( $new_server );
			?>
			<div class="wrap">
				<h1>Client Server Added</h1>
				<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
					<p><strong><?php echo $name; ?></strong> - <?php echo $desc ?></p>
					<p><strong>Client ID</strong> - <?php echo $client_id ?></p>
					<p><strong>Client Secret</strong> - <?php echo $client_secret ?></p>
					<p><?php echo ' <a href="' . esc_url( admin_url( 'admin.php?page=cAcwpssao_server_list' ) ) . '" class="page-title-action">&lsaquo; Server List</a>'; ?></p>
				</div>
			</div>
			<?php
			
		}
		else {
			
			$name = isset( $_POST['cAcwpssao-new-server-name'] ) ? $_POST['cAcwpssao-new-server-name'] : '';
			$desc = isset( $_POST['cAcwpssao-new-server-description'] ) ? $_POST['cAcwpssao-new-server-description'] : '';
			
			?>

			<div class="wrap">
				<h1>Add Client Server</h1>
				<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
					<p>Enter the name and description for this server to generate keys for this user.</p>
				</div>
				<form id="cAcwpssao-server-add" method="post">
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
					<?php wp_nonce_field( 'cAcwpssao-add-server', 'cAcwpssao-add-server-nonce' ); ?>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="cAcwpssao-new-server-name">Name</label>
								</th>
								<td>
									<input type="text" id="cAcwpssao-new-server-name" name="cAcwpssao-new-server-name" class="regular-text" value="<?php echo $name; ?>" />
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="cAcwpssao-new-server-description">Description</label>
								</th>
								<td>
									<input type="text" id="cAcwpssao-new-server-description" name="cAcwpssao-new-server-description" class="regular-text" value="<?php echo $desc; ?>" />
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Add Server"></p>
				</form>
			</div>

			<?php
		}
	
	}
	else {
	
		?>

		<div class="wrap">
			<h1>Add Client Server</h1>
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
				<p>Enter the name and description for this server to generate keys for this user.</p>
			</div>
			<form id="cAcwpssao-server-add" method="post">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php wp_nonce_field( 'cAcwpssao-add-server', 'cAcwpssao-add-server-nonce' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="cAcwpssao-new-server-name">Name</label>
							</th>
							<td>
								<input type="text" id="cAcwpssao-new-server-name" name="cAcwpssao-new-server-name" class="regular-text" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="cAcwpssao-new-server-description">Description</label>
							</th>
							<td>
								<input type="text" id="cAcwpssao-new-server-description" name="cAcwpssao-new-server-description" class="regular-text" />
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Add Server"></p>
			</form>
		</div>

		<?php
	
	}
		
}


function cAcwpssao_render_server_list() {
    
    //Create an instance of our package class...
    $cAcwpssao_server_list = new Cacwpssao_Server_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $cAcwpssao_server_list->prepare_items();
    $post_type = 'cAcwpssaoserver';
    $post_new_file = "admin.php?page=cAcwpssao_add_server";

    
    ?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h1>Client Servers
        <?php
		if( current_user_can( 'create_users' ) ) { echo ' <a href="' . esc_url( admin_url( $post_new_file ) ) . '" class="page-title-action">Add New Server</a>'; }
		?>
        </h1>
        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
            <p>These are client servers currently authorized to access the JSON API</p>
        </div>

        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="cAcwpssao-server-list" method="post">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $cAcwpssao_server_list->display() ?>
        </form>
        
    </div>
    <?php
}


function cAcwpssao_render_server_perms() {

	if( !current_user_can( 'create_users' ) ) {
		wp_die('Sorry, no can do. Ask your site administrator if you need access.');
	}
	if( isset( $_REQUEST['serverid'] ) && !empty( $_REQUEST['serverid'] ) ) {
	
		$server_id = intval( $_REQUEST['serverid'] );
		$pt = get_post_type( $server_id );
		if( 'cacwpssaoserver' !== $pt ) {
			wp_die('Client server not found.');
		}
	
	}
	else {
	
		wp_die('No client server specified.');
	
	}
	$server_name = get_post_meta( $server_id, 'server_name', true );
	$server_desc = get_post_meta( $server_id, 'server_description', true );
	$client_server_perms = new CacwpssaoPermissions( $server_id );
	if( isset( $_POST['submit'] ) ) {
	
		$client_server_perms->saveOptions();
	
	}
	?>
	<div class="wrap">
		<h1>Client Server Permissions <?php echo ' <a href="' . esc_url( admin_url( 'admin.php?page=cAcwpssao_server_list' ) ) . '" class="page-title-action">&lsaquo; Server List</a>'; ?></h1>
		<h2>Server: <?php echo $server_name; ?></h2>
		<h4><?php echo $server_desc; ?></h4>
		<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
			<p>Set permissions for this client server. GET is enabled by default, to force all GET requests to use authentication, set 'Force GET Authorization' to 'Yes' in General Settings for the plugin.</p>
		</div>
		<?php
		$client_server_perms->renderOptionsForm();
		?>
	</div>
	<?php

}
