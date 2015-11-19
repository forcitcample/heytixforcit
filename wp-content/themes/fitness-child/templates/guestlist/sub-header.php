<?php
global $wpv_title;
$wpv_title = htgl_subheader_title();
?>
<div id="sub-header" class="layout-full">
	<div class="meta-header">
		<div class="limit-wrapper">
			<div class="meta-header-inside">
				<?php
					WpvTemplates::breadcrumbs();
					WpvTemplates::page_header(false, $wpv_title);
				?>
			</div>
		</div>
	</div>
</div>