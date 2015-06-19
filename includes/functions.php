<?php

/**
 * Creates from a number of given seconds a readable duration ( HH:MM:SS )
 * @param int $seconds
 */
function yti_human_time( $seconds ){
	
	$seconds = absint( $seconds );
	
	if( $seconds < 0 ){
		return;
	}
	
	$h = floor( $seconds / 3600 );
	$m = floor( $seconds % 3600 / 60 );
	$s = floor( $seconds %3600 % 60 );
	
	return ( ($h > 0 ? $h . ":" : "") . ( ($m < 10 ? "0" : "") . $m . ":" ) . ($s < 10 ? "0" : "") . $s);	
}

/**
 * @deprecated 
 * @since 1.8.1
 * 
 * Use yytt_is_video() instead
 */
function yytt_is_video_post(){
	return yytt_is_video();	
}

/**
 * Utility function. Checks if a given or current post is video created by the plugin 
 * @param object $post
 */
function yytt_is_video( $post = false ){
	global $YTI_POST_TYPE;
	return $YTI_POST_TYPE->is_video( $post );	
}

/**
 * Adds video player script to page
 */
function yytt_enqueue_player(){	
	wp_enqueue_script(
		'yytt-video-player',
		YTI_URL.'assets/front-end/js/video-player.js',
		array('jquery', 'swfobject'),
		'1.0'
	);
	
	wp_enqueue_style(
		'yytt-video-player',
		YTI_URL.'assets/front-end/css/video-player.css'
	);
}

/**
 * Utility function, returns plugin default settings
 */
function yti_plugin_settings_defaults(){
	$defaults = array(
		'public'				=> true, // post type is public or not
		'archives'				=> false, // display video embed on archive pages
		'homepage'				=> false, // include custom post type on homepage
		'main_rss'				=> false, // include custom post type into the main RSS feed
		'use_microdata'			=> false, // put microdata on video pages ( more details on: http://schema.org )
		'post_type_post'		=> false, // when true all videos will be imported as post type post and will disregard the theme compatibility layer
		'check_video_status'	=> false, // when true, it will check the video status on YouTube every 24h and change video post status to pending if video removed or not embeddable
		// rewrite	
		'post_slug'				=> 'video',
		'taxonomy_slug'			=> 'videos',	
		// bulk import
		'import_categories'		=> true, // import categories from YouTube
		'import_title' 			=> true, // import titles on custom posts
		'import_description' 	=> 'post_content', // import descriptions on custom posts
		'remove_after_text'		=> '', // descriptions that have this content will be truncated up to this text
		'prevent_autoembed'		=> false, // prevent autoembeds on video posts
		'make_clickable'		=> false, // make urls pasted in content clickable
		'import_date'			=> false, // import video date as post date
		'featured_image'		=> false, // set thumbnail as featured image; default import on video feed import (takes more time)
		'image_size'			=> 'standard', // image size to set on posts
		'maxres'				=> false, // when importing thumbnails, try to get the maximum resolution if available	
		'image_on_demand'		=> false, // when true, thumbnails will get imported only when viewing the video post as oposed to being imported on feed importing
		'import_results' 		=> 100, // default number of feed results to display
		'import_status'			=> 'draft', // default import status of videos
		// automatic import
		'import_frequency'		=> 5, // in minutes
		'import_quantity'		=> 20,
		'manual_import_per_page' => 20		
	);
	return $defaults;
}

/**
 * Utility function, returns plugin settings
 */
function yti_get_settings(){
	$defaults = yti_plugin_settings_defaults();
	$option = get_option('_yti_plugin_settings', $defaults);
	
	foreach( $defaults as $k => $v ){
		if( !isset( $option[ $k ] ) ){
			$option[ $k ] = $v;
		}
	}
	
	return $option;
}

/**
 * Verification function to see if setting to force imports as posts is set.
 */
function import_as_post(){
	$settings = yti_get_settings();
	if( isset( $settings['post_type_post'] ) && $settings['post_type_post'] ){
		return (bool) $settings['post_type_post'];
	}
	return false;
}

/**
 * Simple verification function to check if image should be imported and when ( on post creation or on post display )
 * 
 * @param string $situation - post_create: import image when creating posts; post_display: import image when displaying the post
 */
function import_image_on( $situation = 'post_create' ){
	$settings = yti_get_settings();
	if( !isset( $settings['featured_image'] ) || !$settings['featured_image'] ){
		return false;
	}
	
	switch( $situation ){
		case 'post_create':
			return !(bool)$settings['image_on_demand'];
		break;	
		case 'post_display':
			return (bool)$settings['image_on_demand'];
		break;	
	}
	return false;	
}

/**
 * Utility function, updates plugin settings
 */
function yti_update_settings(){	
	$defaults = yti_plugin_settings_defaults();
	foreach( $defaults as $key => $val ){
		if( is_numeric( $val ) ){
			if( isset( $_POST[ $key ] ) ){
				$defaults[ $key ] = (int)$_POST[ $key ];
			}
			continue;
		}
		if( is_bool( $val ) ){
			$defaults[ $key ] = isset( $_POST[ $key ] );
			continue;
		}
		
		if( isset( $_POST[ $key ] ) ){
			$defaults[ $key ] = $_POST[ $key ];
		}
	}
	
	// rewrite
	$plugin_settings = yti_get_settings();
	$flush_rules = false;
	if( isset( $_POST['post_slug'] ) ){
		$post_slug = sanitize_title( $_POST['post_slug'] );
		if( !empty( $_POST['post_slug'] ) && $plugin_settings['post_slug'] !== $post_slug ){
			$defaults['post_slug'] = $post_slug;
			$flush_rules = true;
		}else{
			$defaults['post_slug'] = $plugin_settings['post_slug'];
		}
	}
	if( isset( $_POST['taxonomy_slug'] ) ){
		$tax_slug = sanitize_title( $_POST['taxonomy_slug'] );
		if( !empty( $_POST['taxonomy_slug'] ) && $plugin_settings['taxonomy_slug'] !== $tax_slug ){
			$defaults['taxonomy_slug'] = $tax_slug;
			$flush_rules = true;
		}else{
			$defaults['taxonomy_slug'] = $plugin_settings['taxonomy_slug'];
		}
	}
		
	update_option('_yti_plugin_settings', $defaults);
	// update automatic imports
	if( $plugin_settings['import_frequency'] != $defaults['import_frequency'] ){
		global $YTI_AUTOMATIC_IMPORT;
		$YTI_AUTOMATIC_IMPORT->update_transient();
	}
		
	if( $flush_rules ){	
		// create rewrite ( soft )
		global $YTI_POST_TYPE;
		// register custom post
		$YTI_POST_TYPE->register_post();
		// create rewrite ( soft )
		flush_rewrite_rules( false );
	}	
}

/**
 * Global player settings defaults.
 */
function yti_player_settings_defaults(){
	$defaults = array(
		'controls' 	=> 1, // show player controls. Values: 0 or 1
		'autohide' 	=> 0, // 0 - always show controls; 1 - hide controls when playing; 2 - hide progress bar when playing
		'fs'		=> 1, // 0 - fullscreen button hidden; 1 - fullscreen button displayed
		'theme'		=> 'dark', // dark or light
		'color'		=> 'red', // red or white	
	
		'iv_load_policy' => 1, // 1 - show annotations; 3 - hide annotations
		'modestbranding' => 1, // 1 - small branding
		'rel'			 =>	1, // 0 - don't show related videos when video ends; 1 - show related videos when video ends
		'showinfo'		 => 0, // 0 - don't show video info by default; 1 - show video info in player
	
		'autoplay'	=> 0, // 0 - on load, player won't play video; 1 - on load player plays video automatically
		//'loop'		=> 0, // 0 - video won't start again once finished; 1 - video will play again once finished

		'disablekb'	=> 0, // 0 - allow keyboard controls; 1 - disable keyboard controls

		// extra settings
		'aspect_ratio'		=> '16x9',
		'width'				=> 640,
		'video_position' 	=> 'below-content', // in front-end custom post, where to display the video: above or below post content
		'volume'			=> 30, // video default volume	
	);
	return $defaults;
}

/**
 * Get general player settings
 */
function yti_get_player_settings(){
	$defaults 	= yti_player_settings_defaults();
	$option 	= get_option('_yti_player_settings', $defaults);
	
	foreach( $defaults as $k => $v ){
		if( !isset( $option[ $k ] ) ){
			$option[ $k ] = $v;
		}
	}
	
	// various player outputs may set their own player settings. Return those.
	global $YTI_PLAYER_SETTINGS;
	if( $YTI_PLAYER_SETTINGS ){
		foreach( $option as $k => $v ){
			if( isset( $YTI_PLAYER_SETTINGS[$k] ) ){
				$option[$k] = $YTI_PLAYER_SETTINGS[$k];
			}
		}
	}
	
	return $option;
}

/**
 * Update general player settings
 */
function yti_update_player_settings(){
	$defaults = yti_player_settings_defaults();
	foreach( $defaults as $key => $val ){
		if( is_numeric( $val ) ){
			if( isset( $_POST[ $key ] ) ){
				$defaults[ $key ] = (int)$_POST[ $key ];
			}else{
				$defaults[ $key ] = 0;
			}
			continue;
		}
		if( is_bool( $val ) ){
			$defaults[ $key ] = isset( $_POST[ $key ] );
			continue;
		}
		
		if( isset( $_POST[ $key ] ) ){
			$defaults[ $key ] = $_POST[ $key ];
		}
	}
	
	update_option('_yti_player_settings', $defaults);	
}

/**
 * Calculate player height from given aspect ratio and width
 * @param string $aspect_ratio
 * @param int $width
 */
function yti_player_height( $aspect_ratio, $width ){
	$width = absint($width);
	$height = 0;
	switch( $aspect_ratio ){
		case '4x3':
			$height = ($width * 3) / 4;
		break;
		case '16x9':
		default:	
			$height = ($width * 9) / 16;
		break;	
	}
	return $height;
}

/**
 * Single post default settings
 */
function yytt_post_settings_defaults(){
	// general player settings
	$plugin_defaults = yti_get_player_settings();	
	return $plugin_defaults;
}

/**
 * Returns playback settings set on a video post
 */
function yytt_get_video_settings( $post_id = false, $output = false ){
	global $YTI_POST_TYPE;
	if( !$post_id ){
		global $post;
		if( !$post || !yytt_is_video($post) ){
			return false;
		}
		$post_id = $post->ID;		
	}else{
		$post = get_post( $post_id );
		if( !$post || !yytt_is_video($post) ){
			return false;
		}
	}
	
	$defaults = yytt_post_settings_defaults();
	$option = get_post_meta( $post_id, '__yti_playback_settings', true );
	
	foreach( $defaults as $k => $v ){
		if( !isset( $option[ $k ] ) ){
			$option[ $k ] = $v;
		}
	}
	
	if( $output ){
		foreach( $option as $k => $v ){
			if( is_bool( $v ) ){
				$option[$k] = absint( $v );
			}
		}
	}
	
	return $option;
}

/**
 * Utility function, updates video settings
 */
function yytt_update_video_settings( $post_id ){
	
	if( !$post_id ){
		return false;
	}
	
	$post = get_post( $post_id );
	if( !$post || !yytt_is_video( $post ) ){
		return false;
	}
		
	$defaults = yytt_post_settings_defaults();
	foreach( $defaults as $key => $val ){
		if( is_numeric( $val ) ){
			if( isset( $_POST[ $key ] ) ){
				$defaults[ $key ] = (int)$_POST[ $key ];
			}else{
				$defaults[ $key ] = 0;
			}
			continue;
		}
		if( is_bool( $val ) ){
			$defaults[ $key ] = isset( $_POST[ $key ] );
			continue;
		}
		
		if( isset( $_POST[ $key ] ) ){
			$defaults[ $key ] = $_POST[ $key ];
		}
	}
	
	update_post_meta($post_id, '__yti_playback_settings', $defaults);	
}


/**
 * Set thumbnail as featured image for a given post ID
 * @param int $post_id
 */
function yti_set_featured_image( $post_id, $video_meta = false ){
	
	if( !$post_id ){
		return false;
	}
	
	$post = get_post( $post_id );		
	if( !$post ){
		return false;
	}
	
	// try to get video details
	if( !$video_meta ){
		$video_meta = get_post_meta( $post_id, '__yti_video_data', true );
		if( !$video_meta ){
			// if meta isn't found, try to get video ID and retrieve the meta
			$video_id = get_post_meta( $post_id, '__yti_video_id', true );
			// video ID not found, give up
			if( $video_id ){
				// query the video
				$video = yti_yt_api_get_video( $video_id );
				if( $video && !is_wp_error( $video ) ){
					$video_meta = $video;
				}
			}
		}
	}
	
	// check that thumbnails exist to avoid issues
	if( !is_array( $video_meta ) || !array_key_exists( 'thumbnails', $video_meta ) ){
		return false;
	}
	
	// check if thumbnail was already imported
	$attachment = get_posts( array(
		'post_type' 	=> 'attachment',
		'meta_key'  	=> 'video_thumbnail',
		'meta_value'	=> $video_meta['video_id']
	));
	// if thumbnail exists, return it
	if( $attachment ){
		// set image as featured for current post
		set_post_thumbnail( $post_id, $attachment[0]->ID );
		return array(
			'post_id' 		=> $post_id,
			'attachment_id' => $attachment[0]->ID
		);
	}
	
	// get the thumbnail URL
	$settings = yti_get_settings();	
	$img_size = yti_get_image_size();
	if( isset( $video_meta['thumbnails'][ $img_size ]['url'] ) ){
		$thumb_url = $video_meta['thumbnails'][ $img_size ]['url'];
	}else{
		$thumb = end( $video_meta['thumbnails'] );
		if( $thumb ){
			$thumb_url = $thumb['url'];
		}
	}
	
	// get max resolution image if available
	if( isset( $settings['maxres'] ) && $settings['maxres'] ){
		$maxres_url = 'http://img.youtube.com/vi/' . $video_meta['video_id'] . '/maxresdefault.jpg';
		$maxres_result = wp_remote_get( $maxres_url, array( 'sslverify' => false ) );
		if( !is_wp_error( $maxres_result ) && 200 == wp_remote_retrieve_response_code($maxres_result) ){
			$response = $maxres_result;
		}
	}
	
	// if max resolution query wasn't successful, try to get the registered image size
	if( !isset( $response ) ){
		$response = wp_remote_get( $thumb_url, array( 'sslverify' => false ) );	
		if( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code($response) ) {
			return false;
		}
	}

	// set up image details
	$image_contents = $response['body'];
	$image_type 	= wp_remote_retrieve_header( $response, 'content-type' );
	$image_extension = false;
	switch( $image_type ){
		case 'image/jpeg':
			$image_extension = '.jpg';
		break;
		case 'image/png':
			$image_extension = '.png';
		break;	
	}
	// no valid image extension, stop here
	if( !$image_extension ){
		return;
	}

	// Construct a file name using post slug and extension
	$fname 				= urldecode( basename( get_permalink( $post_id ) ) ) ;
	// make suffix optional
	$suffix_filename 	= apply_filters( 'yti_apply_filename_suffix' , true );
	$suffix 			= $suffix_filename ? '-youtube-thumbnail' : '';	
	// construct new file name
	$new_filename = preg_replace('/[^A-Za-z0-9\-]/', '', $fname) . $suffix . $image_extension;
	
	// Save the image bits using the new filename
	$upload = wp_upload_bits( $new_filename, null, $image_contents );
	if ( $upload['error'] ) {
		return false;
	}
		
	$image_url 	= $upload['url'];
	$filename 	= $upload['file'];

	$wp_filetype = wp_check_filetype( basename( $filename ), null );
	$attachment = array(
		'post_mime_type'	=> $wp_filetype['type'],
		'post_title'		=> get_the_title( $post_id ),
		'post_content'		=> '',
		'post_status'		=> 'inherit',
		'guid'				=> $upload['url']
	);
	$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
	// you must first include the image.php file
	// for the function wp_generate_attachment_metadata() to work
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id, $attach_data );

	// Add field to mark image as a video thumbnail
	update_post_meta( $attach_id, 'video_thumbnail', $video_meta['video_id'] );
		
	// set image as featured for current post
	update_post_meta( $post_id, '_thumbnail_id', $attach_id );
	
	return array(
		'post_id' 		=> $post_id,
		'attachment_id' => $attach_id
	);	
}

/**
 * Returns size of image that should be imported
 */
function yti_get_image_size(){
	// plugin settings
	$settings 	= yti_get_settings();
	// allowed image sizes
	$sizes 		= array( 'default', 'medium', 'high', 'standard', 'maxres' );
	// set default to standard
	$img_size	= 'standard';
	
	if( isset( $settings['image_size'] ) ){
		if( in_array( $settings['image_size'], $sizes ) ){
			$img_size = $settings['image_size'];
		}else{
			// old sizes
			switch( $settings['image_size'] ){
				case 'mqdefault':
					$img_size = 'medium';
				break;
				case 'hqdefault':
					$img_size = 'high';
				break;
				case 'sddefault':
					$img_size = 'stadard';
				break;	
			}
		}
	}
	return $img_size;
}

/**
 * Outputs a plugin playlist.
 * 
 * @param unknown_type $videos
 * @param unknown_type $results
 * @param unknown_type $theme
 * @param unknown_type $player_settings
 * @param unknown_type $taxonomy
 */
function yti_output_playlist( $videos = 'latest', $results = 5, $theme = 'default', $player_settings = array(), $taxonomy = false ){
	global $YTI_POST_TYPE;
	$args = array(
		'post_type' 		=> array($YTI_POST_TYPE->get_post_type(), 'post'),
		'posts_per_page' 	=> absint( $results ),
		'numberposts'		=> absint( $results ),
		'post_status'		=> 'publish',
		'supress_filters'	=> true
	);
	
	// taxonomy query
	if( !is_array( $videos ) && isset( $taxonomy ) && !empty( $taxonomy ) && ((int)$taxonomy) > 0 ){
		$term = get_term( $taxonomy, $YTI_POST_TYPE->get_post_tax(), ARRAY_A );
		if( !is_wp_error( $term ) ){			
			$args[ $YTI_POST_TYPE->get_post_tax() ] = $term['slug'];
		}	
	}
	
	// if $videos is array, the function was called with an array of video ids
	if( is_array( $videos ) ){
		
		$ids = array();
		foreach( $videos as $video_id ){
			$ids[] = absint( $video_id );
		}		
		$args['include'] 		= $ids;
		$args['posts_per_page'] = count($ids);
		$args['numberposts'] 	= count($ids);
		
	}elseif( is_string( $videos ) ){
		
		$found = false;
		switch( $videos ){
			case 'latest':
				$args['orderby']	= 'post_date';
				$args['order']		= 'DESC';
				$found 				= true;
			break;	
		}
		if( !$found ){
			return;
		}
				
	}else{ // if $videos is anything else other than array or string, bail out		
		return;		
	}
	
	// get video posts
	$posts = get_posts( $args );
	
	if( !$posts ){
		return;
	}
	
	$videos = array();
	foreach( $posts as $post_key => $post ){
		
		if( !yytt_is_video( $post ) ){
			continue;
		}
		
		if( isset( $ids ) ){
			$key = array_search($post->ID, $ids);
		}else{
			$key = $post_key;
		}	
		
		if( is_numeric( $key ) ){
			$videos[ $key ] = array(
				'ID'			=> $post->ID,
				'title' 		=> $post->post_title,
				'video_data' 	=> get_post_meta( $post->ID, '__yti_video_data', true )
			);
		}
	}
	ksort( $videos );
	
	ob_start();
	
	// set custom player settings if any
	global $YTI_PLAYER_SETTINGS;
	if( $player_settings && is_array( $player_settings ) ){
		
		$YTI_PLAYER_SETTINGS = $player_settings;
	}
	
	global $yti_video;
	
	include( YTI_PATH.'themes/default/player.php' );
	$content = ob_get_contents();
	ob_end_clean();
	
	yytt_enqueue_player();
	wp_enqueue_script(
		'yti-yt-player-default', 
		YTI_URL.'themes/default/assets/script.js', 
		array('yytt-video-player'), 
		'1.0'
	);
	wp_enqueue_style(
		'yytt-yt-player-default', 
		YTI_URL.'themes/default/assets/stylesheet.css', 
		false, 
		'1.0'
	);
	
	// remove custom player settings
	$YTI_PLAYER_SETTINGS = false;
	
	return $content;
	
}


/**
 * TEMPLATING
 */

/**
 * Outputs default player data
 */
function yti_output_player_data( $echo = true ){
	$player = yti_get_player_settings();
	$attributes = yti_data_attributes( $player, $echo );	
	return $attributes;
}

/**
 * Output video parameters as data-* attributes
 * @param array $array - key=>value pairs
 * @param bool $echo	
 */
function yti_data_attributes( $attributes, $echo = false ){
	$result = array();
	foreach( $attributes as $key=>$value ){
		$result[] = sprintf( 'data-%s="%s"', $key, $value );
	}
	if( $echo ){
		echo implode(' ', $result);
	}else{
		return implode(' ', $result);
	}	
}

/**
 * Outputs the default player size
 * @param string $before
 * @param string $after
 * @param bool $echo
 */
function yti_output_player_size( $before = ' style="', $after='"', $echo = true ){
	$player = yti_get_player_settings();
	$height = yti_player_height($player['aspect_ratio'], $player['width']);
	$output = 'width:'.$player['width'].'px; height:'.$height.'px;';
	if( $echo ){
		echo $before.$output.$after;
	}
	
	return $before.$output.$after;
}

/**
 * Output width according to player
 * @param string $before
 * @param string $after
 * @param bool $echo
 */
function yti_output_width( $before = ' style="', $after='"', $echo = true ){
	$player = yti_get_player_settings();
	if( $echo ){
		echo $before.'width: '.$player['width'].'px; '.$after;
	}
	return $before.'width: '.$player['width'].'px; '.$after;
}

/**
 * Output video thumbnail
 * @param string $before
 * @param string $after
 * @param bool $echo
 */
function yti_output_thumbnail( $before = '', $after = '', $echo = true ){
	global $yti_video;
	$output = '';
	if( isset( $yti_video['video_data']['thumbnails'][0] ) ){
		$output = sprintf('<img src="%s" alt="" />', $yti_video['video_data']['thumbnails'][0]);
	}
	if( $echo ){
		echo $before.$output.$after;
	}
	return $before.$output.$after;
}

/**
 * Output video title
 * @param string $before
 * @param string $after
 * @param bool $echo
 */
function yti_output_title( $include_duration = true,  $before = '', $after = '', $echo = true  ){
	global $yti_video;
	$output = '';
	if( isset( $yti_video['title'] ) ){
		$output = $yti_video['title'];
	}
	
	if( $include_duration ){
		$output .= ' <span class="duration">['.yti_human_time( $yti_video['video_data']['duration'] ).']</span>';
	}
	
	if( $echo ){
		echo $before.$output.$after;
	}
	return $before.$output.$after;
}

/**
 * Outputs video data
 * @param string $before
 * @param string $after
 * @param bool $echo
 */
function yti_output_video_data( $before = " ", $after="", $echo = true ){
	global $yti_video;
	
	$video_settings = ccb_get_video_settings( $yti_video['ID'] );	
	$video_id 		= $yti_video['video_data']['video_id'];
	$data = array(
		'video_id' 	=> $video_id,
		'autoplay' 	=> $video_settings['autoplay'],
		'volume'  	=> $video_settings['volume']
	);
	
	$output = yti_data_attributes($data);
	if( $echo ){
		echo $before.$output.$after;
	}
	
	return $before.$output.$after;
}

function yti_video_post_permalink( $echo  = true ){
	global $yti_video;
	
	$pl = get_permalink( $yti_video['ID'] );
	
	if( $echo ){
		echo $pl;
	}
	
	return $pl;
	
}

/**
 * Themes compatibility layer
 */

/**
 * Check if theme is supported by the plugin.
 * Returns false or an array containing a mapping for custom post fields to store information on
 */
function yti_check_theme_support(){	
	
	global $YTI_THIRD_PARTY_THEME;
	if( !$YTI_THIRD_PARTY_THEME ){
		$YTI_THIRD_PARTY_THEME = new YTI_Third_Party_Compat();
	}
	$theme = $YTI_THIRD_PARTY_THEME->get_theme_compatibility();
	return $theme;
}

/**
 * Returns all compatible themes details
 */
function yti_get_compatible_themes(){
	// access the theme support function to create the class instance
	yti_check_theme_support();
	global $YTI_THIRD_PARTY_THEME;
	
	return $YTI_THIRD_PARTY_THEME->get_compatible_themes();
}

/**
 * Playlists
 */

/**
 * Global playlist settings defaults.
 */
function yti_playlist_settings_defaults(){
	$defaults = array(
		'post_title' 		=> '',
		'playlist_type'		=> 'user',
		'playlist_id'		=> '',
		'playlist_live'		=> true,
		'theme_import'		=> false,
		'native_tax'		=> -1,
		'theme_tax'			=> -1,
		'import_user'		=> -1,
		'start_date'		=> false,
		'no_reiterate'		=> false
	);
	return $defaults;
}

/**
 * Get general playlist settings
 */
function yti_get_playlist_settings( $post_id ){
	$defaults 	= yti_playlist_settings_defaults();
	$option 	= get_post_meta($post_id, '_yti_playlist_settings', true);
	
	foreach( $defaults as $k => $v ){
		if( !isset( $option[ $k ] ) ){
			$option[ $k ] = $v;
		}
	}
	
	return $option;
}

function yti_get_server_cron_address(){
	$url = get_bloginfo( 'url' ) . '/?yti_external_cron=true';
	$nonce_url = wp_nonce_url( $url, 'yti_import_yt_playlists', 'yti_nonce' );
	return $nonce_url;
}

function yti_human_update_delay(){
	
	global $YTI_AUTOMATIC_IMPORT;
	$import_data 	= $YTI_AUTOMATIC_IMPORT->get_update();
	$delay 			= $YTI_AUTOMATIC_IMPORT->get_delay();
	
	if( !$import_data || empty( $import_data['time'] ) || isset( $import_data['empty'] ) ){
		return;
	}
	
	// get the time
	$current_time = time();
	$countdown = true;
	// calculate delay for outdated cron jobs - for server cron
	if( $current_time - $import_data['time'] > $delay ){
		$diff = $current_time - ($import_data['time'] + $delay);
		$countdown = false;
	}else{// normal delay countdown	
		$diff = $delay -( $current_time - $import_data['time'] );
	}	
	
	return array( 'time' => yti_human_time($diff), 'seconds' => $diff, 'countdown' => $countdown );
}

function yti_automatic_update_timing(){
	
	$values = array(
		'1'		=> __('minute', 'yti_video'),
		'5'		=> __('5 minutes', 'yti_video'),
		'15' 	=> __('15 minutes', 'yti_video'),
		'30' 	=> __('30 minutes', 'yti_video'),
		'60'	=> __('hour', 'yti_video'),
		'120'	=> __('2 hours', 'yti_video'),
		'180'	=> __('3 hours', 'yti_video'),
		'360'	=> __('6 hours', 'yti_video'),
		'720'	=> __('12 hours', 'yti_video'),
		'1440'	=> __('day', 'yti_video')
	);
	return $values;	
}

function yti_automatic_update_batches(){
	
	$values = array(
		'1'	 => __('1 video', 'yti_video'),
		'5'	 => __('5 videos', 'yti_video'),
		'10' => __('10 videos', 'yti_video'),
		'15' => __('15 videos', 'yti_video'),
		'20' => __('20 videos', 'yti_video'),
		'25' => __('25 videos', 'yti_video'),
		'30' => __('30 videos', 'yti_video'),
		'40' => __('40 videos', 'yti_video'),
		'50' => __('50 videos', 'yti_video')
	);
	
	return $values;
	
}

/**
 * Add microdata on video pages
 * @param string/HTML $content
 */
function yti_video_schema( $content ){
	
	// check if microdata insertion is permitted
	$settings = yti_get_settings();
	if( !isset( $settings['use_microdata'] ) || !$settings['use_microdata'] ){
		return $content;
	}
	// check the post
	global $post;
	if( !$post || !is_object( $post ) ){
		return $content;
	}
	// check if feed
	if ( is_feed() ){
		return $content;
	}
	// get video data from post
	$video_data = get_post_meta( $post->ID, '__yti_video_data', true );
	if( !$video_data ){
		// check if post has video ID
		$video_id = get_post_meta( $post->ID, '__yti_video_id', true );
		if( !$video_id ){
			return $content;
		}
		
		$video = yti_yt_api_get_video( $video_id );
		if( $video && !is_wp_error( $video ) ){				
			$video_data = $video;
			update_post_meta($post->ID, '__yti_video_data', $video_data);
		}else{
			return $content;
		}		
	}
	// if no video data, bail out
	if( !$video_data ){
		return $content;
	}
	
	$image = '';
	if( has_post_thumbnail( $post->ID ) ){
		$img = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ) );
		if( !$img ){
			$image = $video_data['thumbnails'][0];
		}else{
			$image = $img[0];
		}
	}else{
		$image = $video_data['thumbnails'][0];
	}
	// template for meta tag
	$meta = '<meta itemprop="%s" content="%s">';
	
	// create microdata output
	$html = "\n".'<span itemprop="video" itemscope itemtype="http://schema.org/VideoObject">'."\n\t";
	$html.= sprintf( $meta, 'name', esc_attr( yti_strip_tags( get_the_title() ) ) )."\n\t";
	$html.= sprintf( $meta, 'description', trim( substr( esc_attr( yti_strip_tags( $post->post_content ) ), 0, 300 ) ) )."\n\t";
	$html.= sprintf( $meta, 'thumbnailURL', $image )."\n\t";
	$html.= sprintf( $meta, 'embedURL', 'http://www.youtube-nocookie.com/v/'.$video_data['video_id'] )."\n\t";
	$html.= sprintf( $meta, 'uploadDate', date( 'c', strtotime( $post->post_date ) ) )."\n\t";
	$html.= sprintf( $meta, 'duration', yti_iso_duration( $video_data['duration'] ) )."\n";
	$html.= "</span>\n";
	
	return $content.$html;
}
add_filter('the_content', 'yti_video_schema', 999);

/**
 * More efficient strip tags
 *
 * @link  http://www.php.net/manual/en/function.strip-tags.php#110280
 * @param string $string string to strip tags from
 * @return string
 */
function yti_strip_tags($string) {
   
    // ----- remove HTML TAGs -----
    $string = preg_replace ('/<[^>]*>/', ' ', $string);
   
    // ----- remove control characters -----
    $string = str_replace("\r", '', $string);    // --- replace with empty space
    $string = str_replace("\n", ' ', $string);   // --- replace with space
    $string = str_replace("\t", ' ', $string);   // --- replace with space
   
    // ----- remove multiple spaces -----
    $string = trim(preg_replace('/ {2,}/', ' ', $string));
   
    return $string;
}

/**
 * Returns ISO duration from a given number of seconds
 * @param int $seconds
 */
function yti_iso_duration( $seconds ) {
	$return = 'PT';
	$seconds = absint( $seconds );
	if ( $seconds > 3600 ) {
		$hours = floor( $seconds / 3600 );
		$return .= $hours . 'H';
		$seconds = $seconds - ( $hours * 3600 );
	}
	if ( $seconds > 60 ) {
		$minutes = floor( $seconds / 60 );
		$return .= $minutes . 'M';
		$seconds = $seconds - ( $minutes * 60 );
	}
	if ( $seconds > 0 ) {
		$return .= $seconds . 'S';
	}
	return $return;
}

/**
 * Returns the YouTube API key entered by user
 */
function yti_get_yt_api_key( $return = 'key' ){
	$api_key = get_option('_yti_yt_api_key', array('key' => false, 'valid' => true));
	if( !is_array($api_key) ){
		$api_key = array('key' => $api_key, 'valid' => true);
		update_option('_yti_yt_api_key', $api_key);
	}	
	
	switch( $return ){
		case 'full':
			return $api_key;	
		break;	
		case 'key':
		default:
			return $api_key['key'];
		break;
		case 'validity':
			return $api_key['valid'];
		break;	
	}
}

/**
 * Update YouTube API key
 * @param string $key
 */
function yti_update_api_key( $key ){
	if( empty( $key ) ){
		$key = false;
	}
	$api_key = array('key' => trim($key), 'valid' => true);
	update_option('_yti_yt_api_key', $api_key);
}

/**
 * Invalidates API key
 */
function yti_invalidate_api_key(){
	$api_key = yti_get_yt_api_key('full');
	$api_key['valid'] = false;
	update_option('_yti_yt_api_key', $api_key);
}

/**
 * Checks if debug is on.
 * If on, the plugin will display various information in different admin areas
 */
function yti_debug(){
	if( defined('YTI_DEBUG') ){
		return (bool)YTI_DEBUG;
	}
	return false;
}

/*****************************************
 * API query functions
 *****************************************/

/**
 * Loads YouTube API query class
 */
function __load_youtube_api_class(){
	if( !class_exists( 'YouTube_API_Query' ) ){
		require_once YTI_PATH.'includes/libs/youtube-api-query.class.php';
	}
}

/**
 * Perform a YouTube search. Arguments:
 * 
 * include_categories bool - when true, video categories will be retrieved, if false, they won't
 * query string - the search query
 * page_token - YT API 3 page token for pagination
 * order string - any of: date, rating, relevance, title, viewCount
 * duration string - any of: any, short, medium, long
 * 
 * @return array of videos or WP error
 */
function yti_yt_api_search_videos( $args = array() ){
	$defaults = array(
		// if false, YouTube categories won't be retrieved
		'include_categories' => true,
		// the search query
		'query' 		=> '',
		// as of API 3, results pagination is done by tokens
		'page_token' 	=> '',
		// can be: date, rating, relevance, title, viewCount
		'order' 		=> 'relevance',
		// can be: any, short, medium, long
		'duration' 		=> 'any',
		// not used but into the script
		'embed'			=> 'any'
	);
	
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	$settings 	= yti_get_settings();
	$per_page 	= $settings['manual_import_per_page'];
	
	__load_youtube_api_class();
	$q 			= new YouTube_API_Query( $per_page, $include_categories );
	$videos 	= $q->search( $query, $page_token, array( 'order' => $order, 'duration' => $duration, 'embed' => $embed ) );
	$page_info 	= $q->get_list_info();
	
	return array(
		'videos' => $videos,
		'page_info' => $page_info
	);
}

/**
 * Get videos for a given YouTube playlist. Arguments:
 * 
 * include_categories bool - when true, video categories will be retrieved, if false, they won't
 * query string - the search query
 * page_token - YT API 3 page token for pagination
 * type string - auto or manual 
 * 
 * @param array $args
 */
function yti_yt_api_get_playlist( $args = array() ){
	$args['playlist_type'] = 'playlist';
	return yti_yt_api_get_list( $args );
}

/**
 * Get videos for a given YouTube user. Arguments:
 * 
 * include_categories bool - when true, video categories will be retrieved, if false, they won't
 * query string - the search query
 * page_token - YT API 3 page token for pagination
 * type string - auto or manual 
 * 
 * @param array $args
 */
function yti_yt_api_get_user( $args = array() ){
	$args['playlist_type'] = 'user';
	return yti_yt_api_get_list( $args );
}

/**
 * Get videos for a given YouTube channel. Arguments:
 * 
 * include_categories bool - when true, video categories will be retrieved, if false, they won't
 * query string - the search query
 * page_token - YT API 3 page token for pagination
 * type string - auto or manual 
 * 
 * @param array $args
 */
function yti_yt_api_get_channel( $args = array() ){
	$args['playlist_type'] = 'channel';
	return yti_yt_api_get_list( $args );	
}

/**
 * Get details about a single video ID
 * @param string $video_id - YouTube video ID
 */
function yti_yt_api_get_video( $video_id ){
	__load_youtube_api_class();
	$q = new YouTube_API_Query(1, true);
	$video = $q->get_video( $video_id );
	return $video;
}

/**
 * Get details about multiple video IDs
 * @param string $video_ids - YouTube video IDs comma separated or array of video ids
 */
function yti_yt_api_get_videos( $video_ids ){
	__load_youtube_api_class();
	$q = new YouTube_API_Query( 50, true );
	$videos = $q->get_videos( $video_ids );
	return $videos;
}

/**
 * Returns a playlist feed.
 * 
 * include_categories bool - when true, video categories will be retrieved, if false, they won't
 * query string - the search query
 * page_token - YT API 3 page token for pagination
 * type string - auto or manual 
 * playlist_type - one of the following: user, playlist or channel
 * 
 * @param array $args
 */
function yti_yt_api_get_list( $args = array() ){
	$defaults = array(
		'playlist_type' => 'playlist',
		// can be auto or manual - will set pagination according to user settings
		'type' => 'manual',
		// if false, YouTube categories won't be retrieved
		'include_categories' => true,
		// the search query
		'query' 		=> '',
		// as of API 3, results pagination is done by tokens
		'page_token' 	=> '',
	);
	
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );
	
	$types = array( 'user', 'playlist', 'channel' );
	if( !in_array( $playlist_type, $types ) ){
		trigger_error( __('Invalid playlist type. Use as playlist type one of the following: user, playlist or channel.', 'yti_video' ), E_USER_NOTICE );
		return;
	}
	
	$settings 	= yti_get_settings();
	if( 'auto' == $type ){
		$per_page = $settings['import_quantity'];
	}else{
		$per_page = $settings['manual_import_per_page'];
	}
	
	__load_youtube_api_class();
	$q = new YouTube_API_Query( $per_page, $include_categories );
	switch( $playlist_type ){
		case 'playlist':
			$videos = $q->get_playlist( $query, $page_token );
		break;
		case 'user':
			$videos = $q->get_user_uploads( $query, $page_token );
		break;
		case 'channel':
			$videos = $q->get_channel_uploads( $query, $page_token );
		break;	
	}
		
	$page_info = $q->get_list_info();
	
	return array(
		'videos' 	=> $videos,
		'page_info' => $page_info
	);	
}

/**
 * Checks whether variable is a WP error in first place
 * and second will verifyis the error has YouTube flag on it.
 */
function yti_is_youtube_error( $thing ){
	if( !is_wp_error( $thing ) ){
		return false;
	}
	
	$data = $thing->get_error_data();
	if( $data && isset( $data['youtube_error'] ) ){
		return true;
	}
	
	return false;	
}