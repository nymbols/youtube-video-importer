<?php

class YTI_Admin extends YTI_AJAX_Actions{
	
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
			$screen->add_help_tab($help_screen);		
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
			__('Import videos', 'yti_video'), 
			__('Import videos', 'yti_video'), 
			'edit_posts', 
			'yti_import',
			array( $this, 'import_page' )
		);
		add_action( 'load-' . $video_import, 		array( $this, 'video_import_onload' ) );
		
		// automatic import menu page
		$automatic_import = add_submenu_page(
			$parent_slug, 
			__('Automatic YouTube video import', 'yti_video'),
			__('Automatic import', 'yti_video'),
			'edit_posts', 
			'yti_auto_import',
			array( $this, 'automatic_import_page' )
		);	
		add_action( 'load-' . $automatic_import, 	array( $this, 'playlists_onload' ) );	
		
		// plugin settings menu page
		$settings = add_submenu_page(
			$parent_slug, 
			__('Settings', 'yti_video'), 
			__('Settings', 'yti_video'), 
			'manage_options', 
			'yti_settings',
			array( $this, 'plugin_settings' )
		);
		add_action( 'load-' . $settings, 			array( $this, 'plugin_settings_onload' ) );
		
		// help and info menu page
		$compatibility = add_submenu_page(
			$parent_slug,
			__('Info &amp; Help', 'yti_video'),
			__('Info &amp; Help', 'yti_video'),
			'manage_options',
			'yti_help',
			array( $this, 'page_help' )
		);	
		add_action( 'load-' . $compatibility, 	array( $this, 'plugin_help_onload' ) );
		
		// video list page
		$videos_list = add_submenu_page(
			null,
			__('Videos', 'yti_video'), 
			__('Videos', 'yti_video'), 
			'edit_posts', 
			'yti_videos',
			array( $this, 'videos_list' )
		);	
		add_action( 'load-' . $videos_list, 		array( $this, 'video_list_onload' ) );
		
		// set up automatic import help screen
		$this->help_screens[ $automatic_import ] = array( 
			array(
				'id'		=> 'yti_automatic_import_overview',
				'title'		=> __( 'Overview', 'yti_video' ),
				'content'	=> yti_get_contextual_help('automatic-import-overview')
			),
			array(
				'id'		=> 'yti_automatic_import_frequency',
				'title'		=> __('Import frequency', 'yti_video'),
				'content'	=> yti_get_contextual_help('automatic-import-frequency')
			),
			array(
				'id'		=> 'yti_automatic_import_as_post',
				'title'		=> __('Import videos as posts', 'yti_video'),
				'content'	=> yti_get_contextual_help('automatic-import-as-post')
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
		if( isset( $_GET['yti_search_nonce'] ) ){
			if( check_admin_referer( 'yti-video-import', 'yti_search_nonce' ) ){				
				require_once YTI_PATH.'/includes/admin/libs/video-list.class.php';	
				$this->list_table = new YTI_Video_List();		
			}
		}
		
		// import videos / alternative to AJAX import
		if( isset( $_REQUEST['yti_import_nonce'] ) ){
			if( check_admin_referer('yti-import-videos-to-wp', 'yti_import_nonce') ){				
				if( 'import' == $_REQUEST['action_top'] || 'import' == $_REQUEST['action2'] ){
					$this->import_videos();										
				}
				$options = yti_get_settings();
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
		<?php _e('Import videos', 'yti_video')?>
		<?php if( isset( $this->list_table ) ):?>
		<a class="add-new-h2" id="yti-new-search" href="#">New Search</a>
		<?php endif;?>
	</h2>
		<?php 
		if( !isset( $this->list_table ) ){		
			require_once YTI_PATH . 'views/import_videos.php';
		}else{
			$this->list_table->prepare_items();
			// get ajax call details
			$data = parent::__get_action_data( 'manual_video_bulk_import' );			
		?>
	<?php if( yti_debug() ):?>
		<div class="updated"><p><?php do_action('yti-manual-import-admin-message');?></p></div>		
	<?php endif;?>
	
	<div id="search_box" class="hide-if-js">
		<?php include_once YTI_PATH . '/views/import_videos.php';?>
	</div>
	
	<form method="post" action="" class="ajax-submit">
		<?php wp_nonce_field( $data['nonce']['action'], $data['nonce']['name'] );?>
		<input type="hidden" name="action" class="yti_ajax_action" value="<?php echo $data['action'];?>" />
		<input type="hidden" name="yti_source" value="youtube" />
		<?php 
			// import as theme posts - compatibility layer for deTube WP theme
			if( isset( $_REQUEST['yti_theme_import'] ) ):
		?>
		<input type="hidden" name="yti_theme_import" value="1" />
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
			'yti-video-search-js', 
			YTI_URL.'assets/back-end/js/video-import.js', 
			array('jquery'), 
			'1.0'
		);
		wp_localize_script('yti-video-search-js', 'yti_importMessages', array(
			'loading' => __('Importing, please wait...', 'yti_video'),
			'wait'	=> __("Not done yet, still importing. You'll have to wait a bit longer.", 'yti_video'),
			'server_error' => __('There was an error while importing your videos. The process was not successfully completed. Please try again. <a href="#" id="yti_import_error">See error</a>', 'yti_video')
		));
		
		// change view details
		$view = $this->__get_action_data( 'import_view' );
		$data = array( 'action' => $view['action'] );
		$data[ $view['nonce']['name'] ] = wp_create_nonce( $view['nonce']['action'] );
		wp_localize_script( 'yti-video-search-js' , 'yti_view_data', $data );
		
		wp_enqueue_style(
			'yti-video-search-css',
			YTI_URL.'assets/back-end/css/video-import.css',
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
			require_once YTI_PATH . 'includes/admin/libs/playlists-list-table.class.php';
			$this->playlists_table = new YTI_Playlists_List_Table();

			wp_enqueue_script('yti-timer', YTI_URL.'assets/back-end/js/timer.js', array('jquery'));
			wp_enqueue_script('yti-playlists-table', YTI_URL.'assets/back-end/js/auto_import.js', array('jquery'));
			wp_enqueue_style('yti_playlists-table', YTI_URL.'assets/back-end/css/auto_import.css');
			
			$page = menu_page_url('yti_auto_import', false);
			$settings = yti_get_settings();
			
			$message = sprintf('<a class="button" href="%s">%s</a><br />', $page, __('Update now!'));				
			
			wp_localize_script('yti-timer', 'yti_timer', array(
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
						'page'		=> 'yti_auto_import'
					), 'edit.php'
				);
				wp_redirect( $r );
				
			break;
			// bulk start/stop importing from playlists
			case 'stop-import':
			case 'start-import':	
				if( wp_verify_nonce( $_POST['yti_nonce'], 'yti_playlist_table_actions' ) ){
					if( isset( $_POST['yti_playlist'] ) ){
						$playlists = (array)$_POST['yti_playlist'];
						
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
						'page'		=> 'yti_auto_import'
					), 'edit.php'
				);
				wp_redirect( $r );
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
						'page'		=> 'yti_auto_import',
					), 'edit.php'
				);
				wp_redirect( $r );
			break;	
			// delete playlist	
			case 'delete':
				if( isset( $_POST['yti_nonce'] ) ){
					if( wp_verify_nonce( $_POST['yti_nonce'], 'yti_playlist_table_actions' ) ){
						if( isset( $_POST['yti_playlist'] ) ){
							$playlists = (array)$_POST['yti_playlist'];
							foreach( $playlists as $playlist_id ){
								wp_delete_post( $playlist_id, true );
							}	
						}
					}
					$r = add_query_arg(
						array(
							'post_type' => $this->post_type,
							'page'		=> 'yti_auto_import'
						), 'edit.php'
					);
					wp_redirect( $r );
				}else if( isset( $_GET['_wpnonce'] ) ){
					if( wp_verify_nonce( $_GET['_wpnonce'] ) ){
						$post_id = (int)$_GET['id'];
						wp_delete_post( $post_id, true );
					}
					$r = add_query_arg(
						array(
							'post_type' => $this->post_type,
							'page'		=> 'yti_auto_import'
						), 'edit.php'
					);
					wp_redirect( $r );
				}
			break;	
			// create playlist
			case 'add_new':
				if( isset( $_POST['yti_wp_nonce'] ) ){
					if( check_admin_referer('yti-save-playlist', 'yti_wp_nonce') ){
						
						$defaults = yti_playlist_settings_defaults();
						foreach( $defaults as $var => $val ){
							if( is_string($val) && empty( $_POST[$var] ) ){
								$this->playlist_errors = new WP_Error();
								$this->playlist_errors->add('yti_fill_all', __('Please fill all required fields marked with *.', 'yti_video'));
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
								'page' 		=> 'yti_auto_import',
								'action'	=> 'edit',
								'id'		=> $post_id
							),'edit.php'
						);
						
						wp_redirect( $r );
						die();						
					}
				}else{
					wp_enqueue_script(
						'yti-playlist-manage',
						YTI_URL . 'assets/back-end/js/playlist-edit.js',
						array('jquery') 
					);
					wp_enqueue_style(
						'yti-playlist-manage',
						YTI_URL . 'assets/back-end/css/playlist-edit.css'
					);
					wp_localize_script( 'yti-playlist-manage' , 'yti_pq', array(
						'loading' 		=> __( 'Making query, please wait...', 'yti_video' ),
						'still_loading' => __( 'Not done yet, be patient...', 'yti_video' )
					));	
				}
			break;
			// edit playlist
			case 'edit':
				if( isset( $_POST['yti_wp_nonce'] ) ){
					if( check_admin_referer('yti-save-playlist', 'yti_wp_nonce') ){
						$defaults = yti_playlist_settings_defaults();
						foreach( $defaults as $var => $val ){
							if( is_string($val) && empty( $_POST[$var] ) ){
								$this->playlist_errors = new WP_Error();
								$this->playlist_errors->add('yti_fill_all', __('Please fill all required fields marked with *.', 'yti_video'));
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
								'page' 		=> 'yti_auto_import',
								'action'	=> 'edit',
								'id'		=> $post_id
							),'edit.php'
						);
						
						wp_redirect( $r );
						die();						
					}
				}else{
					wp_enqueue_script(
						'yti-playlist-manage',
						YTI_URL . 'assets/back-end/js/playlist-edit.js',
						array('jquery') 
					);
					wp_enqueue_style(
						'yti-playlist-manage',
						YTI_URL . 'assets/back-end/css/playlist-edit.css'
					);
					wp_localize_script( 'yti-playlist-manage' , 'yti_pq', array(
						'loading' 		=> __( 'Making query, please wait...', 'yti_video' ),
						'still_loading' => __( 'Not done yet, be patient...', 'yti_video' )
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
					_e( 'While trying to export the playlists, an error occured. Please try again.', 'yti_video' );
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
				check_admin_referer( 'yti_import_playlists', 'yti_pu_nonce' );
				
				if( !isset( $_FILES['yti_playlists_json'] ) || empty( $_FILES['yti_playlists_json']['tmp_name'] ) ){
					$html = __('Please select a file to upload.', 'yti_video');
					$html.= '</p><p>' . '<a href="' . menu_page_url( 'yti_auto_import' , false ) . '">' . __('Go back', 'yti_video') . '</a>' . '</p>';
					wp_die( $html );					
				}
				
				add_filter( 'upload_mimes' , array( $this, 'upload_mimes' ) );
				
				$uploadedfile 		= $_FILES['yti_playlists_json'];
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
					$html.= '</p><p>' . '<a href="' . menu_page_url( 'yti_auto_import' , false ) . '">' . __('Go back', 'yti_video') . '</a>' . '</p>';
					wp_die( $html );
				}
				
				$r = add_query_arg(
					array(
						'post_type' => $this->post_type,
						'page'		=> 'yti_auto_import'
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
				$title = __('Add new playlist', 'yti_video');
				$options = yti_playlist_settings_defaults();
				
				if( is_wp_error( $this->playlist_errors ) ){
					$error = $this->playlist_errors->get_error_message();
				}
				
				$form_action = menu_page_url('yti_auto_import', false).'&action=add_new';
				require YTI_PATH.'views/manage_playlist.php';
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
				
				$title = sprintf( __( 'Edit playlist <em>%s</em>', 'yti_video' ), $post->post_title );				
				
				$form_action = menu_page_url('yti_auto_import', false).'&action=edit&id='.$post_id;
				
				$add_new_url 	= menu_page_url( 'yti_auto_import', false ) . '&action=add_new';
				$add_new_link 	= sprintf( '<a href="%1$s" title="%2$s" class="add-new-h2">%2$s</a>', $add_new_url, __( 'Add new', 'yti_video' ) );
				
				require YTI_PATH.'views/manage_playlist.php';
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
		<?php _e('Automatic import', 'yti_video')?>
		<a class="add-new-h2" href="<?php menu_page_url('yti_auto_import');?>&action=add_new"><?php _e('Add New', 'yti_video');?></a>
		<a class="add-new-h2" href="<?php menu_page_url('yti_auto_import');?>&action=export_playlists"><?php _e('Export playlists', 'yti_video');?></a>
		<a class="add-new-h2" href="#" id="yti_playlist_import_trigger"><?php _e('Import playlists', 'yti_video');?></a>		
	</h2>
	<div id="yti_import_playlists" class="hide-if-js">
		<form method="post" action="<?php menu_page_url( 'yti_auto_import' );?>&action=import_playlists" enctype="multipart/form-data">
			<label for="yti_playlists_json"><?php _e('Upload export file', 'yti_video');?>: </label>
			<input type="file" id="yti_playlists_json" name="yti_playlists_json" />
			<?php wp_nonce_field( 'yti_import_playlists', 'yti_pu_nonce' );?>
			<?php submit_button( __( 'Upload', 'yti_video' ), 'primary', 'submit', false );?>
		</form>
	</div>
	<?php if( yti_debug() ): global $YTI_AUTOMATIC_IMPORT;?>
	<div class="message updated">
		<p>
			<strong><?php _e('Debug information', 'yti_video');?></strong>
			<ul>
			<?php foreach ($YTI_AUTOMATIC_IMPORT->get_update() as $k=>$v):?>
				<li><strong><?php echo $k;?></strong>: <?php echo $v;?></li>
			<?php endforeach;?>
			</ul>
			<strong><?php _e('Import errors', 'yti_video');?></strong>
			<ul>
			<?php foreach( $YTI_AUTOMATIC_IMPORT->get_errors() as $error ):?>
				<li><?php echo $error;?></li>
			<?php endforeach;?>	
			</ul>
		</p>
	</div>
	<?php endif;?>	
	<?php yti_automatic_update_message( '<div class="message updated"><p>', '</p></div>', true );?>		
	<form method="post" action="">
		<?php wp_nonce_field('yti_playlist_table_actions', 'yti_nonce');?>
		<?php $this->playlists_table->views();?>
		<?php $this->playlists_table->display();?>
	</form>	
		
</div>
<?php 			
	}
	
	/**
	 * Menu page onLoad callback.
	 * Processes plugin settings and saves them.
	 */
	public function plugin_settings_onload(){
		if( isset( $_POST['yti_wp_nonce'] ) ){
			if( check_admin_referer('yti-save-plugin-settings', 'yti_wp_nonce') ){
				yti_update_settings();
				yti_update_player_settings();
				if( isset( $_POST['envato_purchase_code'] ) && !empty( $_POST['envato_purchase_code'] ) ){
					update_option('_yti_yt_plugin_envato_licence', $_POST['envato_purchase_code']);
				}
				if( isset( $_POST['youtube_api_key'] ) ){
					yti_update_api_key( $_POST['youtube_api_key'] );
				}				
			}
		}
		
		wp_enqueue_style(
			'yti-plugin-settings',
			YTI_URL.'assets/back-end/css/plugin-settings.css',
			false
		);
		
		wp_enqueue_script(
			'yti-options-tabs',
			YTI_URL.'assets/back-end/js/tabs.js',
			array('jquery', 'jquery-ui-tabs')
		);
		
		wp_enqueue_script(
			'yti-video-edit',
			YTI_URL.'assets/back-end/js/video-edit.js',
			array('jquery'),
			'1.0'
		);			
	}
	
	/**
	 * Menu page callback.
	 * Outputs the plugin settings page.
	 */
	public function plugin_settings(){
		$options 			= yti_get_settings();
		$player_opt 		= yti_get_player_settings();
		$envato_licence 	= get_option('_yti_yt_plugin_envato_licence', '');
		$youtube_api_key 	= yti_get_yt_api_key();
		// view
		include YTI_PATH . 'views/plugin_settings.php';
	}
	
	/**
	 * Manu page onLoad callback
	 * Enqueue assets for compatibility page
	 */
	public function plugin_help_onload(){
		wp_enqueue_style(
			'yti-admin-compat-style',
			YTI_URL.'assets/back-end/css/help-page.css'
		);		
	}
	
	/**
	 * Menu page callback
	 * Outputs the compatibility page
	 */
	public function page_help(){
		$themes = yti_get_compatible_themes();
		$theme 	= yti_check_theme_support();
		
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
		
		if( !class_exists( 'YTI_Shortcodes' ) ){
			include_once YTI_PATH . 'includes/libs/shortcodes.class.php';			
		}
		$shortcodes_obj = new YTI_Shortcodes();
		
		// view
		include YTI_PATH.'/views/help.php';		
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
						'yti_video',
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
		printf('<title>%s</title>', __('Video list', 'yti_video'));		
		wp_enqueue_style( 'colors' );
		wp_enqueue_style( 'ie' );
		wp_enqueue_script( 'utils' );
		wp_enqueue_style('buttons');
		
		wp_enqueue_style(
			'yti-video-list-modal', 
			YTI_URL.'assets/back-end/css/video-list-modal.css', 
			false, 
			'1.0'
		);
		
		wp_enqueue_script(
			'yti-video-list-modal',
			YTI_URL.'assets/back-end/js/video-list-modal.js',
			array('jquery'),
			'1.0'	
		);
		
		do_action('admin_print_styles');
		do_action('admin_print_scripts');
		do_action('yti_video_list_modal_print_scripts');
		echo '</head>';
		echo '<body class="wp-core-ui">';
		
		
		require YTI_PATH . 'includes/admin/libs/video-list-table.class.php';
		$table = new YTI_Video_List_Table();
		$table->prepare_items();
		
		$post_type = $this->post_type;
		if( isset($_GET['pt']) && 'post' == $_GET['pt'] ){
			$post_type = 'post';
		}
		
		?>
		<div class="wrap">
			<form method="get" action="" id="yti-video-list-form">
				<input type="hidden" name="pt" value="<?php echo $post_type;?>" />
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'];?>" />
				<?php $table->views();?>
				<?php $table->search_box( __('Search', 'yti_video'), 'video' );?>
				<?php $table->display();?>
			</form>
			<div id="yti-shortcode-atts"></div>
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
				'yti-video-settings', 
				__( 'Video settings', 'yti_video' ),
				array( $this, 'post_video_settings_meta_box' ),
				$post->post_type,
				'normal',
				'high'
			);
			
			add_meta_box(
				'yti-show-video', 
				__( 'Live video', 'yti_video' ),
				array( $this, 'post_show_video_meta_box' ),
				$post->post_type,
				'normal',
				'high'
			);	
			
		}else{ // for all other post types add only the shortcode embed panel
			add_meta_box(
				'yti-add-video', 
				__( 'Video shortcode', 'yti_video' ), 
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
		$settings = yytt_get_video_settings( $post->ID );		
		include_once YTI_PATH . 'views/metabox-post-video-settings.php';		
	}
	
	/**
	 * Meta box callback.
	 * Display live video meta box when editing posts
	 */
	public function post_show_video_meta_box(){
		global $post;
		$video_id 	= get_post_meta( $post->ID, '__yti_video_id', true );
		$video_data = get_post_meta( $post->ID, '__yti_video_data', true );
?>	
<script language="javascript">
;(function($){
	$(document).ready(function(){
		$('#yytt-video-preview').YYTT_VideoPlayer({
			'video_id' 	: '<?php echo $video_data['video_id'];?>',
			'source'	: 'youtube'
		});
	})
})(jQuery);
</script>
<div id="yytt-video-preview" style="height:315px; width:560px; max-width:100%;"></div>		
<?php	
	}
	
	/**
	 * Meta box callback
	 * Post add shortcode meta box output
	 */
	public function post_shortcode_meta_box(){
		?>
		<p><?php _e('Add video/playlist into post.', 'yti_video');?><p>
		<a class="button" href="#" id="yti-shortcode-2-post" title="<?php esc_attr_e( 'Add shortcode', 'yti_video' );?>"><?php _e( 'Add video shortcode', 'yti_video' );?></a>
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
		$video_id = get_post_meta( $post->ID, '__yti_video_id', true );		
		if( !$video_id ){
			return;
		}
		
		// some files are needed only on custom post type edit page
		if( $this->is_video() ){		
			// add video player for video preview on post
			yytt_enqueue_player();
			wp_enqueue_script(
				'yti-video-edit',
				YTI_URL.'assets/back-end/js/video-edit.js',
				array('jquery'),
				'1.0'
			);	
		}
		
		// video thumbnail functionality
		wp_enqueue_script(
			'yti-video-thumbnail',
			YTI_URL.'assets/back-end/js/video-thumbnail.js',
			array('jquery'),
			'1.0'
		);

		wp_localize_script( 'yti-video-thumbnail', 'YTI_POST_DATA', array( 'post_id' => $post->ID ) );
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
		
		$video_id = get_post_meta( $post->ID, '__yti_video_id', true );		
		if( !$video_id ){
			return $content;
		}
		
		$content .= sprintf( '<a href="#" id="yti-import-video-thumbnail" class="button primary">%s</a>', __('Import YouTube thumbnail', 'yti_video') );		
		return $content;
	}
	
	/**
	 * save_post callback
	 * Save post data from meta boxes. Hooked to save_post
	 */
	public function save_post( $post_id, $post ){
		if( !isset( $_POST['yti-video-nonce'] ) ){
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
		check_admin_referer('yti-save-video-settings', 'yti-video-nonce');		
		yytt_update_video_settings( $post_id );	
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
		$allow_plugin_import = apply_filters( 'yti_allow_single_video_import' , true );
		if( !$allow_plugin_import ){
			return;			
		}
		
		// store video details
		$this->video_post = false;
		
		if( isset( $_POST['wp_nonce'] ) ){
			if( check_admin_referer('yti_query_new_video', 'wp_nonce') ){
				
				$video_id = sanitize_text_field( $_POST['yti_video_id'] );
				$video = yti_yt_api_get_video( $video_id );
				if( $video && !is_wp_error( $video ) ){
					$this->video_post 	= $video;
					
					// apply filters on title and description
					$import_in_theme = isset( $_POST['single_theme_import'] ) && $_POST['single_theme_import'] ? yti_check_theme_support() : array();
					$this->video_post['description'] 	= apply_filters('yti_video_post_content', $this->video_post['description'], $this->video_post, $import_in_theme);
					$this->video_post['title'] 			= apply_filters('yti_video_post_title', $this->video_post['title'], $this->video_post, $import_in_theme);
					// single post import date
					$import_options = yti_get_settings();
					$post_date 		= $import_options['import_date'] ? date('Y-m-d H:i:s', strtotime( $this->video_post['published'] )) : current_time( 'mysql' );
					$this->video_post['post_date'] = apply_filters( 'yti_video_post_date', $post_date, $this->video_post, $import_in_theme );
					
					add_filter('default_content', array( $this, 'default_content' ), 999, 2);
					add_filter('default_title', array( $this, 'default_title' ), 999, 2);
					add_filter('default_excerpt', array( $this, 'default_excerpt' ), 999, 2);
					
					// add video player for video preview on post
					yytt_enqueue_player();	
				}else{
					$message = __('Video not found.', 'yti_video');
					if( is_wp_error( $video ) ){
						$message = sprintf( __( 'An error occured while trying to query YouTube API.<br />Error: %s (code: %s)', 'yti_video' ), $video->get_error_message(), $video->get_error_code() );
					}
					global $YTI_NEW_VIDEO_NOTICE;
					$YTI_NEW_VIDEO_NOTICE = $message;
					add_action( 'all_admin_notices', array( $this, 'new_post_error_notice' ) );					
				}				
			}else{
				wp_die('Cheatin uh?');
			}
		}
		// if video query not started, display the form
		if( !$this->video_post ){
			wp_enqueue_script(
				'yti-new-video-js',
				YTI_URL.'assets/back-end/js/video-new.js',
				array('jquery'),
				'1.0'
			);
			
			$post_type_object = get_post_type_object( $this->post_type );
			$title = $post_type_object->labels->add_new_item;
			
			include ABSPATH .'wp-admin/admin-header.php';
			include YTI_PATH.'views/new_video.php';
			include ABSPATH .'wp-admin/admin-footer.php';
			die();
		}
	}
	
	/**
	 * Callback function that displays admin error message when importing single videos.
	 * Action is set in function $this->post_new_onload()
	 */
	public function new_post_error_notice(){
		global $YTI_NEW_VIDEO_NOTICE;
		if( $YTI_NEW_VIDEO_NOTICE ){
			echo '<div class="error"><p>' . $YTI_NEW_VIDEO_NOTICE . '</p></div>';
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
		update_post_meta( $post->ID, '__yti_video_id', $this->video_post['video_id'] );
		// needed by other plugins
		update_post_meta( $post->ID, '__yti_video_url', 'https://www.youtube.com/watch?v=' . $this->video_post['video_id'] );
		// save video data on post
		update_post_meta( $post->ID, '__yti_video_data', $this->video_post );
		
		// import video thumbnail as featured image
		$settings = yti_get_settings();
		if( import_image_on( 'post_create' ) ){
			// import featured image
			yti_set_featured_image( $post->ID, $this->video_post );
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
		$theme_import 	= isset( $_POST['single_theme_import'] ) ? yti_check_theme_support() : array();
		// action on post insert that allows setting of different meta on post
		do_action( 'yti_before_post_insert', $this->video_post, $theme_import );
		
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
			$ps = yti_get_player_settings();
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
			
			$embed_code = '<iframe width="'.$ps['width'].'" height="'.yti_player_height($ps['aspect_ratio'], $ps['width']).'" src="https://www.youtube.com/embed/'.$this->video_post['video_id'].'?'.$customize.'" frameborder="0" allowfullscreen></iframe>';
			
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
				
				update_post_meta($post->ID, '__yti_is_video', true);
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
		do_action('yti_post_insert', $post->ID, $this->video_post, $theme_import, $post->post_type);
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
				$cols['video_id'] = __('Video ID', 'yti_video');
				$cols['duration'] = __('Duration', 'yti_video');	
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
				echo get_post_meta( $post_id, '__yti_video_id', true );
			break;
			case 'duration':
				$meta = get_post_meta( $post_id, '__yti_video_data', true );
				
				if( !$meta ){
					echo '-';						
				}else{				
					echo yti_human_time($meta['duration']);
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
		$api_key = yti_get_yt_api_key();
		if( empty( $api_key ) ){
			$url = menu_page_url( 'yti_settings', false );
			
			
			?>
<div class="error">
	<p>
		<?php _e( 'In order to be able to import YouTube videos using the plugin you must enter your YouTube API key.', 'yti_video' );?><br />
		<?php _e( 'Please navigate to plugin <strong>Settings</strong> page, tab <strong>API & License</strong> and enter your <strong>YouTube API key</strong>.', 'yti_video' );?>		
	</p>
	<p><a class="button" href="<?php menu_page_url( 'yti_settings', true );?>"><?php _e( 'Plugin Settings', 'yti_video' );?></a></p>
</div>
		<?php 
			// stop all other messages if API key is missing
			return;
		}// close if
		
		// alert user that he is importing YouTube videos as regular post type
		if( import_as_post() ){
			global $current_user;
			$user_id = $current_user->ID;
			if( !get_user_meta($user_id, 'yti_ignore_post_type_notice', true) ){
				echo '<div class="updated"><p>';
				$theme_support = yti_check_theme_support();
				
				printf(__('Please note that you have chosen to import videos as <strong>regular posts</strong> instead of post type <strong>%s</strong>.', 'yti_video'), $this->post_type);
				echo '<br />' . ( $theme_support ? __('Videos can be imported as regular posts compatible with the plugin or as posts compatible with your theme.', 'yti_video') : __('Videos will be imported as regular posts.', 'yti_video') );
							
				$url = add_query_arg(array(
					'yti_dismiss_post_type_notice' => 1
				), $_SERVER['REQUEST_URI']);
				
				printf(' <a class="button button-small" href="%s">%s</a>', $url, __('Dismiss', 'yti_video'));
				echo '</p></div>';			
			}
		}			
	}
	
	/**
	 * Dismiss regular post import notice
	 */
	public function dismiss_post_type_notice(){
		if( !is_admin() ){
			return;
		}
		
		if( isset( $_GET['yti_dismiss_post_type_notice'] ) && 1 == $_GET['yti_dismiss_post_type_notice'] ){
			global $current_user;
			$user_id = $current_user->ID;
			add_user_meta($user_id, 'yti_ignore_post_type_notice', true);
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
		$plugin_array['yytt_shortcode'] = YTI_URL . 'assets/back-end/js/tinymce/shortcode.js';
		return $plugin_array;
	}
	
	/**
	 * Register tinymce buttons
	 * @param array $buttons
	 */
	public function register_buttons( $buttons ){
		array_push( $buttons, 'separator', 'yytt_shortcode' );
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
			'yytt-shortcode-modal',
			YTI_URL.'assets/back-end/css/shortcode-modal.css',
			false,
			'1.0'
		);
		
		wp_enqueue_script(
			'yytt-shortcode-modal',
			YTI_URL.'assets/back-end/js/shortcode-modal.js',
			false,
			'1.0'
		);
		
		$messages = array(
			'playlist_title' => __('Videos in playlist', 'yti_video'),
			'no_videos'		 => __('No videos selected.<br />To create a playlist check some videos from the list on the right.', 'yti_video'),
			'deleteItem'	 => __('Delete from playlist', 'yti_video'),
			'insert_playlist'=> __('Add shortcode into post', 'yti_video')
		);
		
		wp_localize_script('yytt-shortcode-modal', 'YTI_SHORTCODE_MODAL', $messages);
	}	
	
	/**
	 * A hack to add new bulk actions and to process them by JS.
	 */
	public function bulk_actions_hack(){
		if( !isset( $_GET['post_type'] ) || $this->post_type != $_GET['post_type'] ){
			return;
		}
		
		wp_enqueue_script(
			'yti-bulk-actions',
			YTI_URL.'assets/back-end/js/bulk-actions.js',
			array('jquery'),
			'1.0'
		);
		
		wp_enqueue_style(
			'yti-bulk-actions-response',
			YTI_URL.'assets/back-end/css/video-list.css',
			false,
			'1.0'
		);
		
		wp_localize_script(
			'yti-bulk-actions', 
			'yti_bulk_actions', 
			array(
				'actions' 		=> yti_actions(),
				'wait'			=> __('Processing, please wait...', 'yti_video'),
				'wait_longer'	=> __('Not done yet, please be patient...', 'yti_video'),
				'maybe_error' 	=> __('There was an error while importing your thumbnails. Please try again.', 'yti_video')
			)	
		);
	}
	
	/**
	 * Enqueue some scripts on WP widgets page
	 */
	public function widgets_scripts(){
		$plugin_settings = yti_get_settings();
		if( isset( $plugin_settings['public'] ) && !$plugin_settings['public'] ){
			return;
		}
		
		wp_enqueue_script(
			'yti-video-edit',
			YTI_URL . 'assets/back-end/js/video-edit.js',
			array( 'jquery' ),
			'1.0'
		);
	}
}

/**
 * AJAX Actions management class
 */
abstract class YTI_AJAX_Actions extends YTI_Video_Post_Type{
	
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
			_e('Please enter a playlist ID.', 'yti_video');
			die();
		}
		
		$args = array(
			'playlist_type' 		=> $_POST['type'],
			'include_categories' 	=> false,
			'query'					=> $_POST['id']
		);	
		$details = yti_yt_api_get_list( $args );
		
		if( is_wp_error( $details['videos'] ) ){
			echo '<span style="color:red;">' . $details['videos']->get_error_message() . '</span>';	
		}else{	
			printf( __('Playlist contains %d videos.', 'yti_video'), $details['page_info']['total_results'] );
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
		
		global $YTI_POST_TYPE;
		if( !current_user_can( 'edit_posts' ) ){
			wp_die( -1 );
		}
		
		if( !isset( $_REQUEST['action'] ) && !isset( $_REQUEST['action2'] ) ){
			wp_send_json_error( __('Sorry, there was an error, please try again.', 'yti_video') );
		}
		
		if( !isset( $_REQUEST['post'] ) || empty( $_REQUEST['post'] ) ){
			wp_send_json_error( __('<strong>Error!</strong> Select some posts to import thumbnails for.', 'yti_video') );
		}
		
		if( !isset( $_REQUEST['post_type'] ) || $YTI_POST_TYPE->get_post_type() != $_REQUEST['post_type'] ){
			wp_send_json_error( __('Thumbnail imports work only for custom post type.', 'yti_video') );
		}
		
		$action = false;
		if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
			$action = $_REQUEST['action'];
	
		if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
			$action = $_REQUEST['action2'];
		
		if( !$action || !array_key_exists( $action, yti_actions() ) ){
			wp_send_json_error( __('Please select a valid action.', 'yti_video') );
		}	
		
		// increase time limit
		@set_time_limit( 300 );
		
		$post_ids = array_map( 'intval', $_REQUEST['post'] );
		foreach( $post_ids as $post_id ){			
			switch( $action ){
				case 'yti_thumbnail':
					yti_set_featured_image( $post_id );		
				break;	
			}		
		}		
		wp_send_json_success( __('All thumbnails successfully imported.', 'yti_video') );		
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
					
					$result = $this->import_videos();
					
					if( is_wp_error( $result ) ){
						$response['error'] = $result->get_error_message();
					}else if( $result ){					
						$response['success'] = sprintf( 
							__('<strong>%d videos:</strong> %d imported; %d not found; %d skipped (already imported)', 'yti_video'), 
							$result['total'],
							$result['imported'],
							$result['not_found'],
							$result['skipped']
						);
					}else{
						$response['error'] = __('No videos selected for importing. Please select some videos by checking the checkboxes next to video title.', 'yti_video');
					}													
				}else{
					$response['error'] = __('Please select an action.', 'yti_video');
				}			
			}else{
				$response['error'] = __("Cheatin' uh?", 'yti_video');
			}	
		}else{
			$response['error'] = __("Cheatin' uh?", 'yti_video');
		}	
		
		echo json_encode( $response );
		die();	
	}
	
	/**
	 * Helper for $this->video_bulk_import(). Will import all videos passed by user with the AJAX call.
	 * Import videos to WordPress
	 */	
	private function import_videos(){
		if( !isset( $_POST['yti_import'] ) || !$_POST['yti_import'] ){
			return false;
		}
		
		//get options
		$options = yti_get_settings();
		// check if importing for theme
		$theme_import = false;
		if( isset( $_POST['yti_theme_import'] ) ){
			$theme_import = yti_check_theme_support();
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
		$video_ids = array_reverse( (array)$_POST['yti_import'] );
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
		
		$videos = yti_yt_api_get_videos( $video_ids );
		if( is_wp_error( $videos ) ){
			return $videos;
		}
				
		foreach( $videos as $video ){
			
			// search if video already exists
			$posts = get_posts(array(
				'post_type' 	=> $post_type,
				'meta_key'		=> '__yti_video_id',
				'meta_value' 	=> $video['video_id'],
				'post_status' 	=> array('publish', 'pending', 'draft', 'future', 'private')
			));
			
			// video already exists, don't do anything
			if( $posts ){
				$result['skipped'] += 1;
				continue;
			}

			$video_id = $video['video_id'];
			if( isset( $_POST['yti_title'][ $video_id ] ) ){
				$video['title'] = $_POST['yti_title'][ $video_id ];
			}
			if( isset( $_POST['yti_text'][ $video_id ] ) ){
				$video['description'] = $_POST['yti_text'][ $video_id ];
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
		$thumbnail = yti_set_featured_image( $post_id );
		
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
			update_user_option( $uid, 'yti_video_import_view', $view );
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
				'action' 		=> 'yti_check_playlist',
				'callback' 		=> array( $this, 'callback_query_playlist' ),
				'nonce' 		=> array(
					'name' 		=> 'yti-ajax-nonce',
					'action' 	=> 'yti-playlist-query'
				)
			),
			/**
			 * Bulk import YouTube thumbnails
			 */
			'bulk_import_thumbnails' => array(
				'action' 	=> 'yti_thumbnail',
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
				'action' => 'yti_import_videos',
				'callback' => array( $this, 'video_bulk_import' ),
				'nonce' => array(
					'name' 		=> 'yti_import_nonce',
					'action' 	=> 'yti-import-videos-to-wp'	
				)
			),
			/**
			 * Post thumbnail import
			 */
			'import_post_thumbnail' => array(
				'action' => 'yti_import_video_thumbnail',
				'callback' => array( $this, 'import_post_thumbnail' ),
				'nonce' => array(
					'name' => 'yti_nonce',
					'action' => 'yti-thumbnail-post-import'
				)
			),
			
			'import_view' => array(
				'action' => 'yti_import_list_view',
				'callback' => array( $this, 'change_import_view' ),
				'nonce' => array(
					'name' => 'yti_nonce',
					'action' => 'yti-change-manual-import-list-view'
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