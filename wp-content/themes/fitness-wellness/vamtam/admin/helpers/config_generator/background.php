<?php

$fields = array(
	'color' => __( 'Color:', 'fitness-wellness' ),
	'opacity' => __( 'Opacity:', 'fitness-wellness' ),
	'image' => __( 'Image / pattern:', 'fitness-wellness' ),
	'repeat' => __( 'Repeat:', 'fitness-wellness' ),
	'attachment' => __( 'Attachment:', 'fitness-wellness' ),
	'position' => __( 'Position:', 'fitness-wellness' ),
	'size' => __( 'Size:', 'fitness-wellness' ),
);

$sep = isset( $sep ) ? $sep : '-';

$current = array();

if ( ! isset( $only ) ) {
	if ( isset( $show ) ) {
		$only = explode( ',', $show );
	} else {
		$only = array();
	}
} else {
	$only = explode( ',', $only );
}

$show = array();

global $post;
foreach ( $fields as $field => $fname ) {
	if ( isset( $GLOBALS['wpv_in_metabox'] ) ) {
		$current[$field] = get_post_meta( $post->ID, "$id-$field", true );
	} else {
		$current[$field] = wpv_get_option( "$id-$field" );
	}
	$show[$field] = ( in_array( $field, $only ) || sizeof( $only ) == 0 );
}

$selects = array(
	'repeat' => array(
		'no-repeat' => __( 'No repeat', 'fitness-wellness' ),
		'repeat-x' => __( 'Repeat horizontally', 'fitness-wellness' ),
		'repeat-y' => __( 'Repeat vertically', 'fitness-wellness' ),
		'repeat' => __( 'Repeat both', 'fitness-wellness' ),
	),
	'attachment' => array(
		'scroll' => __( 'scroll', 'fitness-wellness' ),
		'fixed' => __( 'fixed', 'fitness-wellness' ),
	),
	'position' => array(
		'left center' => __( 'left center', 'fitness-wellness' ),
		'left top' => __( 'left top', 'fitness-wellness' ),
		'left bottom' => __( 'left bottom', 'fitness-wellness' ),
		'center center' => __( 'center center', 'fitness-wellness' ),
		'center top' => __( 'center top', 'fitness-wellness' ),
		'center bottom' => __( 'center bottom', 'fitness-wellness' ),
		'right center' => __( 'right center', 'fitness-wellness' ),
		'right top' => __( 'right top', 'fitness-wellness' ),
		'right bottom' => __( 'right bottom', 'fitness-wellness' ),
	),
);

?>

<div class="wpv-config-row background clearfix <?php echo esc_attr( $class ) ?>">

	<div class="rtitle">
		<h4><?php echo $name // xss ok ?></h4>

		<?php wpv_description( $id, $desc ) ?>
	</div>

	<div class="rcontent">
		<div class="bg-inner-row">
			<?php if ( $show['color'] ): ?>
				<div class="bg-block color">
					<div class="single-desc"><?php _e( 'Color:', 'fitness-wellness' ) ?></div>
					<input name="<?php echo esc_attr( $id.$sep ) ?>color" id="<?php echo esc_attr( $id ) ?>-color" type="color" data-hex="true" value="<?php echo esc_attr( $current['color'] ) ?>" class="" />
				</div>
			<?php endif ?>

			<?php if ( $show['opacity'] ): ?>
				<div class="bg-block opacity range-input-wrap clearfix">
					<div class="single-desc"><?php _e( 'Opacity:', 'fitness-wellness' ) ?></div>
					<span>
						<input name="<?php echo esc_attr( $id.$sep )?>opacity" id="<?php echo esc_attr( $id ) ?>-opacity" type="range" value="<?php echo esc_attr( $current['opacity'] )?>" min="0" max="1.01" step="0.05" class="wpv-range-input" />
					</span>
				</div>
			<?php endif ?>
		</div>

		<div class="bg-inner-row">
			<?php if ( $show['image'] ): ?>
				<div class="bg-block bg-image">
					<div class="single-desc"><?php _e( 'Image / pattern:', 'fitness-wellness' ) ?></div>
					<?php $_id = $id;	$id .= $sep.'image'; // temporary change the id so that we can reuse the upload field ?>
					<div class="image <?php wpv_static( $value ) ?>">
						<?php include 'upload-basic.php'; ?>
					</div>
					<?php $id = $_id; unset( $_id ); ?>
				</div>
			<?php endif ?>

			<?php if ( $show['size'] ): ?>
				<div class="bg-block bg-size">
					<div class="single-desc"><?php _e( 'Cover:', 'fitness-wellness' ) ?></div>
					<label class="toggle-radio">
						<input type="radio" name="<?php echo esc_attr( $id.$sep ) ?>size" value="cover" <?php checked( $current['size'], 'cover' ) ?>/>
						<span><?php _e( 'On', 'fitness-wellness' ) ?></span>
					</label>
					<label class="toggle-radio">
						<input type="radio" name="<?php echo esc_attr( $id.$sep ) ?>size" value="auto" <?php checked( $current['size'], 'auto' ) ?>/>
						<span><?php _e( 'Off', 'fitness-wellness' ) ?></span>
					</label>
				</div>
			<?php endif ?>

			<?php foreach ( $selects as $s => $options ): ?>
				<?php if ( $show[$s] ): ?>
					<div class="bg-block bg-<?php echo esc_attr( $s )?>">
						<div class="single-desc"><?php echo $fields[$s] // xss ok ?></div>
						<select name="<?php echo esc_attr( $id.$sep.$s ) ?>" class="bg-<?php echo esc_attr( $s ) ?>">
							<?php foreach ( $options as $val => $opt ): ?>
								<option value="<?php echo esc_attr( $val ) ?>" <?php selected( $val, $current[$s] ) ?>><?php echo $opt // xss ok ?></option>
							<?php endforeach ?>
						</select>
					</div>
				<?php endif ?>
			<?php endforeach ?>
		</div>
	</div>
</div>