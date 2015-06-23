<div class="wrap">
	<div class="icon32" id="icon-themes"><br></div>
	<h2><?php _e('Compatibility', 'yvi_video');?></h2>
	
	<?php if( !yvi_check_theme_support() ):?>
	<div  id="message" class="error">
		<p>
			<strong><?php _e("Seems like your theme isn't compatible with the plugin.", 'yvi_video');?></strong>
			<a class="button" href="http://www.youtubeimporter.com/theme-compatibility-tutorial/"><?php _e('See how to make it compatible!', 'yvi_video');?></a>
		</p>
	</div>
	<?php else:?>
	<div  id="message" class="updated">
		<p>
			<strong><?php _e("Congratulations, your current theme is compatible by default with the plugin.", 'yvi_video');?></strong>			
		</p>
	</div>
	<?php endif;?>
	
	<h3><?php _e('Default Compatible WordPress themes With Youtube Importer', 'yvi_video');?></h3>
	<p>
		<?php _e('If You are using these themes listed below Then You can import YouTube videos directly as posts compatible with the theme .', 'yvi_video');?>
	</p>
	<p>
		<?php foreach($themes as $theme):?>
		<span>
			<?php 
				$class = 'not-installed';
				if( isset( $theme['installed'] ) && $theme['installed'] ){
					$class = 'yvi-installed';
				}
				if( isset($theme['active']) && $theme['active'] ){
					$class = 'yvi-active';
				}				
			?>
			<?php printf('<a href="%1$s" target="_blank" title="%2$s" class="%3$s">%2$s</a>', $theme['url'], $theme['theme_name'], $class);?>
		</span>
		<?php endforeach;?>
	</p>
	
	<p>
		<?php _e("If your theme isn't listed above, the next thing to try is to <strong>Import videos as Regular Post type</strong>. To do this, just visit page plugin page Settings and check the option <strong>Regular Post type</strong>.", 'yvi_video');?><br />
		<?php _e('This will enable you to import YouTube videos as regular posts that have the same player settings as the custom post type and will follow the rules you set in Settings page.', 'yvi_video');?>
	</p>
	
	<p>
		<?php printf(__("If importing as regular post type doesn't do it for you (for example your WP theme has video capabilities and you want to import videos as posts compatible with your theme), just %sfollow the tutorial to make your WP theme compatible with the plugin%s.", 'yvi_video'), '<a href="http://www.youtubeimporter.com/theme-compatibility-tutorial/" target="_blank">', '</a>');?>
	</p>
	
	<h3><?php _e('Default compatible WordPress plugins', 'yvi_video');?></h3>
	<p>
		<?php printf( __('Currently, only %sYoast Video SEO plugin%s is supported by default.', 'yvi_video'), '<a href="https://yoast.com/wordpress/plugins/video-seo/" target="_blank">', '</a>');?>
	</p>
	
	<h3><?php _e('Docs and tutorials', 'yvi_video');?></h3>
	<ul>
		<li><a href="http://www.youtubeimporter.com/documentation-wp-youtube-video-import/" target="_blank"><?php _e('How to use the plugin', 'yvi_video');?></a></li>
		<li><a href="http://www.youtubeimporter.com/how-to-get-your-youtube-api-key/" target="_blank"><?php _e('How to get your YouTube API key', 'yvi_video')?></a></li>
		<li><a href="http://www.youtubeimporter.com/theme-compatibility-tutorial/" target="_blank"><?php _e('How to make a non-supported theme compatible with the plugin', 'yvi_video');?></a></li>		
	</ul>
	
	<h3><?php _e( 'Shortcodes reference', 'yvi_video' );?></h3>
	<ul>
	<?php foreach( $shortcodes_obj->get_shortcodes() as $shortcode => $data ):?>
		<li>
			<h3>[<?php echo $shortcode;?>]</h3>
			<h4><?php _e( 'Attributes', 'yvi_video' );?></h4>
			<ul>
			<?php foreach( $data['atts'] as $att => $details ):?>
				<li><strong><?php echo $att;?></strong>: <?php echo $details['description'];?></li>
			<?php endforeach;?>
			</ul>
		</li>
	<?php endforeach; ?>
	</ul>
</div>	