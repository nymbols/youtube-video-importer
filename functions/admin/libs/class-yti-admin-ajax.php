<?php
if( !class_exists( 'YVI_Video_Post_Type' ) ){
	require_once YVI_PATH . 'functions/libs/custom-post-type.php';
}

/**
 * AJAX Actions management class
 * Extended by YVI_Admin class
 */
abstract class YVI_AJAX_Actions extends YVI_Video_Post_Type{
	
	/**
	 * Constructor. Sets all registered ajax actions.
	 */
	public function __construct(){
		// start parent
		parent::__construct();
		
		// get the actions
		$actions = $this->__actions();
		// add wp actions
		foreach( $actions as $action ){
			add_action( 'wp_ajax_' . $action['action'], $action['callback'] );
		}		
	}
	
	/**
	 * AJAX Callback 
	 * 
	 * Queries a given playlist ID and returns the number of videos found into that playlist.
	 */
	public function callback_query_playlist(){
		if( empty( $_POST['type'] ) || empty( $_POST['id'] ) ){
			_e('Please enter a playlist ID.', 'yvi_video');
			die();
		}
		
		$args = array(
			'playlist_type' 		=> $_POST['type'],
			'include_categories' 	=> false,
			'query'					=> $_POST['id']
		);	
		$details = yvi_yt_api_get_list( $args );
		
		if( is_wp_error( $details['videos'] ) ){
			echo '<span style="color:red;">' . $details['videos']->get_error_message() . '</span>';	
		}else{	
			printf( __('Playlist contains %d videos.', 'yvi_video'), $details['page_info']['total_results'] );
		}
		die();
	}
	
	/**
	 * AJAX Callback
	 * 
	 * Bulk imports YouTube thumbnails on list table screens.
	 * @todo - when importing, the thumbnails array has changed, make the plugin work accordingly
	 */
	public function callback_bulk_import_thumbnails(){
		$action = $this->__get_action_data('bulk_import_thumbnails');
		check_ajax_referer( $action['nonce']['action'], $action['nonce']['name'] );
		
		global $YVI_POST_TYPE;
		if( !current_user_can( 'edit_posts' ) ){
			wp_die( -1 );
		}
		
		if( !isset( $_REQUEST['action'] ) && !isset( $_REQUEST['action2'] ) ){
			wp_send_json_error( __('Sorry, there was an error, please try again.', 'yvi_video') );
		}
		
		if( !isset( $_REQUEST['post'] ) || empty( $_REQUEST['post'] ) ){
			wp_send_json_error( __('<strong>Error!</strong> Select some posts to import thumbnails for.', 'yvi_video') );
		}
		
		if( !isset( $_REQUEST['post_type'] ) || $YVI_POST_TYPE->get_post_type() != $_REQUEST['post_type'] ){
			wp_send_json_error( __('Thumbnail imports work only for custom post type.', 'yvi_video') );
		}
		
		$action = false;
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			$action = $_REQUEST['action'];
	
		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			$action = $_REQUEST['action2'];
		
		if( !$action || !array_key_exists( $action, yvi_actions() ) ){
			wp_send_json_error( __('Please select a valid action.', 'yvi_video') );
		}	
		
		// increase time limit
		@set_time_limit( 300 );
		
		/**
		 * Action that runs before importing thumbnails.
		 * Useful to remove actions and filters of third party plugins.
		 */
		do_action( 'yvi_before_thumbnails_bulk_import' );
		
		$post_ids = array_map( 'intval', $_REQUEST['post'] );
		foreach( $post_ids as $post_id ){			
			switch( $action ){
				case 'yvi_thumbnail':
					yvi_set_featured_image( $post_id );		
				break;	
			}		
		}		
		wp_send_json_success( __('All thumbnails successfully imported.', 'yvi_video') );		
		die();
	}
	
	/**
	 * Manual bulk import AJAX callback
	 */
	public function video_bulk_import(){
		// import videos
		$response = array(
			'success' 	=> false,
			'error'		=> false
		);
		
		$ajax_data = $this->__get_action_data( 'manual_video_bulk_import' );
		
		if( isset( $_POST[ $ajax_data['nonce']['name'] ] ) ){
			if( check_ajax_referer( $ajax_data['nonce']['action'], $ajax_data['nonce']['name'], false ) ){				
				if( 'import' == $_POST['action_top'] || 'import' == $_POST['action2'] ){
					
					// increase time limit
					@set_time_limit( 300 );
					
					/**
					 * Action that runs before importing videos.
					 * Useful to remove actions and filters of third party plugins.
					 */
					do_action( 'yvi_before_manual_bulk_import' );
					
					$result = $this->import_videos();
					
					if( is_wp_error( $result ) ){
						$response['error'] = $result->get_error_message();
					}else if( $result ){					
						$response['success'] = sprintf( 
							__('<strong>%d videos:</strong> %d imported; %d not found; %d skipped (already imported)', 'yvi_video'), 
							$result['total'],
							$result['imported'],
							$result['not_found'],
							$result['skipped']
						);
					}else{
						$response['error'] = __('No videos selected for importing. Please select some videos by checking the checkboxes next to video title.', 'yvi_video');
					}													
				}else{
					$response['error'] = __('Please select an action.', 'yvi_video');
				}			
			}else{
				$response['error'] = __("Cheatin' uh?", 'yvi_video');
			}	
		}else{
			$response['error'] = __("Cheatin' uh?", 'yvi_video');
		}	
		
		echo json_encode( $response );
		die();	
	}
	
	/**
	 * Helper for $this->video_bulk_import(). Will import all videos passed by user with the AJAX call.
	 * Import videos to WordPress
	 */	
	private function import_videos(){
		if( !isset( $_POST['yvi_import'] ) || !$_POST['yvi_import'] ){
			return false;
		}
		
		//get options
		$options = yvi_get_settings();
		// check if importing for theme
		$theme_import = false;
		if( isset( $_POST['yvi_theme_import'] ) ){
			$theme_import = yvi_check_theme_support();
		}		
		// set post type and taxonomy
		if( $theme_import ){
			$post_type = $theme_import['post_type']; // set post type
			$taxonomy = (!$theme_import['taxonomy'] && 'post' == $theme_import['post_type']) ?
						'category' : // if taxonomy is false and is post type post, set it to category
						$theme_import['taxonomy'];
			$post_format = 	isset( $theme_import['post_format'] ) && $theme_import['post_format'] ? 
							$theme_import['post_format'] :
							'video'; 	
		}else{
			// should imports be made as regular posts?
			$as_post = import_as_post();
			$post_type = $as_post ? 'post' : $this->post_type;
			$taxonomy = $as_post ? 'category' : $this->taxonomy;
			$post_format = 'video';	
		}
		
		// set category
		$category = false;
		if( isset( $_REQUEST['cat_top'] ) && 'import' == $_REQUEST['action_top'] ){
			$category = $_REQUEST['cat_top'];
		}elseif ( isset($_REQUEST['cat2']) && 'import' == $_REQUEST['action2']){
			$category = $_REQUEST['cat2'];
		}
		// reset category if not set correctly		
		if( -1 == $category || 0 == $category ){
			$category = false;
		}
		
		// prepare array of video IDs
		$video_ids = array_reverse( (array)$_POST['yvi_import'] );
		// stores after import results
		$result = array(
			'imported' 	=> 0,
			'skipped' 	=> 0,
			'not_found' => 0,
			'total'		=> count( $video_ids )
		);
		
		// set post status
		$statuses 	= array('publish', 'draft', 'pending' );
		$status 	= in_array( $options['import_status'], $statuses ) ? $options['import_status'] : 'draft';
		
		// set user
		$user = false;
		if( isset( $_REQUEST['user_top'] ) && $_REQUEST['user_top'] ){
			$user = (int)$_REQUEST['user_top'];
		}else if( isset( $_REQUEST['user2'] ) && $_REQUEST['user2'] ){
			$user = (int)$_REQUEST['user2'];
		}
		if( $user ){
			$user_data = get_userdata( $user );
			$user = !$user_data ? false : $user_data->ID;			
		}
		
		$videos = yvi_yt_api_get_videos( $video_ids );
		if( is_wp_error( $videos ) ){
			return $videos;
		}
				
		foreach( $videos as $video ){
			
			// search if video already exists
			$posts = get_posts(array(
				'post_type' 	=> $post_type,
				'meta_key'		=> '__yvi_video_id',
				'meta_value' 	=> $video['video_id'],
				'post_status' 	=> array('publish', 'pending', 'draft', 'future', 'private')
			));
			
			// video already exists, don't do anything
			if( $posts ){
				$result['skipped'] += 1;
				continue;
			}

			$video_id = $video['video_id'];
			if( isset( $_POST['yvi_title'][ $video_id ] ) ){
				$video['title'] = $_POST['yvi_title'][ $video_id ];
			}
			if( isset( $_POST['yvi_text'][ $video_id ] ) ){
				$video['description'] = $_POST['yvi_text'][ $video_id ];
			}
			
			$r = $this->import_video( array(
				'video' 			=> $video, // video details retrieved from YouTube
				'category' 			=> $category, // category name (if any); if false, it will create categories from YouTube
				'post_type' 		=> $post_type, // what post type to import as
				'taxonomy' 			=> $taxonomy, // what taxonomy should be used
				'user'				=> $user, // save as a given user if any
				'post_format'		=> $post_format, // post format will default to video
				'status'			=> $status, // post status
				'theme_import'		=> $theme_import // to check in callbacks if importing as theme post
			) );	
			if( $r ){
				$result['imported'] += 1;						
			}
		}

		return $result;
	}
	
	/**
	 * AJAX Callback
	 * Import post thumbnail
	 */
	public function import_post_thumbnail(){
		if( !isset( $_POST['id'] ) ){
			die();
		}
		
		$post_id = absint( $_POST['id'] );
		$thumbnail = yvi_set_featured_image( $post_id );
		
		if( !$thumbnail ){
			die();
		}
		
		$response = _wp_post_thumbnail_html( $thumbnail['attachment_id'], $thumbnail['post_id'] );
		wp_send_json_success( $response );
		
		die();
	}
	
	/**
	 * Callback function to change manual bulk import view from grid to list and viceversa
	 */
	public function change_import_view(){
		$action = $this->__get_action_data('import_view');
		check_ajax_referer( $action['nonce']['action'], $action['nonce']['name'] );
		
		$view = 'grid';
		if( isset( $_POST['view'] ) ){
			$view = 'list' == $_POST['view'] ? 'list' : 'grid';
		}
		
		$uid = get_current_user_id();
		if( $uid ){
			update_user_option( $uid, 'yvi_video_import_view', $view );
		}
		
		die();
	}
	
	/**
	 * Stores all ajax actions references.
	 * This is where all ajax actions are added.
	 */	
	private function __actions(){
		$actions = array(
			/**
			 * Query for playlist details. Used on automatic playlists to list statistics about playlists
			 */
			'playlist_query' 	=> array(
				'action' 		=> 'yvi_check_playlist',
				'callback' 		=> array( $this, 'callback_query_playlist' ),
				'nonce' 		=> array(
					'name' 		=> 'yvi-ajax-nonce',
					'action' 	=> 'yvi-playlist-query'
				)
			),
			/**
			 * Bulk import YouTube thumbnails
			 */
			'bulk_import_thumbnails' => array(
				'action' 	=> 'yvi_thumbnail',
				'callback' 	=> array( $this, 'callback_bulk_import_thumbnails' ),
				'nonce' 	=> array(
					'name' 		=> '_wpnonce',
					'action' 	=> 'bulk-posts'
				)
			),
			/**
			 * Manual bulk import video AJAX callback
			 */
			'manual_video_bulk_import' => array(
				'action' => 'yvi_import_videos',
				'callback' => array( $this, 'video_bulk_import' ),
				'nonce' => array(
					'name' 		=> 'yvi_import_nonce',
					'action' 	=> 'yvi-import-videos-to-wp'	
				)
			),
			/**
			 * Post thumbnail import
			 */
			'import_post_thumbnail' => array(
				'action' => 'yvi_import_video_thumbnail',
				'callback' => array( $this, 'import_post_thumbnail' ),
				'nonce' => array(
					'name' => 'yvi_nonce',
					'action' => 'yvi-thumbnail-post-import'
				)
			),
			
			'import_view' => array(
				'action' => 'yvi_import_list_view',
				'callback' => array( $this, 'change_import_view' ),
				'nonce' => array(
					'name' => 'yvi_nonce',
					'action' => 'yvi-change-manual-import-list-view'
				) 
			)
			
		);
		
		return $actions;
	}
	
	/**
	 * Gets all details of a given action from registered actions
	 * @param string $key
	 */
	protected function __get_action_data( $key ){
		$actions = $this->__actions();
		if( array_key_exists( $key, $actions ) ){
			return $actions[ $key ];
		}else{
			trigger_error( sprintf( __( 'Action %s not found.'), $key ), E_USER_WARNING);
		}
	}	
}