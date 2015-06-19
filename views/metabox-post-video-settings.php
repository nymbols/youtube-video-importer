<?php wp_nonce_field('yti-save-video-settings', 'yti-video-nonce');?>
<table class="form-table yti-player-settings-options">
	<tbody>
		<tr>
			<th><label for="yti_aspect_ratio"><?php _e('Player size', 'yti_video');?>:</label></th>
			<td>
				<label for="yti_aspect_ratio"><?php _e('Aspect ratio');?> :</label>
				<?php 
					$args = array(
						'options' 	=> array(
							'4x3' 	=> '4x3',
							'16x9' 	=> '16x9'
						),
						'name' 		=> 'aspect_ratio',
						'id'		=> 'yti_aspect_ratio',
						'class'		=> 'yti_aspect_ratio',
						'selected' 	=> $settings['aspect_ratio']
					);
					yti_select( $args );
				?>
				<label for="yti_width"><?php _e('Width', 'yti_video');?>:</label>
				<input type="text" name="width" id="yti_width" class="yti_width" value="<?php echo $settings['width'];?>" size="2" />px
				| <?php _e('Height', 'yti_video');?> : <span class="yti_height" id="yti_calc_height"><?php echo yti_player_height( $settings['aspect_ratio'], $settings['width'] );?></span>px
			</td>
		</tr>
				
		<tr>
			<th><label for="yti_video_position"><?php _e('Display video in custom post','yti_video');?>:</label></th>
			<td>
				<?php 
					$args = array(
						'options' => array(
							'above-content' => __('Above post content', 'yti_video'),
							'below-content' => __('Below post content', 'yti_video')
						),
						'name' 		=> 'video_position',
						'id'		=> 'yti_video_position',
						'selected' 	=> $settings['video_position']
					);
					yti_select($args);
				?>
			</td>
		</tr>
		<tr>
			<th><label for="yti_volume"><?php _e('Volume', 'yti_video');?>:</label></th>
			<td>
				<input type="text" name="volume" id="yti_volume" value="<?php echo $settings['volume'];?>" size="1" maxlength="3" />
				<label for="yti_volume"><span class="description">( <?php _e('number between 0 (mute) and 100 (max)', 'yti_video');?> )</span></label>
			</td>
		</tr>
		<tr>
			<th><label for="yti_autoplay"><?php _e('Autoplay', 'yti_video');?>:</label></th>
			<td>
				<input name="autoplay" id="yti_autoplay" type="checkbox" value="1"<?php yti_check((bool)$settings['autoplay']);?> />
				<label for="yti_autoplay"><span class="description">( <?php _e('when checked, video will start playing once page is loaded', 'yti_video');?> )</span></label>
			</td>
		</tr>
		
		<tr>
			<th><label for="yti_controls"><?php _e('Show controls', 'yti_video');?>:</label></th>
			<td>
				<input name="controls" id="yti_controls" class="yti_controls" type="checkbox" value="1"<?php yti_check((bool)$settings['controls']);?> />
				<label for="yti_controls"><span class="description">( <?php _e('when checked, player will display video controls', 'yti_video');?> )</span></label>
			</td>
		</tr>
		
		<tr class="controls_dependant"<?php yti_hide((bool)$settings['controls']);?>>
			<th><label for="yti_fs"><?php _e('Allow full screen', 'yti_video');?>:</label></th>
			<td>
				<input name="fs" id="yti_fs" type="checkbox" value="1"<?php yti_check((bool)$settings['fs']);?> />
			</td>
		</tr>
		
		<tr class="controls_dependant"<?php yti_hide((bool)$settings['controls']);?>>
			<th><label for="yti_autohide"><?php _e('Autohide controls');?>:</label></th>
			<td>
				<?php 
					$args = array(
						'options' => array(
							'0' => __('Always show controls', 'yti_video'),
							'1' => __('Hide controls on load and when playing', 'yti_video'),
							'2' => __('Hide controls when playing', 'yti_video')
						),
						'name' => 'autohide',
						'id' => 'yti_autohide',
						'selected' => $settings['autohide']
					);
					yti_select($args);
				?>
			</td>
		</tr>
		
		<tr class="controls_dependant"<?php yti_hide((bool)$settings['controls']);?>>
			<th><label for="yti_theme"><?php _e('Player theme', 'yti_video');?>:</label></th>
			<td>
				<?php 
					$args = array(
						'options' => array(
							'dark' => __('Dark', 'yti_video'),
							'light' => __('Light', 'yti_video')
						),
						'name' => 'theme',
						'id' => 'yti_theme',
						'selected' => $settings['theme']
					);
					yti_select($args);
				?>
			</td>
		</tr>
		
		<tr class="controls_dependant"<?php yti_hide((bool)$settings['controls']);?>>
			<th><label for="yti_color"><?php _e('Player color', 'yti_video');?>:</label></th>
			<td>
				<?php 
					$args = array(
						'options' => array(
							'red' => __('Red', 'yti_video'),
							'white' => __('White', 'yti_video')
						),
						'name' => 'color',
						'id' => 'yti_color',
						'selected' => $settings['color']
					);
					yti_select($args);
				?>
			</td>
		</tr>
		
		<tr class="controls_dependant" valign="top"<?php yti_hide($settings['controls']);?>>
			<th scope="row"><label for="modestbranding"><?php _e('No YouTube logo on controls bar', 'yti_video')?>:</label></th>
			<td>
				<input type="checkbox" value="1" id="modestbranding" name="modestbranding"<?php yti_check( (bool)$settings['modestbranding'] );?> />
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
						'selected' => $settings['iv_load_policy']
					);
					yti_select($args);
				?>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="rel"><?php _e('Show related videos', 'yti_video')?>:</label></th>
			<td>
				<input type="checkbox" value="1" id="rel" name="rel"<?php yti_check( (bool)$settings['rel'] );?> />
				<label for="rel"><span class="description"><?php _e('when checked, after video ends player will display related videos', 'yti_video');?></span></label>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="showinfo"><?php _e('Show video title in player', 'yti_video')?>:</label></th>
			<td><input type="checkbox" value="1" id="showinfo" name="showinfo"<?php yti_check( (bool )$settings['showinfo']);?> /></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="disablekb"><?php _e('Disable keyboard player controls', 'yti_video')?>:</label></th>
			<td>
				<input type="checkbox" value="1" id="disablekb" name="disablekb"<?php yti_check( (bool)$settings['disablekb'] );?> />
				<span class="description"><?php _e('Works only when player has focus.', 'yti_video');?></span>
			</td>
		</tr>	
		
	</tbody>
</table>