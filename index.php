<?php
/*
Plugin Name: YouTube Video Importer
Plugin URI: https://wordpress.org/plugins/youtube-video-importer/
Description: Import YouTube videos directly into WordPress and display them as posts or embedded in existing posts and/or pages as single videos or play-lists
Author: YouTube Importer
Version: 1.0.2
Author URI: http://www.youtubeimporter.com
*/	

define( 'YVI_PATH'		, plugin_dir_path(__FILE__) );
define( 'YVI_URL'		, plugin_dir_url(__FILE__) );
define( 'YVI_VERSION'	, '1.0.2');
define( 'YVI_DEBUG'		, false); // if true, will display various information in various admin areas

include_once YVI_PATH . 'functions/functions.php';
include_once YVI_PATH . 'functions/libs/custom-post-type.php';
include_once YVI_PATH . 'functions/third-party-com-yti.php';
include_once YVI_PATH . 'functions/libs/auto-import-videos.php';

class YVI_YouTube_Videos extends YVI_Video_Post_Type{
	
	/**
	 * Holds the number of units used by various YouTube API requests.
	 * @var int - number of units
	 */
	private $yt_units = 0;
	
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
		
		// hook to update the number of used YouTube units by various YouTube API requests
		add_action( 'yvi_yt_api_query', array( $this, 'add_yt_units' ), 10, 2 );
		add_action( 'shutdown', array( $this, 'store_yt_units' ), 9999 );
	}
	
	/**
	 * Verifies video post YouTube video and checks whether the video was removed from YouTube
	 * or if the video embed status is off.
	 */
	public function check_video_posts(){
		if( is_singular() ){
			// check if action is allowed from settings
			$settings = yvi_get_settings();
			if( !isset( $settings['check_video_status'] ) || !$settings['check_video_status'] ){
				return;
			}
			
			global $post;
			if( !$post ){
				return;
			}
			// get video ID
			$video_id = get_post_meta( $post->ID, '__yvi_video_id', true );
			// apply this only for published posts
			if( $video_id && 'publish' == $post->post_status ){
				/**
				 * A filter to bypass checking the video
				 * @var bool
				 */
				$allow_check = apply_filters( 'yvi_check_video_status' , true, $post, $video_id );
				if( !$allow_check ){
					return;
				}
				
				$time = get_post_meta( $post->ID, '__yvi_last_video_status_check', true );
				if( $time && DAY_IN_SECONDS > ( time() - $time ) ){
					return;
				}
				// update the timestamp
				update_post_meta( $post->ID , '__yvi_last_video_status_check', time() );
								
				// get the video details
				$video = yvi_yt_api_get_video( $video_id );
				// if video returned error, set post to draft
				if( yvi_is_youtube_error( $video ) || ( is_array( $video ) && !$video['privacy']['embeddable'] ) ){
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
					do_action( 'yvi_unpublish_video', $post, $video );
					
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
					do_action( 'yvi_video_status_check', $post, $video );
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
			include_once YVI_PATH . 'functions/libs/shortcodes-yti.php';
			new YVI_Shortcodes();			
		}		
		
		// add administration resources
		if( is_admin() ){
			// load translation
			load_plugin_textdomain('yvi_video', false, dirname( plugin_basename( __FILE__ ) ).'/languages');
			// load administration related functions
			require_once YVI_PATH . 'functions/admin/functions.php';
			// add administration class
			require_once YVI_PATH . 'functions/admin/libs/class-yti-admin.php';
			global $yvi_admin;
			$yvi_admin = new YVI_Admin();			
			
			include_once YVI_PATH . 'functions/admin/libs/upgrade-yti.php';
						
		}
	}
	
	/**
	 * wp_print_scripts callback
	 * Enqueue video player scripts in front-end
	 */
	public function print_scripts(){	
		$settings 	= yvi_get_settings();
		$is_visible = $settings['archives'] ? true : is_single();
		
		if( is_admin() || !$is_visible || !yvii_is_video() ){
			return;
		}
		
		yvii_enqueue_player();		
	}
	
	/**
	 * Process the post content to remove autoembeds if needed
	 * @param string $content
	 */
	public function content_filter( $content ){		
		if( is_admin() || !yvii_is_video() ){
			return $content;
		}
		
		$settings 	= yvi_get_settings();
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
		$plugin_settings 	= yvi_get_settings();
		$is_visible = $plugin_settings['archives'] ? true : is_single();
		
		if( is_admin() || !$is_visible || !yvii_is_video() ){
			return $content;
		}
		
		global $post;
		$settings 	= yvii_get_video_settings( $post->ID, true );
		$video 		= get_post_meta($post->ID, '__yvi_video_data', true);
		
		if( !$video ){
			return $content;
		}
		
		/**
		 * Filter that allows prevention of automatically embedding
		 * videos on post/video post type pages.
		 * @var bool - allow or deny embedding
		 */
		$allow_embed = apply_filters( 'yvii_embed_videos' , true, $post, $video );
		if( !$allow_embed ){
			return $content;
		}
		
		$settings['video_id'] = $video['video_id'];
		// player size	
		$width 	= $settings['width'];
		$height = yvi_player_height( $settings['aspect_ratio'] , $width);
		
		/**
		 * Filter that allows adding extra CSS classes on video container
		 * for styling.
		 * @var array - array of classes
		 */
		$class 		= apply_filters('yvii_video_post_css_class', array(), $post);
		$extra_css 	= implode(' ', $class);
		
		$video_container = '<div class="yvii_single_video_player ' . $extra_css.'" ' . yvi_data_attributes( $settings ) . ' style="width:' . $width . 'px; height:' . $height . 'px; max-width:100%;"><!-- player container --></div>';
		
		/**
		 * Filter that can display content before the video output
		 * @var string - HTML
		 * @param $post - post object
		 * @param $video - video array
		 */
		$before_video 	= apply_filters( 'yvi_before_video_embed', '', $post, $video );
		/**
		 * Filter that can display content after the video output
		 * @var string - HTML
		 * @param $post - post object
		 * @param $video - video array
		 */
		$after_video 	= apply_filters( 'yvi_after_video_embed', '', $post, $video );
		
		yvii_enqueue_player();
		
		// put the filter back for other posts; remove in function 'yvii_first_content_filter'
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
		$options = yvi_get_settings();
		if( !isset( $options['public'] ) || !$options['public'] ){
			return;
		}
			
		include YVI_PATH . 'functions/libs//latest-videos-widget.php';
		register_widget( 'YVI_Latest_Videos_Widget' );
		
		include YVI_PATH . 'functions/libs/videos-taxonomy-widget.php';
		register_widget( 'YVI_Videos_Taxonomy_Widget' );
	}

	/**
	 * Store the number of units used on a page display.
	 * @param string $endpoint
	 * @param int $units
	 */
	public function add_yt_units( $endpoint, $units ){
		$this->yt_units += $units;
	}
	
	/**
	 * Store any consumed units into plugin option
	 */
	public function store_yt_units(){		
		// set timezone to PST
		date_default_timezone_set('America/Los_Angeles');
		$day = date('z');
		$stats = get_option( 'yti_daily_yt_units', array( 'day' => -1, 'count' => 0 ) );
		
		// no units used, no reset needed, no need to do anything
		if( 0 == $this->yt_units && $day == $stats['day'] ){
			return;
		}		
		
		// reset count if day changed
		if( $day != $stats['day'] ){
			$stats['count'] = 0;
			$stats['day'] = $day;
		}
		// update count
		$stats['count'] += $this->yt_units;
		// update option
		update_option( 'yvi_daily_yt_units' , $stats );
	}
}
global $YVI_POST_TYPE;
$YVI_POST_TYPE = new YVI_YouTube_Videos();