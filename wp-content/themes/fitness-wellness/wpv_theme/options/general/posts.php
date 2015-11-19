<?php

/**
 * Theme options / General / Posts
 *
 * @package wpv
 * @subpackage fitness-wellness
 */

return array(

array(
	'name' => __('Posts and Content', 'fitness-wellness'),
	'type' => 'start',
),

array(
	'name' => __('Blog and Portfolio Listing Pages and Archives', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	'name' => __('Pagination Type', 'fitness-wellness'),
	'desc' => __('Please note that you will need WP-PageNavi plugin installed if you chose "paged" style.', 'fitness-wellness'),
	'id' => 'pagination-type',
	'type' => 'select',
	'options' => array(
		'paged' => __('Paged', 'fitness-wellness'),
		'load-more' => __('Load more button', 'fitness-wellness'),
		'infinite-scrolling' => __('Infinite scrolling', 'fitness-wellness'),
	),
	'class' => 'slim',
),


array(
	'name' => __('Blog Posts', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	'name' => __('"View All Posts" Link', 'fitness-wellness'),
	'desc' => __('In a single blog post view in the top you will find navigation with 3 buttons. The middle gets you to the blog listing view.<br>
You can place the link here.', 'fitness-wellness'),
	'id' => 'post-all-items',
	'type' => 'text',
	'static' => true,
	'class' => 'slim',
),

array(
	'name' => __('Show "Related Posts" in Single Post View', 'fitness-wellness'),
	'desc' => __('Enabling this option will show more posts from the same category when viewing a single post.', 'fitness-wellness'),
	'id' => 'show-related-posts',
	'type' => 'toggle',
	'class' => 'slim',
),

array(
	'name' => __('"Related Posts" title', 'fitness-wellness'),
	'id' => 'related-posts-title',
	'type' => 'text',
	'class' => 'slim',
),

array(
	'name' => __('Show Post Author', 'fitness-wellness'),
	'desc' => __('Blog post meta info, works for the single blog post view.', 'fitness-wellness'),
	'id' => 'show-post-author',
	'type' => 'toggle',
	'class' => 'slim'
),
array(
	'name' => __('Show Categories and Tags', 'fitness-wellness'),
	'desc' => __('Blog post meta info, works for the single blog post view.', 'fitness-wellness'),
	'id' => 'meta_posted_in',
	'type' => 'toggle',
	'class' => 'slim',
),
array(
	'name' => __('Show Post Timestamp', 'fitness-wellness'),
	'desc' => __('Blog post meta info, works for the single blog post view.', 'fitness-wellness'),
	'id' => 'meta_posted_on',
	'type' => 'toggle',
	'class' => 'slim',
),
array(
	'name' => __('Show Comment Count', 'fitness-wellness'),
	'desc' => __('Blog post meta info, works for the single blog post view.', 'fitness-wellness'),
	'id' => 'meta_comment_count',
	'type' => 'toggle',
	'class' => 'slim',
),

array(
	'name' => __('Portfolio Posts', 'fitness-wellness'),
	'type' => 'separator',
),

array(
	'name' => __('"View All Portfolios" Link', 'fitness-wellness'),
	'desc' => __('In a single portfolio post view in the top you will find navigation with 3 buttons. The middle gets you to the portfolio listing view.<br>
You can place the link here.', 'fitness-wellness'),
	'id' => 'portfolio-all-items',
	'type' => 'text',
	'static' => true,
	'class' => 'slim',
),
array(
	'name' => __('Show "Related Portfolios" in Single Portfolio View', 'fitness-wellness'),
	'desc' => __('Enabling this option will show more portfolio posts from the same category in the single portfolio post.', 'fitness-wellness'),
	'id' => 'show-related-portfolios',
	'type' => 'toggle',
	'class' => 'slim',
),

array(
	'name' => __('"Related Portfolios" title', 'fitness-wellness'),
	'id' => 'related-portfolios-title',
	'type' => 'text',
	'class' => 'slim',
),

array(
	'name' => __('URL Prefix for Single Portfolios', 'fitness-wellness'),
	'desc' => __('Use an unique string without spaces. It must not be the same as any other URL slugs (used on pages, etc.).', 'fitness-wellness'),
	'id' => 'portfolio-slug',
	'type' => 'text',
	'class' => 'slim',
),

	array(
		'type' => 'end'
	),
);