<?php
/*
Plugin Name: YouTube Importer
Plugin URI: http://www.youtubeimporter.com
Description: Import YouTube videos directly into WordPress and display them as posts or embeded in existing posts and/or pages as single videos or playlists.
Author: YouTube Importer
Version: 1.0
Author URI: http://www.youtubeimporter.com
*/	

define( 'YTI_PATH'		, plugin_dir_path(__FILE__) );
define( 'YTI_URL'		, plugin_dir_url(__FILE__) );
define( 'YTI_VERSION'	, '1.0');
define( 'YTI_DEBUG'		, false); // if true, will display various information in various admin areas

include_once YTI_PATH . 'includes/functions.php';
include_once YTI_PATH . 'includes/libs/custom-post-type.class.php';
include_once YTI_PATH . 'includes/third-party-compatibility.php';
include_once YTI_PATH . 'includes/libs/auto-import-videos.class.php';

class YTI_YouTube_Videos extends YTI_Video_Post_Type{
	
	/**
	 * Constructor, sets up various actions and filters
	 */
	public function __construct(){
		
		add_action( 'wp' , array( $this, 'check_video_posts' ) );
		
		// allows differential loading on front-end and back-end
		add_action( 'init', array( $this, 'on_init' ), -99999 );
		
		// enqueue video player script on video pages
		add_action('wp_print_scripts', array( $this, 'print_scripts' ) );
		
		// first post content filter, removes WP autoembed if video descriptions contain youtube links 
		add_filter( 'the_content', array( $this, 'content_filter' ) , 1 );
		
		// second post content filter, embeds the video
		add_filter( 'the_content', array( $this, 'embed_video' ) , 1 );
		
		// activation hook
		register_activation_hook( __FILE__, array( $this, 'on_activation' ) );
		
		// fire up widgets
		add_action( 'widgets_init' , array( $this, 'init_widgets' ) );
	}
	
	/**
	 * Verifies video post YouTube video and checks whether the video was removed from YouTube
	 * or if the video embed status is off.
	 */
	public function check_video_posts(){
		if( is_singular() ){
			// check if action is allowed from settings
			$settings = yti_get_settings();
			if( !isset( $settings['check_video_status'] ) || !$settings['check_video_status'] ){
				return;
			}
			
			global $post;
			if( !$post ){
				return;
			}
			// get video ID
			$video_id = get_post_meta( $post->ID, '__yti_video_id', true );
			// apply this only for published posts
			if( $video_id && 'publish' == $post->post_status ){
				/**
				 * A filter to bypass checking the video
				 * @var bool
				 */
				$allow_check = apply_filters( 'yti_check_video_status' , true, $post, $video_id );
				if( !$allow_check ){
					return;
				}
				
				$time = get_post_meta( $post->ID, '__yti_last_video_status_check', true );
				if( $time && DAY_IN_SECONDS > ( time() - $time ) ){
					return;
				}
				// update the timestamp
				update_post_meta( $post->ID , '__yti_last_video_status_check', time() );
								
				// get the video details
				$video = yti_yt_api_get_video( $video_id );
				// if video returned error, set post to draft
				if( yti_is_youtube_error( $video ) || ( is_array( $video ) && !$video['privacy']['embeddable'] ) ){
					wp_update_post(
						array(
							'post_status' 	=> 'pending',
							'ID' 			=> $post->ID
						)
					);
					
					/**
					 * Run action after post is modified to pending.
					 * @var $post - the post being modified
					 * @var $video - WP_Error object or video details array
					 */
					do_action( 'yti_unpublish_video', $post, $video );
					
					if( !is_user_logged_in() || !current_user_can('manage_options') ){					
						// issue a 404
						global $wp_query;
					    $wp_query->set_404();
					    status_header(404);	
					}				
				}else{
					/**
					 * Action that may allow other maintenance or update scripts to hook in.
					 * For example, this hook can be used to update the video stats.
					 * @var $post - current post object
					 * @var $video - video details
					 */					
					do_action( 'yti_video_status_check', $post, $video );
				}
			}
		}
	}
	
	/**
	 * Init callback, loads different stuff differentially
	 * on front and back-end
	 */
	public function on_init(){	
		// front-end
		if( !is_admin() ){
			// start custom post type class
			parent::__construct();
			include_once YTI_PATH . 'includes/libs/shortcodes.class.php';
			new YTI_Shortcodes();			
		}		
		
		// add administration resources
		if( is_admin() ){
			// load translation
			load_plugin_textdomain('yti_video', false, dirname( plugin_basename( __FILE__ ) ).'/languages');
			// load administration related functions
			require_once YTI_PATH . 'includes/admin/functions.php';
			// add administration class
			require_once YTI_PATH . 'includes/admin/libs/class-yti-admin.php';
			global $yti_admin;
			$yti_admin = new YTI_Admin();			
			
			include_once YTI_PATH . 'includes/admin/libs/upgrade.class.php';
			new YYTT_Plugin_Upgrade(array(
				'plugin'			=> __FILE__,
				'code' 				=> get_option('_yti_yt_plugin_envato_licence', ''),
				'product'			=> 71,
				'remote_url' 		=> 'http://www.youtubeimporter.com/check-updates/',
				'changelog_url'		=> 'http://www.youtubeimporter.com/plugin-details/',
				'current_version' 	=> YTI_VERSION
			));			
		}
	}
	
	/**
	 * wp_print_scripts callback
	 * Enqueue video player scripts in front-end
	 */
	public function print_scripts(){	
		$settings 	= yti_get_settings();
		$is_visible = $settings['archives'] ? true : is_single();
		
		if( is_admin() || !$is_visible || !yytt_is_video() ){
			return;
		}
		
		yytt_enqueue_player();		
	}
	
	/**
	 * Process the post content to remove autoembeds if needed
	 * @param string $content
	 */
	public function content_filter( $content ){		
		if( is_admin() || !yytt_is_video() ){
			return $content;
		}
		
		$settings 	= yti_get_settings();
		if( isset( $settings['prevent_autoembed'] ) && $settings['prevent_autoembed'] ){
			// remove the autoembed filter
			remove_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		}
	
		return $content;		
	}
	
	/**
	 * the_content callback function
	 * Embeds video in post content
	 * @param string $content
	 */
	public function embed_video( $content ){
		// plugin settings
		$plugin_settings 	= yti_get_settings();
		$is_visible = $plugin_settings['archives'] ? true : is_single();
		
		if( is_admin() || !$is_visible || !yytt_is_video() ){
			return $content;
		}
		
		global $post;
		$settings 	= yytt_get_video_settings( $post->ID, true );
		$video 		= get_post_meta($post->ID, '__yti_video_data', true);
		
		if( !$video ){
			return $content;
		}
		
		/**
		 * Filter that allows prevention of automatically embedding
		 * videos on post/video post type pages.
		 * @var bool - allow or deny embedding
		 */
		$allow_embed = apply_filters( 'yytt_embed_videos' , true, $post, $video );
		if( !$allow_embed ){
			return $content;
		}
		
		$settings['video_id'] = $video['video_id'];
		// player size	
		$width 	= $settings['width'];
		$height = yti_player_height( $settings['aspect_ratio'] , $width);
		
		/**
		 * Filter that allows adding extra CSS classes on video container
		 * for styling.
		 * @var array - array of classes
		 */
		$class 		= apply_filters('yytt_video_post_css_class', array(), $post);
		$extra_css 	= implode(' ', $class);
		
		$video_container = '<div class="yytt_single_video_player ' . $extra_css.'" ' . yti_data_attributes( $settings ) . ' style="width:' . $width . 'px; height:' . $height . 'px; max-width:100%;"><!-- player container --></div>';
		
		/**
		 * Filter that can display content before the video output
		 * @var string - HTML
		 * @param $post - post object
		 * @param $video - video array
		 */
		$before_video 	= apply_filters( 'yti_before_video_embed', '', $post, $video );
		/**
		 * Filter that can display content after the video output
		 * @var string - HTML
		 * @param $post - post object
		 * @param $video - video array
		 */
		$after_video 	= apply_filters( 'yti_after_video_embed', '', $post, $video );
		
		yytt_enqueue_player();
		
		// put the filter back for other posts; remove in function 'yytt_first_content_filter'
		add_filter( 'the_content', array( $GLOBALS['wp_embed'], 'autoembed' ), 8 );
		
		if( 'below-content' == $settings['video_position'] ){
			return $content . $before_video . $video_container . $after_video;
		}else{
			return $before_video . $video_container . $after_video . $content;
		}
	}
	
	/**
	 * Plugin activation hook callback
	 */
	public function on_activation(){
		// register custom post
		parent::register_post();
		// create rewrite ( soft )
		flush_rewrite_rules( false );
	}
	
	/**
	 * Initialize plugin widgets
	 */
	public function init_widgets(){
		// check if posts are public
		$options = yti_get_settings();
		if( !isset( $options['public'] ) || !$options['public'] ){
			return;
		}
			
		include YTI_PATH . 'includes/libs/latest-videos-widget.class.php';
		register_widget( 'YTI_Latest_Videos_Widget' );
		
		include YTI_PATH . 'includes/libs/videos-taxonomy-widget.class.php';
		register_widget( 'YTI_Videos_Taxonomy_Widget' );
	}	
}
global $YTI_POST_TYPE;
$YTI_POST_TYPE = new YTI_YouTube_Videos();