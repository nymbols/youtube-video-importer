<?php

class YTI_Shortcodes{	
	/**
	 * Constructor, implements all available shortcodes
	 */
	public function __construct(){
		$shortcodes = $this->_shortcodes();
		foreach( $shortcodes as $tag => $data ){
			add_shortcode( $tag , $data['callback'] );
		}		
	}
	
	/**
	 * Contains all shortcode definitions with attributes and callbacks.
	 * @param string $shortcode - a shortcode name to return
	 */
	private function _shortcodes( $shortcode = '' ){
		
		$shortcodes = array(
			// single video shortcode
			'yti_video' => array(
				'callback' 	=> array( $this, 'shortcode_video' ),
				'atts' 		=> array(
					'id' => array(
						'description' 	=> __( 'video post ID (required)', 'yti_video' ),
						'value' 		=> false
					),
					'controls' => array(
						'description' 	=> __( 'Display controls on video player ( values: 0 or 1 )', 'yti_video' ),
						'value' 		=> 1
					),
					'autoplay' 		=> array(
						'description' 	=> __( 'Autoplay video ( value: 0 or 1 )', 'yti_video', 'yti_video' ),
						'value' 		=> 0
					),
					'volume' 		=> array(
						'description' 	=> __( 'Playback volums ( value between 0 and 100 )', 'yti_video' ),
						'value' 		=> 30
					),
					'width' 		=> array(
						'description' 	=> __( 'Player width ( value in pixels )', 'yti_video' ),
						'value' 		=> 600
					),
					'aspect_ratio' 	=> array(
						'description' 	=> __( 'Video player aspect ratio ( value: 16x9 or 4x3 )', 'yti_video' ),
						'value' 		=> '16x9'
					)
				)
			),
			// plugin playlist shortcode
			'yti_playlist' => array(
				'callback' => array( $this, 'shortcode_playlist' ),
				'atts' => array(
					'videos' => array(
						'description' 	=> __( 'Comma separated list of video post IDs ( ie: 12,15,29,44 ) ', 'yti_video' ),
						'value' 		=> ''
					)
				)
			)
		);
		
		if( !empty( $shortcode ) ){
			if( array_key_exists( $shortcode , $shortcodes ) ){
				return $shortcodes[ $shortcode ];
			}else{
				return false;
			}
		}		
		return $shortcodes;
		
	}
	
	/**
	 * Returns default atts for a given shortcode name
	 * @param string $shortcode
	 */
	private function _shortcode_defaults( $shortcode ){
		$data = $this->_shortcodes( $shortcode );
		if( !$data ){
			trigger_error( sprintf( __('Unknown shortcode %s.', 'yti_video'), $shortcode ) , E_USER_NOTICE );
			return;
		}

		$atts = array();
		foreach( $data['atts'] as $att => $val ){
			$atts[ $att ] = $val['value'];
		}
		
		return $atts;
	}
	
	/**
	 * Shortcode callback
	 * Single video embed
	 * 
	 * Usage: [yti_video id="WP post ID"]
	 *  
	 * @param array $atts
	 * @param string $content
	 */
	public function shortcode_video( $atts, $content = '' ){
		// don't embed on feeds or video posts
		if( is_feed() || yytt_is_video() ){
			return;
		}
		// get shortcode defaults
		$defaults = $this->_shortcode_defaults( 'yti_video' );
		if( !$defaults ){
			return;
		}
		// extract shortcode atts
		$atts = shortcode_atts( 
			$defaults, 
			$atts 
		);
		
		// no ID provided, bail out
		if( empty( $atts['id'] ) || !$atts['id'] ){
			return;
		}
				
		// get video options attached to post
		$video_opt = yytt_get_video_settings( $atts['id'] );
		// get video data
		$video 	= get_post_meta( $atts['id'], '__yti_video_data', true );
		
		if( !$video ){
			if( current_user_can( 'edit_posts' ) ){
				return '<span style="color:red;">' . sprintf( __('Shortcode error: Video post ID %d not found.', 'yti_video'), $atts['id'] ) . '</span>';
			}
			// video not found, stop
			return;
		}
		
		// combine video vars with atts
		$vars = shortcode_atts( array(
			'controls' 		=> $video_opt['controls'],
			'autoplay'		=> $video_opt['autoplay'],
			'volume' 		=> $video_opt['volume'],
			'width' 		=> $video_opt['width'],
			'aspect_ratio' 	=> $video_opt['aspect_ratio']
		), $atts );
		
		if( !$vars['width'] ){
			return false;
		}
		
		$width	= absint( $vars['width'] );
		$height = yti_player_height( $vars['aspect_ratio'] , $vars['width'] );
		
		$settings 				= wp_parse_args( $vars, $video_opt );
		$settings['video_id'] 	= $video['video_id'];
		
		$video_container = '<div class="yytt_single_video_player" ' . yti_data_attributes( $settings ) . ' style="width:' . $width . 'px; height:' . $height . 'px; max-width:100%;"><!--video player--></div>';
		// add JS file
		yytt_enqueue_player();
		return $video_container;		
	}
	
	/**
	 * Shortcode callback
	 * Displays a playlist made of imported videos
	 * 
	 * Usage: [yti_playlist videos="ID,ID"]
	 * 
	 * @param array $atts
	 * @param string $content
	 */
	public function shortcode_playlist( $atts, $content = '' ){
		// check if atts is set
		if( !is_array( $atts ) || !array_key_exists('videos', $atts) ){
			return;
		}
		
		// look for video ids
		$video_ids = explode( ',', $atts['videos'] );
		if( !$video_ids ){
			return;
		}
		
		$content = yti_output_playlist( $video_ids );	
		return $content;		
	}
	
	/**
     * Returns all registered shortcodes
     */
	public function get_shortcodes(){
		return $this->_shortcodes();
	}
}