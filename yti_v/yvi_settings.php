<div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2><?php _e('Videos - Plugin settings', 'yvi_video');?></h2>
	<form method="post" action="<?php echo $form_action;?>">
		<div id="yvi_tabs">
			<?php wp_nonce_field('yvi-save-plugin-settings', 'yvi_wp_nonce');?>

			<div class="tab active" id="content1"><?php _e('Post Settings', 'yvi_video')?></div>

			<div class="content content1" style="display:block">
							<div id="yvi-settings-post-options">
				<table class="form-table">
					<tbody>
						<!-- Import type -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-admin-tools"></i> <?php _e('General settings', 'yvi_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="post_type_post"><?php _e('Regular Post type', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" name="post_type_post" value="1" id="post_type_post"<?php yvi_check( $options['post_type_post'] );?> />
								<span class="description">
								<?php _e('Check This Option to Import Video as a Regular Posts. Videos will be imported as <strong>regular posts</strong> instead of custom post type video.', 'yvi_video');?>
								</span>
							</td>
						</tr>				
						<tr valign="top">
							<th scope="row"><label for="archives"><?php _e('Archive Pages', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" name="archives" value="1" id="archives"<?php yvi_check( $options['archives'] );?> />
								<span class="description">
									<?php _e('Check This Option if you want to Embed videos in Archive Pages. When checked, videos will be visible on all pages displaying lists of video posts.', 'yvi_video');?>
								</span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="use_microdata"><?php _e('Microdata', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" name="use_microdata" value="1" id="use_microdata"<?php yvi_check( $options['use_microdata'] );?> />
								<span class="description">
									<?php _e('Check this option if you want to include Microdata on video pages SEO purposes ( more on <a href="http://schema.org" target="_blank">http://schema.org</a> ).', 'yvi_video');?>
								</span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="check_video_status"><?php _e('Video Statuses', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" name="check_video_status" value="1" id="check_video_status"<?php yvi_check( $options['check_video_status'] );?> />
								<span class="description">
									<?php _e('If Checked plugin will verify on YouTube every 24Hours if the video still exists or is embeddable and if not, it will automatically set the post status to pending. This action is triggered by your website visitors.', 'yvi_video');?>
								</span>
							</td>
						</tr>
						
						<!-- Visibility -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-video-alt3"></i> <?php _e('Video post type Settings', 'yvi_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="public"><?php _e('Public', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" name="public" value="1" id="public"<?php yvi_check( $options['public'] );?> />
								<span class="description">
								<?php if( !$options['public'] ):?>
									<span style="color:red;"><?php _e('Videos cannot be displayed in front-end. You can only incorporate them in playlists or display them in regular posts using shortcodes.', 'yvi_video');?></span>
								<?php else:?>
								<?php _e('When Video post type is public. Videos will display in front-end as post type video are and can also be incorporated in playlists or displayed in regular posts.', 'yvi_video');?>
								<?php endif;?>
								</span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="homepage"><?php _e('Post type on homepage', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" name="homepage" value="1" id="homepage"<?php yvi_check( $options['homepage'] );?> />
								<span class="description">
									<?php _e('When checked, if your homepage displays a list of regular posts, videos will be included among them.', 'yvi_video');?>
								</span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="main_rss"><?php _e('Post type in main RSS feed', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" name="main_rss" value="1" id="main_rss"<?php yvi_check( $options['main_rss'] );?> />
								<span class="description">
									<?php _e('When checked, custom post type will be included in your main RSS feed.', 'yvi_video');?>
								</span>
							</td>
						</tr>				
						
						
						<!-- Rewrite settings -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-admin-links"></i> <?php _e('Video post type rewrite (Pretty Links)', 'yvi_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="post_slug"><?php _e('Post slug', 'yvi_video')?>:</label></th>
							<td>
								<input type="text" id="post_slug" name="post_slug" value="<?php echo $options['post_slug'];?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="taxonomy_slug"><?php _e('Taxonomy slug', 'yvi_video')?> :</label></th>
							<td>
								<input type="text" id="taxonomy_slug" name="taxonomy_slug" value="<?php echo $options['taxonomy_slug'];?>" />
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yvi_video'));?>	
			</div>
			<!-- /Tab post options -->
			</div>


			<div class="tab" id="content2"><?php _e('Content Settings', 'yvi_video')?></div>

			<div class="content content2">
				<!-- Tab content options -->
			<div id="yvi-settings-content-options">
				<table class="form-table">
					<tbody>
						<!-- Content settings -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-admin-post"></i> <?php _e('Post content settings', 'yvi_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="import_categories"><?php _e('Import categories', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="import_categories" name="import_categories"<?php yvi_check($options['import_categories']);?> />
								<span class="description"><?php _e('When Checked, Plugin will auto import Categories from YouTube and assign Videos to that category.', 'yvi_video');?></span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="import_date"><?php _e('Import date', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="import_date" id="import_date"<?php yvi_check($options['import_date']);?> />
								<span class="description"><?php _e("When Checked, Plugin will import YouTube publishing date.", 'yvi_video');?></span>
							</td>
						</tr>	
						
						<tr valign="top">
							<th scope="row"><label for="import_title"><?php _e('Import titles', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="import_title" name="import_title"<?php yvi_check($options['import_title']);?> />
								<span class="description"><?php _e('When Checked , It will auto import video titles from feeds as post title.', 'yvi_video');?></span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="import_description"><?php _e('Import descriptions as', 'yvi_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'content' 			=> __('post content', 'yvi_video'),
											'excerpt' 			=> __('post excerpt', 'yvi_video'),
											'content_excerpt' 	=> __('post content and excerpt', 'yvi_video'),
											'none'				=> __('do not import', 'yvi_video')
										),
										'name' => 'import_description',
										'selected' => $options['import_description']								
									);
									yvi_select($args);
								?>
								<p class="description"><?php _e('Import video description from feeds as post description, excerpt or none.', 'yvi_video')?></p>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="remove_after_text"><?php _e('Remove text from descriptions found after', 'yvi_video')?>:</label></th>
							<td>
								<input type="text" name="remove_after_text" value="<?php echo $options['remove_after_text'];?>" id="remove_after_text" size="70" />
								<p class="description">
									<?php _e('If text above is found in description, all text following it (including the one entered above) will be removed from post content.', 'yvi_video');?><br />
									<?php _e('<strong>Please note</strong> that the plugin will search for the entire string entered here, not parts of it. An exact match must be found to perform the action.', 'yvi_video');?>
								</p>
							</td>
						</tr>				
						
						<tr valign="top">
							<th scope="row"><label for="prevent_autoembed"><?php _e('Prevent auto embed on video content', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="prevent_autoembed" id="prevent_autoembed"<?php yvi_check($options['prevent_autoembed']);?> />
								<span class="description">
									<?php _e('If content retrieved from YouTube has links to other videos, checking this option will prevent auto embedding of videos in your post content.', 'yvi_video');?>
								</span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="make_clickable"><?php _e("Make URL's in video content clickable", 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="make_clickable" id="make_clickable"<?php yvi_check($options['make_clickable']);?> />
								<span class="description">
									<?php _e("Automatically make all valid URL's from content retrieved from YouTube clickable.", 'yvi_video');?>
								</span>
							</td>
						</tr>															
						
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yvi_video'));?>	
			</div>
			<!-- /Tab content options -->
			</div>


			<div class="tab" id="content3"><?php _e('Image Settings', 'yvi_video')?></div>

			<div class="content content3">
				<!-- Tab image options -->
			<div id="yvi-settings-image-options">
				<table class="form-table">
					<tbody>
						<tr><th colspan="2"><h4><i class="dashicons dashicons-format-image"></i> <?php _e('Image settings', 'yvi_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="featured_image"><?php _e('Import images', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="featured_image" id="featured_image"<?php yvi_check($options['featured_image']);?> />
								<span class="description"><?php _e("When Checked, YouTube video thumbnail will be set as post featured image.", 'yvi_video');?></span>						
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="image_on_demand"><?php _e('Import featured image on request', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="image_on_demand" id="image_on_demand"<?php yvi_check($options['image_on_demand']);?> />
								<span class="description"><?php _e("YouTube video thumbnail will be imported only when featured images needs to be displayed (ie. a post created by the plugin is displayed).", 'yvi_video');?></span>
							</td>
						</tr>
										
						<tr valign="top">
							<th scope="row"><label for="image_size"><?php _e('Image size', 'yvi_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											''				=> __('Choose', 'yvi_video'),
											'default' 		=> __('Default (120x90 px)', 'yvi_video'),
											'medium' 		=> __('Medium (320x180 px)', 'yvi_video'),
											'high' 			=> __('High (480x360 px)', 'yvi_video'),
											'standard'		=> __('Standard (640x480 px)', 'yvi_video'),
											'maxres'		=> __('Maximum (1280x720 px)', 'yvi_video'  )
										),
										'name' 		=> 'image_size',
										'selected' 	=> $options['image_size']								
									);
									yvi_select($args);
								?>	
								( <input type="checkbox" value="1" name="maxres" id="maxres"<?php yvi_check( $options['maxres'] );?> /> <label for="maxres"><?php _e('try to retrieve maximum resolution if available', 'yvi_video');?></label> )					
							</td>
						</tr>									
						
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yvi_video'));?>
			</div>
			<!-- /Tab image options -->
			</div>


			<div class="tab" id="content4"><?php _e('Import Settings', 'yvi_video')?></div>

			<div class="content content4">
				<!-- Tab import options -->
			<div id="yvi-settings-import-options">
				<table class="form-table">
					<tbody>
						<!-- Manual Import settings -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-download"></i> <?php _e('Bulk Import settings', 'yvi_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="import_status"><?php _e('Import status', 'yvi_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'publish' 	=> __('Published', 'yvi_video'),
											'draft' 	=> __('Draft', 'yvi_video'),
											'pending'	=> __('Pending', 'yvi_video')
										),
										'name' 		=> 'import_status',
										'selected' 	=> $options['import_status']
									);
									yvi_select($args);
								?>
								<p class="description"><?php _e('Imported videos will have this status. Set Published if you want to auto publish all post.', 'yvi_video');?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="import_frequency"><?php _e('Automatic import', 'yvi_video')?>:</label></th>
							<td>
								<?php _e('Import ', 'yvi_video');?>
								<?php 
									$args = array(
										'options' 	=> yvi_automatic_update_batches(),
										'name'		=> 'import_quantity',
										'selected'	=> $options['import_quantity']
									);
									yvi_select( $args );
								?>
								<?php _e('every', 'yvi_video');?>
								<?php 
									$args = array(
										'options' => yvi_automatic_update_timing(),
										'name' 		=> 'import_frequency',
										'selected' 	=> $options['import_frequency']
									);
									yvi_select( $args );
								?>
								<p class="description"><?php _e('Set Query Time For Auto Import', 'yvi_video');?></p>
								<?php if( $options['page_load_autoimport'] ):?>
								<span class="description" style="color:red;">
									<?php _e( 'You chose to auto import videos by using the legacy page load trigger. We recommend that you set the number of videos to be imported at a maximum of 10 or 15 videos at a time.', 'yvi_video' );?>
								</span>
								<?php endif;?>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for=""><?php _e('Automatic import trigger', 'yvi_video')?>:</label></th>
							<td>
								<p>
									<?php _e('By default, automatic imports are triggered by your website visitors when they view any of your website pages.', 'yvi_video');?><br />
									<?php _e("If your website doesn't have enough traffic to consistently trigger the automatic imports we suggest that you set up a server Cron Job to open any of your pages at the given time interval.", 'yvi_video');?><br />
									<?php _e("As an alternative to Cron Jobs, your could use the services of a Website Monitoring service that will access your website at a given time interval.", 'yvi_video');?>
								</p>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="page_load_autoimport"><?php _e('Legacy automatic import', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" name="page_load_autoimport" id="page_load_autoimport" value="1" <?php yvi_check( (bool)$options['page_load_autoimport'] )?> />
								<span class="description"><?php _e( 'Trigger automatic video imports on page load (will increase page load time when doing automatic imports)', 'yvi_video' );?></span>
								<p>
									<?php _e( 'Starting with version 1.2, automatic imports are triggered by making a remote call to your website that triggers the imports. This decreases page loading time and improves the import process.', 'yvi_video' );?><br />
									<?php _e( 'Some systems may not allow this functionality. If you notice that your automatic import playlists aren\'t importing, enable this option.', 'yvi_video' );?>
								</p>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="unpublish_on_yt_error"><?php _e('Remove playlist from queue on YouTube error', 'yvi_video');?>:</label></th>
							<td>
								<input type="checkbox" name="unpublish_on_yt_error" id="unpublish_on_yt_error" value="1" <?php yvi_check( (bool)$options['unpublish_on_yt_error'] );?> />
								<span class="description">
									<?php _e( 'When checked, if automatically imported playlist returns a YouTube error when queued, it will be unpublished.', 'yvi_video' );?>
								</span>
							</td>
						</tr>
						
						<tr>	
							<th scope="row"><label for="manual_import_per_page"><?php _e('Manual import results per page', 'yvi_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' 	=> yvi_automatic_update_batches(),
										'name'		=> 'manual_import_per_page',
										'selected'	=> $options['manual_import_per_page']
									);
									yvi_select( $args );
								?>
								<p class="description"><?php _e('How many results to display per page on manual import.', 'yvi_video');?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yvi_video'));?>
			</div>
			<!-- /Tab import options -->
			</div>


			<div class="tab" id="content5"><?php _e('Embed Settings', 'yvi_video')?></div>

			<div class="content content5">
				<!-- Tab embed options -->
			<div id="yvi-settings-embed-options">
				<table class="form-table">
					<tbody>
						<tr>
							<th colspan="2">
								<h4><i class="dashicons dashicons-video-alt3"></i> <?php _e('Player settings', 'yvi_video');?></h4>
								<p class="description"><?php _e('General YouTube player settings. These settings will be applied to any new video by default and can be changed individually for every imported video.', 'yvi_video');?></p>
							</th>
						</tr>
						
						<tr>
							<th><label for="yvi_aspect_ratio"><?php _e('Player size', 'yvi_video');?>:</label></th>
							<td class="yvi-player-settings-options">
								<label for="yvi_aspect_ratio"><?php _e('Aspect ratio', 'yvi_video');?>:</label>
								<?php 
									$args = array(
										'options' 	=> array(
											'4x3' 	=> '4x3',
											'16x9' 	=> '16x9'
										),
										'name' 		=> 'aspect_ratio',
										'id'		=> 'yvi_aspect_ratio',
										'class'		=> 'yvi_aspect_ratio',
										'selected' 	=> $player_opt['aspect_ratio']
									);
									yvi_select( $args );
								?>
								<label for="yvi_width"><?php _e('Width', 'yvi_video');?>:</label>
								<input type="text" name="width" id="yvi_width" class="yvi_width" value="<?php echo $player_opt['width'];?>" size="2" />px
								| <?php _e('Height', 'yvi_video');?> : <span class="yvi_height" id="yvi_calc_height"><?php echo yvi_player_height( $player_opt['aspect_ratio'], $player_opt['width'] );?></span>px
							</td>
						</tr>
						
						<tr>
							<th><label for="yvi_video_position"><?php _e('Show video in custom post','yvi_video');?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'above-content' => __('Above post content', 'yvi_video'),
											'below-content' => __('Below post content', 'yvi_video')
										),
										'name' 		=> 'video_position',
										'id'		=> 'yvi_video_position',
										'selected' 	=> $player_opt['video_position']
									);
									yvi_select($args);
								?>
							</td>
						</tr>
						
						<tr>
							<th><label for="yvi_volume"><?php _e('Volume', 'yvi_video');?></label>:</th>
							<td>
								<input type="text" name="volume" id="yvi_volume" value="<?php echo $player_opt['volume'];?>" size="1" maxlength="3" />
								<label for="yvi_volume"><span class="description">( <?php _e('number between 0 (mute) and 100 (max)', 'yvi_video');?> )</span></label>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="autoplay"><?php _e('Autoplay', 'yvi_video')?>:</label></th>
							<td><input type="checkbox" value="1" id="autoplay" name="autoplay"<?php yvi_check( (bool )$player_opt['autoplay'] );?> /></td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="yvi_controls"><?php _e('Show player controls', 'yvi_video')?>:</label></th>
							<td><input type="checkbox" value="1" id="yvi_controls" class="yvi_controls" name="controls"<?php yvi_check( (bool)$player_opt['controls'] );?> /></td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yvi_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="fs"><?php _e('Allow fullscreen', 'yvi_video')?>:</label></th>
							<td><input type="checkbox" name="fs" id="fs" value="1"<?php yvi_check( (bool)$player_opt['fs'] );?> /></td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yvi_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="autohide"><?php _e('Autohide controls', 'yvi_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'0' => __('Always show controls', 'yvi_video'),
											'1' => __('Hide controls on load and when playing', 'yvi_video'),
											'2' => __('Fade out progress bar when playing', 'yvi_video')	
										),
										'name' => 'autohide',
										'selected' => $player_opt['autohide']
									);
									yvi_select($args);
								?>
							</td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yvi_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="theme"><?php _e('Player theme', 'yvi_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'dark' => __('Dark', 'yvi_video'),
											'light'=> __('Light', 'yvi_video')
										),
										'name' => 'theme',
										'selected' => $player_opt['theme']
									);
									yvi_select($args);
								?>
							</td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yvi_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="color"><?php _e('Player color', 'yvi_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'red' => __('Red', 'yvi_video'),
											'white'=> __('White', 'yvi_video')
										),
										'name' => 'color',
										'selected' => $player_opt['color']
									);
									yvi_select($args);
								?>
							</td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yvi_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="modestbranding"><?php _e('No YouTube logo on controls bar', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="modestbranding" name="modestbranding"<?php yvi_check( (bool)$player_opt['modestbranding'] );?> />
								<span class="description"><?php _e('Setting the color parameter to white will cause this option to be ignored.', 'yvi_video');?></span>
							</td>					
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="iv_load_policy"><?php _e('Annotations', 'yvi_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'1' => __('Show annotations by default', 'yvi_video'),
											'3'=> __('Hide annotations', 'yvi_video')
										),
										'name' => 'iv_load_policy',
										'selected' => $player_opt['iv_load_policy']
									);
									yvi_select($args);
								?>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="rel"><?php _e('Show related videos', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="rel" name="rel"<?php yvi_check( (bool)$player_opt['rel'] );?> />
								<label for="rel"><span class="description"><?php _e('when checked, after video ends player will display related videos', 'yvi_video');?></span></label>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="showinfo"><?php _e('Show video title by default', 'yvi_video')?>:</label></th>
							<td><input type="checkbox" value="1" id="showinfo" name="showinfo"<?php yvi_check( (bool )$player_opt['showinfo']);?> /></td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="disablekb"><?php _e('Disable keyboard player controls', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="disablekb" name="disablekb"<?php yvi_check( (bool)$player_opt['disablekb'] );?> />
								<span class="description"><?php _e('Works only when player has focus.', 'yvi_video');?></span>
								<p class="description"><?php _e('Controls:<br> - spacebar : play/pause,<br> - arrow left : jump back 10% in current video,<br> - arrow-right: jump ahead 10% in current video,<br> - arrow up - volume up,<br> - arrow down - volume down.', 'yvi_video');?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yvi_video'));?>
			</div>
			<!-- /Tab embed options -->
			</div>


			<div class="tab" id="content6"><?php _e('YouTube API', 'yvi_video')?></div>

			<div class="content content6">
				<!-- Tab auth options -->
			<div id="yvi-settings-auth-options">
				<table class="form-table">
					<tbody>
						<tr>
							<th colspan="2">
								<h4><i class="dashicons dashicons-admin-network"></i> <?php _e('YouTube API key', 'yvi_video');?></h4>
							</th>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="youtube_api_key"><?php _e('Enter YouTube API server key', 'yvi_video')?>:</label></th>
							<td>
								<input type="text" name="youtube_api_key" id="youtube_api_key" value="<?php echo $youtube_api_key;?>" size="60" />
								<p class="description">
									<?php if( !yvi_get_yt_api_key('validity') ):?>
									<span style="color:red;"><?php _e('YouTube API key is invalid. All requests will stop unless a valid API key is provided. Please check the Google Console for the correct API key.', 'yvi_video');?></span><br />
									<?php endif;?>
									<?php _e('To get your YouTube API key, visit this address:', 'yvi_video');?> <a href="https://code.google.com/apis/console" target="_blank">https://code.google.com/apis/console</a>.<br />
									<?php _e('After signing in, visit <strong>Create a new project</strong> and enable <strong>YouTube Data API</strong>.', 'yvi_video');?><br />
									<?php _e('To get your API key, visit <strong>APIs & auth</strong> and under <strong>Public API access</strong> create a new <strong>Server Key</strong>.', 'yvi_video');?><br />
									<?php  printf( __('For more detailed informations please see <a href="%s" target="_blank">this tutorial</a>.', 'yvi_video') , 'http://www.youtubeimporter.com/how-to-get-your-youtube-api-key/' ); ?>
								</p>														
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="show_quota_estimates"><?php _e('Show YouTube API daily quota', 'yvi_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="show_quota_estimates" name="show_quota_estimates" <?php yvi_check( $options['show_quota_estimates'] );?> />
								<span class="description">
									<?php _e( 'When checked, will display estimates regarding your daily YouTube API available units.', 'yvi_video' );?>
								</span>
							</td>
						</tr>
						
						<tr>
							<td colspan="2">
								<h4><i class="dashicons dashicons-admin-network"></i> <?php _e('YouTube OAuth credentials', 'yvi_video');?></h4>
								<p class="description">
									<?php _e( 'By allowing the plugin to access your YouTube account, you will be able to quickly create automatic imports from your YouTube playlists.', 'yvi_videos' );?><br />
									<?php _e( 'Please note that you will still have to enter the server API key into the field above.', 'yvi_video' );?><br />
									<?php _e( 'To get your OAuth credentials, please visit: ', 'yvi_video' );?> <a href="https://code.google.com/apis/console" target="_blank">https://code.google.com/apis/console</a>. <br />
									<?php _e( 'When creating OAuth credentials, make sure that under Authorized redirect URIs you enter: ' )?> <strong><?php echo yvi_get_oauth_redirect_uri();?></strong>									
								</p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="oauth_client_id"><?php _e('Client ID', 'yvi_video')?>:</label></th>
							<td>
								<input type="text" name="oauth_client_id" id="oauth_client_id" value="<?php echo $oauth_opt['client_id'];?>" size="60" />														
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="oauth_client_secret"><?php _e('Client secret', 'yvi_video')?>:</label></th>
							<td>
								<input type="text" name="oauth_client_secret" id="oauth_client_secret" value="<?php echo $oauth_opt['client_secret'];?>" size="60" />														
								<p><?php yvi_show_oauth_link();?></p>
							</td>
						</tr>						
						
						
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yvi_video'));?>
			</div>
			<!-- /Tab auth options -->
			</div>
		</div><!-- #yvi_tabs -->		
	</form>
</div>