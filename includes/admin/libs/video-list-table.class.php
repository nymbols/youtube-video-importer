<?php
/**
 * Load WP_List_Table class
 */
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class YTI_Video_List_Table extends WP_List_Table{
	
	function __construct( $args = array() ){
		parent::__construct( array(
			'singular' => 'video',
			'plural'   => 'videos',
			'screen'   => isset( $args['screen'] ) ? $args['screen'] : null,
		) );
	}
	
	/**
	 * Default column
	 * @param array $item
	 * @param string $column
	 */
	function column_default( $item, $column  ){
		return $item[$column];
	}
	
	/**
	 * Title
	 * @param array $item
	 */
	function column_post_title( $item ){
		
		$meta = get_post_meta( $item['ID'], '__yti_video_data', true );
		
		$label = sprintf( '<label for="yti-video-%1$s" id="title%1$s" class="yti_video_label">%2$s</label>', $item['ID'], $item['post_title'] );
		
		$settings = yytt_get_video_settings( $item['ID'] );
		
		$form = '<div class="single-video-settings" id="single-video-settings-'.$item['ID'].'">';
		$form.= '<h4>'.$item['post_title'].' ('.yti_human_time( $meta['duration'] ).')</h4>';
		$form.= '<label for="yti_volume'.$item['ID'].'">'.__('Volume', 'yti_video').'</label> <input size="3" type="text" name="volume['.$item['ID'].']" id="yti_volume'.$item['ID'].'" value="'.$settings['volume'].'" /><br />';
		$form.= '<label for="yti_width'.$item['ID'].'">'.__('Width', 'yti_video').'</label> <input size="3" type="text" name="width['.$item['ID'].']" id="yti_width'.$item['ID'].'" value="'.$settings['width'].'" /><br />';
		
		$aspect_select = yti_select(
			array(
				'options' => array(
					'4x3' 	=> '4x3',
					'16x9' 	=> '16x9'
				),
				'name' 		=> 'aspect_ratio['.$item['ID'].']',
				'id' 		=> 'yti_aspect_ratio'.$item['ID'],
				'selected' 	=> $settings['aspect_ratio']
			), false
		);
		$form.= '<label for="yti_aspect_ratio'.$item['ID'].'">'.__('Aspect ratio', 'yti_video').'</label> '.$aspect_select.'<br />';
		$form.= '<input type="checkbox" name="autoplay['.$item['ID'].']" id="yti_autoplay'.$item['ID'].'" value="1"'.yti_check( (bool)$settings['autoplay'], false ).' /> <label class="inline" for="yti_autoplay'.$item['ID'].'">'.__('Auto play', 'yti_video').'</label><br />';
		$form.= '<input type="checkbox" name="controls['.$item['ID'].']" id="yti_controls'.$item['ID'].'" value="1"'.yti_check( (bool)$settings['controls'] , false ).' /> <label class="inline" for="yti_controls'.$item['ID'].'">'.__('Show player controls', 'yti_video').'</label><br />';
		$form.= '<input type="button" id="shortcode'.$item['ID'].'" value="'.__('Insert shortcode', 'yti_video').'" class="button yti-insert-shortcode" />';
		$form.= '<input type="button" id="cancel'.$item['ID'].'" value="'.__('Cancel', 'yti_video').'" class="button yti-cancel-shortcode" />';
		$form.= '<div style="width:100%; display:block; clear:both"></div>';
		$form.= '</div>';
		
		// row actions
    	$actions = array(
    		'shortcode' => sprintf( '<a href="#" id="yti-embed-%1$s" class="yti-show-form">%2$s</a>'.$form, $item['ID'], __('Get video shortcode', 'yti_video') ),
    	);
    	
    	return sprintf('%1$s %2$s',
    		$label,
    		$this->row_actions( $actions )
    	);	
		
	}
	
	/**
	 * Checkbox column
	 * @param array $item
	 */
	function column_cb( $item ){
		return sprintf(
			'<input type="checkbox" name="%1$s" value="%2$s" id="%3$s" class="yti-video-checkboxes">',
			'yti_video[]',
			$item['ID'],
			'yti-video-'.$item['ID']
		);
	}
	
	/**
	 * YouTube video ID column
	 * @param array $item
	 */
	function column_video_id( $item ){
		$meta = get_post_meta( $item['ID'], '__yti_video_data', true );
		return $meta['video_id'];
	}
	
	/**
	 * Video duration column
	 * @param array $item
	 */
	function column_duration( $item ){
		$meta = get_post_meta( $item['ID'], '__yti_video_data', true );
		return '<span id="duration'.$item['ID'].'">'.yti_human_time($meta['duration']).'</span>';
	}
	
	/**
	 * Display video categories
	 * @param array $item
	 */
	function column_category( $item ){
		$taxonomy = $this->_get_taxonomy_view();
		if ( $terms = get_the_terms( $item['ID'], $taxonomy ) ) {
			$out = array();
			foreach ( $terms as $t ) {
				$url = add_query_arg(
					array(
						'pt' => $this->_get_post_type_view(),
						'page' 		=> 'yti_videos',
						'cat'		=> $t->term_id
					)
				, 'edit.php');
				
				$out[] = sprintf('<a href="%s">%s</a>', $url, $t->name);
			}
			return implode(', ', $out);
		}else {
			return '&#8212;';
		}
	}
	
	/**
	 * Returns the post type associated with the current view
	 */
	private function _get_post_type_view(){
		if( isset( $_REQUEST['pt'] ) && 'post' == $_REQUEST['pt'] ){
			return 'post';
		}
		global $YTI_POST_TYPE;
		return $YTI_POST_TYPE->get_post_type();
	}
	
	/**
	 * Returns the post type taxonomy associated with the current view
	 */
	private function _get_taxonomy_view(){
		$post_type = $this->_get_post_type_view();
		if( 'post' == $post_type ){
			return 'category';
		}
		global $YTI_POST_TYPE;
		return $YTI_POST_TYPE->get_post_tax();
	}
	
	/**
	 * Date column
	 * @param array $item
	 */
	function column_post_date( $item ){
		
		$output = sprintf( '<abbr title="%s">%s</abbr><br />', $item['post_date'], mysql2date( __( 'Y/m/d' ), $item['post_date'] ) );
		$output.= 'publish' == $item['post_status'] ? __('Published', 'yti_video') : '';
		return $output;
		
	}
	
	function extra_tablenav($which){
		
		if( 'top' !== $which ){
			return ;
		}
		
		$selected = false;
		if( isset( $_GET['cat'] ) ){
			$selected = $_GET['cat'];
		}
		
		global $YTI_POST_TYPE;
		$taxonomy = $YTI_POST_TYPE->get_post_tax();
		if( isset( $_GET['pt'] ) && 'post' == $_GET['pt'] ){
			$taxonomy = 'category';
		}
		
		
		$args = array(
			'show_option_all' => __('All categories', 'yti_video'),
			'show_count' 	=> 1,
			'taxonomy' 		=> $taxonomy,
			'name'			=> 'cat',
			'id'			=> 'yti_video_categories',
			'selected'		=> $selected,
			'hide_if_empty'	=> true,
			'echo'			=> false
		);
		$categories_select = wp_dropdown_categories($args);
		if( !$categories_select ){
			return;
		}		
		?>
		<label for="yti_video_categories"><?php _e('Categories', 'yti_video');?> :</label>
		<?php echo $categories_select;?>
		<?php submit_button( __( 'Filter', 'yti_video' ), 'button-secondary apply', 'filter_videos', false );?>
		<?php		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_views()
	 */
	function get_views(){
		
		$uri = remove_query_arg('cat', $_SERVER['REQUEST_URI']);
		
		$video_active = ' class="current"';
		$post_active = '';
		if( isset( $_GET['pt'] ) && 'post' == $_GET['pt'] ){
			$post_active = ' class="current"';
			$video_active = '';
		}
		
		global $YTI_POST_TYPE;
		$video_type = $YTI_POST_TYPE->get_post_type();
		
		$actions = array(
			'video' => sprintf('<a href="%1$s" title="%2$s"%3$s>%2$s</a>', add_query_arg(array('pt' => $video_type), $uri), __('Videos'), $video_active),
			'post'	=> sprintf('<a href="%1$s" title="%2$s"%3$s>%2$s</a>', add_query_arg(array('pt' => 'post'), $uri), __('Posts'), $post_active)
		);
		
		return $actions;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_List_Table::get_columns()
	 */
	function get_columns(){
		$columns = array(
			'cb'			=> '<input type="checkbox" class="yytt-video-list-select-all" />',
			'post_title'	=> __('Title', 'yti_video'),
			'video_id'		=> __('Video ID', 'yti_video'),
			'duration'		=> __('Duration', 'yti_video'),
			'category'	=> __('Category', 'yti_video'),
			'post_date' 	=> __('Date', 'yti_video'),
		);    	
    	return $columns;
	}
	
	/**
     * (non-PHPdoc)
     * @see WP_List_Table::prepare_items()
     */    
    function prepare_items() {
    	
    	$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        global $YTI_POST_TYPE;
        $this->_column_headers = array($columns, $hidden, $sortable);
                
    	$per_page 		= 20;
    	$current_page 	= $this->get_pagenum();
        
    	$search_for = '';
    	if( isset($_REQUEST['s']) ){
    		$search_for = esc_attr( stripslashes( $_REQUEST['s'] ) );
    	}

    	$category = false;
    	if( isset( $_GET['cat'] ) && $_GET['cat'] ){
    		$category = (int)$_GET['cat'];
    	}
    	
    	$post_type = $YTI_POST_TYPE->get_post_type();
    	if( isset( $_GET['pt'] ) && 'post' == $_GET['pt']){
    		$post_type = 'post';
    	}
    	
        $args = array(
			'post_type'			=> $post_type,
			'orderby' 			=> 'post_date',
		    'order' 			=> 'DESC',
	    	'posts_per_page'	=> $per_page,
	    	'offset'			=> ($current_page-1) * $per_page,
        	'post_status'		=> 'publish',
			's'					=> $search_for        	
        );
        
        // for regular posts stored as video, run meta query
        if( 'post' == $post_type ){
        	$args['meta_query'] = array(
        		array(
        			'key' => '__yti_is_video',
        			'value' => true
        		)
        	);
        }
        
        if( $category ){
        	if( 'post' == $post_type ){
        		$args['cat'] = $category;
        	}else{        	
	        	$args['tax_query'] = array(
	        		array(
	        			'taxonomy' => 'videos', 
	        			'field' => 'id', 
	        			'terms' => $category
	        		)
	        	);
        	}	
        }
        
        
		$query = new WP_Query( $args );
		
		$data = array();    
        if( $query->posts ){
        	foreach($query->posts as $k => $item){
        		$data[$k] = (array)$item;
        	}
        }
        
        $total_items = $query->found_posts;
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  
            'per_page'    => $per_page,                     
            'total_pages' => ceil($total_items/$per_page)  
        ) );
    }
	
}