<?php
/*
 * Partial for showing social site profiles
 */

/* Array holding the available social profiles: name => array( title => fa class name) */
$lafka_social_profiles = array(
		'facebook' => array('title' => esc_html__('Follow on Facebook', 'lafka'), 'class' => 'fa fa-facebook'),
		'twitter' => array('title' => esc_html__('Follow on Twitter', 'lafka'), 'class' => 'fa fa-twitter'),
		'google' => array('title' => esc_html__('Follow on Google+', 'lafka'), 'class' => 'fa fa-google-plus'),
		'youtube' => array('title' => esc_html__('Follow on YouTube', 'lafka'), 'class' => 'fa fa-youtube-play'),
		'vimeo' => array('title' => esc_html__('Follow on Vimeo', 'lafka'), 'class' => 'fa fa-vimeo-square'),
		'dribbble' => array('title' => esc_html__('Follow on Dribbble', 'lafka'), 'class' => 'fa fa-dribbble'),
		'linkedin' => array('title' => esc_html__('Follow on LinkedIn', 'lafka'), 'class' => 'fa fa-linkedin'),
		'stumbleupon' => array('title' => esc_html__('Follow on StumbleUpon', 'lafka'), 'class' => 'fa fa-stumbleupon'),
		'flicker' => array('title' => esc_html__('Follow on Flickr', 'lafka'), 'class' => 'fa fa-flickr'),
		'instegram' => array('title' => esc_html__('Follow on Instagram', 'lafka'), 'class' => 'fa fa-instagram'),
		'pinterest' => array('title' => esc_html__('Follow on Pinterest', 'lafka'), 'class' => 'fa fa-pinterest'),
		'vkontakte' => array('title' => esc_html__('Follow on VKontakte', 'lafka'), 'class' => 'fa fa-vk'),
		'behance' => array('title' => esc_html__('Follow on Behance', 'lafka'), 'class' => 'fa fa-behance')
);
?>
<div class="lafka-social">
	<ul>
		<?php foreach ($lafka_social_profiles as $lafka_social_name => $lafka_details): ?>
			<?php if (lafka_get_option($lafka_social_name . '_profile')): ?>
				<li><a title="<?php echo esc_attr($lafka_details['title']) ?>" class="<?php echo esc_attr($lafka_social_name) ?>" target="_blank"  href="<?php echo esc_url(lafka_get_option($lafka_social_name . '_profile')) ?>"><i class="<?php echo esc_attr($lafka_details['class']) ?>"></i></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>
</div>