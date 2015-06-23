<?php
/**
 * Video post type class. Registers post type and sets up some filters and
 * actions needed in front-end to display video post type correctly according
 * to user settings.
 */
abstract class YVI_Video_Post_Type{
	
	/**
	 * Video custom post type name
	 * @var string
	 */
	protected $post_type 		= 'video';
	/**
	 * Video custom post type taxonomy
	 * @var string
	 */
	protected $taxonomy 		= 'videos';
	/**
	 * Automatic import playlists post type
	 * @var string
	 */
	protected $playlist_type				= 'yvi_yt_playlist';
	/**
	 * Automatic import playlists custom field name
	 * @var string
	 */
	protected $playlist_meta				= '__yvi_yt_playlist';
	
	/**
	 * Constructor, registers post types and sets different actions and filters 
	 * needed in front-end.
	 */
	public function __construct(){
		// custom post type registration and messages
		add_action( 'init', array( $this, 'register_post' ), 10 );
		// init action to make various verifications
		add_action( 'init', array( $this, 'init_callback' ), 9999 );
		// custom post type messages
		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
		
		// add video post type to homepage list of posts
		add_filter( 'pre_get_posts', array( $this, 'add_on_homepage' ), 999 );
		
		// add video post type to main RSS feed
		add_filter( 'request', array( $this, 'add_to_main_feed' ) );
		
		// plugin filters
		add_filter( 'yvi_video_post_content', array( $this, 'format_description' ), 999, 3 );		
	}	
	
	/**
	 * Register video post type and taxonomies
	 */
	public function register_post(){
		$labels = array(
			'name' 					=> _x('Videos', 'Videos', 'yvi_video'),
	    	'singular_name' 		=> _x('Video', 'Video', 'yvi_video'),
	    	'add_new' 				=> _x('Add new', 'Add new video', 'yvi_video'),
	    	'add_new_item' 			=> __('Add new video', 'yvi_video'),
	    	'edit_item' 			=> __('Edit video', 'yvi_video'),
	    	'new_item'				=> __('New video', 'yvi_video'),
	    	'all_items' 			=> __('All videos', 'yvi_video'),
	    	'view_item' 			=> __('View', 'yvi_video'),
	    	'search_items' 			=> __('Search', 'yvi_video'),
	    	'not_found' 			=> __('No videos found', 'yvi_video'),
	    	'not_found_in_trash' 	=> __('No videos in trash', 'yvi_video'), 
	    	'parent_item_colon' 	=> '',
	    	'menu_name' 			=> __('YouTube VI', 'yvi_video')
		);
		
		$options 	= yvi_get_settings();
		$is_public 	= $options['public'];
		
		$args = array(
    		'labels' 				=> $labels,
    		'public' 				=> $is_public,
			'exclude_from_search'	=> !$is_public,
    		'publicly_queryable' 	=> $is_public,
			'show_in_nav_menus'		=> $is_public,
		
    		'show_ui' 				=> true,
			'show_in_menu' 			=> true,
			'menu_position' 		=> 5,
			'menu_icon'				=> YVI_URL.'assets/adminpenal/images/video.png',	
		
    		'query_var' 			=> true,
    		'capability_type' 		=> 'post',
    		'has_archive' 			=> true, 
    		'hierarchical' 			=> false,
    		'rewrite'				=> array(
				'slug' => $options['post_slug']
			),		
    		'supports' 				=> array( 
    			'title', 
    			'editor', 
    			'author', 
    			'thumbnail', 
    			'excerpt', 
    			'trackbacks',
				'custom-fields',
    			'comments',  
    			'revisions',
    			'post-formats' 
			),			
 		); 
 		
 		register_post_type( $this->post_type, $args );
  
  		// Add new taxonomy, make it hierarchical (like categories)
  		$cat_labels = array(
	    	'name' 					=> _x( 'Video categories', 'video', 'yvi_video' ),
	    	'singular_name' 		=> _x( 'Video category', 'video', 'yvi_video' ),
	    	'search_items' 			=>  __( 'Search video category', 'yvi_video' ),
	    	'all_items' 			=> __( 'All video categories', 'yvi_video' ),
	    	'parent_item' 			=> __( 'Video category parent', 'yvi_video' ),
	    	'parent_item_colon'		=> __( 'Video category parent:', 'yvi_video' ),
	    	'edit_item' 			=> __( 'Edit video category', 'yvi_video' ), 
	    	'update_item' 			=> __( 'Update video category', 'yvi_video' ),
	    	'add_new_item' 			=> __( 'Add new video category', 'yvi_video' ),
	    	'new_item_name' 		=> __( 'Video category name', 'yvi_video' ),
	    	'menu_name' 			=> __( 'Video categories', 'yvi_video' ),
		); 	

		register_taxonomy( $this->taxonomy, array($this->post_type), array(
			'public'			=> $is_public,
    		'show_ui' 			=> true,
			'show_in_nav_menus' => $is_public,
			'show_admin_column' => true,		
			'hierarchical' 		=> true,
			'rewrite' 			=> array( 
				'slug' => $options['taxonomy_slug'] 
			),
			'capabilities'		=> array('edit_posts'),		
    		'labels' 			=> $cat_labels,    		
    		'query_var' 		=> true    		
  		));
  		
  		// playlists post type  		
  		register_post_type( $this->playlist_type, array(
  			'public' 				=> false,
  			'exclude_from_search' 	=> true,
  			'publicly_queryable'	=> false,
  			'show_ui'				=> false,
  			'show_in_nav_menus'		=> false,
  			'show_in_menu'			=> false,
  			'show_in_admin_bar'		=> false
  		));  		 		
	}
	
	/**
	 * Custom post type messages on edit, update, create, etc.
	 * @param array $messages
	 */
	public function updated_messages( $messages ){
		global $post, $post_ID;
	
		$messages['video'] = array(
			0 => '', // Unused. Messages start at index 1.
	    	1 => sprintf( __('Video updated <a href="%s">See video</a>', 'yvi_video'), esc_url( get_permalink($post_ID) ) ),
	    	2 => __('Custom field updated.', 'yvi_video'),
	    	3 => __('Custom field deleted.', 'yvi_video'),
	    	4 => __('Video updated.', 'yvi_video'),
	   		/* translators: %s: date and time of the revision */
	    	5 => isset($_GET['revision']) ? sprintf( __('Video restored to version %s', 'yvi_video'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
	    	6 => sprintf( __('Video published. <a href="%s">See video</a>', 'yvi_video'), esc_url( get_permalink($post_ID) ) ),
	    	7 => __('Video saved.', 'yvi_video'),
	    	8 => sprintf( __('Video saved. <a target="_blank" href="%s">See video</a>', 'yvi_video'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
	    	9 => sprintf( __('Video will be published at: <strong>%1$s</strong>. <a target="_blank" href="%2$s">See video</a>', 'yvi_video'),
	      	// translators: Publish box date format, see http://php.net/date
	      	date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
	    	10 => sprintf( __('Video draft saved. <a target="_blank" href="%s">See video</a>', 'yvi_video'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),

	    	101 => __('Please select a source', 'yvi_video'),
	    		
	    );
	
		return $messages;
	}
	
	/**
	 * Callback on init to make various verifications
	 */
	public function init_callback(){
		// if images should be imported on post display, add the filter
  		if( import_image_on('post_display') ){
			add_filter('get_post_metadata', array($this, 'import_on_display'), 999, 4);
			add_filter('get_'.$this->post_type.'_metadata', array($this, 'import_on_display'), 999, 4);
			
			$theme = yvi_check_theme_support();
			if( $theme ){
				if( 'post' != $theme['post_type'] || $this->post_type != $theme['post_type'] ){
					add_filter('get_'.$theme['post_type'].'_metadata', array($this, 'import_on_display'), 999, 4);
				}				
			}
  		} 
	}
	
	/**
	 * Callback function to import images when requested by the script (on-demand).
	 * Filter set in function self:init_callback()
	 * Less stress on server since it only imports the images.
	 * Triggered by the options in Settings page to import images.
	 * 
	 * @param null $null - passed by filter, always null
	 * @param int $object_id - post ID
	 * @param string $meta_key - meta key name
	 * @param bool $single - return single value
	 */
	public function import_on_display($null, $object_id, $meta_key, $single){
		// if image importing isn't set on post display, remove filter and bail out
		if( !import_image_on('post_display') ){
			// remove the filter to avoid loops
			remove_filter('get_post_metadata', array($this, 'import_on_display'), 999);
			return $null;
		}
		// if not looking for field _thumbnail_id, bail out
		if( '_thumbnail_id' != $meta_key ){
			return $null;
		}
		
		// remove the filter to avoid loops
		remove_filter('get_post_metadata', array($this, 'import_on_display'), 999);
		
		// if already has thumbnail, bail out
		if( has_post_thumbnail( $object_id ) ){
			// add the filter back
			add_filter('get_post_metadata', array($this, 'import_on_display'), 999, 4);
			// bail
			return $null;		
		}
		
		// start the thumbnail import
		$data = yvi_set_featured_image( $object_id );
		
		// add the filter back
		add_filter('get_post_metadata', array($this, 'import_on_display'), 999, 4);
		
		// return the attachment ID
		if( isset( $data['attachment_id'] ) ){
			return $data['attachment_id'];
		}	
	}	
	
	/**
	 * Automatic import callback
	 * 
	 * @param array $raw_feed - feed returned from YouTube
	 * @param array $playlist_details - details for currently processing playlist
	 */
	public function run_import( $raw_feed, $playlist_details ){
		
		if( !is_array( $raw_feed ) || !is_array($playlist_details) ){
			return;	
		}
		
		// get settings
		$options = yvi_get_settings();
		
		// check if importing for theme
		$theme_import = false;
		if( $playlist_details['theme_import'] ){
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
			$category = isset( $playlist_details['theme_tax'] ) ? $playlist_details['theme_tax'] : false;			 	
		}else{
			// should imports be made as regular posts?
			$as_post = import_as_post();
			$post_type = $as_post ? 'post' : $this->post_type;
			$taxonomy = $as_post ? 'category' : $this->taxonomy;
			$post_format = 'video';
			$category = isset( $playlist_details['native_tax'] ) ? $playlist_details['native_tax'] : false;
		}
		
		// reset category if not set correctly		
		if( -1 == $category || 0 == $category ){
			$category = false;
		}
		
		$user = false;
		if( $playlist_details['import_user'] ){
			$user_data = get_userdata( $playlist_details['import_user'] );
			$user = !$user_data ? false : $user_data->ID;			
		}
		
		// set post status
		$statuses 	= array('publish', 'draft', 'pending');
		$status 	= in_array( $options['import_status'], $statuses ) ? $options['import_status'] : 'draft';
		
		// store result
		$result = array(
			'imported' 	=> 0,
			'skipped' 	=> 0,
			'total'		=> count( $raw_feed )
		);
		
		$video_ids = array();
		foreach( $raw_feed as $video ){
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
			// process the video			
			$r = $this->import_video(array(
				'video' 			=> $video, // video details retrieved from YouTube
				'category' 			=> $category, // category name (if any) - will be created if category_id is false
				'post_type' 		=> $post_type, // what post type to import as
				'taxonomy' 			=> $taxonomy, // what taxonomy should be used
				'user'				=> $user, // save as a given user if any
				'post_format'		=> $post_format, // post format will default to video
				'status'			=> $status, // post status
				'theme_import'		=> $theme_import
			));
			
			if( $r ){
				$result['imported'] += 1;
			}
			$video_ids[] = $video['video_id'];			
		}
		
		// check for duplicates
		if( $video_ids ){		
			global $wpdb;
			$sql = $wpdb->prepare( 'SELECT post_id, meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key=%s AND meta_value IN(%s)' , '__yvi_video_id', implode("','", $video_ids) );
			$r = $wpdb->get_results( stripslashes( $sql ), ARRAY_A );
			if( $r ){
				$ids = array();
				foreach( $r as $rr ){
					$ids[ $rr['meta_value'] ][] = $rr['post_id'];	
				}			
				foreach( $ids as $post_ids ){
					// if 1 post found with the ID, get to the next ID
					if( 2 > count( $post_ids ) ){
						continue;
					}
					
					array_shift( $post_ids );				
					foreach ( $post_ids as $post_id ){
						wp_delete_post( $post_id, true );
					}				
				}			
			}
		}	
		
		return $result;		
	}
	
	/**
	 * Import a single video based on the passed data
	 */
	protected function import_video( $args = array() ){
		
		$defaults = array(
			'video' 			=> array(), // video details retrieved from YouTube
			'category' 			=> false, // category name (if any) - will be created if category_id is false
			'post_type' 		=> false, // what post type to import as
			'taxonomy' 			=> false, // what taxonomy should be used
			'user'				=> false, // save as a given user if any
			'post_format'		=> 'video', // post format will default to video
			'status'			=> 'draft', // post status
			'theme_import'		=> false		
		);
		extract( wp_parse_args($args, $defaults), EXTR_SKIP );
		// if no video details or post type, bail out
		if( !$video || !$post_type ){			
			return false;
		}
		
		/**
		 * Filter that allows video imports. Can be used to prevent importing of 
		 * videos.
		 * 
		 * @param $video - video details array
		 * @param $post_type - post type that should be created from the video details
		 * @param $theme_import - if video should be imported as theme compatible post, holds theme details array
		 */
		$allow_import = apply_filters('yvi_allow_video_import', true, $video, $post_type, $theme_import );
		if( !$allow_import ){
			return false;
		}
		
		// plugin settings
		$options = yvi_get_settings();
		
		/**
		 * Import category if not set to an existing one
		 */
		if( !$category && $options['import_categories'] && $video['category'] ){
			$cat = term_exists( $video['category'], $taxonomy );
			// if not existing, create it
			if( 0 == $cat || null == $cat ){
				$cat = wp_insert_term($video['category'], $taxonomy);
			}
			// set category to newly inserted term
			if( isset( $cat['term_id'] ) ){
				$category = $cat['term_id'];
			}			
		}
		
		/**
		 * Filter on video description
		 * 
		 * @param string - video description
		 * @param bool - import description value as set by the user in plugin settings
		 * 
		 */
		$video['description'] = apply_filters('yvi_video_description', $video['description'], $options['import_description']);
		
		// post content
		$post_content = '';
		if( 'content' == $options['import_description'] || 'content_excerpt' == $options['import_description'] ){
			$post_content = $video['description'];
		}
		// post excerpt
		$post_excerpt = '';
		if( 'excerpt' == $options['import_description'] || 'content_excerpt' == $options['import_description'] ){
			$post_excerpt = $video['description'];
		}
		
		// post title
		$video['title'] = apply_filters('yvi_video_title', $video['title'], $options['import_title']);
		$post_title 	= $options['import_title'] ? $video['title'] : '';
		
		// action on post insert that allows setting of different meta on post
		do_action('yvi_before_post_insert', $video, $theme_import);
		
		// set post data
		$post_data = array(
			/**
			 * Filter on post title
			 * 
			 * @param string - the post title
			 * @param array - the video details
			 * @param bool/array - false if not imported as theme, array if imported as theme and theme is active
			 */
			'post_title' 	=> apply_filters('yvi_video_post_title', $post_title, $video, $theme_import),
			/**
			 * Filter on post content
			 * 
			 * @param string - the post content
			 * @param array - the video details
			 * @param bool/array - false if not imported as theme, array if imported as theme and theme is active
			 */
			'post_content' 	=> apply_filters('yvi_video_post_content', $post_content, $video, $theme_import),
			/**
			 * Filter on post excerpt
			 * 
			 * @param string - the post excerpt
			 * @param array - the video details
			 * @param bool/array - false if not imported as theme, array if imported as theme and theme is active
			 */
			'post_excerpt'	=> apply_filters('yvi_video_post_excerpt', $post_excerpt, $video, $theme_import),
			'post_type'		=> $post_type,
			'post_status'	=> apply_filters('yvi_video_post_status', $status, $video, $theme_import )
		);
		
		$pd = $options['import_date'] ? date('Y-m-d H:i:s', strtotime( $video['published'] )) : current_time( 'mysql' );
		/**
		 * Filter on post date
		 * 
		 * @param string - the post date
		 * @param array - the video details
		 * @param bool/array - false if not imported as theme, array if imported as theme and theme is active
		 */
		$post_date = apply_filters( 'yvi_video_post_date', $pd, $video, $theme_import );
		
		if( isset( $options['import_date'] ) && $options['import_date'] ){
			$post_data['post_date_gmt'] = $post_date;
			$post_data['edit_date']		= $post_date;
			$post_data['post_date']		= $post_date;			
		}				
		
		// set user
		if( $user ){
			$post_data['post_author'] = $user;
		}
		
		$post_id = wp_insert_post( $post_data, true );
		
		// import video thumbnail as featured image
		if( import_image_on('post_create') ){				
			// import featured image
			yvi_set_featured_image( $post_id, $video );
		}	
		
		// set post format
		if( $post_format  ){
			set_post_format( $post_id, $post_format );
		}
		
		// check if post was created
		if( !is_wp_error($post_id) ){
			// set post category
			if( $category ){
				wp_set_post_terms( $post_id, array($category), $taxonomy );
			}
			
			// action on post insert that allows setting of different meta on post
			do_action('yvi_post_insert', $post_id, $video, $theme_import, $post_type);
			
			// if importing as theme post, there might be some meta fields to be set
			if( $theme_import ){
				// video URL
				$url 		= 'https://www.youtube.com/watch?v='.$video['video_id'];
				// video thumbnail
				$thumb 		= end( $video['thumbnails'] );
				$thumbnail	= $thumb['url'];
				
				if( isset( $options['image_size'] ) && isset( $video['thumbnails'][ $options['image_size'] ]['url'] ) ){
					$thumbnail = $video['thumbnails'][ $options['image_size'] ]['url'];
				}
				// video embed
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
				$embed_code = '<iframe width="'.$ps['width'].'" height="'.yvi_player_height($ps['aspect_ratio'], $ps['width']).'" src="https://www.youtube.com/embed/'.$video['video_id'].'?'.$customize.'" frameborder="0" allowfullscreen></iframe>';
				
				foreach( $theme_import['post_meta'] as $k => $meta_key ){
					switch( $k ){
						case 'url' :
							update_post_meta($post_id, $meta_key, $url);
						break;	
						case 'thumbnail':
							update_post_meta($post_id, $meta_key, $thumbnail);
						break;
						case 'embed':
							update_post_meta($post_id, $meta_key, $embed_code);
						break;	
					}							
				}
			}
			
			// set video ID meta to identify the video as imported
			update_post_meta($post_id, '__yvi_video_id', $video['video_id']);
			// set video URL; most likely it will be needed by other plugins
			update_post_meta($post_id, '__yvi_video_url', 'https://www.youtube.com/watch?v='.$video['video_id']);
			// store the video data for later use
			update_post_meta($post_id, '__yvi_video_data', $video);
			
			// if imported as regular post, flag it as video
			if( !$theme_import && import_as_post() ){	
				// flag post as video post					
				update_post_meta($post_id, '__yvi_is_video', true);
			}
			
			return true;			
		}// end checking if not wp error on post insert
		return false;		
	}
	
	/**
	 * Removes extra text if set in Settings page to check descriptions and if found in
	 * imported description 
	 * 
	 * Callback function for filter 'yvi_video_post_content' set in class constructor
	 * 
	 * @param $content
	 * @param $video
	 * @param $theme_import
	 */
	public function format_description( $content, $video, $theme_import ){
		$settings = yvi_get_settings();
		
		// trim description based on given string delimiter
		$delimiter = false;
		if( isset( $settings['remove_after_text'] ) ){
			$delimiter = trim( esc_attr( yvi_strip_tags( $settings['remove_after_text'] ) ) );
		}
		if( $delimiter && !empty($delimiter) ){
			$position = strpos( $content, $delimiter );
			if( false != $position ){
				$content = substr( $content, 0, $position );
			}			
		}
		
		// make url's clickable if set
		if( isset($settings['make_clickable']) && $settings['make_clickable'] ){
			$content = make_clickable( $content );
		}
		
		return $content;		
	}
	
	/**
	 * Add video post type to homepage list of latest posts
	 * 
	 * Callback function for filter 'pre_get_posts' set in class constructor
	 */
	public function add_on_homepage( $query ){
		// check that page isn't admin page, is homepage and the query
		if ( !is_admin() && is_home() && $query->is_main_query() ){
			// get plugin settings
			$settings = yvi_get_settings();
			if( $settings['public'] && isset( $settings['homepage'] ) && $settings['homepage'] ){
				// get the post types queried
				$post_types = get_query_var('post_type');
				// add video to post type
				if( !is_array($post_types) ){
					$post_types = array( 'post', $this->post_type );
				}else{
					$post_types[] = $this->post_type;
				}
				
				// add video post type to query
				$query->set( 'post_type', $post_types );
			}				
		}
		return $query;	
	}
	
	/**
	 * Adds video post type to main feed.
	 * 
	 * Callback function to filter 'request' set in class constructor.
	 */
	public function add_to_main_feed( $vars ){
		if( isset( $vars['feed'] ) ){		
			$settings = yvi_get_settings();
			if( $settings['public'] && isset( $settings['main_rss'] ) && $settings['main_rss'] ){
				if( !isset( $vars['post_type'] ) ){
					$vars['post_type'] = array('post', $this->post_type);
					// set filter to put the correct taxonomy on custom post type video in feed entry
					add_filter('get_the_categories', array($this, 'set_feed_video_categories'));
				}
			}	
		}		
		return $vars;
	}
	
	/**
	 * Callback function for filter 'get_the_categories' set up in function 'YVI_Video_Post_Type->add_to_main_feed'
	 * When custom post type is inserted into main feed for each post the correct categorties based
	 * on post type taxonomy must be set. This does that otherwise all custom post type categories in 
	 * feed will end up as Uncategorized.
	 *
	 * @param array $categories
	 */
	public function set_feed_video_categories( $categories ){
		
		global $post;
		
		if( !$post || $this->post_type != $post->post_type ){
			return $categories;
		}
		
		$categories = get_the_terms( $post, $this->taxonomy );
		if ( ! $categories || is_wp_error( $categories ) )
			$categories = array();
		
		$categories = array_values( $categories );
		foreach ( array_keys( $categories ) as $key ) {
			_make_cat_compat( $categories[$key] );
		}	
		
		return $categories;
	}
	
	/**
	 * Helper function. Checks is current post is a video post.
	 * Also verifies regular post type and looks for flag variable '__yvi_is_video'
	 */
	public function is_video( $post = false ){
		if( !$post ){
			global $post;
		}
		if( is_numeric( $post ) ){
			get_post( $post );
		}	
		if( !$post ){
			return false;
		}
		
		if( $this->post_type == $post->post_type ){
			$is_video = get_post_meta( $post->ID, '__yvi_video_data', true );
			if( $is_video ){
				return true;
			}	
		}
		
		if( 'post' == $post->post_type ){
			$is_video = get_post_meta( $post->ID, '__yvi_is_video', true );
			if( $is_video ){
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Return post type
	 */
	public function get_post_type(){
		return $this->post_type;
	}
	/**
	 * Return taxonomy
	 */
	public function get_post_tax(){
		return $this->taxonomy;
	}
	/**
	 * Return playlist post type
	 */
	public function get_playlist_post_type(){
		return $this->playlist_type;
	}
	/**
	 * Return playlist custom field name
	 */
	public function get_playlist_meta_name(){
		return $this->playlist_meta;
	}
}