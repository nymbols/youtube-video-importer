<div class="yti-yt-playlist default"<?php yti_output_width();?>>
	<div class="yti-player"<?php yti_output_player_size();?> <?php yti_output_player_data();?>></div>
	<div class="yti-playlist-wrap">
		<div class="yti-playlist">
			<?php foreach( $videos as $yti_video ): ?>
			<div class="yti-playlist-item">
				<a href="<?php yti_video_post_permalink();?>"<?php yti_output_video_data();?>>
					<?php yti_output_thumbnail();?>
					<?php yti_output_title();?>
				</a>
			</div>
			<?php endforeach;?>
		</div>
		<a href="#" class="playlist-visibility collapse"></a>
	</div>	
</div>