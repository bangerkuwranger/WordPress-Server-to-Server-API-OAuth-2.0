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
		'public'                => true,
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
                return $item[$column_name];
            default:
                return print_r( $item, true );
        }
    
    }
    
    public function column_cb($item) {
    
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            $this->_args['singular'],
            $item['ID']
        );
    
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
    
        if( 'delete'===$this->current_action() ) {
            wp_die('Items deleted (or they would be if we had items to delete)!');
        }
        
    }
    
    public function prepare_items() {
    
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
				$data[$i] = array(
					'ID'			=> $id,
					'enabled'		=> $enabled,
					'name'			=> $name,
					'description'	=> $description,
					'client_id' 	=> $clientid,
					'client_secret'	=> $clientsecret
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



/** ************************ REGISTER THE TEST PAGE ****************************
 *******************************************************************************
 * Now we just need to define an admin page. For this example, we'll add a top-level
 * menu item to the bottom of the admin menus.
 */
function cAcwpssao_add_menu_items() {

    add_menu_page('Client Servers', 'Servers', 'activate_plugins', 'cAcwpssao_server_list', 'cAcwpssao_render_server_list');

} 
add_action('admin_menu', 'cAcwpssao_add_menu_items');





/** *************************** RENDER TEST PAGE ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function cAcwpssao_render_server_list() {
    
    //Create an instance of our package class...
    $cAcwpssao_server_list = new Cacwpssao_Server_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $cAcwpssao_server_list->prepare_items();
    
    ?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Client Servers</h2>
        
        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
            <p>These are client servers currently authorized to access the JSON API</p>
        </div>
        
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="cAcwpssao-server-list" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php $cAcwpssao_server_list->display() ?>
        </form>
        
    </div>
    <?php
}
