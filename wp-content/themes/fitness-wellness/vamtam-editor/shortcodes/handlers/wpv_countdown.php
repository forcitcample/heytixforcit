<?php

class WPV_Sc_Countdown {
	public function __construct() {
		add_shortcode('wpv_countdown', array(&$this, 'shortcode'));
	}

	public function shortcode( $atts, $content = null, $code ) {
		extract( shortcode_atts( array(
			'datetime' => '',
			'done' => '',
		), $atts ) );

		ob_start();

		?>
		<div class="wpv-countdown regular" data-until="<?php echo esc_attr( strtotime( $datetime ) ) ?>" data-done="<?php echo esc_attr( $done ) ?>" data-respond>
			<span class="wpvc-days wpvc-block">
				<div class="value"></div>
				<div class="value-label"><?php _e('Days', 'fitness-wellness') ?></div>
			</span>
			<span class="wpvc-sep">:</span>
			<span class="wpvc-hours wpvc-block">
				<div class="value"></div>
				<div class="value-label"><?php _e('Hours', 'fitness-wellness') ?></div>
			</span>
			<?php if ( ! trim( $content ) === false ): ?>
				<div class="wpvc-description">
					<?php echo $content // xss ok ?>
				</div>
			<?php else: ?>
				<span class="wpvc-sep">:</span>
			<?php endif ?>
			<span class="wpvc-minutes wpvc-block">
				<div class="value"></div>
				<div class="value-label"><?php _e('Minutes', 'fitness-wellness') ?></div>
			</span>
			<span class="wpvc-sep">:</span>
			<span class="wpvc-seconds wpvc-block">
				<div class="value"></div>
				<div class="value-label"><?php _e('Seconds', 'fitness-wellness') ?></div>
			</span>
		</div>
<?php
		return ob_get_clean();
	}
}

new WPV_Sc_Countdown;
