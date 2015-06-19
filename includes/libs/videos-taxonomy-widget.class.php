<?php

/**
 * Custom post type taxonomy widget
 */
class YTI_Videos_Taxonomy_Widget extends WP_Widget{
	/**
	 * Constructor
	 */
	function YTI_Videos_Taxonomy_Widget(){
		/* Widget settings. */
		$widget_options = array( 
			'classname' 	=> 'widget_categories', 
			'description' 	=> __('Video categories.', 'yti_video') 
		);

		/* Widget control settings. */
		$control_options = array( 
			'id_base' => 'yti-taxonomy-video-widget' 
		);

		/* Create the widget. */
		$this->WP_Widget( 
			'yti-taxonomy-video-widget', 
			__('Video Categories', 'yti_video'), 
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
				
		$widget_title = '';
		if( isset( $instance['yti_widget_title'] ) && !empty( $instance['yti_widget_title'] ) ){
			$widget_title = $before_title . apply_filters('widget_title', $instance['yti_widget_title']) . $after_title;
		}
		
		$args = array(
			'show_option_all'    => false,
			'orderby'            => 'name',
			'order'              => 'ASC',
			'style'              => 'list',
			'show_count'         => (bool)$instance['yti_post_count'],
			'hide_empty'         => true,
			'use_desc_for_title' => true,
			'hierarchical'       => (bool)$instance['yti_hierarchy'],
			'title_li'           => false,
			'show_option_none'   => __('No video categories', 'yti_video'),
			'number'             => null,
			'echo'               => 1,
			'depth'              => 0,
			'current_category'   => 0,
			'pad_counts'         => 0,
			'taxonomy'           => $YTI_POST_TYPE->get_post_tax()
		);
				
		echo $before_widget;
		
		if( !empty( $instance['yti_widget_title'] ) ){		
			echo $before_title . apply_filters('widget_title', $instance['yti_widget_title']) . $after_title;
		}
		?>
		<ul>
			<?php wp_list_categories( $args );?>
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
		$instance['yti_post_count']	  	= (bool)$new_instance['yti_post_count'];
		$instance['yti_hierarchy'] 	= (bool)$new_instance['yti_hierarchy'];
				
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
			<input class="checkbox yti_post_count" type="checkbox" name="<?php echo $this->get_field_name('yti_post_count');?>" id="<?php echo $this->get_field_id('yti_post_count')?>"<?php yti_check((bool)$options['yti_post_count']);?> />
			<label for="<?php echo $this->get_field_id('yti_post_count')?>"><?php _e('Show video counts', 'yti_video');?></label>
		</p>
		<p>
			<input class="checkbox yti_hierarchy" type="checkbox" name="<?php echo $this->get_field_name('yti_hierarchy');?>" id="<?php echo $this->get_field_id('yti_hierarchy')?>"<?php yti_check((bool)$options['yti_hierarchy']);?> />
			<label for="<?php echo $this->get_field_id('yti_hierarchy')?>"><?php _e('Show hierarchy', 'yti_video');?></label>
		</p>
	</div>	
		<?php 		
	}
	
	/**
	 * Default widget values
	 */
	private function get_defaults(){
		$player_defaults = yti_get_player_settings();		
		$defaults = array(
			'yti_widget_title' 	=> __('Video categories', 'yti_video'),
			'yti_post_count' 	=> false,
			'yti_hierarchy'		=> false
		);
		return $defaults;
	}
}