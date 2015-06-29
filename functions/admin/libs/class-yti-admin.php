<?php
if( !class_exists( 'YVI_AJAX_Actions' ) ){
	require_once YVI_PATH . 'functions/admin/libs/class-yti-admin-ajax.php';
}

class YVI_Admin extends YVI_AJAX_Actions{
	
	/**
	 * Stores help screens info
	 * @var array
	 */
	private $help_screens 	= array();
	
	/**
	 * Constructor, calls parent::__construct() and sets up all hooks and 
	 * filters needed for the plugin functionality.
	 */
	public function __construct(){
		// fire up parent
		parent::__construct();
		
		// help screens
		add_filter('contextual_help', array( $this, 'contextual_help' ), 10, 3);
		
		// add extra menu pages
		add_action( 'admin_menu', array( $this, 'menu_pages' ), 1 );
		
		// create edit meta boxes
		add_action( 'admin_head', array( $this, 'add_meta_boxes' ) );
		// post thumbnails
		add_filter('admin_post_thumbnail_html', array( $this, 'post_thumbnail_meta_panel' ), 10, 2);
		// enqueue scripts/styles on post edit screen
		add_action( 'admin_enqueue_scripts', array( $this, 'post_edit_assets' ) );
		
		// save data from meta boxes
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action('load-post-new.php', array($this, 'post_new_onload'));
		
		// for empty imported posts, skip $maybe_empty verification
		add_filter('wp_insert_post_empty_content', array( $this, 'force_empty_insert' ), 999, 2);
		
		// add columns to posts table
		add_filter( 'manage_edit-' . $this->post_type . '_columns', array( $this, 'extra_columns' ) );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'output_extra_columns' ), 10, 2 );
		
		// alert if setting to import as post type post by default is set on all plugin pages
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		// mechanism to remove the alert above
		add_action( 'admin_init', array( $this, 'dismiss_post_type_notice' ) );
		
		// add tinyMCE buttons to allow easy shortcode management by tinyMCE plugin
		add_action( 'admin_head', array( $this, 'tinymce' ) );
		
		// enqueue scripts/styles on post edit screen to allow video options editing
		add_action( 'admin_print_styles-post.php', array( $this, 'post_edit_styles' ) );
		add_action( 'admin_print_styles-post-new.php', array( $this, 'post_edit_styles' ) );
		
		// Bulk actions hack - remove when issue on creating new bulk actions is solved in WP
		add_action( 'admin_print_scripts-edit.php', array( $this, 'bulk_actions_hack' ) );
		
		// enqueue scripts in WP Widgets page to implement the video widget functionality
		add_action('admin_print_scripts-widgets.php', array( $this, 'widgets_scripts' ) );
	}
	
	/**
	 * Display contextual help on plugin pages
	 */
	public function contextual_help( $contextual_help, $screen_id, $screen ){
		// if not hooks page, return default contextual help
		if( !is_array( $this->help_screens ) || !array_key_exists( $screen_id, $this->help_screens )){
			return $contextual_help;
		}
		
		// current screen help screens
		$help_screens = $this->help_screens[$screen_id];
		
		// create help tabs
		foreach( $help_screens as $help_screen ){		
			$screen->add_help_tab( $help_screen );
		}
	}
	
	/**
	 * Add subpages on our custom post type
	 */
	public function menu_pages(){
		// add to post type video menu
		$parent_slug = 'edit.php?post_type=' . $this->post_type;
		
		// bulk manual import menu page
		$video_import = add_submenu_page(
			$parent_slug, 
			__('YVI Single Import', 'yvi_video'), 
			__('YVI Single Import', 'yvi_video'), 
			'edit_posts', 
			'yvi_import',
			array( $this, 'import_page' )
		);
		add_action( 'load-' . $video_import, 		array( $this, 'video_import_onload' ) );
		
		// automatic import menu page
		$automatic_import = add_submenu_page(
			$parent_slug, 
			__('Automatic YouTube video import', 'yvi_video'),
			__('YVI Auto Import', 'yvi_video'),
			'edit_posts', 
			'yvi_auto_import',
			array( $this, 'automatic_import_page' )
		);	
		add_action( 'load-' . $automatic_import, 	array( $this, 'playlists_onload' ) );	
		
		// automatic import menu page
		$youtube_account = add_submenu_page(
			$parent_slug, 
			__('My YouTube', 'yvi_video'),
			__('My YouTube', 'yvi_video'),
			'edit_posts', 
			'yvi_my_youtube',
			array( $this, 'yt_account_page' )
		);	
		add_action( 'load-' . $youtube_account, 	array( $this, 'yt_account_page_onload' ) );	
		
		// plugin settings menu page
		$settings = add_submenu_page(
			$parent_slug, 
			__('YVI Settings', 'yvi_video'), 
			__('YVI Settings', 'yvi_video'), 
			'manage_options', 
			'yvi_settings',
			array( $this, 'plugin_settings' )
		);
		add_action( 'load-' . $settings, 			array( $this, 'plugin_settings_onload' ) );
		
		// help and info menu page
		$compatibility = add_submenu_page(
			$parent_slug,
			__('YVImporter', 'yvi_video'),
			__('YVImporter', 'yvi_video'),
			'manage_options',
			'yvi_help',
			array( $this, 'page_help' )
		);	
		add_action( 'load-' . $compatibility, 	array( $this, 'plugin_help_onload' ) );
		
		// video list page
		$videos_list = add_submenu_page(
			null,
			__('Videos', 'yvi_video'), 
			__('Videos', 'yvi_video'), 
			'edit_posts', 
			'yvi_videos',
			array( $this, 'videos_list' )
		);	
		add_action( 'load-' . $videos_list, 		array( $this, 'video_list_onload' ) );
		
		// set up automatic import help screen
		$this->help_screens[ $automatic_import ] = array( 
			array(
				'id'		=> 'yvi_automatic_import_overview',
				'title'		=> __( 'Overview', 'yvi_video' ),
				'content'	=> yvi_get_contextual_help('automatic-import-overview')
			),
			array(
				'id'		=> 'yvi_automatic_import_frequency',
				'title'		=> __('Import frequency', 'yvi_video'),
				'content'	=> yvi_get_contextual_help('automatic-import-frequency')
			),
			array(
				'id'		=> 'yvi_automatic_import_as_post',
				'title'		=> __('Import videos as posts', 'yvi_video'),
				'content'	=> yvi_get_contextual_help('automatic-import-as-post')
			)
		);	
	}
	
	/**
	 * Menu page onLoad callback
	 * Prepares scripts/classes for manual bulk import page.
	 */
	public function video_import_onload(){
		
		$this->video_import_assets();
		
		// search videos result
		if( isset( $_GET['yvi_search_nonce'] ) ){
			if( check_admin_referer( 'yvi-video-import', 'yvi_search_nonce' ) ){				
				require_once YVI_PATH.'/functions/admin/libs/video-list-yti.php';	
				$this->list_table = new YVI_Video_List();		
			}
		}
		
		// import videos / alternative to AJAX import
		if( isset( $_REQUEST['yvi_import_nonce'] ) ){
			if( check_admin_referer('yvi-import-videos-to-wp', 'yvi_import_nonce') ){				
				if( 'import' == $_REQUEST['action_top'] || 'import' == $_REQUEST['action2'] ){
					$this->import_videos();										
				}
				$options = yvi_get_settings();
				wp_redirect('edit.php?post_status='.$options['import_status'].'&post_type='.$this->post_type);
				exit();
			}
		}			
	}
	
	/**
	 * Menu page callback.
	 * Outputs the manual bulk import page.
	 */
	public function import_page(){		
?>
<div class="wrap">
	<div class="icon32 icon32-posts-video" id="icon-edit"><br></div>
	<h2>
		<?php _e('Import videos', 'yvi_video')?>
		<?php if( isset( $this->list_table ) ):?>
		<a class="add-new-h2" id="yvi-new-search" href="#">New Search</a>
		<?php endif;?>
	</h2>
		<?php 
		if( !isset( $this->list_table ) ){		
			require_once YVI_PATH . 'yti_v/import_videos.php';
		}else{
			$this->list_table->prepare_items();
			// get ajax call details
			$data = parent::__get_action_data( 'manual_video_bulk_import' );			
		?>
	<?php if( yvi_debug() ):?>
		<div class="updated"><p><?php do_action('yvi-manual-import-admin-message');?></p></div>		
	<?php endif;?>
	
	<div id="search_box" class="hide-if-js">
		<?php include_once YVI_PATH . '/yti_v/import_videos.php';?>
	</div>
	
	<form method="post" action="" class="ajax-submit">
		<?php wp_nonce_field( $data['nonce']['action'], $data['nonce']['name'] );?>
		<input type="hidden" name="action" class="yvi_ajax_action" value="<?php echo $data['action'];?>" />
		<input type="hidden" name="yvi_source" value="youtube" />
		<?php 
			// import as theme posts - compatibility layer for deTube WP theme
			if( isset( $_REQUEST['yvi_theme_import'] ) ):
		?>
		<input type="hidden" name="yvi_theme_import" value="1" />
		<?php endif;// end of condition for compatibility layer for themes?>
		
		<?php $this->list_table->display();?>
	</form>	
		<?php 	
		}
		?>
</div>		
<?php 	
	}
	
	/**
	 * Enqueue scripts and styles needed on import page
	 */
	private function video_import_assets(){
		// video import form functionality
		wp_enqueue_script(
			'yvi-video-search-js', 
			YVI_URL.'assets/adminpenal/js/video-import.js', 
			array('jquery'), 
			'1.0'
		);
		wp_localize_script('yvi-video-search-js', 'yvi_importMessages', array(
			'loading' => __('Importing, please wait...', 'yvi_video'),
			'wait'	=> __("Not done yet, still importing. You'll have to wait a bit longer.", 'yvi_video'),
			'server_error' => __('There was an error while importing your videos. The process was not successfully completed. Please try again. <a href="#" id="yvi_import_error">See error</a>', 'yvi_video')
		));
		
		// change view details
		$view = $this->__get_action_data( 'import_view' );
		$data = array( 'action' => $view['action'] );
		$data[ $view['nonce']['name'] ] = wp_create_nonce( $view['nonce']['action'] );
		wp_localize_script( 'yvi-video-search-js' , 'yvi_view_data', $data );
		
		wp_enqueue_style(
			'yvi-video-search-css',
			YVI_URL.'assets/adminpenal/css/video-import.css',
			array(),
			'1.0'
		);

	}
	
	/**
	 * Menu page onLoad callback
	 * Prepares scripts/classes for automatic import playlists.
	 */
	public function playlists_onload(){
		
		$action = false;
		if( isset( $_GET['action'] ) ){
			$action = $_GET['action'];
		}else if( isset( $_POST['action'] ) || isset( $_POST['action2'] ) ){
			$action = ( isset( $_POST['action'] ) && -1 != $_POST['action'] ) ? $_POST['action'] : $_POST['action2'];
		}		
		
		if( !$action || -1 == $action ){
			require_once YVI_PATH . 'functions/admin/libs/playlists-list-table.php';
			$this->playlists_table = new YVI_Playlists_List_Table();

			wp_enqueue_script('yvi-timer', YVI_URL.'assets/adminpenal/js/timer.js', array('jquery'));
			wp_enqueue_script('yvi-playlists-table', YVI_URL.'assets/adminpenal/js/auto_import.js', array('jquery'));
			wp_enqueue_style('yvi_playlists-table', YVI_URL.'assets/adminpenal/css/auto_import.css');
			
			$page = menu_page_url('yvi_auto_import', false);
			$settings = yvi_get_settings();
			
			$message = sprintf('<a class="button" href="%s">%s</a><br />', $page, __('Update now!'));				
			
			wp_localize_script('yvi-timer', 'yvi_timer', array(
				'ready' => $message
			));
			
			return;
		}
		
		// set up a variable to hold any errors
		$this->playlist_errors = false;
				
		switch( $action ){
			// reset playlist action
			case 'reset':
				if( isset( $_GET['_wpnonce'] ) ){
					if( wp_verify_nonce( $_GET['_wpnonce'] ) ){
						$post_id = (int)$_GET['id'];
						$meta = get_post_meta( $post_id, $this->playlist_meta, true );
						if( $meta ){
							$meta['updated'] 	= false;
							$meta['imported'] 	= 0;
							$meta['processed']	= 0;
							
							unset( $meta['first_video'] );
							unset( $meta['last_video'] );
							unset( $meta['finished'] );
							unset( $meta['page_token'] );
							unset( $meta['error'] );
							
							update_post_meta($post_id, $this->playlist_meta, $meta);	
						}					
					}
				}
				
				$r = add_query_arg(
					array(
						'post_type' => $this->post_type,
						'page'		=> 'yvi_auto_import'
					), 'edit.php'
				);
				wp_redirect( $r );
				die();
			break;
			// bulk start/stop importing from playlists
			case 'stop-import':
			case 'start-import':	
				if( wp_verify_nonce( $_POST['yvi_nonce'], 'yvi_playlist_table_actions' ) ){
					if( isset( $_POST['yvi_playlist'] ) ){
						$playlists = (array)$_POST['yvi_playlist'];
						
						$status = 'stop-import' == $action ? 'draft' : 'publish';						
						foreach( $playlists as $playlist_id ){							
							wp_update_post( array(
								'ID' 			=> $playlist_id,
								'post_status' 	=> $status
							));

							$meta = get_post_meta( $playlist_id, $this->playlist_meta, true );
							if( $meta && isset( $meta['error'] ) ){
								unset( $meta['error'] );
								update_post_meta( $playlist_id, $this->playlist_meta, $meta );								
							}							
						}	
					}
				}
				$r = add_query_arg(
					array(
						'post_type' => $this->post_type,
						'page'		=> 'yvi_auto_import'
					), 'edit.php'
				);
				wp_redirect( $r );
				die();
			break;	
			// change playlist status action
			case 'queue':
				if( isset( $_GET['_wpnonce'] ) ){
					if( wp_verify_nonce( $_GET['_wpnonce'] ) ){
						$post_id = (int)$_GET['id'];
						$post = get_post( $post_id );
						if( $post && $this->playlist_type == $post->post_type ){
							$status = 'draft' == $post->post_status ? 'publish' : 'draft';
							wp_update_post( array(
								'ID' 			=> $post_id,
								'post_status' 	=> $status
							));	
							
							$meta = get_post_meta( $post_id, $this->playlist_meta, true );
							if( $meta && isset( $meta['error'] ) ){
								unset( $meta['error'] );
								update_post_meta( $post_id, $this->playlist_meta, $meta );								
							}
							
						}					
					}
				}
				
				$r = add_query_arg(
					array(
						'post_type' => $this->post_type,
						'page'		=> 'yvi_auto_import',
					), 'edit.php'
				);
				wp_redirect( $r );
				die();
			break;	
			// delete playlist	
			case 'delete':
				if( isset( $_POST['yvi_nonce'] ) ){
					if( wp_verify_nonce( $_POST['yvi_nonce'], 'yvi_playlist_table_actions' ) ){
						if( isset( $_POST['yvi_playlist'] ) ){
							$playlists = (array)$_POST['yvi_playlist'];
							foreach( $playlists as $playlist_id ){
								wp_delete_post( $playlist_id, true );
							}	
						}
					}
					$r = add_query_arg(
						array(
							'post_type' => $this->post_type,
							'page'		=> 'yvi_auto_import'
						), 'edit.php'
					);
					wp_redirect( $r );
					die();
				}else if( isset( $_GET['_wpnonce'] ) ){
					if( wp_verify_nonce( $_GET['_wpnonce'] ) ){
						$post_id = (int)$_GET['id'];
						wp_delete_post( $post_id, true );
					}
					$r = add_query_arg(
						array(
							'post_type' => $this->post_type,
							'page'		=> 'yvi_auto_import'
						), 'edit.php'
					);
					wp_redirect( $r );
					die();
				}
			break;	
			// create playlist
			case 'add_new':
				if( isset( $_POST['yvi_wp_nonce'] ) ){
					if( check_admin_referer('yvi-save-playlist', 'yvi_wp_nonce') ){
						
						$defaults = yvi_playlist_settings_defaults();
						foreach( $defaults as $var => $val ){
							if( is_string($val) && empty( $_POST[$var] ) ){
								$this->playlist_errors = new WP_Error();
								$this->playlist_errors->add('yvi_fill_all', __('Please fill all required fields marked with *.', 'yvi_video'));
								break;
							}
						}
						
						if( is_wp_error( $this->playlist_errors ) ){
							return;
						}
						
						$post_id = wp_insert_post(array(
							'post_title' 	=> $_POST['post_title'],
							'post_type' 	=> $this->playlist_type,
							'post_status' 	=> isset( $_POST['playlist_live'] ) ? 'publish' : 'draft'
						));
						
						$meta = array(
							'type' 			=> $_POST['playlist_type'],
							'id'			=> $_POST['playlist_id'],
							'theme_import' 	=> isset( $_POST['theme_import'] ),
							'native_tax'	=> isset( $_POST['native_tax'] ) ? (int)$_POST['native_tax'] : -1,
							'theme_tax'		=> isset( $_POST['theme_tax'] ) ? (int)$_POST['theme_tax'] : -1,
							'import_user'	=> isset( $_POST['import_user'] ) && $_POST['import_user'] ? (int)$_POST['import_user'] : get_current_user_id(),
							'start_date'	=> isset( $_POST['start_date'] ) ? $_POST['start_date'] : false,
							'no_reiterate'  => isset( $_POST['no_reiterate'] ),
							'updated' 		=> false,
							'total'			=> 0,
							'imported'		=> 0,
							'processed'		=> 0
						);
						
						if( $post_id ){
							update_post_meta($post_id, $this->playlist_meta, $meta);
						}
						
						
						$r = add_query_arg(
							array(
								'post_type' => $this->post_type,
								'page' 		=> 'yvi_auto_import',
								'action'	=> 'edit',
								'id'		=> $post_id
							),'edit.php'
						);
						
						wp_redirect( $r );
						die();						
					}
				}else{
					wp_enqueue_script(
						'yvi-playlist-manage',
						YVI_URL . 'assets/adminpenal/js/playlist-edit.js',
						array('jquery') 
					);
					wp_enqueue_style(
						'yvi-playlist-manage',
						YVI_URL . 'assets/adminpenal/css/playlist-edit.css'
					);
					wp_localize_script( 'yvi-playlist-manage' , 'yvi_pq', array(
						'loading' 		=> __( 'Making query, please wait...', 'yvi_video' ),
						'still_loading' => __( 'Not done yet, be patient...', 'yvi_video' )
					));	
				}
			break;
			// edit playlist
			case 'edit':
				if( isset( $_POST['yvi_wp_nonce'] ) ){
					if( check_admin_referer('yvi-save-playlist', 'yvi_wp_nonce') ){
						$defaults = yvi_playlist_settings_defaults();
						foreach( $defaults as $var => $val ){
							if( is_string($val) && empty( $_POST[$var] ) ){
								$this->playlist_errors = new WP_Error();
								$this->playlist_errors->add('yvi_fill_all', __('Please fill all required fields marked with *.', 'yvi_video'));
								break;
							}
						}
						
						if( is_wp_error( $this->playlist_errors ) ){
							return;
						}
						
						$post_id = (int)$_GET['id'];

						wp_update_post(array(
							'ID' => $post_id,
							'post_title' => $_POST['post_title'],
							'post_status' 	=> isset( $_POST['playlist_live'] ) ? 'publish' : 'draft'
						));
						
						$o_meta = get_post_meta( $post_id, $this->playlist_meta, true );
						
						$meta = array(
							'type' 			=> $_POST['playlist_type'],
							'id'			=> $_POST['playlist_id'],
							'theme_import' 	=> isset( $_POST['theme_import'] ),
							'native_tax'	=> isset( $_POST['native_tax'] ) ? (int)$_POST['native_tax'] : -1,
							'theme_tax'		=> isset( $_POST['theme_tax'] ) ? (int)$_POST['theme_tax'] : -1,
							'import_user'	=> isset( $_POST['import_user'] ) && $_POST['import_user'] ? (int)$_POST['import_user'] : get_current_user_id(),
							'start_date'	=> isset( $_POST['start_date'] ) ? $_POST['start_date'] : false,
							'no_reiterate'	=> isset( $_POST['no_reiterate'] ),
							'updated' 		=> $o_meta['updated'],
							'total'			=> $o_meta['total'],
							'imported'		=> $o_meta['imported'],
							'processed'		=> $o_meta['processed']
						);
						
						update_post_meta($post_id, $this->playlist_meta, $meta);
						
						$r = add_query_arg(
							array(
								'post_type' => $this->post_type,
								'page' 		=> 'yvi_auto_import',
								'action'	=> 'edit',
								'id'		=> $post_id
							),'edit.php'
						);
						
						wp_redirect( $r );
						die();						
					}
				}else{
					wp_enqueue_script(
						'yvi-playlist-manage',
						YVI_URL . 'assets/adminpenal/js/playlist-edit.js',
						array('jquery') 
					);
					wp_enqueue_style(
						'yvi-playlist-manage',
						YVI_URL . 'assets/adminpenal/css/playlist-edit.css'
					);
					wp_localize_script( 'yvi-playlist-manage' , 'yvi_pq', array(
						'loading' 		=> __( 'Making query, please wait...', 'yvi_video' ),
						'still_loading' => __( 'Not done yet, be patient...', 'yvi_video' )
					));					
				}
			break;	
			case 'export_playlists':
				//*
				header("Content-type: text/json");
				header("Content-Disposition: attachment; filename=playlists_export.json");
				header("Pragma: no-cache");
				header("Expires: 0");
				//*/
			
				// get all playlists
				$args = array(
					'post_type' 	=> $this->get_playlist_post_type(),
					'post_status' 	=> 'any',
					'orderby' 		=> 'ID',
					'order'			=> 'ASC',
					'numberposts'	=> -1
				);	
				$playlists = get_posts( $args );
				
				if( !$playlists || is_wp_error( $playlists ) ){
					_e( 'While trying to export the playlists, an error occured. Please try again.', 'yvi_video' );
					die();			
				}
				
				$output = array();
				
				foreach( $playlists as $playlist ){
					
					$meta = get_post_meta( 
						$playlist->ID,
						$this->get_playlist_meta_name(), 
						true 
					);
					if( !$meta ){
						continue;
					}
					
					$output[] = array(
						'post_title' 	=> esc_attr( $playlist->post_title ),
						'post_status' 	=> $playlist->post_status,
						'type'			=> $meta['type'],
						'id'			=> $meta['id'],
						'theme_import' 	=> $meta['theme_import'],
						'native_tax'	=> $meta['native_tax'],
						'theme_tax'		=> $meta['theme_tax'],
						'import_user'	=> $meta['import_user'],
						'start_date'	=> $meta['start_date'],
						'no_reiterate'	=> $meta['no_reiterate']
					);					
				}
				
				echo json_encode( $output );				
				die();
				
			break;

			case 'import_playlists':
				check_admin_referer( 'yvi_import_playlists', 'yvi_pu_nonce' );
				
				if( !isset( $_FILES['yvi_playlists_json'] ) || empty( $_FILES['yvi_playlists_json']['tmp_name'] ) ){
					$html = __('Please select a file to upload.', 'yci_video');
					$html.= '</p><p>' . '<a href="' . menu_page_url( 'yci_auto_import' , false ) . '">' . __('Go back', 'yvi_video') . '</a>' . '</p>';
					wp_die( $html );					
				}
				
				add_filter( 'upload_mimes' , array( $this, 'upload_mimes' ) );
				
				$uploadedfile 		= $_FILES['yvi_playlists_json'];
				$upload_overrides 	= array( 'test_form' => false );
				$movefile 			= wp_handle_upload( $uploadedfile, $upload_overrides );
				
				if( $movefile && !isset( $movefile['error'] ) ){
					$content = wp_remote_retrieve_body( wp_remote_get( $movefile['url'] ) );
					
					$args = array(
						'post_type' 	=> $this->get_playlist_post_type(),
						'post_status' 	=> 'any',
						'orderby' 		=> 'ID',
						'order'			=> 'ASC',
						'numberposts'	=> -1
					);
					$playlists = get_posts( $args );
					$yt_ids = array();
					if( $playlists ){
						foreach( $playlists as $playlist ){
							$meta = get_post_meta( $playlist->ID, $this->get_playlist_meta_name(), true );
							if( $meta ){
								$yt_ids[] = $meta['id'];
							}					
						}						
					}
					
					if( $content ){
						$content = json_decode( $content, true );
						foreach( $content as $list ){
							if( !isset( $list['id'] ) || in_array( $list['id'] ,  $yt_ids ) ){
								continue;
							}				
							
							$data = array(
								'post_type' => $this->get_playlist_post_type(),
								'post_status' => $list['post_status'],
								'post_title' => $list['post_title']
							);
							$post_id = wp_insert_post( $data );
							
							if( !is_wp_error( $post_id ) ){
								$meta = array(
									'type'			=> $list['type'],
									'id'			=> $list['id'],
									'theme_import' 	=> $list['theme_import'],
									'native_tax'	=> $list['native_tax'],
									'theme_tax'		=> $list['theme_tax'],
									'import_user'	=> $list['import_user'],
									'start_date'	=> $list['start_date'],
									'no_reiterate'	=> $list['no_reiterate'],
									'updated'		=> '',
									'total'			=> 0,
									'imported'		=> 0,
									'processed'		=> 0,
									'errors'		=> false,
									'page_token'	=> '',
									'first_video'	=> ''
								);
								update_post_meta( $post_id , $this->get_playlist_meta_name(), $meta );		
							}							
						}						
					}					
				}else{
					$html = $movefile['error'];
					$html.= '</p><p>' . '<a href="' . menu_page_url( 'yvi_auto_import' , false ) . '">' . __('Go back', 'yvi_video') . '</a>' . '</p>';
					wp_die( $html );
				}
				
				$r = add_query_arg(
					array(
						'post_type' => $this->post_type,
						'page'		=> 'yvi_auto_import'
					), 'edit.php'
				);
				wp_redirect( $r );
				
				die();
			break;
		}		
	}
	
	/**
	 * Add additional mime types for uploading
	 * @param array $mime_types
	 */
	public function upload_mimes( $mime_types ){
		$mime_types['json'] = 'text/json';
		return $mime_types;
	}
	
	/**
	 * Menu page callback.
	 * Outputs the automatic import page.
	 */
	public function automatic_import_page(){
		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;
		
		switch( $action ){
			case 'add_new':
				$title = __('Add new playlist', 'yvi_video');
				$options = yvi_playlist_settings_defaults();
				
				if( is_wp_error( $this->playlist_errors ) ){
					$error = $this->playlist_errors->get_error_message();
				}
				
				if( isset( $_GET['feed_type'] ) ){
					$options['playlist_type'] = $_GET['feed_type'];				
				}
				if( isset( $_GET['list_id'] ) ){
					$options['playlist_id'] = $_GET['list_id'];
				}
				if( isset( $_GET['title'] ) ){
					$options['post_title'] = esc_attr( urldecode( $_GET['title'] ) );					
				}
				
				$form_action = menu_page_url('yvi_auto_import', false).'&action=add_new';
				require YVI_PATH.'yti_v/manage_playlist.php';
			break;
			case 'edit':
				$post_id 	= (int)$_GET['id'];
				$post 		= get_post( $post_id );
				$meta 		= get_post_meta($post_id, $this->playlist_meta, true);
				
				$options = array(
					'post_title' 	=> $post->post_title,
					'playlist_type' => $meta['type'],
					'playlist_id'	=> $meta['id'],
					'playlist_live'	=> 'publish' == $post->post_status,
					'theme_import'	=> $meta['theme_import'],
					'native_tax'	=> isset( $meta['native_tax'] ) ? $meta['native_tax'] : false,
					'theme_tax'		=> isset( $meta['theme_tax'] ) ? $meta['theme_tax'] : false,
					'import_user'	=> isset( $meta['import_user'] ) ? $meta['import_user'] : -1,
					'start_date'	=> isset( $meta['start_date'] ) ? $meta['start_date'] : false,
					'no_reiterate'  => isset( $meta['no_reiterate'] ) ? $meta['no_reiterate'] : false
				);
				
				$title = sprintf( __( 'Edit playlist <em>%s</em>', 'yvi_video' ), $post->post_title );				
				
				$form_action = menu_page_url('yvi_auto_import', false).'&action=edit&id='.$post_id;
				
				$add_new_url 	= menu_page_url( 'yvi_auto_import', false ) . '&action=add_new';
				$add_new_link 	= sprintf( '<a href="%1$s" title="%2$s" class="add-new-h2">%2$s</a>', $add_new_url, __( 'Add new', 'yvi_video' ) );
				
				require YVI_PATH.'yti_v/manage_playlist.php';
			break;	
		}
		// if action is set, don't show the list of playlists
		if( $action ){
			wp_enqueue_script( 'jquery-ui-datepicker' );			
			wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );			
			return;
		}		
		
		$this->playlists_table->prepare_items();
?>		
<div class="wrap">
	<div class="icon32 icon32-posts-video" id="icon-edit"><br></div>
	<h2>
		<?php _e('YVI Auto Import', 'yvi_video')?>
		<a class="add-new-h2" href="<?php menu_page_url('yvi_auto_import');?>&action=add_new"><?php _e('Add New', 'yvi_video');?></a>
		<a class="add-new-h2" href="<?php menu_page_url('yvi_auto_import');?>&action=export_playlists"><?php _e('Export playlists', 'yvi_video');?></a>
		<a class="add-new-h2" href="#" id="yvi_playlist_import_trigger"><?php _e('Import playlists', 'yvi_video');?></a>		
	</h2>
	<div id="yvi_import_playlists" class="hide-if-js">
		<form method="post" action="<?php menu_page_url( 'yvi_auto_import' );?>&action=import_playlists" enctype="multipart/form-data">
			<label for="yvi_playlists_json"><?php _e('Upload export file', 'yvi_video');?>: </label>
			<input type="file" id="yvi_playlists_json" name="yvi_playlists_json" />
			<?php wp_nonce_field( 'yvi_import_playlists', 'yvi_pu_nonce' );?>
			<?php submit_button( __( 'Upload', 'yvi_video' ), 'primary', 'submit', false );?>
		</form>
	</div>
	<?php if( yvi_debug() ): global $YVI_AUTOMATIC_IMPORT;?>
	<div class="message updated">
		<p>
			<strong><?php _e('Debug information', 'yvi_video');?></strong>
			<ul>
			<?php foreach ($YVI_AUTOMATIC_IMPORT->get_update() as $k=>$v):?>
				<li><strong><?php echo $k;?></strong>: <?php echo $v;?></li>
			<?php endforeach;?>
			</ul>
			<strong><?php _e('Import errors', 'yvi_video');?></strong>
			<ul>
			<?php foreach( $YVI_AUTOMATIC_IMPORT->get_errors() as $error ):?>
				<li><?php echo $error;?></li>
			<?php endforeach;?>	
			</ul>
		</p>
	</div>
	<?php endif;?>	
	<?php yvi_automatic_update_message( '<div class="message updated"><p>', '</p></div>', true );?>		
	<form method="post" action="">
		<?php wp_nonce_field('yvi_playlist_table_actions', 'yvi_nonce');?>
		<?php $this->playlists_table->views();?>
		<?php $this->playlists_table->display();?>
	</form>	
		
</div>
<?php 			
	}
	
	public function yt_account_page_onload(){
		require_once YVI_PATH . '/functions/admin/libs/channels-list-table.php';
		$this->my_yt_table = new YVI_Channels_List_Table();		
	}
	
	public function yt_account_page(){
		
		$this->my_yt_table->prepare_items();
?>		
<div class="wrap">
	<div class="icon32 icon32-posts-video" id="icon-edit"><br></div>
	<h2>
		<?php _e('My YouTube Account', 'yvi_video')?>
		<a class="add-new-h2" href="<?php menu_page_url( 'yvi_settings' );?>#yvi-settings-auth-options"><?php _e( 'Setup OAuth', 'yvi_video' );?></a>		
	</h2>
	<form method="post" action="">
		<?php wp_nonce_field('yvi_channels_table_actions', 'yvi_nonce');?>
		<?php $this->my_yt_table->views();?>
		<?php $this->my_yt_table->display();?>
	</form>		
</div>
<?php
	}
	
	
	
	/**
	 * Menu page onLoad callback.
	 * Processes plugin settings and saves them.
	 */
	public function plugin_settings_onload(){
		
		$redirect = false;
		$tab = false;
		
		if( isset( $_POST['yvi_wp_nonce'] ) ){
			if( check_admin_referer('yvi-save-plugin-settings', 'yvi_wp_nonce') ){
				yvi_update_settings();
				yvi_update_player_settings();
				if( isset( $_POST['envato_purchase_code'] ) && !empty( $_POST['envato_purchase_code'] ) ){
					update_option('_yvi_yt_plugin_envato_licence', $_POST['envato_purchase_code']);
				}
				if( isset( $_POST['youtube_api_key'] ) ){
					yvi_update_api_key( $_POST['youtube_api_key'] );
				}
				if( isset( $_POST['oauth_client_id'] ) && isset( $_POST['oauth_client_secret'] ) ){
					yvi_update_yt_oauth( $_POST['oauth_client_id'], $_POST['oauth_client_secret'] );					
				}			
			}
			$redirect = true;			
		}
		
		if( isset( $_GET['unset_token'] ) && 'true' == $_GET['unset_token'] ){
			if( check_admin_referer( 'yvi-revoke-oauth-token', 'yvi_nonce' ) ){
				$tokens 	= yvi_get_yt_oauth_details();				
				$endpoint 	= 'https://accounts.google.com/o/oauth2/revoke?token=' . $tokens['token']['value'];
				$response = wp_remote_post( $endpoint );				
				yvi_update_yt_oauth( false, false, '' );
			}
			$redirect = true;
			$tab = '#yvi-settings-auth-options';					
		}
		
		if( $redirect ){
			wp_redirect( html_entity_decode( menu_page_url( 'yvi_settings', false ) ) . $tab );
			die();			
		}
		
		wp_enqueue_style(
			'yvi-plugin-settings',
			YVI_URL.'assets/adminpenal/css/plugin-settings.css',
			false
		);
		
		wp_enqueue_script(
			'yvi-options-tabs',
			YVI_URL.'assets/adminpenal/js/tabs.js',
			array('jquery', 'jquery-ui-tabs')
		);
		
		wp_enqueue_script(
			'yvi-video-edit',
			YVI_URL.'assets/adminpenal/js/video-edit.js',
			array('jquery'),
			'1.0'
		);			
	}
	
	/**
	 * Menu page callback.
	 * Outputs the plugin settings page.
	 */
	public function plugin_settings(){
		$options 			= yvi_get_settings();
		$player_opt 		= yvi_get_player_settings();
		$envato_licence 	= get_option('_yvi_yt_plugin_envato_licence', '');
		$youtube_api_key 	= yvi_get_yt_api_key();
		$oauth_opt 			= yvi_get_yt_oauth_details();
		$form_action		= html_entity_decode( menu_page_url( 'yvi_settings', false ) );
		
		// view
		include YVI_PATH . 'yti_v/yvi_settings.php';
	}
	
	/**
	 * Menu page onLoad callback
	 * Enqueue assets for compatibility page
	 */
	public function plugin_help_onload(){
		wp_enqueue_style(
			'yvi-admin-compat-style',
			YVI_URL.'assets/adminpenal/css/help-page.css'
		);		
	}
	
	/**
	 * Menu page callback
	 * Outputs the compatibility page
	 */
	public function page_help(){
		$themes = yvi_get_compatible_themes();
		$theme 	= yvi_check_theme_support();
		
		if( $theme ){
			$key = array_search($theme, $themes);
			if( $key ){
				$themes[$key]['active'] = true;				
			}	
		}
		
		$installed_themes = wp_get_themes( array( 'allowed' => true ) );
		foreach( $installed_themes as $t ){
			$name = strtolower( $t->Name );
			if( array_key_exists( $name, $themes ) && !isset( $themes[ $name ]['active'] ) ){
				$themes[ $name ]['installed'] = true;				
			}
		}
		
		if( !class_exists( 'YVI_Shortcodes' ) ){
			include_once YVI_PATH . 'functions/libs/shortcodes-yti.php';			
		}
		$shortcodes_obj = new YVI_Shortcodes();
		
		// view
		include YVI_PATH.'/yti_v/yvi_help.php';		
	}
	
	/**
	 * Video list is a modal page used for various actions that implie using videos.
	 * Should have no header and should be set as iframe.
	 */
	public function video_list_onload(){
		$_GET['noheader'] = 'true';
		if( !defined('IFRAME_REQUEST') ){
			define('IFRAME_REQUEST', true);
		}
		
		if( isset( $_GET['_wp_http_referer'] ) ){
			wp_redirect( 
				remove_query_arg( 
					array(
						'_wp_http_referer', 
						'_wpnonce',
						'volume',
						'width',
						'aspect_ratio',
						'autoplay',
						'controls',
						'yvi_video',
						'filter_videos'
					), 
					stripslashes( $_SERVER['REQUEST_URI'] ) 
				) 
			);			
		}		
	}
	
	/**
	 * Video list is a modal page used for various actions that implie using videos.
	 */
	function videos_list(){
		
		_wp_admin_html_begin();
		printf('<title>%s</title>', __('Video list', 'yvi_video'));		
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'ie' );
		wp_enqueue_script( 'utils' );
		wp_enqueue_style('buttons');
		
		wp_enqueue_style(
			'yvi-video-list-modal', 
			YVI_URL.'assets/adminpenal/css/video-list-modal.css', 
			false, 
			'1.0'
		);
		
		wp_enqueue_script(
			'yvi-video-list-modal',
			YVI_URL.'assets/adminpenal/js/video-list-modal.js',
			array('jquery'),
			'1.0'	
		);
		
		do_action('admin_print_styles');
		do_action('admin_print_scripts');
		do_action('yvi_video_list_modal_print_scripts');
		echo '</head>';
		echo '<body class="wp-core-ui">';
		
		
		require YVI_PATH . 'functions/admin/libs/video-list-table-yti.php';
		$table = new YVI_Video_List_Table();
		$table->prepare_items();
		
		$post_type = $this->post_type;
		if( isset($_GET['pt']) && 'post' == $_GET['pt'] ){
			$post_type = 'post';
		}
		
		?>
		<div class="wrap">
			<form method="get" action="" id="yvi-video-list-form">
				<input type="hidden" name="pt" value="<?php echo $post_type;?>" />
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'];?>" />
				<?php $table->views();?>
				<?php $table->search_box( __('Search', 'yvi_video'), 'video' );?>
				<?php $table->display();?>
			</form>
			<div id="yvi-shortcode-atts"></div>
		</div>	
		<?php
		
		echo '</body>';
		echo '</html>';
		die();
	}
	
	/**
	 * admin_head callback
	 * Add meta boxes on video post type
	 */
	public function add_meta_boxes(){
		
		global $post;
		if( !$post ){
			return;
		}
		
		// add meta boxes to video posts, either default post type is imported as such or video post type	
		if( $this->is_video() ){
			add_meta_box(
				'yvi-video-settings', 
				__( 'Video settings', 'yvi_video' ),
				array( $this, 'post_video_settings_meta_box' ),
				$post->post_type,
				'normal',
				'high'
			);
			
			add_meta_box(
				'yvi-show-video', 
				__( 'Live video', 'yvi_video' ),
				array( $this, 'post_show_video_meta_box' ),
				$post->post_type,
				'normal',
				'high'
			);	
			
		}else{ // for all other post types add only the shortcode embed panel
			add_meta_box(
				'yvi-add-video', 
				__( 'Video shortcode', 'yvi_video' ), 
				array( $this, 'post_shortcode_meta_box' ),
				$post->post_type,
				'side'
			);	
		}		
	}
	
	/**
	 * Meta box callback. 
	 * Displays video settings when editing posts.
	 */
	public function post_video_settings_meta_box(){
		global $post;		
		$settings = yvii_get_video_settings( $post->ID );		
		include_once YVI_PATH . 'yti_v/metabox-post-video-settings.php';		
	}
	
	/**
	 * Meta box callback.
	 * Display live video meta box when editing posts
	 */
	public function post_show_video_meta_box(){
		global $post;
		$video_id 	= get_post_meta( $post->ID, '__yvi_video_id', true );
		$video_data = get_post_meta( $post->ID, '__yvi_video_data', true );
?>	
<script language="javascript">
;(function($){
	$(document).ready(function(){
		$('#yvii-video-preview').YVII_VideoPlayer({
			'video_id' 	: '<?php echo $video_data['video_id'];?>',
			'source'	: 'youtube'
		});
	})
})(jQuery);
</script>
<div id="yvii-video-preview" style="height:315px; width:560px; max-width:100%;"></div>		
<?php	
	}
	
	/**
	 * Meta box callback
	 * Post add shortcode meta box output
	 */
	public function post_shortcode_meta_box(){
		?>
		<p><?php _e('Add video/playlist into post.', 'yvi_video');?><p>
		<a class="button" href="#" id="yvi-shortcode-2-post" title="<?php esc_attr_e( 'Add shortcode', 'yvi_video' );?>"><?php _e( 'Add video shortcode', 'yvi_video' );?></a>
		<?php	
	}
	
	/**
	 * admin_scripts callback
	 * Add scripts to custom post edit page
	 * @param string $hook
	 */
	public function post_edit_assets( $hook ){
		if( 'post.php' !== $hook ){
			return;
		}
		global $post;
		
		// check for video id to see if it was imported using the plugin
		$video_id = get_post_meta( $post->ID, '__yvi_video_id', true );		
		if( !$video_id ){
			return;
		}
		
		// some files are needed only on custom post type edit page
		if( $this->is_video() ){		
			// add video player for video preview on post
			yvii_enqueue_player();
			wp_enqueue_script(
				'yvi-video-edit',
				YVI_URL.'assets/adminpenal/js/video-edit.js',
				array('jquery'),
				'1.0'
			);	
		}
		
		// video thumbnail functionality
		wp_enqueue_script(
			'yvi-video-thumbnail',
			YVI_URL.'assets/adminpenal/js/video-thumbnail.js',
			array('jquery'),
			'1.0'
		);

		wp_localize_script( 'yvi-video-thumbnail', 'YVI_POST_DATA', array( 'post_id' => $post->ID ) );
	}
	
	/**
	 * Manipulate output for featured image on custom post 
	 * to allow importing of thumbnail as featured image
	 */
	public function post_thumbnail_meta_panel( $content, $post_id ){
		$post = get_post( $post_id );
		
		if( !$post ){
			return $content;
		}
		
		$video_id = get_post_meta( $post->ID, '__yvi_video_id', true );		
		if( !$video_id ){
			return $content;
		}
		
		$content .= sprintf( '<a href="#" id="yvi-import-video-thumbnail" class="button primary">%s</a>', __('Import YouTube thumbnail', 'yvi_video') );		
		return $content;
	}
	
	/**
	 * save_post callback
	 * Save post data from meta boxes. Hooked to save_post
	 */
	public function save_post( $post_id, $post ){
		if( !isset( $_POST['yvi-video-nonce'] ) ){
			return;
		}
		
		// check if post is the correct type		
		if( !$this->is_video() ){
			return;
		}
		// check if user can edit
		if( !current_user_can('edit_post', $post_id) ){
			return;
		}
		// check nonce
		check_admin_referer('yvi-save-video-settings', 'yvi-video-nonce');		
		yvii_update_video_settings( $post_id );	
	}
	
	/**
	 * New post load action for videos.
	 * Will first display a form to query for the video.
	 */
	public function post_new_onload(){
		if( !isset( $_REQUEST['post_type'] ) || $this->post_type !== $_REQUEST['post_type'] ){
			return;
		}
		
		/**
		 * Filter that can be used to prevent the plugin from overriding the single video import process.
		 * This is useful in rare cases when WP theme uses the same post type as the plugin.
		 * @var bool
		 */
		$allow_plugin_import = apply_filters( 'yvi_allow_single_video_import' , true );
		if( !$allow_plugin_import ){
			return;			
		}
		// store video details
		$this->video_post = false;
		
		if( isset( $_POST['wp_nonce'] ) ){
			if( check_admin_referer('yvi_query_new_video', 'wp_nonce') ){
				
				$video_id = sanitize_text_field( $_POST['yvi_video_id'] );
				$video = yvi_yt_api_get_video( $video_id );
				if( $video && !is_wp_error( $video ) ){
					$this->video_post 	= $video;
					
					// apply filters on title and description
					$import_in_theme = isset( $_POST['single_theme_import'] ) && $_POST['single_theme_import'] ? yvi_check_theme_support() : array();
					$this->video_post['description'] 	= apply_filters('yvi_video_post_content', $this->video_post['description'], $this->video_post, $import_in_theme);
					$this->video_post['title'] 			= apply_filters('yvi_video_post_title', $this->video_post['title'], $this->video_post, $import_in_theme);
					// single post import date
					$import_options = yvi_get_settings();
					$post_date 		= $import_options['import_date'] ? date('Y-m-d H:i:s', strtotime( $this->video_post['published'] )) : current_time( 'mysql' );
					$this->video_post['post_date'] = apply_filters( 'yvi_video_post_date', $post_date, $this->video_post, $import_in_theme );
					
					add_filter('default_content', array( $this, 'default_content' ), 999, 2);
					add_filter('default_title', array( $this, 'default_title' ), 999, 2);
					add_filter('default_excerpt', array( $this, 'default_excerpt' ), 999, 2);
					
					// add video player for video preview on post
					yvii_enqueue_player();	
				}else{
					$message = __('Video not found.', 'yvi_video');
					if( is_wp_error( $video ) ){
						$message = sprintf( __( 'An error occured while trying to query YouTube API.<br />Error: %s (code: %s)', 'yvi_video' ), $video->get_error_message(), $video->get_error_code() );
					}
					global $YVI_NEW_VIDEO_NOTICE;
					$YVI_NEW_VIDEO_NOTICE = $message;
					add_action( 'all_admin_notices', array( $this, 'new_post_error_notice' ) );					
				}				
			}else{
				wp_die('Cheatin uh?');
			}
		}
		// if video query not started, display the form
		if( !$this->video_post ){
			wp_enqueue_script(
				'yvi-new-video-js',
				YVI_URL.'assets/adminpenal/js/video-new.js',
				array('jquery'),
				'1.0'
			);
			
			$post_type_object = get_post_type_object( $this->post_type );
			$title = $post_type_object->labels->add_new_item;
			
			include ABSPATH .'wp-admin/admin-header.php';
			include YVI_PATH.'yti_v/new_video.php';
			include ABSPATH .'wp-admin/admin-footer.php';
			die();
		}
	}
	
	/**
	 * Callback function that displays admin error message when importing single videos.
	 * Action is set in function $this->post_new_onload()
	 */
	public function new_post_error_notice(){
		global $YVI_NEW_VIDEO_NOTICE;
		if( $YVI_NEW_VIDEO_NOTICE ){
			echo '<div class="error"><p>' . $YVI_NEW_VIDEO_NOTICE . '</p></div>';
		}
	}
	
	/**
	 * Set video description on new post
	 * @param string $post_content
	 * @param object $post
	 */
	public function default_content( $post_content, $post ){
		if( !isset( $this->video_post ) || !$this->video_post ){
			return;
		}
		
		return $this->video_post['description'];	
	}
	
	/**
	 * Set video title on new post
	 * @param string $post_title
	 * @param object $post
	 */
	public function default_title( $post_title, $post ){
		if( !isset( $this->video_post ) || !$this->video_post ){
			return;
		}
		
		return $this->video_post['title'];		
	}
	
	/**
	 * Set video excerpt on new post, add taxonomies and save meta
	 * @param string $post_excerpt
	 * @param object $post
	 */
	public function default_excerpt( $post_excerpt, $post ){
		if( !isset( $this->video_post ) || !$this->video_post ){
			return;
		}
		// set video ID on post meta
		update_post_meta( $post->ID, '__yvi_video_id', $this->video_post['video_id'] );
		// needed by other plugins
		update_post_meta( $post->ID, '__yvi_video_url', 'https://www.youtube.com/watch?v=' . $this->video_post['video_id'] );
		// save video data on post
		update_post_meta( $post->ID, '__yvi_video_data', $this->video_post );
		
		// import video thumbnail as featured image
		$settings = yvi_get_settings();
		if( import_image_on( 'post_create' ) ){
			// import featured image
			yvi_set_featured_image( $post->ID, $this->video_post );
		}

		if( isset( $settings['import_date'] ) && $settings['import_date'] ){
			$postarr = array(
				'ID' => $post->ID,
				'post_date_gmt' => $this->video_post['post_date'],
				'edit_date'		=> $this->video_post['post_date'],
				'post_date'		=> $this->video_post['post_date']
			);
			wp_update_post( $postarr );
		}
		
		// check if video should be imported as theme post
		$theme_import 	= isset( $_POST['single_theme_import'] ) ? yvi_check_theme_support() : array();
		// action on post insert that allows setting of different meta on post
		do_action( 'yvi_before_post_insert', $this->video_post, $theme_import );
		
		if( $theme_import && isset( $_POST['single_theme_import'] ) ){			
			$cat_id = wp_create_category( $this->video_post['category'] );
			$postarr = array(
				'ID' 			=> $post->ID,
				'post_type' 	=> $theme_import['post_type'],
				'post_content' 	=> $this->video_post['description'],
				'post_title'	=> $this->video_post['title'],
				'post_status'	=> 'draft'
				
			);
			wp_update_post($postarr);
			
			if( $cat_id ){
				wp_set_post_categories( $post->ID, array( $cat_id ) );
			}
			
			if( isset( $theme_import['post_format'] ) && $theme_import['post_format']  ){
				set_post_format( $post->ID, $theme_import['post_format'] );
			}
			
			$url 		= 'https://www.youtube.com/watch?v='.$this->video_post['video_id'];
			$thumb	 	= end( $this->video_post['thumbnails']);
			$thumbnail  = $thumb['url'];
			
			// player settings
			$ps = yvi_get_player_settings();
			$customize = implode('&', array(
				'controls='.$ps['controls'],
				'autohide='.$ps['autohide'],
				'fs='.$ps['fs'],
				'theme='.$ps['theme'],
				'color='.$ps['color'],
				'iv_load_policy='.$ps['iv_load_policy'],
				'modestbranding='.$ps['modestbranding'],
				'rel='.$ps['rel'],
				'showinfo='.$ps['showinfo'],
				'autoplay='.$ps['autoplay']
			));
			
			$embed_code = '<iframe width="'.$ps['width'].'" height="'.yvi_player_height($ps['aspect_ratio'], $ps['width']).'" src="https://www.youtube.com/embed/'.$this->video_post['video_id'].'?'.$customize.'" frameborder="0" allowfullscreen></iframe>';
			
			foreach( $theme_import['post_meta'] as $k => $meta_key ){
				switch( $k ){
					case 'url' :
						update_post_meta($post->ID, $meta_key, $url);
					break;	
					case 'thumbnail':
						update_post_meta( $post->ID, $meta_key, $thumbnail );
					break;
					case 'embed':
						update_post_meta( $post->ID, $meta_key, $embed_code );
					break;	
				}							
			}
			
			$redirect = add_query_arg(array(
				'post' 		=> $post->ID,
				'action' 	=> 'edit'				
			), 'post.php');			
						
		}else{	// process video as plugin custom post type	
			
			$plugin_post_type 	= $this->post_type;
			$plugin_taxonomy 	= $this->taxonomy;
			
			// if imported as regular post, set a few things on the post
			if( import_as_post() ){
				$plugin_post_type 	= 'post';
				$plugin_taxonomy 	= 'category';
				
				$postarr = array(
					'ID' 			=> $post->ID,
					'post_type' 	=> 'post',
					'post_content' 	=> $this->video_post['description'],
					'post_title'	=> $this->video_post['title'],
					'post_status'	=> 'draft'					
				);				
				wp_update_post($postarr);
				
				update_post_meta($post->ID, '__yvi_is_video', true);
			}
			
			// check if category exists
			$term = term_exists( $this->video_post['category'], $plugin_taxonomy );
			if( 0 == $term || null == $term ){
				// create the category
				$term = wp_insert_term( $this->video_post['category'], $plugin_taxonomy );
			}		
			// add category to video
			wp_set_post_terms( $post->ID, array( $term['term_id'] ), $plugin_taxonomy );
			
			// on default imports, set post format to video
			set_post_format( $post->ID, 'video' );
			
			if( import_as_post() ){			
				$redirect = add_query_arg(array(
					'post' 		=> $post->ID,
					'action' 	=> 'edit'				
				), 'post.php');
			}			
		}

		// action on post insert that allows setting of different meta on post
		// consistent with action on bulk import
		do_action('yvi_post_insert', $post->ID, $this->video_post, $theme_import, $post->post_type);
		if( isset( $redirect ) ){
			wp_redirect($redirect);
			die();
		}		
	}
	
	/**
	 * When trying to insert an empty post, WP is running a filter. Given the fact that
	 * users are allowed to insert empty posts when importing, the filter will return 
	 * false on maybe_empty to allow insertion of video. 
	 * 
	 * @param bool $maybe_empty
	 * @param array $postarr
	 */
	public function force_empty_insert( $maybe_empty, $postarr ){
		if( $this->post_type == $postarr['post_type'] ){
			return false;
		}
	}
	
	/**
	 * Extra columns in videos list table
	 * @param array $columns
	 */
	public function extra_columns( $columns ){		
		
		$cols = array();
		foreach( $columns as $c => $t ){
			$cols[$c] = $t;
			if( 'title' == $c ){
				$cols['video_id'] = __('Video ID', 'yvi_video');
				$cols['duration'] = __('Duration', 'yvi_video');	
			}	
		}		
		return $cols;
	}
	
	/**
	 * Extra columns in videos list table output
	 * @param string $column_name
	 * @param int $post_id
	 */
	public function output_extra_columns( $column_name, $post_id ){
		
		switch( $column_name ){
			case 'video_id':
				echo get_post_meta( $post_id, '__yvi_video_id', true );
			break;
			case 'duration':
				$meta = get_post_meta( $post_id, '__yvi_video_data', true );
				
				if( !$meta ){
					echo '-';						
				}else{				
					echo yvi_human_time($meta['duration']);
				}	
			break;	
		}
			
	}
	
	/**
	 * Display an alert to user when he chose to import videos by default as regular posts
	 */
	public function admin_notices(){
		if( !is_admin() || !current_user_can('manage_options') ){
			return;			
		}
		global $pagenow;
		if( !'edit.php' == $pagenow || !isset( $_GET['post_type']) || $this->post_type != $_GET['post_type'] ){
			return;
		}
		
		// alert user to insert his YouTube API Key.
		$api_key = yvi_get_yt_api_key();
		if( empty( $api_key ) ){
			?>
<div class="error">
	<p>
		<?php _e( 'You must enter your YouTube API key In order to be able to import YouTube videos using the plugin', 'yvi_video' );?><br />
		<?php _e( 'Please navigate to plugin <strong>Settings</strong> page, tab <strong>API</strong> and enter your <strong>YouTube API key</strong>.', 'yvi_video' );?>		
	</p>
	<p><a class="button" href="<?php menu_page_url( 'yvi_settings', true );?>#content5"><?php _e( 'Plugin Settings', 'yvi_video' );?></a></p>
</div>
		<?php 
			// stop all other messages if API key is missing
			return;
		}// close if
		
		// alert user that he is importing YouTube videos as regular post type
		if( import_as_post() ){
			global $current_user;
			$user_id = $current_user->ID;
			if( !get_user_meta($user_id, 'yvi_ignore_post_type_notice', true) ){
				echo '<div class="updated"><p>';
				$theme_support = yvi_check_theme_support();
				
				printf(__('Please note that you have chosen to import videos as <strong>regular posts</strong> instead of post type <strong>%s</strong>.', 'yvi_video'), $this->post_type);
				echo '<br />' . ( $theme_support ? __('Videos can be imported as regular posts compatible with the plugin or as posts compatible with your theme.', 'yvi_video') : __('Videos will be imported as regular posts.', 'yvi_video') );
							
				$url = add_query_arg(array(
					'yvi_dismiss_post_type_notice' => 1
				), $_SERVER['REQUEST_URI']);
				
				printf(' <a class="button button-small" href="%s">%s</a>', $url, __('Dismiss', 'yvi_video'));
				echo '</p></div>';			
			}
		}

		$settings = yvi_get_settings();
		if( isset( $settings['show_quota_estimates'] ) && $settings['show_quota_estimates'] ){
			echo '<div class="updated"><p>';
			yvi_yt_quota_message();
			echo '</p></div>';
		}
		
	}
	
	/**
	 * Dismiss regular post import notice
	 */
	public function dismiss_post_type_notice(){
		if( !is_admin() ){
			return;
		}
		
		if( isset( $_GET['yvi_dismiss_post_type_notice'] ) && 1 == $_GET['yvi_dismiss_post_type_notice'] ){
			global $current_user;
			$user_id = $current_user->ID;
			add_user_meta($user_id, 'yvi_ignore_post_type_notice', true);
		}
	}
	
	/**
	 * Add tinyce buttons to easily embed video playlists
	 */
	public function tinymce(){
		// Don't bother doing this stuff if the current user lacks permissions
		if ( ! current_user_can('edit_posts') && ! current_user_can( 'edit_pages' ) )
			return;
	 	
		// Don't load unless is post editing (includes post, page and any custom posts set)
		$screen = get_current_screen();
		if( 'post' != $screen->base || $this->is_video() ){
			return;
		}  
		
		// Add only in Rich Editor mode
		if ( get_user_option('rich_editing') == 'true') {
	   		
			wp_enqueue_script(array(
				'jquery-ui-dialog'
			));
				
			wp_enqueue_style(array(
				'wp-jquery-ui-dialog'
			));
	   	
		    add_filter('mce_external_plugins', array( $this, 'tinymce_plugin' ) );
		    add_filter('mce_buttons', array( $this, 'register_buttons' ) );
	   }
	}
	
	/**
	 * Register tinymce plugin
	 * @param array $plugin_array
	 */
	public function tinymce_plugin( $plugin_array ){
		$plugin_array['yvii_shortcode'] = YVI_URL . 'assets/adminpenal/js/tinymce/shortcode.js';
		return $plugin_array;
	}
	
	/**
	 * Register tinymce buttons
	 * @param array $buttons
	 */
	public function register_buttons( $buttons ){
		array_push( $buttons, 'separator', 'yvii_shortcode' );
		return $buttons;
	}
	
	/**
	 * Load styling on post edit screen
	 */
	public function post_edit_styles(){
		global $post;
		if( !$post || $this->is_video( $post ) ){
			return;
		}
		
		wp_enqueue_style(
			'yvii-shortcode-modal',
			YVI_URL.'assets/adminpenal/css/shortcode-modal.css',
			false,
			'1.0'
		);
		
		wp_enqueue_script(
			'yvii-shortcode-modal',
			YVI_URL.'assets/adminpenal/js/shortcode-modal.js',
			false,
			'1.0'
		);
		
		$messages = array(
			'playlist_title' => __('Videos in playlist', 'yvi_video'),
			'no_videos'		 => __('No videos selected.<br />To create a playlist check some videos from the list on the right.', 'yvi_video'),
			'deleteItem'	 => __('Delete from playlist', 'yvi_video'),
			'insert_playlist'=> __('Add shortcode into post', 'yvi_video')
		);
		
		wp_localize_script('yvii-shortcode-modal', 'YVI_SHORTCODE_MODAL', $messages);
	}	
	
	/**
	 * A hack to add new bulk actions and to process them by JS.
	 */
	public function bulk_actions_hack(){
		if( !isset( $_GET['post_type'] ) || $this->post_type != $_GET['post_type'] ){
			return;
		}
		
		wp_enqueue_script(
			'yvi-bulk-actions',
			YVI_URL.'assets/adminpenal/js/bulk-actions.js',
			array('jquery'),
			'1.0'
		);
		
		wp_enqueue_style(
			'yvi-bulk-actions-response',
			YVI_URL.'assets/adminpenal/css/video-list.css',
			false,
			'1.0'
		);
		
		wp_localize_script(
			'yvi-bulk-actions', 
			'yvi_bulk_actions', 
			array(
				'actions' 		=> yvi_actions(),
				'wait'			=> __('Processing, please wait...', 'yvi_video'),
				'wait_longer'	=> __('Not done yet, please be patient...', 'yvi_video'),
				'maybe_error' 	=> __('There was an error while importing your thumbnails. Please try again.', 'yvi_video')
			)	
		);
	}
	
	/**
	 * Enqueue some scripts on WP widgets page
	 */
	public function widgets_scripts(){
		$plugin_settings = yvi_get_settings();
		if( isset( $plugin_settings['public'] ) && !$plugin_settings['public'] ){
			return;
		}
		
		wp_enqueue_script(
			'yvi-video-edit',
			YVI_URL . 'assets/adminpenal/js/video-edit.js',
			array( 'jquery' ),
			'1.0'
		);
	}
}