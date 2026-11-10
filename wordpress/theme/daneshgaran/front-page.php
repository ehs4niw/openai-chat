<?php if(!defined('ABSPATH'))exit; get_header(); ?>
<?php if(have_posts()):while(have_posts()):the_post(); ?><div class="dg-front-content"><?php the_content(); ?></div><?php endwhile;else: ?><section class="dg-card dg-empty"><h1>به مدرسه دانشگران خوش آمدید</h1><p>معرفی مدرسه، برنامه‌ها و اخبار آموزشی</p><?php if(current_user_can('manage_options')): ?><a class="dg-btn" href="<?php echo esc_url(admin_url('admin.php?page=dgs-setup')); ?>">راه‌اندازی صفحات دانشگران</a><?php endif; ?></section><?php endif; ?>
<?php get_footer(); ?>
