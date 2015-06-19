<div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2><?php _e('Videos - Plugin settings', 'yti_video');?></h2>
	<form method="post" action="">
		<div id="yti_tabs">
			<?php wp_nonce_field('yti-save-plugin-settings', 'yti_wp_nonce');?>
			<ul class="yti-tab-labels">
				<li><a href="#yti-settings-post-options"><i class="dashicons dashicons-arrow-right"></i> <?php _e('Post options', 'yti_video')?></a></li>
				<li><a href="#yti-settings-content-options"><i class="dashicons dashicons-arrow-right"></i> <?php _e('Content options', 'yti_video')?></a></li>
				<li><a href="#yti-settings-image-options"><i class="dashicons dashicons-arrow-right"></i> <?php _e('Image options', 'yti_video')?></a></li>
				<li><a href="#yti-settings-import-options"><i class="dashicons dashicons-arrow-right"></i> <?php _e('Import options', 'yti_video')?></a></li>
				<li><a href="#yti-settings-embed-options"><i class="dashicons dashicons-arrow-right"></i> <?php _e('Embed options', 'yti_video')?></a></li>
				<li><a href="#yti-settings-auth-options"><i class="dashicons dashicons-arrow-right"></i> <?php _e('API & License', 'yti_video')?></a></li>
			</ul>
			<!-- Tab post options -->
			<div id="yti-settings-post-options">
				<table class="form-table">
					<tbody>
						<!-- Import type -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-admin-tools"></i> <?php _e('General settings', 'yti_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="post_type_post"><?php _e('Import as regular post type (aka post)', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" name="post_type_post" value="1" id="post_type_post"<?php yti_check( $options['post_type_post'] );?> />
								<span class="description">
								<?php _e('Videos will be imported as <strong>regular posts</strong> instead of custom post type video. Posts having attached videos will display having the same player options as video post types.', 'yti_video');?>
								</span>
							</td>
						</tr>				
						<tr valign="top">
							<th scope="row"><label for="archives"><?php _e('Embed videos in archive pages', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" name="archives" value="1" id="archives"<?php yti_check( $options['archives'] );?> />
								<span class="description">
									<?php _e('When checked, videos will be visible on all pages displaying lists of video posts.', 'yti_video');?>
								</span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="use_microdata"><?php _e('Include microdata on video pages', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" name="use_microdata" value="1" id="use_microdata"<?php yti_check( $options['use_microdata'] );?> />
								<span class="description">
									<?php _e('When checked, all pages displaying videos will also include microdata for SEO purposes ( more on <a href="http://schema.org" target="_blank">http://schema.org</a> ).', 'yti_video');?>
								</span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="check_video_status"><?php _e('Check video statuses after import', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" name="check_video_status" value="1" id="check_video_status"<?php yti_check( $options['check_video_status'] );?> />
								<span class="description">
									<?php _e('When checked, will verify on YouTube every 24H if the video still exists or is embeddable and if not, it will automatically set the post status to pending. This action is triggered by your website visitors.', 'yti_video');?>
								</span>
							</td>
						</tr>
						
						<!-- Visibility -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-video-alt3"></i> <?php _e('Video post type options', 'yti_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="public"><?php _e('Video post type is public', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" name="public" value="1" id="public"<?php yti_check( $options['public'] );?> />
								<span class="description">
								<?php if( !$options['public'] ):?>
									<span style="color:red;"><?php _e('Videos cannot be displayed in front-end. You can only incorporate them in playlists or display them in regular posts using shortcodes.', 'yti_video');?></span>
								<?php else:?>
								<?php _e('Videos will display in front-end as post type video are and can also be incorporated in playlists or displayed in regular posts.', 'yti_video');?>
								<?php endif;?>
								</span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="homepage"><?php _e('Include videos post type on homepage', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" name="homepage" value="1" id="homepage"<?php yti_check( $options['homepage'] );?> />
								<span class="description">
									<?php _e('When checked, if your homepage displays a list of regular posts, videos will be included among them.', 'yti_video');?>
								</span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="main_rss"><?php _e('Include videos post type in main RSS feed', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" name="main_rss" value="1" id="main_rss"<?php yti_check( $options['main_rss'] );?> />
								<span class="description">
									<?php _e('When checked, custom post type will be included in your main RSS feed.', 'yti_video');?>
								</span>
							</td>
						</tr>				
						
						
						<!-- Rewrite settings -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-admin-links"></i> <?php _e('Video post type rewrite (pretty links)', 'yti_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="post_slug"><?php _e('Post slug', 'yti_video')?>:</label></th>
							<td>
								<input type="text" id="post_slug" name="post_slug" value="<?php echo $options['post_slug'];?>" />
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="taxonomy_slug"><?php _e('Taxonomy slug', 'yti_video')?> :</label></th>
							<td>
								<input type="text" id="taxonomy_slug" name="taxonomy_slug" value="<?php echo $options['taxonomy_slug'];?>" />
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yti_video'));?>	
			</div>
			<!-- /Tab post options -->
			
			<!-- Tab content options -->
			<div id="yti-settings-content-options">
				<table class="form-table">
					<tbody>
						<!-- Content settings -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-admin-post"></i> <?php _e('Post content settings', 'yti_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="import_categories"><?php _e('Import categories', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="import_categories" name="import_categories"<?php yti_check($options['import_categories']);?> />
								<span class="description"><?php _e('Categories retrieved from YouTube will be automatically created and videos assigned to them accordingly.', 'yti_video');?></span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="import_date"><?php _e('Import date', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="import_date" id="import_date"<?php yti_check($options['import_date']);?> />
								<span class="description"><?php _e("Imports will have YouTube's publishing date.", 'yti_video');?></span>
							</td>
						</tr>	
						
						<tr valign="top">
							<th scope="row"><label for="import_title"><?php _e('Import titles', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="import_title" name="import_title"<?php yti_check($options['import_title']);?> />
								<span class="description"><?php _e('Automatically import video titles from feeds as post title.', 'yti_video');?></span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="import_description"><?php _e('Import descriptions as', 'yti_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'content' 			=> __('post content', 'yti_video'),
											'excerpt' 			=> __('post excerpt', 'yti_video'),
											'content_excerpt' 	=> __('post content and excerpt', 'yti_video'),
											'none'				=> __('do not import', 'yti_video')
										),
										'name' => 'import_description',
										'selected' => $options['import_description']								
									);
									yti_select($args);
								?>
								<p class="description"><?php _e('Import video description from feeds as post description, excerpt or none.', 'yti_video')?></p>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="remove_after_text"><?php _e('Remove text from descriptions found after', 'yti_video')?>:</label></th>
							<td>
								<input type="text" name="remove_after_text" value="<?php echo $options['remove_after_text'];?>" id="remove_after_text" size="70" />
								<p class="description">
									<?php _e('If text above is found in description, all text following it (including the one entered above) will be removed from post content.', 'yti_video');?><br />
									<?php _e('<strong>Please note</strong> that the plugin will search for the entire string entered here, not parts of it. An exact match must be found to perform the action.', 'yti_video');?>
								</p>
							</td>
						</tr>				
						
						<tr valign="top">
							<th scope="row"><label for="prevent_autoembed"><?php _e('Prevent auto embed on video content', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="prevent_autoembed" id="prevent_autoembed"<?php yti_check($options['prevent_autoembed']);?> />
								<span class="description">
									<?php _e('If content retrieved from YouTube has links to other videos, checking this option will prevent auto embedding of videos in your post content.', 'yti_video');?>
								</span>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="make_clickable"><?php _e("Make URL's in video content clickable", 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="make_clickable" id="make_clickable"<?php yti_check($options['make_clickable']);?> />
								<span class="description">
									<?php _e("Automatically make all valid URL's from content retrieved from YouTube clickable.", 'yti_video');?>
								</span>
							</td>
						</tr>															
						
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yti_video'));?>	
			</div>
			<!-- /Tab content options -->
			
			<!-- Tab image options -->
			<div id="yti-settings-image-options">
				<table class="form-table">
					<tbody>
						<tr><th colspan="2"><h4><i class="dashicons dashicons-format-image"></i> <?php _e('Image settings', 'yti_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="featured_image"><?php _e('Import images', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="featured_image" id="featured_image"<?php yti_check($options['featured_image']);?> />
								<span class="description"><?php _e("YouTube video thumbnail will be set as post featured image.", 'yti_video');?></span>						
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="image_on_demand"><?php _e('Import featured image on request', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" name="image_on_demand" id="image_on_demand"<?php yti_check($options['image_on_demand']);?> />
								<span class="description"><?php _e("YouTube video thumbnail will be imported only when featured images needs to be displayed (ie. a post created by the plugin is displayed).", 'yti_video');?></span>
							</td>
						</tr>
										
						<tr valign="top">
							<th scope="row"><label for="image_size"><?php _e('Image size', 'yti_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											''				=> __('Choose', 'yti_video'),
											'default' 		=> __('Default (120x90 px)', 'yti_video'),
											'medium' 		=> __('Medium (320x180 px)', 'yti_video'),
											'high' 			=> __('High (480x360 px)', 'yti_video'),
											'standard'		=> __('Standard (640x480 px)', 'yti_video'),
											'maxres'		=> __('Maximum (1280x720 px)', 'yti_video'  )
										),
										'name' 		=> 'image_size',
										'selected' 	=> $options['image_size']								
									);
									yti_select($args);
								?>	
								( <input type="checkbox" value="1" name="maxres" id="maxres"<?php yti_check( $options['maxres'] );?> /> <label for="maxres"><?php _e('try to retrieve maximum resolution if available', 'yti_video');?></label> )					
							</td>
						</tr>									
						
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yti_video'));?>
			</div>
			<!-- /Tab image options -->
			
			<!-- Tab import options -->
			<div id="yti-settings-import-options">
				<table class="form-table">
					<tbody>
						<!-- Manual Import settings -->
						<tr><th colspan="2"><h4><i class="dashicons dashicons-download"></i> <?php _e('Bulk Import settings', 'yti_video');?></h4></th></tr>
						<tr valign="top">
							<th scope="row"><label for="import_status"><?php _e('Import status', 'yti_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'publish' 	=> __('Published', 'yti_video'),
											'draft' 	=> __('Draft', 'yti_video'),
											'pending'	=> __('Pending', 'yti_video')
										),
										'name' 		=> 'import_status',
										'selected' 	=> $options['import_status']
									);
									yti_select($args);
								?>
								<p class="description"><?php _e('Imported videos will have this status.', 'yti_video');?></p>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="import_frequency"><?php _e('Automatic import', 'yti_video')?>:</label></th>
							<td>
								<?php _e('Import ', 'yti_video');?>
								<?php 
									$args = array(
										'options' 	=> yti_automatic_update_batches(),
										'name'		=> 'import_quantity',
										'selected'	=> $options['import_quantity']
									);
									yti_select( $args );
								?>
								<?php _e('every', 'yti_video');?>
								<?php 
									$args = array(
										'options' => yti_automatic_update_timing(),
										'name' 		=> 'import_frequency',
										'selected' 	=> $options['import_frequency']
									);
									yti_select( $args );
								?>
								<p class="description"><?php _e('How often should YouTube be queried for playlist updates.', 'yti_video');?></p>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for=""><?php _e('Automatic import trigger', 'yti_video')?>:</label></th>
							<td>
								<p>
									<?php _e('By default, automatic imports are triggered by your website visitors when they view any of your website pages.', 'yti_video');?><br />
									<?php _e("If your website doesn't have enough traffic to consistently trigger the automatic imports we suggest that you set up a server Cron Job to open any of your pages at the given time interval.", 'yti_video');?><br />
									<?php _e("As an alternative to Cron Jobs, your could use the services of a Website Monitoring service that will access your website at a given time interval.", 'yti_video');?>
								</p>
							</td>
						</tr>
						
						<tr>	
							<th scope="row"><label for="manual_import_per_page"><?php _e('Manual import results per page', 'yti_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' 	=> yti_automatic_update_batches(),
										'name'		=> 'manual_import_per_page',
										'selected'	=> $options['manual_import_per_page']
									);
									yti_select( $args );
								?>
								<p class="description"><?php _e('How many results to display per page on manual import.', 'yti_video');?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yti_video'));?>
			</div>
			<!-- /Tab import options -->
			
			<!-- Tab embed options -->
			<div id="yti-settings-embed-options">
				<table class="form-table">
					<tbody>
						<tr>
							<th colspan="2">
								<h4><i class="dashicons dashicons-video-alt3"></i> <?php _e('Player settings', 'yti_video');?></h4>
								<p class="description"><?php _e('General YouTube player settings. These settings will be applied to any new video by default and can be changed individually for every imported video.', 'yti_video');?></p>
							</th>
						</tr>
						
						<tr>
							<th><label for="yti_aspect_ratio"><?php _e('Player size', 'yti_video');?>:</label></th>
							<td class="yti-player-settings-options">
								<label for="yti_aspect_ratio"><?php _e('Aspect ratio', 'yti_video');?>:</label>
								<?php 
									$args = array(
										'options' 	=> array(
											'4x3' 	=> '4x3',
											'16x9' 	=> '16x9'
										),
										'name' 		=> 'aspect_ratio',
										'id'		=> 'yti_aspect_ratio',
										'class'		=> 'yti_aspect_ratio',
										'selected' 	=> $player_opt['aspect_ratio']
									);
									yti_select( $args );
								?>
								<label for="yti_width"><?php _e('Width', 'yti_video');?>:</label>
								<input type="text" name="width" id="yti_width" class="yti_width" value="<?php echo $player_opt['width'];?>" size="2" />px
								| <?php _e('Height', 'yti_video');?> : <span class="yti_height" id="yti_calc_height"><?php echo yti_player_height( $player_opt['aspect_ratio'], $player_opt['width'] );?></span>px
							</td>
						</tr>
						
						<tr>
							<th><label for="yti_video_position"><?php _e('Show video in custom post','yti_video');?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'above-content' => __('Above post content', 'yti_video'),
											'below-content' => __('Below post content', 'yti_video')
										),
										'name' 		=> 'video_position',
										'id'		=> 'yti_video_position',
										'selected' 	=> $player_opt['video_position']
									);
									yti_select($args);
								?>
							</td>
						</tr>
						
						<tr>
							<th><label for="yti_volume"><?php _e('Volume', 'yti_video');?></label>:</th>
							<td>
								<input type="text" name="volume" id="yti_volume" value="<?php echo $player_opt['volume'];?>" size="1" maxlength="3" />
								<label for="yti_volume"><span class="description">( <?php _e('number between 0 (mute) and 100 (max)', 'yti_video');?> )</span></label>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="autoplay"><?php _e('Autoplay', 'yti_video')?>:</label></th>
							<td><input type="checkbox" value="1" id="autoplay" name="autoplay"<?php yti_check( (bool )$player_opt['autoplay'] );?> /></td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="yti_controls"><?php _e('Show player controls', 'yti_video')?>:</label></th>
							<td><input type="checkbox" value="1" id="yti_controls" class="yti_controls" name="controls"<?php yti_check( (bool)$player_opt['controls'] );?> /></td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yti_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="fs"><?php _e('Allow fullscreen', 'yti_video')?>:</label></th>
							<td><input type="checkbox" name="fs" id="fs" value="1"<?php yti_check( (bool)$player_opt['fs'] );?> /></td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yti_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="autohide"><?php _e('Autohide controls', 'yti_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'0' => __('Always show controls', 'yti_video'),
											'1' => __('Hide controls on load and when playing', 'yti_video'),
											'2' => __('Fade out progress bar when playing', 'yti_video')	
										),
										'name' => 'autohide',
										'selected' => $player_opt['autohide']
									);
									yti_select($args);
								?>
							</td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yti_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="theme"><?php _e('Player theme', 'yti_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'dark' => __('Dark', 'yti_video'),
											'light'=> __('Light', 'yti_video')
										),
										'name' => 'theme',
										'selected' => $player_opt['theme']
									);
									yti_select($args);
								?>
							</td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yti_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="color"><?php _e('Player color', 'yti_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'red' => __('Red', 'yti_video'),
											'white'=> __('White', 'yti_video')
										),
										'name' => 'color',
										'selected' => $player_opt['color']
									);
									yti_select($args);
								?>
							</td>
						</tr>
						
						<tr valign="top" class="controls_dependant"<?php yti_hide((bool)$player_opt['controls']);?>>
							<th scope="row"><label for="modestbranding"><?php _e('No YouTube logo on controls bar', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="modestbranding" name="modestbranding"<?php yti_check( (bool)$player_opt['modestbranding'] );?> />
								<span class="description"><?php _e('Setting the color parameter to white will cause this option to be ignored.', 'yti_video');?></span>
							</td>					
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="iv_load_policy"><?php _e('Annotations', 'yti_video')?>:</label></th>
							<td>
								<?php 
									$args = array(
										'options' => array(
											'1' => __('Show annotations by default', 'yti_video'),
											'3'=> __('Hide annotations', 'yti_video')
										),
										'name' => 'iv_load_policy',
										'selected' => $player_opt['iv_load_policy']
									);
									yti_select($args);
								?>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="rel"><?php _e('Show related videos', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="rel" name="rel"<?php yti_check( (bool)$player_opt['rel'] );?> />
								<label for="rel"><span class="description"><?php _e('when checked, after video ends player will display related videos', 'yti_video');?></span></label>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="showinfo"><?php _e('Show video title by default', 'yti_video')?>:</label></th>
							<td><input type="checkbox" value="1" id="showinfo" name="showinfo"<?php yti_check( (bool )$player_opt['showinfo']);?> /></td>
						</tr>
						
						<tr valign="top">
							<th scope="row"><label for="disablekb"><?php _e('Disable keyboard player controls', 'yti_video')?>:</label></th>
							<td>
								<input type="checkbox" value="1" id="disablekb" name="disablekb"<?php yti_check( (bool)$player_opt['disablekb'] );?> />
								<span class="description"><?php _e('Works only when player has focus.', 'yti_video');?></span>
								<p class="description"><?php _e('Controls:<br> - spacebar : play/pause,<br> - arrow left : jump back 10% in current video,<br> - arrow-right: jump ahead 10% in current video,<br> - arrow up - volume up,<br> - arrow down - volume down.', 'yti_video');?></p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yti_video'));?>
			</div>
			<!-- /Tab embed options -->
			
			<!-- Tab auth options -->
			<div id="yti-settings-auth-options">
				<table class="form-table">
					<tbody>
						<tr>
							<th colspan="2">
								<h4><i class="dashicons dashicons-admin-network"></i> <?php _e('YouTube API key', 'yti_video');?></h4>
							</th>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="youtube_api_key"><?php _e('Enter YouTube API server key', 'yti_video')?>:</label></th>
							<td>
								<input type="text" name="youtube_api_key" id="youtube_api_key" value="<?php echo $youtube_api_key;?>" size="60" />
								<p class="description">
									<?php if( !yti_get_yt_api_key('validity') ):?>
									<span style="color:red;"><?php _e('YouTube API key is invalid. All requests will stop unless a valid API key is provided. Please check the Google Console for the correct API key.', 'yti_video');?></span><br />
									<?php endif;?>
									<?php _e('To get your YouTube API key, visit this address:', 'yti_video');?> <a href="https://code.google.com/apis/console" target="_blank">https://code.google.com/apis/console</a>.<br />
									<?php _e('After signing in, visit <strong>Create a new project</strong> and enable <strong>YouTube Data API</strong>.', 'yti_video');?><br />
									<?php _e('To get your API key, visit <strong>APIs & auth</strong> and under <strong>Public API access</strong> create a new <strong>Server Key</strong>.', 'yti_video');?><br />
									<?php  printf( __('For more detailed informations please see <a href="%s" target="_blank">this tutorial</a>.', 'yti_video') , 'http://www.youtubeimporter.com/youtube-video-post-for-wordpress-how-to-get-your-youtube-api-key/' ); ?>
								</p>						
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button(__('Save settings', 'yti_video'));?>
			</div>
			<!-- /Tab auth options -->
		</div><!-- #yti_tabs -->		
	</form>
</div>