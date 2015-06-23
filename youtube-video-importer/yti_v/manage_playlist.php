<div class="wrap">
	<div class="icon32 icon32-posts-video" id="icon-edit"><br></div>
	<h2>
		<?php echo $title;?>
		<?php if( isset($add_new_link) ) echo $add_new_link;?>
		<a class="add-new-h2" href="<?php menu_page_url('yvi_auto_import');?>"><?php _e('Cancel', 'yvi_video');?></a>	
	</h2>
		
	<form method="post" action="<?php echo $form_action;?>">
		<?php if( isset($error) ):?>
		<?php echo $error;?>
		<div id="message" class="error">
			<p><?php echo $error;?></p>
		</div>
		<?php endif;?>
		<?php wp_nonce_field('yvi-save-playlist', 'yvi_wp_nonce');?>
		
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row"><label for="post_title">*<?php _e('Playlist name', 'yvi_video');?>:</label></th>
					<td>
						<input type="text" name="post_title" id="post_title" value="<?php echo $options['post_title'];?>" />
						<span class="description"><?php _e('A name for your internal reference.', 'yvi_video');?></span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="playlist_type">*<?php _e('Feed type', 'yvi_video')?>:</label></th>
					<td>
						<?php 
							$args = array(
								'options' => array(
									'user' 		=> __('User playlist', 'yvi_video'),
									'channel'	=> __('YouTube channel', 'yvi_video'),
									'playlist' 	=> __('YouTube playlist', 'yvi_video')									
								),
								'name' => 'playlist_type',
								'selected' => $options['playlist_type']
							);						
							yvi_select($args);
						?>
						<span class="description"><?php _e('Choose the kind of playlist you want to import.', 'yvi_video');?></span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="playlist_id">*<?php _e('Playlist ID', 'yvi_video');?>:</label></th>
					<td>
						<input type="text" name="playlist_id" id="playlist_id" value="<?php echo $options['playlist_id'];?>" />
						<a href="#" id="yvi_verify_playlist" class="button"><?php _e('Check playlist', 'yvi_video');?></a>
						<div id="yvi_check_playlist" class="description"><?php _e('Enter playlist ID or user ID according to Feed Type selection.', 'yvi_video');?></div>
						
					</td>
				</tr>
				
			<?php 
				// users dropdown
				$users = wp_dropdown_users(array(
					'show_option_all' 			=> __('Current user', 'yvi_video'),
					'echo'						=> false,
					'name'						=> 'import_user',
					'id'						=> 'yvi_video_user',
					'hide_if_only_one_author' 	=> true,
					'selected'					=> $options['import_user']
				));
				if( $users ):
			?>
				<tr valign="top">
					<th scope="row"><label for="yvi_video_user"><?php _e('Import as user', 'yvi_video');?>:</label></th>
					<td>
						<?php echo $users;?>
						<span class="description"><?php _e('Video posts will be created as written by the selected user.', 'yvi_video');?></span>					
					</td>
				</tr>
			<?php endif;// end users dropdown?>
				
				<?php 
					$hidden = $options['playlist_type'] == 'user' || $options['playlist_type'] == 'channel';
				?>
				<tr valign="top" id="publish-date-filter"<?php yvi_hide( $hidden, false );?>>
					<th scope="row"><label for="start_date"><?php _e('Import if published after', 'yvi_video');?>:</label></th>
					<td>
						<input type="text" id="start_date" name="start_date" value="<?php echo $options['start_date'];?>"/>		
						<script>
						jQuery(document).ready(function() {
						    jQuery('#start_date').datepicker({
						        dateFormat : 'M d yy'
						    });
						});
						</script>
						<span class="description"><?php _e('If a date is specified, only videos published after this date will be imported.', 'yvi_video');?></span>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><label for="playlist_live"><?php _e('Add to import queue?', 'yvi_video');?></label></th>
					<td>
						<input type="checkbox" name="playlist_live" id="playlist_live" value="1"<?php yvi_check( $options['playlist_live'] );?> />
						<span class="description"><?php _e('If checked, playlist will be added to importing queue and will import when its turn comes.', 'yvi_video');?></span>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><label for="no_reiterate"><?php _e('When finished, import only new videos', 'yvi_video');?> :</label></th>
					<td>
						<input type="checkbox" name="no_reiterate" id="no_reiterate" value="1"<?php yvi_check( $options['no_reiterate'] );?> />
						<span class="description"><?php _e("After finishing to import all videos in playlist the plugin will check only for new videos.", 'yvi_video');?></span>
						<?php 
							$hide = !('playlist' == $options['playlist_type'] && $options['no_reiterate']);
						?>
						<div id="playlist-alert" class="warning" <?php yvi_hide( $hide, true );?>>
							<?php _e( 'Please make sure that the playlist is ordered on YouTube by <strong>Date added(newest - oldest)</strong>', 'yvi_video' );?><br />
							<?php _e( "If you're not sure how the playlist is ordered you should uncheck the option to import new videos after playlist finished importing.", 'yvi_video' );?>
						</div>
					</td>
				</tr>
				
				<?php 
					global $YVI_POST_TYPE;
					$args = array(
						'show_count' 		=> 1,
			    		'hide_empty'		=> 0,
						'taxonomy' 			=> $YVI_POST_TYPE->get_post_tax(),
						'name'				=> 'native_tax',
						'id'				=> 'native_tax',
						'selected'			=> $options['native_tax'],
			    		'hide_if_empty' 	=> true,
			    		'echo'				=> false
					);
					$plugin_options = yvi_get_settings();
					if( isset( $plugin_options ) && $plugin_options['import_categories'] ){
						$args['show_option_all'] = __('Create categories from YouTube', 'yvi_video');
					}else{
						$args['show_option_all'] = __('Select category (optional)', 'yvi_video');						
					}
					
					// if set to import as regular post, change taxonomy to category
					if( isset( $plugin_options['post_type_post'] ) && $plugin_options['post_type_post'] ){
						$args['taxonomy'] = 'category';
					}
					
					$plugin_categories = wp_dropdown_categories($args);
					if( $plugin_categories ):						
						$hidden = $options['theme_import'] && yvi_check_theme_support();					
				?>
				<tr valign="top" id="native_tax_row"<?php yvi_hide( $hidden, true );?>>
					<th scope="row"><label for="native_tax"><?php _e('Import in category', 'yvi_video');?>:</label></th>
					<td>
						<?php echo $plugin_categories;?>
						<span class="description"><?php _e('Select category for all videos imported from this playlist.', 'yvi_video');?></span>
					</td>
				</tr>
				<?php endif;?>
				
				
				<?php
				$theme_support =  yvi_check_theme_support();
				if( $theme_support ):
				?>
				<tr>
					<th valign="top">
						<label for="theme_import"><?php printf( __('Import as post compatible with <em>%s</em>?', 'yvi_video'), $theme_support['theme_name']);?></label>
					</th>
					<td>
						<input type="checkbox" name="theme_import" id="theme_import" value="1"<?php yvi_check($options['theme_import']);?> />
						<span class="description">
							<?php printf( __('If you choose to import in %s, all videos will be imported as post type <strong>%s</strong> and will be visible in your blog categories.', 'yvi_video'), $theme_support['theme_name'], $theme_support['post_type']);?>
						</span>
					</td>
				</tr>				
				<?php 
					$args = array(
						'show_count' 		=> 1,
			    		'hide_empty'		=> 0,
						'name'				=> 'theme_tax',
						'id'				=> 'theme_tax',
						'selected'			=> $options['theme_tax'],
			    		'hide_if_empty' 	=> true,
			    		'echo'				=> false
					);
					if( !$theme_support['taxonomy'] && 'post' == $theme_support['post_type']  ){
						$args['taxonomy'] = 'category';
					}else{
						$args['taxonomy'] = $theme_support['taxonomy'];
					}
										
					$plugin_options = yvi_get_settings();
					if( isset( $plugin_options ) && $plugin_options['import_categories'] ){
						$args['show_option_all'] = __('Create categories from YouTube', 'yvi_video');
					}else{
						$args['show_option_all'] = __('Select category (optional)', 'yvi_video');						
					}
					$plugin_categories = wp_dropdown_categories($args);
					if( $plugin_categories ):
				?>
				<tr valign="top" id="theme_tax_row"<?php yvi_hide( $options['theme_import'], false );?>>
					<th scope="row"><label for="theme_tax"><?php printf( __('Import in <strong>%s</strong> category', 'yvi_video'), $theme_support['theme_name']);?>:</label></th>
					<td>
						<?php echo $plugin_categories;?>
						<span class="description"><?php _e('Select category for all videos imported from this playlist as theme posts.', 'yvi_video');?></span>
					</td>
				</tr>
				<?php endif;?>
				
				
				<?php 
					endif
				?>
				<!-- 
				<tr valign="top">
					<th scope="row"><label for=""></label></th>
					<td>
					</td>
				</tr>
				-->				
			</tbody>
		</table>
		<?php submit_button( __('Save', 'yvi_video'));?>	
	</form>	
		
</div>