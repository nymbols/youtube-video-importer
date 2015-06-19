<div class="wrap">
	<div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
	<h2><?php echo $title;?> - <?php _e('step 1', 'yti_video');?></h2>
	<form method="post" action="" >
		<?php wp_nonce_field('yti_query_new_video', 'wp_nonce');?>
		
		<p><?php _e('Please enter the video ID you want to search for:', 'yti_video');?></p>
		<input type="text" name="yti_video_id" value="" />
		<a href="#" id="yti_explain"><?php _e('how to get video ID', 'yti_video');?></a>
		<?php if( $theme_supported = yti_check_theme_support() ):?>
		<br />
		<input type="checkbox" name="single_theme_import" id="single_theme_import" value="1" />
		<label for="single_theme_import"><?php printf( __('Import as post compatible with theme <strong>%s</strong>', 'yti_video'), $theme_supported['theme_name'] );?></label>
		<?php endif;?>
		<p class="hidden" id="yti_explain_output">
			<?php _e('<strong>Step 1</strong> - open any YouTube video page with your favourite browser.', 'yti_video');?><br />
			<?php _e('<strong>Step 2</strong> - From your browser address bar copy the value from variable v (highlighted in image below).', 'yti_video');?><br />
			<img vspace="10" src="<?php echo YTI_URL;?>assets/back-end/images/yt-video-id-example.png" /><br />
			<?php _e('<strong>Step 3</strong> - paste the ID into the field above and hit Search video below.', 'yti_video');?>
		</p>
		
		<input type="hidden" name="yti_source" value="youtube" />
		<?php submit_button(__('Search video', 'yti_video'));?>
	</form>
</div>