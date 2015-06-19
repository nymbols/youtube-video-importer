<?php
/**
 * Load WP_List_Table class
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class YTI_Playlists_List_Table extends WP_List_Table{
	
	private $playlist;
	
	function __construct( $args = array() ){
		
		global $YTI_AUTOMATIC_IMPORT;
		$this->playlist = $YTI_AUTOMATIC_IMPORT->get_next_playlist();
		
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
		return $item[$column];
	}
	
	/**
	 * Checkbox column
	 * @param array $item
	 */
	function column_cb( $item ){
		return sprintf(
			'<input type="checkbox" name="%1$s" value="%2$s" id="%3$s" class="yti-video-checkboxes">',
			'yti_playlist[]',
			$item['ID'],
			'yti-playlist-'.$item['ID']
		);
	}
	
	function column_post_title( $item ){
		
		global $YTI_POST_TYPE;
		
		$page_args = array(
				'post_type' => $YTI_POST_TYPE->get_post_type(),
				'page'		=> 'yti_auto_import',
				'action' 	=> 'edit',
				'id'		=> $item['ID']
		);		
		$edit_url = add_query_arg( $page_args, 'edit.php' );
		
		$page_args['action'] = 'delete';
		$delete_url = add_query_arg( $page_args, 'edit.php' );
		
		$page_args['action'] = 'reset';
		$reset_url = add_query_arg( $page_args, 'edit.php' );
		
		$page_args['action'] = 'queue';
		$queue_url = add_query_arg( $page_args, 'edit.php' );
		$queue_text = 'draft' == $item['post_status'] ? __('Start', 'yti_video') : __('Pause', 'yti_video');
		
		// row actions
    	$actions = array(
    		'edit' 		=> sprintf( '<a href="%s">%s</a>', $edit_url, __('Edit', 'yti_video') ),
    		'delete'	=> sprintf( '<a href="%s">%s</a>', wp_nonce_url( $delete_url ), __('Delete', 'yti_video') ),
    		'reset'		=> sprintf('<a href="%s">%s</a>', wp_nonce_url($reset_url), __('Reset', 'yti_video') ),
    		'queue'		=> sprintf( '<a href="%s">%s</a>', wp_nonce_url($queue_url), $queue_text),
    	);
    	
    	$prefix = '';
		if( $item['ID'] == $this->playlist ){
    		$prefix = '<i class="dashicons dashicons-update"></i> ';	
    	}
    	
    	$text = 'draft' == $item['post_status'] ? '<em>'.$item['post_title'].'</em>' : '<strong>'.$prefix.$item['post_title'].'</strong>';
    	
    	
    	
    	return sprintf('%1$s %2$s',
    		sprintf('<a href="%s" title="">%s</a>', $edit_url, $text),
    		$this->row_actions( $actions )
    	);	
		
	}
	
	function column_playlist_type( $item ){
		global $YTI_POST_TYPE;
		$meta_key = $YTI_POST_TYPE->get_playlist_meta_name();
		$meta = get_post_meta( $item['ID'], $meta_key, true );
		
		switch( $meta['type'] ){
			case 'user':
				return __('User uploads', 'yti_video');
			break;
			case 'playlist':
				return __('Video playlist', 'yti_video');
			break;
			case 'channel':
				return __('Video channel', 'yti_video');
			break;	
		}		
	}
	
	function column_playlist_id( $item ){
		
		global $YTI_POST_TYPE;
		$meta_key = $YTI_POST_TYPE->get_playlist_meta_name();
		$meta = get_post_meta( $item['ID'], $meta_key, true );
		
		return $meta['id'];	
	}
	
	function column_videos_imported( $item ){
		global $YTI_POST_TYPE;
		$meta_key = $YTI_POST_TYPE->get_playlist_meta_name();
		$meta = get_post_meta( $item['ID'], $meta_key, true );
		
		return $meta['imported'];	
	}
	
	function column_videos_processed( $item ){
		global $YTI_POST_TYPE;
		$meta_key = $YTI_POST_TYPE->get_playlist_meta_name();
		$meta = get_post_meta( $item['ID'], $meta_key, true );
		$settings = yti_get_settings();
		
		if( !$meta['total'] ){
			return __('no videos', 'yti_video');
		}
		
		$from = $meta['processed'] + 1;
		$to = $meta['processed'] + $settings['import_quantity'];
		
		if( $to > $meta['total'] ){
			$to = $meta['total'];
		}
		if( $from > $meta['total'] ){
			$from = $meta['total'];
		}
		
		$message = sprintf( __('%d - %d', 'yti_video'), $from, $to);
		return $message;	
	}
	
	function column_total_videos( $item ){
		global $YTI_POST_TYPE;
		$meta_key = $YTI_POST_TYPE->get_playlist_meta_name();
		$meta = get_post_meta( $item['ID'], $meta_key, true );
		
		return $meta['total'];	
	}
	
	function column_not_older($item){
		global $YTI_POST_TYPE;
		$meta_key = $YTI_POST_TYPE->get_playlist_meta_name();
		$meta = get_post_meta( $item['ID'], $meta_key, true );
		
		$result = empty($meta['start_date']) ? '<em style="color:#999;">' . __('not set', 'yti_video') . '</em>' : $meta['start_date'];
		return $result;
	}
	
	function column_last_query( $item ){
		global $YTI_POST_TYPE;
		$meta_key = $YTI_POST_TYPE->get_playlist_meta_name();
		$meta = get_post_meta( $item['ID'], $meta_key, true );
		
		if( !$meta['updated'] ){
			return __('never', 'yti_video');
		}
		
		return $meta['updated'];	
	}
	
	function column_status( $item ){
		global $YTI_POST_TYPE;
		$meta_key 		= $YTI_POST_TYPE->get_playlist_meta_name();
		$meta 			= get_post_meta( $item['ID'], $meta_key, true );
		
		if( 'draft' == $item['post_status'] ){
			$message = '<span style="color:red; font-style:italic;">'.__('Not in queue', 'yti_video').'</span>';
			if( isset( $meta['error'] ) && !empty( $meta['error'] ) ){
				$message .= '<br /><strong>' . __('Error: ', 'yti_video') . '</strong><em>' . $meta['error'] . '</em>'; 				
			}
			
			return $message;
		}
		
		$plugin_options = yti_get_settings();
		
		$import_type_message 	= '';	
		$is_theme_import 		= false;
		
		if( $meta['theme_import'] ){
			$theme = yti_check_theme_support();
			if( $theme ){			
				$import_type_message 	= '<span>'.sprintf( __('Import as posts compatible with <strong>%s</strong>.', 'yti_video'), $theme['theme_name'] ).'</span>';
				$is_theme_import 		= true;
			}else{
				$import_type_message = __('Theme not active. Videos will be imported as plugin custom posts.', 'yti_video');
			}			
		}else{
			$import_type_message = '<span>'.__('Import as custom post.').'</span>';			
		}
		
		if( isset( $meta['error'] ) ){
			$import_type_message .= '<br /><strong>Error: </strong><em>' . $meta['error'] . '</em>';
		}
		
		return $import_type_message;
	}
	
	function column_import_category( $item ){
		
		global $YTI_POST_TYPE;
		$meta_key 		= $YTI_POST_TYPE->get_playlist_meta_name();
		$meta 			= get_post_meta( $item['ID'], $meta_key, true );
		
		if( !isset($meta['theme_import']) || !$meta['theme_import'] ){
			if( isset($meta['native_tax']) && $meta['native_tax'] ){
				
				$options = yti_get_settings();
				$taxonomy = isset( $options['post_type_post'] ) && $options['post_type_post'] ? 'category' : $YTI_POST_TYPE->get_post_tax();				
				$term = get_term( $meta['native_tax'], $taxonomy );
				if( $term && !is_wp_error( $term ) ){
					return '<em>'.$term->name.'</em>';
				}else{
					return '-';
				}				
			}else{
				return __('Create from feed', 'yti_video');
			}
		}else{
			if( isset( $meta['theme_tax'] ) && $meta['theme_tax'] ){
				$theme = yti_check_theme_support();
				if( !$theme ){
					return '-';
				}
				
				$term = get_term( $meta['theme_tax'], ( !$theme['taxonomy'] ? 'category' : $theme['taxonomy'] ) );
				if( $term && !is_wp_error( $term ) ){
					return $term->name;
				}else{
					return '-';
				}				
				
			}else{
				return __('Create from feed', 'yti_video');
			}
		}		
	}
	
	/**
	 * Visible when YTI_DEBUG is true
	 * @param array $item
	 */
	function column_debug($item){
		global $YTI_POST_TYPE;
		$meta_key 	= $YTI_POST_TYPE->get_playlist_meta_name();
		$meta 		= get_post_meta( $item['ID'], $meta_key, true );
		?>
		<ul>
		<?php foreach($meta as $key=>$val):?>
			<li><strong><?php echo $key;?> : </strong><?php echo $val;?></li>
		<?php endforeach;?>
		</ul>
		<?php
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns(){
		$columns = array(
			'cb'				=> '<input type="checkbox" />',
			'post_title'		=> __('Title', 'yti_video'),
			'playlist_type' 	=> __('Playlist type', 'yti_video'),
			'import_category'	=> __('Import in category', 'yti_video'),
			'not_older'			=> __('Newer than', 'yti_video'),
			'playlist_id'		=> __('Playlist ID', 'yti_video'),
			//'videos_processed'	=> __('Next to process', 'yti_video'),
			'videos_imported' 	=> __('Imported', 'yti_video'),
			'total_videos'		=> __('Total videos', 'yti_video'),
			'last_query'		=> __('Queried on', 'yti_video'),
			'status'			=> __('Status', 'yti_video')
		); 
		
		if( yti_debug() ){
			$columns['debug'] = __('Meta', 'yti_video');
		}
		
    	return $columns;
	}
	
	/**
     * (non-PHPdoc)
     * @see WP_List_Table::get_bulk_actions()
     */
    function get_bulk_actions() {    	
    	$actions = array(
    		'delete' 		=> __('Delete', 'yti_video'),
    		'stop-import'	=> __('Remove from import queue', 'yti_video'),
    		'start-import'	=> __('Add to import queue', 'yti_video')
    	);
    	return $actions;
    }
	
    /**
     * (non-PHPdoc)
     * @see WP_List_Table::get_views()
     */
    function get_views(){
    	
    	$url = menu_page_url('yti_auto_import', false).'&view=%s';
    	$lt = '<a href="'.$url.'" title="%s" class="%s">%s<span class="count"> (%d)</span></a>';
    	
    	global $YTI_POST_TYPE;
    	$totals = wp_count_posts( $YTI_POST_TYPE->get_playlist_post_type() );
    	$all = $totals->publish + $totals->draft;
    	
    	$this->active = $totals->publish;
    	
    	$views = array(
    		'all'		=> sprintf( $lt, 'all', __('All automatic importers', 'yti_video'), ( !isset($_GET['view']) || 'all' == $_GET['view'] ? 'current' : '' ) , __('All', 'yti_video'), $all),
    		'active' 	=> sprintf( $lt, 'active', __('Active automatic importers', 'yti_video'), ( isset( $_GET['view'] ) && 'active' == $_GET['view'] ? 'current' : '' ), __('Active', 'yti_video'), $totals->publish ),
    		'inactive' 	=> sprintf( $lt, 'inactive', __('Inactive automatic importers', 'yti_video'), ( isset( $_GET['view'] ) && 'inactive' == $_GET['view'] ? 'current' : '' ), __('Inactive', 'yti_video'), $totals->draft )
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
    	
    	global $YTI_POST_TYPE;
    	
    	$status = array('publish', 'draft');
    	if( isset( $_GET['view'] ) ){
    		switch( $_GET['view'] ){
    			case 'active':// active playlists
    				$status = 'publish';
    			break;
    			case 'inactive'://inactive playlists
					$status = 'draft';
    			break;	
    		}
    	}
    	
    	$args = array(
			'post_type'			=> $YTI_POST_TYPE->get_playlist_post_type(),
			'orderby' 			=> 'ID',
		    'order' 			=> 'ASC',
	    	'posts_per_page'	=> $per_page,
	    	'offset'			=> ($current_page-1) * $per_page,
        	'post_status'		=> $status   	
        );
    	
        $query = new WP_Query( $args );
    	$data = array();    
        if( $query->posts ){
        	foreach($query->posts as $k => $item){
        		$data[$k] = (array)$item;
        	}
        }
        
        $total_items = $query->found_posts;
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                     
            'total_pages' => ceil($total_items/$per_page)  
        ) );        
    }	
}