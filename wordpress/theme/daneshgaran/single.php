<?php if(!defined('ABSPATH'))exit;get_header();while(have_posts()):the_post(); ?>
<article class="dg-card dg-content-page"><a class="dg-text-link" href="<?php echo esc_url(dgt_page_url(get_post_type()==='dgs_teacher'?'teachers':(get_post_type()==='dgs_class'?'academy':'news'))); ?>">بازگشت <?php echo dgt_icon('arrow'); ?></a><h1><?php the_title(); ?></h1>
<?php if(has_post_thumbnail()): ?><div class="dg-single-image"><?php the_post_thumbnail('large'); ?></div><?php endif; ?>
<div class="dg-entry-content"><?php the_content(); ?></div>
<?php if(get_post_type()==='dgs_teacher'): ?><div class="dg-chips"><span><?php echo esc_html(get_post_meta(get_the_ID(),'_dgs_specialty',true)); ?></span></div><?php $video=get_post_meta(get_the_ID(),'_dgs_video',true);if($video)echo '<div class="dg-video">'.wp_video_shortcode(array('src'=>esc_url($video))).'</div>'; ?><?php endif; ?>
<?php if(get_post_type()==='dgs_class'): ?><div class="dg-course-info"><?php foreach(array('_dgs_schedule'=>'زمان برگزاری','_dgs_teacher_name'=>'مدرس','_dgs_grade'=>'پایه') as $key=>$label){$value=get_post_meta(get_the_ID(),$key,true);if($value)echo '<p><strong>'.esc_html($label).': </strong>'.esc_html($value).'</p>';} ?></div><?php if(function_exists('dgs_request_form'))echo dgs_request_form('class',get_the_ID()); ?><?php endif; ?>
</article><?php endwhile;get_footer(); ?>
