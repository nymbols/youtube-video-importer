<?php
/**
 * Displays checked argument in checkbox
 * @param bool $val
 * @param bool $echo
 */
function yvi_check( $val, $echo = true ){
	$checked = '';
	if( is_bool($val) && $val ){
		$checked = ' checked="checked"';
	}
	if( $echo ){
		echo $checked;
	}else{
		return $checked;
	}	
}

/**
 * Displays a style="display:hidden;" if passed $val is bool and false
 * @param bool $val
 * @param string $before
 * @param string $after
 * @param bool $echo
 */
function yvi_hide( $val, $compare = false, $before=' style="', $after = '"', $echo = true ){
	$output = '';
	if(  $val == $compare ){
		$output .= $before.'display:none;'.$after;
	}
	if( $echo ){
		echo $output;
	}else{
		return $output;
	}
}

/**
 * Display select box
 * @param array $args - see $defaults in function
 * @param bool $echo
 */
function yvi_select( $args = array(), $echo = true ){
	
	$defaults = array(
		'options' 	=> array(),
		'name'		=> false,
		'id'		=> false,
		'class'		=> '',
		'selected'	=> false,
		'use_keys'	=> true
	);
	
	$o = wp_parse_args($args, $defaults);
	
	if( !$o['id'] ){
		$output = sprintf( '<select name="%1$s" id="%1$s" class="%2$s" autocomplete="off">', $o['name'], $o['class']);
	}else{
		$output = sprintf( '<select name="%1$s" id="%2$s" class="%3$s" autocomplete="off">', $o['name'], $o['id'], $o['class']);
	}	
	
	foreach( $o['options'] as $val => $text ){
		$opt = '<option value="%1$s"%2$s>%3$s</option>';
		
		$value = $o['use_keys'] ? $val : $text;
		$c = $o['use_keys'] ? $val == $o['selected'] : $text == $o['selected'];
		$checked = $c ? ' selected="selected"' : '';		
		$output .= sprintf($opt, $value, $checked, $text);		
	}
	
	$output .= '</select>';
	
	if( $echo ){
		echo $output;
	}
	
	return $output;
}

/**
 * A list of allowed bulk actions implemented by the plugin
 */
function yvi_actions(){	
	$actions = array(
		'yvi_thumbnail' => __('Import thumbnails', 'yvi_video')
	);
	
	return $actions;
}

/**
 * Templating function for administration purposes. Displays the next update session of playlists automatic update.
 * @param string $before
 * @param string $after
 * @param bool $echo
 */
function yvi_automatic_update_message( $before = '', $after = '', $echo = true ){
	
	$api_key = yvi_get_yt_api_key();
	if( empty( $api_key ) ){
		return;
	}
	
	global $YVI_AUTOMATIC_IMPORT;
	$import_data 	= $YVI_AUTOMATIC_IMPORT->get_update();
	
	if( !$import_data ){
		return;
	}
	
	/**
	 * If an update is currently running, display this information to the user
	 */
	if( $import_data['running_update'] ){
		
		$playlist = get_post( $import_data['post_id'] );
		if( !$playlist || is_wp_error( $playlist ) ){
			$message = __( "A playlist should be importing but we couldn't find it. Automatic imports have stopped but should resume in less than 10 minutes.", 'yvi_video' );
		}else{
			$message = sprintf( __( '... waiting for videos from playlist %s to be imported.', 'yvi_video' ), '<strong>' . $playlist->post_title . '</strong>' );
		}
		
		$output = $before . $message . $after;
		if( $echo ){
			echo $output;
		}
		return $output;
	}
	
	
	
	$playlist = $YVI_AUTOMATIC_IMPORT->get_next_playlist();
	if( $playlist ){
		$post = get_post( $playlist );
		if( !$post ){
			return;
		}
	}else{
		return;
	}
	
	$message = '';
	$options = yvi_get_settings();	
	$timeout = yvi_human_update_delay();
	
	// start messages
	if( $timeout['countdown'] ){
		
		$text = '<span id="yvi-update-message"><i class="dashicons dashicons-update"></i> '.__('Time until playlist %s can be imported %s', 'yvi_video').'</span>';
		if( $post ){
			$message = sprintf( 
				$text, 
				'<strong>'.$post->post_title.'</strong>', 
				'<strong id="yvi-timer" data-type="decrease">'.$timeout['time'].'</strong>' 
			);
		}
		
	}else{
		
		$text = '<span id="yvi-update-message"><i class="dashicons dashicons-update"></i> '.__('Importing videos from playlist %s.', 'yvi_video').'</span>';
		if( $post ){
			$message = sprintf( 
				$text, 
				'<strong>'.$post->post_title.'</strong>'
			);
		}		
	}
	
	if( $echo ){
		echo $before.$message.$after;
	}
	
	return $before.$message.$after;
}

/**
 * Returns contextual help content from file
 * @param string $file - partial file name
 */
function yvi_get_contextual_help( $file ){
	if( !$file ){
		return false;
	}	
	$file_path = YVI_PATH. 'yti_v/help/' . $file.'.html.php';
	if( is_file($file_path) ){
		ob_start();
		include( $file_path );
		$help_contents = ob_get_contents();
		ob_end_clean();		
		return $help_contents;
	}else{
		return false;
	}
}

/**
 * Displays a message regarding YouTube quota usage
 * @param bool $echo
 */
function yvi_yt_quota_message( $echo = true ){
	$stats 	= get_option( 'yvi_daily_yt_units', array( 'day' => -1, 'count' => 0 ) );
	$units 	= 50000000;
	$used 	= $stats['count'] > $units ? $units : $stats['count'];
	$percent = $used * 100 / $units ;
	
	$message = sprintf( __( 'Estimated quota units used today: %s (%s of %s)', 'yvi_video' ), number_format_i18n( $used ), number_format_i18n( $percent, 2 ) . '%', number_format_i18n($units) );
	if( $echo ){
		echo $message;
	}
	return $message;
}




/**
 * YouTube OAuth functions
 */

/**
 * Displays the link that begins OAuth authorization
 * @param string $text
 */
function yvi_show_oauth_link( $text = '', $echo  = true ){
	
	if( empty( $text ) ){
		$text = __( 'Grant plugin access', 'yvi_video' );
	}
	
	$options = yvi_get_yt_oauth_details();	
	if( empty( $options['client_id'] ) || empty( $options['client_secret'] ) ){
		return;
	}else{
		if( !empty( $options['token']['value'] ) ){
			$nonce = wp_create_nonce( 'yvi-revoke-oauth-token' );
			$url = menu_page_url( 'yvi_settings', false ) . '&unset_token=true&yvi_nonce=' . $nonce . '#yvi-settings-auth-options';
			printf( '<a href="%s" class="button">%s</a>', $url, __( 'Revoke access', 'yvi_video' ) );			
			return;
		}
	}
	
	$endpoint = 'https://accounts.google.com/o/oauth2/auth';
	$parameters = array(
		'response_type' => 'code',
		'client_id' 	=> $options['client_id'],
		'redirect_uri' 	=> yvi_get_oauth_redirect_uri(),
		'scope' 		=> 'https://www.googleapis.com/auth/youtube.readonly',
		'state'			=> wp_create_nonce( 'yvii-youtube-oauth-grant' ),
		'access_type' 	=> 'offline'
	);
	
	$url = $endpoint . '?' . http_build_query( $parameters );
		
	$anchor = sprintf( '<a href="%s">%s</a>', $url, $text ); 
	if( $echo ){
		echo $anchor;
	}
	return $anchor;
}

/**
 * Returns the OAuth redirect URL
 */
function yvi_get_oauth_redirect_uri(){
	$url = get_admin_url();
	return $url;
}

/**
 * Get authentification token if request is response returned from YouTube
 */
function yvi_check_youtube_auth_code(){
	if( isset( $_GET['code'] ) && isset( $_GET['state'] ) ){
		if( wp_verify_nonce( $_GET['state'], 'yvii-youtube-oauth-grant' ) ){
			$options = yvi_get_yt_oauth_details();
			$fields = array(
				'code' 			=> $_GET['code'],
				'client_id' 	=> $options['client_id'],
				'client_secret' => $options['client_secret'],
				'redirect_uri' 	=> yvi_get_oauth_redirect_uri(),
				'grant_type' 	=> 'authorization_code'
			);			
			$token_url = 'https://accounts.google.com/o/oauth2/token';
			
			$response = wp_remote_post( $token_url, array(
				'method' 		=> 'POST',
				'timeout' 		=> 45,
				'redirection' 	=> 5,
				'httpversion' 	=> '1.0',
				'blocking' 		=> true,
				'headers' 		=> array(),
				'body' 			=> $fields,
				'cookies' 		=> array()
			    )
			);
			
			if( !is_wp_error( $response ) ){
				$response = json_decode( wp_remote_retrieve_body( $response ), true );
				if( isset( $response['access_token'] ) ){
					$token = array(
						'value' => $response['access_token'],
						'valid' => $response['expires_in'],
						'time' 	=> time()
					);
					
					yvi_update_yt_oauth( false, false, $token );
				}				
			}

			wp_redirect( html_entity_decode( menu_page_url( 'yvi_settings', false ) ) . '#yvi-settings-auth-options' );
			die();			
		}
	}
}
add_action( 'admin_init', 'yvi_check_youtube_auth_code' );

/**
 * Refresh the access token
 */
function yvi_refresh_oauth_token(){
	
	$token 		= yvi_get_yt_oauth_details();
	if( empty( $token['client_id'] ) || empty( $token['client_secret'] ) ){
		return new WP_Error( 'yvi_token_refresh_missing_oauth_login', __( 'YouTube API OAuth credentials missing. Please visit plugin Settings page and enter your credentials.', 'yvi_video' ) );
	}	
	
	$endpoint 	= 'https://accounts.google.com/o/oauth2/token';
	$fields = array(
		'client_id' 	=> $token['client_id'],
		'client_secret' => $token['client_secret'],
		'refresh_token' => $token['token']['value'],
		'grant_type' 	=> 'refresh_token'
	);				
	$response = wp_remote_post( $endpoint, array(
		'method' 		=> 'POST',
		'timeout' 		=> 45,
		'redirection' 	=> 5,
		'httpversion' 	=> '1.0',
		'blocking' 		=> true,
		'headers' 		=> array(),
		'body' 			=> $fields,
		'cookies' 		=> array()
	    )
	);
	
	if( is_wp_error( $response ) ){
		return $response;
	}
	
	if( 200 != wp_remote_retrieve_response_code( $response ) ){
		$details = json_decode( wp_remote_retrieve_body( $response ), true );
		if( isset( $details['error'] ) ){
			return new WP_Error( 'yvi_invalid_yt_grant', sprintf( __('While refreshing the access token, YouTube returned error code <strong>%s</strong>. Please refresh tokens manually by revoking current access and granting new access.', 'yvi_video'), $details['error'] ), $details );
		}
		return new WP_Error( 'yvi_token_refresh_error', __( 'While refreshing the access token, YouTube returned an unknown error.', 'yvi_video' ) );
	}
	
	$data = json_decode( wp_remote_retrieve_body( $response ), true );
	$token = array(
		'value' => $data['access_token'],
		'valid' => $data['expires_in'],
		'time' 	=> time()
	);
	yvi_update_yt_oauth( false, false, $token );
	return $token;	
}










/**
 * Returns all playlists created by currently authenticated user using OAuth.
 * 
 * page_token - YT API 3 page token for pagination
 */
function yvi_yt_api_get_user_playlists( $page_token = '', $per_page = 20 ){
	__load_youtube_api_class();
	$q = new YouTube_API_Query( $per_page );
	$playlists = $q->get_user_playlists( $page_token );
		
	$page_info = $q->get_list_info();
	
	return array(
		'items' 		=> $playlists,
		'page_info' 	=> $page_info
	);	
}

/**
 * Returns all channels for currently authenticated used using OAuth.
 * 
 * page_token - YT API 3 page token for pagination
 */
function yvi_yt_api_get_user_channels( $page_token = '', $per_page = 20  ){
	__load_youtube_api_class();
	$q = new YouTube_API_Query( $per_page );
	$channels = $q->get_user_channels( $page_token );
		
	$page_info = $q->get_list_info();
	
	return array(
		'items' 		=> $channels,
		'page_info' 	=> $page_info
	);	
}

/**
 * Returns all subscriptions for currently authenticated used using OAuth.
 * 
 * page_token - YT API 3 page token for pagination
 */
function yvi_yt_api_get_user_subscriptions( $page_token = '', $per_page = 20  ){
	__load_youtube_api_class();
	$q = new YouTube_API_Query( $per_page );
	$channels = $q->get_user_subscriptions( $page_token );
		
	$page_info = $q->get_list_info();
	
	return array(
		'items' 		=> $channels,
		'page_info' 	=> $page_info
	);	
}
