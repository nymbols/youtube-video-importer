	<p class="description">
		<?php _e('Import videos from YouTube.', 'yvi_video');?><br />
		<?php _e('Enter your search criteria and submit. All found videos will be displayed and you can selectively import videos into WordPress.', 'yvi_video');?>
	</p>
	
	<form method="get" action="" id="yvi_load_feed_form">
		<?php wp_nonce_field('yvi-video-import', 'yvi_search_nonce');?>
		<input type="hidden" name="post_type" value="<?php echo $this->post_type;?>" />
		<input type="hidden" name="page" value="yvi_import" />
		<input type="hidden" name="yvi_source" value="youtube" />
		<table class="form-table">
			<tr class="yvi_feed">
				<th valign="top">
					<label for="yvi_feed"><?php _e('Feed type', 'yvi_video');?>:</label>
				</th>
				<td>					
					<?php 
						$selected = isset( $_GET['yvi_feed'] ) ? $_GET['yvi_feed'] : 'query';
						$args = array(
							'options' => array(
								'user' 		=> __('User feed', 'yvi_video'),
								'playlist'	=> __('Playlist feed', 'yvi_video'),
								'channel' 	=> __('Channel uploads feed', 'yvi_video'),
								'query' 	=> __('Search query feed', 'yvi_video')
							),
							'name' 	=> 'yvi_feed',
							'id' 	=> 'yvi_feed',
							'selected' => $selected
						);							
						yvi_select( $args );
					?>
					<span class="description"><?php _e('Select the type of feed you want to load.', 'yvi_video');?></span>									
				</td>
			</tr>
			
			<tr class="yvi_duration">
				<th valign="top"><label for="yvi_duration"><?php _e('Video duration', 'yvi_video');?>:</label></th>
				<td>
					<?php 
						$selected = isset( $_GET['yvi_duration'] ) ? $_GET['yvi_duration'] : '';
						$args = array(
							'options' => array(
								'any' 		=> __('Any', 'yvi_video'),
								'short' 	=> __('Short (under 4min.)', 'yvi_video'),
								'medium' 	=> __('Medium (between 4 and 20min.)', 'yvi_video'),
								'long' 		=> __('Long (over 20min.)', 'yvi_video')
							),
							'name' 		=> 'yvi_duration',
							'id' 		=> 'yvi_duration',
							'selected'	=> $selected
						);
						yvi_select( $args );
					?>		
				</td>
			</tr>
			
			<tr class="yvi_query">
				<th valign="top">
					<label for="yvi_query"><?php _e('Search by', 'yvi_video');?>:</label>
				</th>
				<td>
					<?php $query = isset( $_GET['yvi_query'] ) ? sanitize_text_field( $_GET['yvi_query'] ) : '';?>
					<input type="text" name="yvi_query" id="yvi_query" value="<?php echo $query;?>" size="50" />
					<span class="description"><?php _e('Enter playlist ID, user ID or search query according to Feed Type selection.', 'yvi_video');?></span>
				</td>
			</tr>
			
			<tr class="yvi_order">
				<th valign="top"><label for="yvi_order"><?php _e('Order by', 'yvi_video');?>:</label></th>
				<td>
					<?php 
						$selected = isset( $_GET['yvi_order'] ) ? $_GET['yvi_order'] : false;
						$args = array(
							'options' => array(
								'date' 		=> __('Date of publishing', 'yvi_video'),
								'rating' 	=> __('Rating', 'yvi_video'),
								'relevance' => __('Search relevance', 'yvi_video'),
								'title' 	=> __('Video title', 'yvi_video'),
								'viewCount' => __('Number of views', 'yvi_video')
 							),
 							'name' 		=> 'yvi_order',
 							'id' 		=> 'yvi_order',
 							'selected' 	=> $selected
						);
						yvi_select( $args );
					?>
				</td>
			</tr>
			
			<?php
				$theme_support =  yvi_check_theme_support();
				if( $theme_support ):
			?>
			<tr>
				<th valign="top">
					<label for="yvi_theme_import"><?php printf( __('Import as posts <br />compatible with <strong>%s</strong>?', 'yvi_video'), $theme_support['theme_name']);?></label>
				</th>
				<td>
					<?php $checked = isset( $_GET['yvi_theme_import'] ) ? ' checked="checked"' : '';?>
					<input type="checkbox" name="yvi_theme_import" id="yvi_theme_import" value="1"<?php echo $checked?> />
					<span class="description">
						<?php printf( __('If you choose to import as %s posts, all videos will be imported as post type <strong>%s</strong> and will be visible in your blog categories.', 'yvi_video'), $theme_support['theme_name'], $theme_support['post_type']);?>
					</span>
				</td>
			</tr>
			<?php 
				endif
			?>
			
			<!-- 
			<tr>
				<th valign="top"><label for=""></label></th>
				<td></td>
			</tr>
			-->			
		</table>
		<?php submit_button( __('Load feed', 'yvi_video'));?>
	</form>