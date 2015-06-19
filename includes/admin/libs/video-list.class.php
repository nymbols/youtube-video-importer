<?php
class YTI_Video_List{
	
	private $items = array();
	private $feed_errors = false;
	private $total_items = 0;
	private $next_token = '';
	private $prev_token = '';
	
	public function display(){
		
		if( !$this->items ){
			$this->no_items();
			return;
		}
		// show top navigation
		$this->navigation( 'top' );
		
		echo '<div class="yti-video-import-list">';
		foreach( $this->items as $item ){
			$this->display_item( $item );			
		}
		echo '</div>';
		
		// show bottom navigation
		$this->navigation( 'bottom' );
	}
	
	private function display_item( $item ){
		
		$thumbnail = $item['thumbnails']['medium']['url'];
		$view_class = get_user_option( 'yti_video_import_view' );
		if( !$view_class ){
			$view = 'grid';
		}
?>
<div class="yti-video-item <?php echo $view_class;?>">
	<div class="item-title">
		<input type="text" name="yti_title[<?php echo $item['video_id'];?>]" value="<?php echo esc_attr( $item['title'] );?>" class="item-title" />
	</div>
	<div class="yti-left"><img src="<?php echo $thumbnail?>" class="item-image" /></div>
	<div class="yti-right">
		<textarea name="yti_text[<?php echo $item['video_id'];?>]"><?php echo $item['description'];?></textarea>		
	</div>
	<div class="controls">
		<strong class="duration"><?php echo yti_human_time( $item['duration'] );?></strong> / <?php printf( _n( '1 item', '%s views', $item['stats']['views'] ), number_format_i18n( $item['stats']['views'] ) );?> 
		<a href="https://www.youtube.com/watch?v=<?php echo $item['video_id'];?>" target="_blank"><span class="dashicons dashicons-admin-links"></span></a><br />
		<hr />
		<label><?php printf( '<input type="checkbox" name="yti_import[]" value="%1$s" id="yti_video_%1$s" class="yti-item-check" />', $item['video_id'] );?>
		<?php _e( 'Import this video', 'yti_video' );?>
		</label>
	</div>
</div>
<?php
	}
	
	/**
	 * Display navigation
	 * @param string $which
	 */
	private function navigation( $which = 'top' ){
		$which = 'bottom' == $which ? 'bottom' : 'top';
		$suffix = 'top' == $which ? '_top' : '2';
		
		// plugin options
    	$options = yti_get_settings();
    	// set selected category
   		$selected = false;
		if( isset( $_GET['cat'] ) ){
			$selected = $_GET['cat'];
		}
		// dropdown arguments
    	$args = array(
			'show_count' 	=> 1,
    		'hide_empty'	=> 0,
			'taxonomy' 		=> 'videos',
			'name'			=> 'cat'.$suffix,
			'id'			=> 'yti_video_categories'.$suffix,
			'selected'		=> $selected,
    		'hide_if_empty' => true,
    		'echo'			=> false
		);
		// if importing as theme compatible posts
		if( isset( $_REQUEST['yti_theme_import'] ) ){
			$theme_import = yti_check_theme_support();
			if( $theme_import ){
				if( !$theme_import['taxonomy'] && 'post' == $theme_import['post_type']  ){
					$args['taxonomy'] = 'category';
				}else{
					$args['taxonomy'] = $theme_import['taxonomy'];
				}
			}
		}else if( isset( $options['post_type_post'] ) && $options['post_type_post'] ){ // plugin should import as regular post
			// set args for default post categories
			$args['taxonomy'] = 'category';
		}
		
		if( isset( $options ) && $options['import_categories'] ){
			$args['show_option_all'] = __('Create categories from YouTube', 'yti_video');
		}else{
			$args['show_option_all'] = __('Select category (optional)', 'yti_video');
		}
		// get dropdown output
		$categ_select = wp_dropdown_categories($args);
		// users dropdown
		$users = wp_dropdown_users(array(
			'show_option_all' 			=> __('Current user', 'yti_video'),
			'echo'						=> false,
			'name'						=> 'user'.$suffix,
			'id'						=> 'yti_video_user'.$suffix,
			'hide_if_only_one_author' 	=> true
		));		
?>	
<div class="tablenav <?php echo $which;?>">	
	<label class="sel_all"><input type="checkbox" value="1" name="select_all" id="select_all" /> <?php _e('Select all', 'yti_video');?></label>
	
	<input type="hidden" name="action<?php echo $suffix?>" value="import" />
		
    <?php if( $categ_select ):?>
    	<label for="yti_video_categories<?php echo $suffix;?>"><?php _e('Import into category', 'yti_video');?> :</label>
		<?php echo $categ_select;?>
	<?php endif;?>
	
	<?php if( $users ):?>
		<label for="yti_video_user<?php echo $suffix;?>"><?php _e('Import as user', 'yti_video');?> :</label>
		<?php echo $users;?>
	<?php endif;?>
	
	<?php submit_button( __( 'Import videos' ), 'action', false, false, array( 'id' => "doaction$suffix" ) );?>		
    <span class="yti-ajax-response"></span>
    	
	<?php $this->pagination();?>
</div>	
<?php		
	}
	
	/**
	 * Display pagination
	 */
	private function pagination(){
		$current_url 	= set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url 	= remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first' ), $current_url );
		$disable_first 	= empty( $this->prev_token ) ? ' disabled' : false;
		$disable_last 	= empty( $this->next_token ) ? ' disabled' : false;
		
		$prev_page = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page', 'yti_video' ),
			esc_url( add_query_arg( 'token', $this->prev_token, $current_url ) ),
			'&lsaquo;'
		);
		
		$view = get_user_option( 'yti_video_import_view' );
		if( !$view ){
			$view = 'grid';
		}
?>
<div class="tablenav-pages">
	<span class="displaying-num"><?php printf( _n( '1 item', '%s items', $this->total_items ), number_format_i18n( $this->total_items ) );?></span>
	<span class="pagination-links">
		<?php 
			// prev page
			printf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'prev-page' . $disable_first,
				esc_attr__( 'Go to the previous page', 'yti_video' ),
				esc_url( add_query_arg( 'token', $this->prev_token, $current_url ) ),
				'&lsaquo;'
			);			
		?>
		<?php 
			// next page
			printf( "<a class='%s' title='%s' href='%s'>%s</a>",
				'prev-page' . $disable_last,
				esc_attr__( 'Go to the next page', 'yti_video' ),
				esc_url( add_query_arg( 'token', $this->next_token, $current_url ) ),
				'&rsaquo;'
			);
		?>
	</span>
</div>
<div class="view-switch">
	<a id="view-switch-grid" class="yti-view view-grid<?php if( 'grid' == $view ):?> current<?php endif;?>" href="#" data-view="grid"><span class="screen-reader-text"><?php _e( 'Grid View', 'yti_video' );?></span></a>
	<a id="view-switch-list" class="yti-view view-list<?php if( 'list' == $view ):?> current<?php endif;?>" href="#" data-view="list"><span class="screen-reader-text"><?php _e('List View', 'yti_video');?></span></a>
</div>
<?php		
	}
	
	/**
	 * Displays a message if playlist is empty
	 */
	public function no_items(){
		_e('YouTube feed is empty.', 'yti_video');    	
    	if( is_wp_error( $this->feed_errors ) ){
    		echo '<br />';
    		printf( __(' <strong>API error (code: %s)</strong>: %s', 'yti_video') , $this->feed_errors->get_error_code(), $this->feed_errors->get_error_message() ) ;
    	}
	}
	
	/**
	 * Makes YouTube API query for videos and populates vlass variables
	 */
	public function prepare_items(){
		
		$videos = array();
		$token 	= isset( $_GET['token'] ) ? $_GET['token'] : '';
		
		switch( $_GET['yti_feed'] ){
			case 'user':
			case 'playlist':
			case 'channel':
				$args = array(
					'type'			=> 'manual',
					'query' 		=> $_GET['yti_query'],
					'page_token' 	=> $token,
					'include_categories' => true,
					'playlist_type' => $_GET['yti_feed']
				);
				
				$q = yti_yt_api_get_list( $args );
				
				$videos 	= $q['videos'];
				$list_stats = $q['page_info'];
			break;
			// perform a search query
			case 'query':				
				$args = array(
					'query' 		=> $_GET['yti_query'],
					'page_token' 	=> $token,
					'order' 		=> $_GET['yti_order'],
					'duration' 	=> $_GET['yti_duration']
				);
				$q = yti_yt_api_search_videos( $args );
				$videos 	= $q['videos'];
				$list_stats = $q['page_info'];
				
			break;
		}
		
		if( is_wp_error( $videos ) ){
			$this->feed_errors = $videos;
			$videos = array();
		}
			
		$this->items 		= $videos;
		$this->total_items 	= $list_stats['total_results'];
    	$this->next_token	= $list_stats['next_page'];
    	$this->prev_token	= $list_stats['prev_page'];		
	}
}