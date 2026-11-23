<?php if(!defined('ABSPATH'))exit; ?></main>
<?php $dgt_pages=get_option('dgs_pages',array());$dgt_panel=(!empty($dgt_pages['portal'])&&is_page((int)$dgt_pages['portal']))||(function_exists('dgs_is_role_portal')&&dgs_is_role_portal());if(!$dgt_panel): ?>
<?php $dgt_footer_css=get_template_directory().'/assets/footer.css';if(is_readable($dgt_footer_css))echo '<style id="dgt-shared-footer-css">'.file_get_contents($dgt_footer_css).'</style>'; ?>
<footer class="dg-footer dg-footer-lilac" aria-label="پایین صفحه">
<div class="dg-footer-columns">
<section class="dg-footer-contact"><h2>ارتباط با مدرسه</h2><?php foreach(array('phone'=>'phone','address'=>'pin') as $key=>$icon){$value=dgt_option($key);if($value)echo '<p class="dg-contact-line">'.dgt_icon($icon).'<span>'.esc_html($value).'</span></p>';} ?><a href="<?php echo esc_url(dgt_page_url('contact')); ?>">راه‌های ارتباطی <?php echo dgt_icon('arrow'); ?></a></section>
<div class="dg-footer-intro"><?php $logo_url=dgt_main_logo_url(); ?><img class="dg-footer-logo" src="<?php echo esc_attr($logo_url); ?>" alt="نشان مدرسه دانشگران" width="64" height="64" loading="lazy" decoding="async"><strong><?php echo esc_html(dgt_option('school_name','مدرسه دانشگران')); ?></strong><p>یادگیری امروز، ساختن فردا</p></div>
<nav class="dg-footer-links" aria-label="دسترسی سریع"><h2>دسترسی سریع</h2><a href="<?php echo esc_url(dgt_page_url('admission')); ?>">پیش‌ثبت‌نام مدرسه</a><a href="<?php echo esc_url(dgt_page_url('academy')); ?>">آموزشگاه و کلاس‌های تقویتی</a><a href="<?php echo esc_url(dgt_page_url('portal')); ?>">ورود به سامانه</a></nav>
</div><div class="dg-footer-bottom"><p>تمام حقوق این وب‌سایت متعلق به <?php echo esc_html(dgt_option('school_name','مدرسه دانشگران')); ?> است.</p><span class="dg-developer"><span>طراحی و برنامه‌نویسی</span><strong dir="ltr">ehs4niw</strong><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7 6-6 6 6 6m10-12 6 6-6 6m-3-15-4 18"/></svg></span></div></footer>
<?php endif; ?>
</div></div><?php wp_footer(); ?></body></html>
