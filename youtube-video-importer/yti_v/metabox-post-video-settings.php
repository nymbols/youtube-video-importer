<?php wp_nonce_field('yvi-save-video-settings', 'yvi-video-nonce');?>
<table class="form-table yvi-player-settings-options">
	<tbody>
		<tr>
			<th><label for="yvi_aspect_ratio"><?php _e('Player size', 'yvi_video');?>:</label></th>
			<td>
				<label for="yvi_aspect_ratio"><?php _e('Aspect ratio');?> :</label>
				<?php 
					$args = array(
						'options' 	=> array(
							'4x3' 	=> '4x3',
							'16x9' 	=> '16x9'
						),
						'name' 		=> 'aspect_ratio',
						'id'		=> 'yvi_aspect_ratio',
						'class'		=> 'yvi_aspect_ratio',
						'selected' 	=> $settings['aspect_ratio']
					);
					yvi_select( $args );
				?>
				<label for="yvi_width"><?php _e('Width', 'yvi_video');?>:</label>
				<input type="text" name="width" id="yvi_width" class="yvi_width" value="<?php echo $settings['width'];?>" size="2" />px
				| <?php _e('Height', 'yvi_video');?> : <span class="yvi_height" id="yvi_calc_height"><?php echo yvi_player_height( $settings['aspect_ratio'], $settings['width'] );?></span>px
			</td>
		</tr>
				
		<tr>
			<th><label for="yvi_video_position"><?php _e('Display video in custom post','yvi_video');?>:</label></th>
			<td>
				<?php 
					$args = array(
						'options' => array(
							'above-content' => __('Above post content', 'yvi_video'),
							'below-content' => __('Below post content', 'yvi_video')
						),
						'name' 		=> 'video_position',
						'id'		=> 'yvi_video_position',
						'selected' 	=> $settings['video_position']
					);
					yvi_select($args);
				?>
			</td>
		</tr>
		<tr>
			<th><label for="yvi_volume"><?php _e('Volume', 'yvi_video');?>:</label></th>
			<td>
				<input type="text" name="volume" id="yvi_volume" value="<?php echo $settings['volume'];?>" size="1" maxlength="3" />
				<label for="yvi_volume"><span class="description">( <?php _e('number between 0 (mute) and 100 (max)', 'yvi_video');?> )</span></label>
			</td>
		</tr>
		<tr>
			<th><label for="yvi_autoplay"><?php _e('Autoplay', 'yvi_video');?>:</label></th>
			<td>
				<input name="autoplay" id="yvi_autoplay" type="checkbox" value="1"<?php yvi_check((bool)$settings['autoplay']);?> />
				<label for="yvi_autoplay"><span class="description">( <?php _e('when checked, video will start playing once page is loaded', 'yvi_video');?> )</span></label>
			</td>
		</tr>
		
		<tr>
			<th><label for="yvi_controls"><?php _e('Show controls', 'yvi_video');?>:</label></th>
			<td>
				<input name="controls" id="yvi_controls" class="yvi_controls" type="checkbox" value="1"<?php yvi_check((bool)$settings['controls']);?> />
				<label for="yvi_controls"><span class="description">( <?php _e('when checked, player will display video controls', 'yvi_video');?> )</span></label>
			</td>
		</tr>
		
		<tr class="controls_dependant"<?php yvi_hide((bool)$settings['controls']);?>>
			<th><label for="yvi_fs"><?php _e('Allow full screen', 'yvi_video');?>:</label></th>
			<td>
				<input name="fs" id="yvi_fs" type="checkbox" value="1"<?php yvi_check((bool)$settings['fs']);?> />
			</td>
		</tr>
		
		<tr class="controls_dependant"<?php yvi_hide((bool)$settings['controls']);?>>
			<th><label for="yvi_autohide"><?php _e('Autohide controls');?>:</label></th>
			<td>
				<?php 
					$args = array(
						'options' => array(
							'0' => __('Always show controls', 'yvi_video'),
							'1' => __('Hide controls on load and when playing', 'yvi_video'),
							'2' => __('Hide controls when playing', 'yvi_video')
						),
						'name' => 'autohide',
						'id' => 'yvi_autohide',
						'selected' => $settings['autohide']
					);
					yvi_select($args);
				?>
			</td>
		</tr>
		
		<tr class="controls_dependant"<?php yvi_hide((bool)$settings['controls']);?>>
			<th><label for="yvi_theme"><?php _e('Player theme', 'yvi_video');?>:</label></th>
			<td>
				<?php 
					$args = array(
						'options' => array(
							'dark' => __('Dark', 'yvi_video'),
							'light' => __('Light', 'yvi_video')
						),
						'name' => 'theme',
						'id' => 'yvi_theme',
						'selected' => $settings['theme']
					);
					yvi_select($args);
				?>
			</td>
		</tr>
		
		<tr class="controls_dependant"<?php yvi_hide((bool)$settings['controls']);?>>
			<th><label for="yvi_color"><?php _e('Player color', 'yvi_video');?>:</label></th>
			<td>
				<?php 
					$args = array(
						'options' => array(
							'red' => __('Red', 'yvi_video'),
							'white' => __('White', 'yvi_video')
						),
						'name' => 'color',
						'id' => 'yvi_color',
						'selected' => $settings['color']
					);
					yvi_select($args);
				?>
			</td>
		</tr>
		
		<tr class="controls_dependant" valign="top"<?php yvi_hide($settings['controls']);?>>
			<th scope="row"><label for="modestbranding"><?php _e('No YouTube logo on controls bar', 'yvi_video')?>:</label></th>
			<td>
				<input type="checkbox" value="1" id="modestbranding" name="modestbranding"<?php yvi_check( (bool)$settings['modestbranding'] );?> />
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
						'selected' => $settings['iv_load_policy']
					);
					yvi_select($args);
				?>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="rel"><?php _e('Show related videos', 'yvi_video')?>:</label></th>
			<td>
				<input type="checkbox" value="1" id="rel" name="rel"<?php yvi_check( (bool)$settings['rel'] );?> />
				<label for="rel"><span class="description"><?php _e('when checked, after video ends player will display related videos', 'yvi_video');?></span></label>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="showinfo"><?php _e('Show video title in player', 'yvi_video')?>:</label></th>
			<td><input type="checkbox" value="1" id="showinfo" name="showinfo"<?php yvi_check( (bool )$settings['showinfo']);?> /></td>
		</tr>
		
		<tr valign="top">
			<th scope="row"><label for="disablekb"><?php _e('Disable keyboard player controls', 'yvi_video')?>:</label></th>
			<td>
				<input type="checkbox" value="1" id="disablekb" name="disablekb"<?php yvi_check( (bool)$settings['disablekb'] );?> />
				<span class="description"><?php _e('Works only when player has focus.', 'yvi_video');?></span>
			</td>
		</tr>	
		
	</tbody>
</table>