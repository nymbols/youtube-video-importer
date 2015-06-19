	<p class="description">
		<?php _e('Import videos from YouTube.', 'yti_video');?><br />
		<?php _e('Enter your search criteria and submit. All found videos will be displayed and you can selectively import videos into WordPress.', 'yti_video');?>
	</p>
	
	<form method="get" action="" id="yti_load_feed_form">
		<?php wp_nonce_field('yti-video-import', 'yti_search_nonce');?>
		<input type="hidden" name="post_type" value="<?php echo $this->post_type;?>" />
		<input type="hidden" name="page" value="yti_import" />
		<input type="hidden" name="yti_source" value="youtube" />
		<table class="form-table">
			<tr class="yti_feed">
				<th valign="top">
					<label for="yti_feed"><?php _e('Feed type', 'yti_video');?>:</label>
				</th>
				<td>					
					<?php 
						$selected = isset( $_GET['yti_feed'] ) ? $_GET['yti_feed'] : 'query';
						$args = array(
							'options' => array(
								'user' 		=> __('User feed', 'yti_video'),
								'playlist'	=> __('Playlist feed', 'yti_video'),
								'channel' 	=> __('Channel uploads feed', 'yti_video'),
								'query' 	=> __('Search query feed', 'yti_video')
							),
							'name' 	=> 'yti_feed',
							'id' 	=> 'yti_feed',
							'selected' => $selected
						);							
						yti_select( $args );
					?>
					<span class="description"><?php _e('Select the type of feed you want to load.', 'yti_video');?></span>									
				</td>
			</tr>
			
			<tr class="yti_duration">
				<th valign="top"><label for="yti_duration"><?php _e('Video duration', 'yti_video');?>:</label></th>
				<td>
					<?php 
						$selected = isset( $_GET['yti_duration'] ) ? $_GET['yti_duration'] : '';
						$args = array(
							'options' => array(
								'any' 		=> __('Any', 'yti_video'),
								'short' 	=> __('Short (under 4min.)', 'yti_video'),
								'medium' 	=> __('Medium (between 4 and 20min.)', 'yti_video'),
								'long' 		=> __('Long (over 20min.)', 'yti_video')
							),
							'name' 		=> 'yti_duration',
							'id' 		=> 'yti_duration',
							'selected'	=> $selected
						);
						yti_select( $args );
					?>		
				</td>
			</tr>
			
			<tr class="yti_query">
				<th valign="top">
					<label for="yti_query"><?php _e('Search by', 'yti_video');?>:</label>
				</th>
				<td>
					<?php $query = isset( $_GET['yti_query'] ) ? sanitize_text_field( $_GET['yti_query'] ) : '';?>
					<input type="text" name="yti_query" id="yti_query" value="<?php echo $query;?>" size="50" />
					<span class="description"><?php _e('Enter playlist ID, user ID or search query according to Feed Type selection.', 'yti_video');?></span>
				</td>
			</tr>
			
			<tr class="yti_order">
				<th valign="top"><label for="yti_order"><?php _e('Order by', 'yti_video');?>:</label></th>
				<td>
					<?php 
						$selected = isset( $_GET['yti_order'] ) ? $_GET['yti_order'] : false;
						$args = array(
							'options' => array(
								'date' 		=> __('Date of publishing', 'yti_video'),
								'rating' 	=> __('Rating', 'yti_video'),
								'relevance' => __('Search relevance', 'yti_video'),
								'title' 	=> __('Video title', 'yti_video'),
								'viewCount' => __('Number of views', 'yti_video')
 							),
 							'name' 		=> 'yti_order',
 							'id' 		=> 'yti_order',
 							'selected' 	=> $selected
						);
						yti_select( $args );
					?>
				</td>
			</tr>
			
			<?php
				$theme_support =  yti_check_theme_support();
				if( $theme_support ):
			?>
			<tr>
				<th valign="top">
					<label for="yti_theme_import"><?php printf( __('Import as posts <br />compatible with <strong>%s</strong>?', 'yti_video'), $theme_support['theme_name']);?></label>
				</th>
				<td>
					<?php $checked = isset( $_GET['yti_theme_import'] ) ? ' checked="checked"' : '';?>
					<input type="checkbox" name="yti_theme_import" id="yti_theme_import" value="1"<?php echo $checked?> />
					<span class="description">
						<?php printf( __('If you choose to import as %s posts, all videos will be imported as post type <strong>%s</strong> and will be visible in your blog categories.', 'yti_video'), $theme_support['theme_name'], $theme_support['post_type']);?>
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
		<?php submit_button( __('Load feed', 'yti_video'));?>
	</form>