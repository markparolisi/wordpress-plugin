<div class="poet-badge">
	<!--   @TODO add link to po.et Work verification with target="_blank"   -->
	<div class="poet-badge__container">
		<img class="poet-badge__container__image" src="<?php echo esc_url( $quill_image_url ); ?>" height="31" width="31" rel="poet-badge" title="poet-badge">
		<div class="poet-badge__container__text">
			<p class="poet-badge__container__text__verified" title="<?php echo esc_attr( $work_id ); ?>">
				Verified on Po.et</p>
			<p class="poet-badge__container__text__date">
				<?php echo esc_attr( $post_publication_date ); ?></p>
		</div>
	</div>
</div>
