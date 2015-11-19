<?php
	$normal_style = $hover_style = '';

	$id = 'wpv-expandable-'.md5( uniqid() );

	$readable = WpvTemplates::readable_color_mixin();

	if( ! empty( $background_color ) || ! empty( $background_image ) ) {
		$l = new WpvLessc();

		$l->importDir = '.';
		$l->setFormatter( "compressed" );

		$background_color = wpv_sanitize_accent( $background_color );

		if ( ! empty( $background_image ) ) {
			$background_image = "
				background: url( '$background_image' ) $background_repeat $background_position $background_attachment;
				background-size: $background_size;
			";
		}

		if ( empty( $background_color ) ) {
			$background_color = 'transparent';
		}

		$text_color = '';
		if ( $background_color !== 'transparent' ) {
			$text_color = "
				&,
				p,
				.sep-text h2.regular-title-wrapper,
				.text-divider-double,
				.sep-text .sep-text-line,
				.sep,
				.sep-2,
				.sep-3,
				h1, h2, h3, h4, h5, h6,
				td,
				th,
				caption {
					.readable-color( $background_color );
				}
			";
		}

		$normal_style = $l->compile( $readable . "
			#{$id} .closed {
				$background_image
				background-color: $background_color;

				$text_color
			}
		" );
	}

	if( ! empty( $hover_background ) && $hover_background !== 'transparent' ) {
		$l = new WpvLessc();
		$l->importDir = '.';
		$l->setFormatter( "compressed" );

		$hover_background = wpv_sanitize_accent( $hover_background );

		$hover_style = $l->compile( $readable . "
			#{$id} .open {
				background: $hover_background;

				&,
				p,
				.sep-text h2.regular-title-wrapper,
				.text-divider-double,
				.sep-text .sep-text-line,
				.sep,
				.sep-2,
				.sep-3,
				h1, h2, h3, h4, h5, h6,
				td,
				th,
				caption {
					.readable-color( $hover_background );
				}
			}
		" );
	}
?>
<div class="services has-more <?php echo $class?>" id="<?php echo $id ?>">
	<div class="closed services-inside">
		<div class="services-content-wrapper clearfix">
			<?php if ( ! empty( $image ) ): ?>
				<div class="image-wrapper">
					<?php wpv_lazy_load( $image, '', array( 'class'=> 'aligncenter' ) ) ?>
				</div>
			<?php elseif( ! empty( $icon ) ): ?>
				<div class="image-wrapper"><?php
					echo wpv_shortcode_icon( array(
						'name' => $icon,
						'size' => $icon_size,
						'color' => wpv_sanitize_accent( $icon_color ),
					 ) );
				?></div>
			<?php endif ?>

			<?php if ( ! empty( $title ) ): ?>
				<h3 class="title"><?php echo $title ?></h3><br>
			<?php endif ?>
			<?php echo do_shortcode( $before ) ?>

		</div>
	</div>
	<?php if ( ! empty( $content ) ) : ?>
		<div class="open services-inside">
			<div class="services-content-wrapper">
				<div class="row">
					<?php echo do_shortcode( $content )?>
				</div>
			</div>
		</div>
	<?php endif ?>
</div>
<style scoped><?php echo $normal_style . $hover_style ?></style>
