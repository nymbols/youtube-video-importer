<?php
/**
 * Latest videos widget
 */
class YTI_Latest_Videos_Widget extends WP_Widget{
	/**
	 * Constructor
	 */
	function YTI_Latest_Videos_Widget(){
		/* Widget settings. */
		$widget_options = array( 
			'classname' 	=> 'yti-latest-videos', 
			'description' 	=> __('The most recent videos on your site.', 'yti_video') 
		);

		/* Widget control settings. */
		$control_options = array( 
			'id_base' => 'yti-latest-videos-widget' 
		);

		/* Create the widget. */
		$this->WP_Widget( 
			'yti-latest-videos-widget', 
			__('Recent videos', 'yti_video'), 
			$widget_options, 
			$control_options 
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see WP_Widget::widget()
	 */
	function widget( $args, $instance ){
		
		extract($args);
		
		global $YTI_POST_TYPE;
		$posts = absint($instance['yti_posts_number']);
		
		$widget_title = '';
		if( isset( $instance['yti_widget_title'] ) && !empty( $instance['yti_widget_title'] ) ){
			$widget_title = $before_title . apply_filters('widget_title', $instance['yti_widget_title']) . $after_title;
		}
		
		// if setting to display player is set, show it
		if( isset( $instance['yti_show_playlist'] ) && $instance['yti_show_playlist'] ){
			
			$player_settings = array(
				'width' 		=> $instance['width'],
				'aspect_ratio' 	=> $instance['aspect_ratio'],
				'volume'		=> $instance['volume']
			);
			
			echo $before_widget;
			echo $widget_title;
			echo yti_output_playlist( 'latest', $posts, 'default', $player_settings, $instance['yti_posts_tax'] );
			echo $after_widget;
			return;	
		}
		
		$args = array(
			'numberposts' 		=> $posts,
			'posts_per_page' 	=> $posts,
			'orderby' 			=> 'post_date',
			'order' 			=> 'DESC',
			'post_type' 		=> $YTI_POST_TYPE->get_post_type(),
			'post_status' 		=> 'publish',
			'suppress_filters' 	=> true
		);

		if( isset( $instance['yti_posts_tax'] ) && !empty( $instance['yti_posts_tax'] ) && ((int)$instance['yti_posts_tax']) > 0 ){
			$term = get_term( $instance['yti_posts_tax'], $YTI_POST_TYPE->get_post_tax(), ARRAY_A );
			if( !is_wp_error( $term ) ){			
				$args[ $YTI_POST_TYPE->get_post_tax() ] = $term['slug'];
			}	
		}
		
		// display a list of video posts
		$posts = get_posts( $args );
		if( !$posts ){
			return;
		}
		
		echo $before_widget;
		
		if( !empty( $instance['yti_widget_title'] ) ){		
			echo $before_title . apply_filters('widget_title', $instance['yti_widget_title']) . $after_title;
		}
		?>
		<ul class="yti-recent-videos-widget">
			<?php foreach($posts as $post):?>
			<?php 
			if( $instance['yti_yt_image'] ){
				$video_data = get_post_meta($post->ID, '__yti_video_data', true);
				if( isset( $video_data['thumbnails'][0] ) ){
					$thumbnail = sprintf('<img src="%s" alt="%s" />', $video_data['thumbnails'][0], apply_filters('the_title', $post->post_title));
				}
			}else{
				$thumbnail = '';
			}
			?>
			<li><a href="<?php echo get_permalink($post->ID);?>" title="<?php echo apply_filters('the_title', $post->post_title);?>"><?php echo $thumbnail;?> <?php echo apply_filters('post_title', $post->post_title);?></a></li>
			<?php endforeach;?>
		</ul>
		<?php 
		echo $after_widget;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_Widget::update()
	 */
	function update($new_instance, $old_instance){

		$instance = $old_instance;
		$instance['yti_widget_title'] 	= $new_instance['yti_widget_title'];
		$instance['yti_posts_number'] 	= (int)$new_instance['yti_posts_number'];
		$instance['yti_posts_tax']		= (int)$new_instance['yti_posts_tax'];
		$instance['yti_yt_image']	  	= (bool)$new_instance['yti_yt_image'];
		$instance['yti_show_playlist'] 	= (bool)$new_instance['yti_show_playlist'];
		$instance['aspect_ratio'] 		= $new_instance['aspect_ratio'];
		$instance['width'] 				= absint( $new_instance['width'] );
		$instance['volume'] 			= absint( $new_instance['volume'] );
		
		return $instance;		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see WP_Widget::form()
	 */
	function form( $instance ){
		
		$defaults 	= $this->get_defaults();;
		$options 	= wp_parse_args( (array)$instance, $defaults );
		
		?>
	<div class="yti-player-settings-options">	
		<p>
			<label for="<?php echo  $this->get_field_id('yti_widget_title');?>"><?php _e('Title', 'yti_video');?>: </label>
			<input type="text" name="<?php echo  $this->get_field_name('yti_widget_title');?>" id="<?php echo  $this->get_field_id('yti_widget_title');?>" value="<?php echo $options['yti_widget_title'];?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('yti_posts_number');?>"><?php _e('Number of videos to show', 'yti_video');?>: </label>
			<input type="text" name="<?php echo $this->get_field_name('yti_posts_number');?>" id="<?php echo $this->get_field_id('yti_posts_number');?>" value="<?php echo $options['yti_posts_number'];?>" size="3" />
		</p>		
		<p>
			<label for="<?php echo $this->get_field_id('yti_posts_tax');?>"><?php _e('Category', 'yti_video');?>: </label>
			<?php 
				global $YTI_POST_TYPE;
				$args = array(
					'show_option_all' 	=> false,
					'show_option_none'	=> __('All categories', 'yti_video'),
					'orderby' 			=> 'NAME',
					'order' 			=> 'ASC',
					'show_count' 		=> true,
					'hide_empty'		=> false,
					'selected'			=> $options['yti_posts_tax'],
					'hierarchical'		=> true,
					'name'				=> $this->get_field_name('yti_posts_tax'),
					'id'				=> $this->get_field_id('yti_posts_tax'),
					'taxonomy'			=> $YTI_POST_TYPE->get_post_tax(),
					'hide_if_empty'		=> true
				);
				$select = wp_dropdown_categories( $args );
				if( !$select ){
					_e('Nothing found.', 'yti_video');
					?>
					<input type="hidden" name="<?php echo $this->get_field_name('yti_posts_tax');?>" id="<?php echo $this->get_field_id('yti_posts_tax');?>" value="" />
					<?php
				}
			?>
		</p>		
		<p class="yti-widget-show-yt-thumbs"<?php if( $options['yti_show_playlist'] ):?> style="display:none;"<?php endif;?>>
			<input class="checkbox" type="checkbox" name="<?php echo $this->get_field_name('yti_yt_image')?>" id="<?php echo $this->get_field_id('yti_yt_image');?>"<?php yti_check( (bool)$options['yti_yt_image'] );?> />
			<label for="<?php echo $this->get_field_id('yti_yt_image');?>"><?php _e('Display YouTube thumbnails?', 'yti_video');?></label>
		</p>
		<p>
			<input class="checkbox yti-show-as-playlist-widget" type="checkbox" name="<?php echo $this->get_field_name('yti_show_playlist');?>" id="<?php echo $this->get_field_id('yti_show_playlist')?>"<?php yti_check((bool)$options['yti_show_playlist']);?> />
			<label for="<?php echo $this->get_field_id('yti_show_playlist')?>"><?php _e('Show as video playlist', 'yti_video');?></label>
		</p>
		<div class="yti-recent-videos-playlist-options"<?php if( !$options['yti_show_playlist'] ):?> style="display:none;"<?php endif;?>>
			
			<p>
				<label for="yti_aspect_ratio"><?php _e('Aspect');?> :</label>
				<?php 
					$args = array(
						'options' 	=> array(
							'4x3' 	=> '4x3',
							'16x9' 	=> '16x9'
						),
						'name' 		=> $this->get_field_name( 'aspect_ratio' ),
						'id'		=> $this->get_field_id( 'aspect_ratio' ),
						'class'		=> 'yti_aspect_ratio',
						'selected' 	=> $options['aspect_ratio']
					);
					yti_select( $args );
				?><br />
				<label for="<?php echo $this->get_field_id('width')?>"><?php _e('Width', 'yti_video');?> :</label>
				<input type="text" class="yti_width" name="<?php echo $this->get_field_name('width');?>" id="<?php echo $this->get_field_id('width')?>" value="<?php echo $options['width'];?>" size="2" />px
				| <?php _e('Height', 'yti_video');?> : <span class="yti_height" id="<?php echo $this->get_field_id('yti_calc_height')?>"><?php echo yti_player_height( $options['aspect_ratio'], $options['width'] );?></span>px
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('volume');?>"><?php _e('Volume', 'yti_video');?></label> :
				<input type="text" name="<?php echo $this->get_field_name('volume');?>" id="<?php echo $this->get_field_id('volume');?>" value="<?php echo $options['volume'];?>" size="1" maxlength="3" />
				<label for="<?php echo $this->get_field_id('volume');?>"><span class="description"><?php _e('number between 0 (mute) and 100 (max)', 'yti_video');?></span></label>
					
			</p>
		</div>
	</div>	
		<?php 		
	}
	
	/**
	 * Default widget values
	 */
	private function get_defaults(){
		$player_defaults = yti_get_player_settings();		
		$defaults = array(
			'yti_widget_title' 	=> '',
			'yti_posts_number' 	=> 5,
			'yti_yt_image'		=> false,
			'yti_show_playlist'	=> false,
			'yti_posts_tax'		=> -1,
			'aspect_ratio'	=> $player_defaults['aspect_ratio'],
			'width'			=> $player_defaults['width'],
			'volume'		=> $player_defaults['volume'],
		);
		return $defaults;
	}
}