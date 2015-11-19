<?php

$content   = trim($content);
$icons_map = array(
	'googleplus' => 'googleplus',
	'linkedin'   => 'linkedin',
	'facebook'   => 'facebook',
	'twitter'    => 'twitter',
	'youtube'    => 'youtube',
	'pinterest'  => 'pinterest',
	'lastfm'     => 'lastfm',
	'instagram'  => 'instagram',
	'dribble'    => 'dribbble2',
	'vimeo'      => 'vimeo',
);

?>
<div class="team-member<?php echo (!empty($content) ? ' has-content' : '')?>">
	<?php if(!empty($picture)): ?>
	<div class="thumbnail">
		<?php if(!empty($url)):?>
			<a href="<?php echo $url ?>" title="<?php echo $name?>">
		<?php endif ?>
			<?php wpv_lazy_load($picture, $name)?>
		<?php if(!empty($url)):?>
			</a>
		<?php endif ?>
	</div>
	<?php endif ?>
	<div class="team-member-info">
		<h3>
			<?php if(!empty($url)):?>
				<a href="<?php echo $url ?>" title="<?php echo $name?>">
			<?php endif ?>
				<?php echo $name?>
			<?php if(!empty($url)):?>
				</a>
			<?php endif ?>
		</h3>
		<?php if(!empty($position)): ?>
			<h5 class="regular-title-wrapper team-member-position"> <?php echo $position ?> </h5>
		<?php endif ?>
		<?php if(!empty($email)):?>
			<div><a href="mailto:<?php echo $email ?>" title="<?php printf(__('email %s', 'fitness-wellness'), $name)?>"><?php echo $email?></a></div>
		<?php endif ?>
		<?php if(!empty($phone)):?>
			<div class="team-member-phone"><?php echo $phone?></div>
		<?php endif ?>
		<div class="share-icons clearfix">
			<?php
				$icons = array_keys( $icons_map );
				foreach($icons as $icon): if(!empty($$icon)):  // that's not good enough, should be changed
					$icon_name = isset( $icons_map[$icon] ) ? $icons_map[$icon] : $icon;
			?>
					<a href="<?php echo $$icon?>" title=""><?php echo do_shortcode('[icon name="'.$icon_name.'"]'); ?></a>
			<?php endif; endforeach; ?>
		</div>
	</div>
	<?php if(!empty($content)): ?>
	<div class="team-member-bio">
		<?php echo do_shortcode($content) ?>
	</div>
	<?php endif ?>
</div>
