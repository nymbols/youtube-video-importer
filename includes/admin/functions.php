<?php
/**
 * Displays checked argument in checkbox
 * @param bool $val
 * @param bool $echo
 */
function yti_check( $val, $echo = true ){
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
function yti_hide( $val, $compare = false, $before=' style="', $after = '"', $echo = true ){
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
function yti_select( $args = array(), $echo = true ){
	
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
		$output = sprintf( '<select name="%1$s" id="%1$s" class="%2$s">', $o['name'], $o['class']);
	}else{
		$output = sprintf( '<select name="%1$s" id="%2$s" class="%3$s">', $o['name'], $o['id'], $o['class']);
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
function yti_actions(){	
	$actions = array(
		'yti_thumbnail' => __('Import thumbnails', 'yti_video')
	);
	
	return $actions;
}

/**
 * Templating function for administration purposes. Displays the next update session of playlists automatic update.
 * @param string $before
 * @param string $after
 * @param bool $echo
 */
function yti_automatic_update_message( $before = '', $after = '', $echo = true ){
	global $YTI_AUTOMATIC_IMPORT;
	$import_data 	= $YTI_AUTOMATIC_IMPORT->get_update();
	
	if( !$import_data ){
		return;
	}
	
	/**
	 * If an update is currently running, display this information to the user
	 */
	if( $import_data['running_update'] ){
		
		$playlist = get_post( $import_data['post_id'] );
		if( !$playlist || is_wp_error( $playlist ) ){
			$message = __( "A playlist should be importing but we couldn't find it. Automatic imports have stopped but should resume in less than 10 minutes.", 'yti_video' );
		}else{
			$message = sprintf( __( '... waiting for videos from playlist %s to be imported.', 'yti_video' ), '<strong>' . $playlist->post_title . '</strong>' );
		}
		
		$output = $before . $message . $after;
		if( $echo ){
			echo $output;
		}
		return $output;
	}
	
	
	
	$playlist = $YTI_AUTOMATIC_IMPORT->get_next_playlist();
	if( $playlist ){
		$post = get_post( $playlist );
		if( !$post ){
			return;
		}
	}else{
		return;
	}
	
	$message = '';
	$options = yti_get_settings();	
	$timeout = yti_human_update_delay();
	
	// start messages
	if( $timeout['countdown'] ){
		
		$text = '<span id="yti-update-message"><i class="dashicons dashicons-update"></i> '.__('Time until playlist %s can be imported %s', 'yti_video').'</span>';
		if( $post ){
			$message = sprintf( 
				$text, 
				'<strong>'.$post->post_title.'</strong>', 
				'<strong id="yti-timer" data-type="decrease">'.$timeout['time'].'</strong>' 
			);
		}
		
	}else{
		
		$text = '<span id="yti-update-message"><i class="dashicons dashicons-update"></i> '.__('Importing videos from playlist %s.', 'yti_video').'</span>';
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
function yti_get_contextual_help( $file ){
	if( !$file ){
		return false;
	}	
	$file_path = YTI_PATH. 'views/help/' . $file.'.html.php';
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