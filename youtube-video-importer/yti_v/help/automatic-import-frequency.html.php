<p>
	<?php _e('Import frequency is basically how often YouTube will be queried for new videos that might have been published into a playlist.', 'yvi_video');?><br />
	<?php _e('To change the import frequency just visit Settings page in plugin menu and modify the option <em>Automatic import</em>.', 'yvi_video');?>	
</p>
<p>
	<?php _e('Please note that only one playlist is update with each query made to YouTube.', 'yvi_video');?><br />
	<?php _e('This means that for each period of time set under Automatic import the number of videos set in Settings page will be retrieved from the playlist that comes next in line.', 'yvi_video');?><br />
	<?php _e("Also, make sure you don't exceed 50 videos per query since this is a limitation set by YouTube.", 'yvi_video');?>
</p>
<p>
	<?php _e("Please note that if videos from a playlist are already imported the plugin won't create double posts. All duplicates will be skipped but the import count for that playlist will get updated.", 'yvi_video' );?>
</p>