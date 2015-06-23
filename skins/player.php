<div class="yvi-yt-playlist default"<?php yvi_output_width();?>>
	<div class="yvi-player"<?php yvi_output_player_size();?> <?php yvi_output_player_data();?>></div>
	<div class="yvi-playlist-wrap">
		<div class="yvi-playlist">
			<?php foreach( $videos as $yvi_video ): ?>
			<div class="yvi-playlist-item">
				<a href="<?php yvi_video_post_permalink();?>"<?php yvi_output_video_data();?>>
					<?php yvi_output_thumbnail();?>
					<?php yvi_output_title();?>
				</a>
			</div>
			<?php endforeach;?>
		</div>
		<a href="#" class="playlist-visibility collapse"></a>
	</div>	
</div>