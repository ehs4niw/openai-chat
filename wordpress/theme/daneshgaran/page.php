<?php if(!defined('ABSPATH'))exit; get_header();while(have_posts()):the_post();
$built=class_exists('\Elementor\Plugin')&&\Elementor\Plugin::$instance->documents->get(get_the_ID())&&\Elementor\Plugin::$instance->documents->get(get_the_ID())->is_built_with_elementor();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class($built?'dg-elementor-page':'dg-card dg-content-page'); ?>>
<?php if(!$built && !is_front_page()): ?><h1><?php the_title(); ?></h1><?php endif; ?>
<div class="dg-entry-content"><?php the_content(); ?></div></article>
<?php endwhile;get_footer(); ?>
