<?php

/**
 * Custom post type taxonomy widget
 */
class YVI_Videos_Taxonomy_Widget extends WP_Widget{
	/**
	 * Constructor
	 */
	function YVI_Videos_Taxonomy_Widget(){
		/* Widget settings. */
		$widget_options = array( 
			'classname' 	=> 'widget_categories', 
			'description' 	=> __('Video categories.', 'yvi_video') 
		);

		/* Widget control settings. */
		$control_options = array( 
			'id_base' => 'yvi-taxonomy-video-widget' 
		);

		/* Create the widget. */
		$this->WP_Widget( 
			'yvi-taxonomy-video-widget', 
			__('Video Categories', 'yvi_video'), 
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
		
		global $YVI_POST_TYPE;
				
		$widget_title = '';
		if( isset( $instance['yvi_widget_title'] ) && !empty( $instance['yvi_widget_title'] ) ){
			$widget_title = $before_title . apply_filters('widget_title', $instance['yvi_widget_title']) . $after_title;
		}
		
		$args = array(
			'show_option_all'    => false,
			'orderby'            => 'name',
			'order'              => 'ASC',
			'style'              => 'list',
			'show_count'         => (bool)$instance['yvi_post_count'],
			'hide_empty'         => true,
			'use_desc_for_title' => true,
			'hierarchical'       => (bool)$instance['yvi_hierarchy'],
			'title_li'           => false,
			'show_option_none'   => __('No video categories', 'yvi_video'),
			'number'             => null,
			'echo'               => 1,
			'depth'              => 0,
			'current_category'   => 0,
			'pad_counts'         => 0,
			'taxonomy'           => $YVI_POST_TYPE->get_post_tax()
		);
				
		echo $before_widget;
		
		if( !empty( $instance['yvi_widget_title'] ) ){		
			echo $before_title . apply_filters('widget_title', $instance['yvi_widget_title']) . $after_title;
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
		$instance['yvi_widget_title'] 	= $new_instance['yvi_widget_title'];
		$instance['yvi_post_count']	  	= (bool)$new_instance['yvi_post_count'];
		$instance['yvi_hierarchy'] 	= (bool)$new_instance['yvi_hierarchy'];
				
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
	<div class="yvi-player-settings-options">	
		<p>
			<label for="<?php echo  $this->get_field_id('yvi_widget_title');?>"><?php _e('Title', 'yvi_video');?>: </label>
			<input type="text" name="<?php echo  $this->get_field_name('yvi_widget_title');?>" id="<?php echo  $this->get_field_id('yvi_widget_title');?>" value="<?php echo $options['yvi_widget_title'];?>" class="widefat" />
		</p>
		<p>
			<input class="checkbox yvi_post_count" type="checkbox" name="<?php echo $this->get_field_name('yvi_post_count');?>" id="<?php echo $this->get_field_id('yvi_post_count')?>"<?php yvi_check((bool)$options['yvi_post_count']);?> />
			<label for="<?php echo $this->get_field_id('yvi_post_count')?>"><?php _e('Show video counts', 'yvi_video');?></label>
		</p>
		<p>
			<input class="checkbox yvi_hierarchy" type="checkbox" name="<?php echo $this->get_field_name('yvi_hierarchy');?>" id="<?php echo $this->get_field_id('yvi_hierarchy')?>"<?php yvi_check((bool)$options['yvi_hierarchy']);?> />
			<label for="<?php echo $this->get_field_id('yvi_hierarchy')?>"><?php _e('Show hierarchy', 'yvi_video');?></label>
		</p>
	</div>	
		<?php 		
	}
	
	/**
	 * Default widget values
	 */
	private function get_defaults(){
		$player_defaults = yvi_get_player_settings();		
		$defaults = array(
			'yvi_widget_title' 	=> __('Video categories', 'yvi_video'),
			'yvi_post_count' 	=> false,
			'yvi_hierarchy'		=> false
		);
		return $defaults;
	}
}