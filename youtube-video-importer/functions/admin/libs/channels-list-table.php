<?php
 /*
 * Load WP_List_Table class
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class YVI_Channels_List_Table extends WP_List_Table{
	
	private $error;
	
	public function __construct( $args = array() ){
		parent::__construct( array(
			'singular' => 'playlist',
			'plural'   => 'playlists',
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );		
	}
	
	/**
	 * Default column
	 * @param array $item
	 * @param string $column
	 */
	function column_default( $item, $column  ){
		return $item[ $column ];
	}
	
	
	public function column_item_title( $item ){
		
		$view = $this->_get_view();
		$id = 'playlists' == $view ? $item['playlist_id'] : $item['channel_id'];
		
		$manual_bulk_url = add_query_arg(
			array(
				'yvi_source' 	=> 'youtube',
				'yvi_feed' 		=> 'playlists' == $view ? 'playlist' : 'channel',
				'yvi_query'		=> $id
			),
			html_entity_decode( menu_page_url( 'yvi_import',  false ) )
		);
		$manual_bulk_url = wp_nonce_url( $manual_bulk_url, 'yvi-video-import', 'yvi_search_nonce' );
		
		$automatic_bulk_url = add_query_arg(
			array(
				'feed_type' => 'playlists' == $view ? 'playlist' : 'channel',
				'list_id'	=> $id,
				'title'		=> urlencode( $item['title'] ),
				'action'	=> 'add_new'
			),
			html_entity_decode( menu_page_url( 'yvi_auto_import',  false ) )
		);
				
		$actions = array(
			'manual_bulk' => sprintf( '<a href="%s">%s</a>', $manual_bulk_url, __( 'Manual import', 'yvi_video' ) ),
			'automatic_bulk' => sprintf( '<a href="%s">%s</a>', $automatic_bulk_url, __( 'Create automatic import', 'yvi_video' ) )
		);
				
		$fragment = 'playlists' == $view ? 'playlist?list=' : 'channel/';
		$url = 'https://www.youtube.com/' . $fragment . $id;
		
		return sprintf('%1$s %2$s',
    		sprintf( '<a href="%s" title="%s" target="_blank">%s</a>', $url, __('Open on YouTube.com', 'yvi_video'), $item['title'] ),
    		$this->row_actions( $actions )
    	);
	}
	
	public function column_item_id( $item ){
		$view = $this->_get_view();
		if( 'playlists' == $view ){
			return $item['playlist_id'];
		}
		return $item['channel_id'];
	}
	
	public function column_total_videos( $item ){
		return $item['videos'];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::no_items()
	 */
	public function no_items(){
		if( is_wp_error( $this->error ) ){
			echo  $this->error->get_error_message();
			if( 'yvi_invalid_yt_grant' == $this->error->get_error_code() ){
				echo '<br>';
				yvi_show_oauth_link();
			}	
			if( 'yvi_oauth_no_credentials' == $this->error->get_error_code() ){
				printf( '<p><a href="%s" class="button button-primary">%s</a></p>', menu_page_url( 'yvi_settings', false ) . '#yvi-settings-auth-options', __( 'Go to plugin settings', 'yvi_video' ) );
			}
			
			
		}else{		
			_e('Nothing found.', 'yvi_video');
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns(){
		$columns = array(
			'item_title'		=> __('Title', 'yvi_video'),
			'item_id'			=> __('ID', 'yvi_video'),
			'total_videos' 		=> __('Videos', 'yvi_video')
		); 
		return $columns;
	}
	
	/**
     * (non-PHPdoc)
     * @see WP_List_Table::get_bulk_actions()
     */
    function get_bulk_actions() {    	
    	$actions = array();
    	return $actions;
    }
	
    /**
     * (non-PHPdoc)
     * @see WP_List_Table::get_views()
     */
    function get_views(){
    	
    	$url = menu_page_url( 'yvi_my_youtube', false ) . '&view=%s';
    	$lt = '<a href="' . $url . '" title="%s" class="%s">%s</a>';
    	
    	$views = array(
    		'playlists'		=> sprintf( $lt, 'playlists', __('Playlists', 'yvi_video'), ( !isset($_GET['view']) || 'playlists' == $_GET['view'] ? 'current' : '' ) , __('Playlists', 'yvi_video') ),
    		'channels' 		=> sprintf( $lt, 'channels', __('Channels', 'yvi_video'), ( isset( $_GET['view'] ) && 'channels' == $_GET['view'] ? 'current' : '' ), __('Channels', 'yvi_video') ),
    		'subscriptions' => sprintf( $lt, 'subscriptions', __('Subscriptions', 'yvi_video'), ( isset( $_GET['view'] ) && 'subscriptions' == $_GET['view'] ? 'current' : '' ), __('Subscriptions', 'yvi_video') )
    	);
    	
    	return $views;
    }
    
	/**
     * (non-PHPdoc)
     * @see WP_List_Table::prepare_items()
     */    
    function prepare_items() {
    	
    	$per_page 		= 20;
    	$current_page 	= $this->get_pagenum();
    	
    	$query = array(
    		'items' => array(),
    		'page_info' => array(
    			'total_results' => 0
    		)
    	);
		$page_token = isset( $_GET['page_token'] ) ? $_GET['page_token'] : '';
    	
    	switch( $this->_get_view() ){
    		case 'channels':
    			$query = yvi_yt_api_get_user_channels( $page_token , $per_page );
    		break;
    		case 'subscriptions':
				$query = yvi_yt_api_get_user_subscriptions( $page_token, $per_page );
    		break;
    		case 'playlists':   
    		default: 			
				$query = yvi_yt_api_get_user_playlists( $page_token, $per_page );
    		break;	
    	}
    	
		
    	if( is_wp_error( $query['items'] ) ){
    		$this->error = $query['items'];
    		$query['items'] = array();
    	}
    	
        $this->items = $query['items'];
        
        $this->set_pagination_args( array(
            'total_items' => $query['page_info']['total_results'],                  
            'per_page'    => $per_page,                     
            'total_pages' => ceil( $query['page_info']['total_results'] / $per_page )  
        ) );        
    }
	
    /**
     * Returns current active view
     */
    private function _get_view(){
    	$view = isset( $_GET['view'] ) ? $_GET['view'] : '';
    	$views = array( 'channels', 'subscriptions', 'playlists' );
    	if( in_array( $view, $views ) ){
    		return $view;
    	}else{    	
    		return 'playlists';
    	}    	
    }
}