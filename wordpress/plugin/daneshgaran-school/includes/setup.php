<?php
if(!defined('ABSPATH'))exit;
function dgs_page_definitions(){return array(
    'home'=>array('خانه','school-home','[daneshgaran_home]'),
    'about'=>array('معرفی مدرسه','about-school','<h2>مدرسه دانشگران؛ یادگیری امروز، ساختن فردا</h2><p>مدرسه دانشگران در دوره دوم متوسطه، همراه دانش‌آموزان پایه‌های دهم، یازدهم و دوازدهم است.</p><p>در این صفحه می‌توانید معرفی مدرسه، امکانات و برنامه‌های آموزشی را مطالعه کنید.</p><h2>مدیریت و کادر آموزشی</h2>[daneshgaran_staff]'),
    'teachers'=>array('اساتید مدرسه','teachers','[daneshgaran_teachers]'),
    'academy'=>array('آموزشگاه و کلاس‌های تقویتی','academy','[daneshgaran_academy]'),
    'news'=>array('اخبار و اطلاعیه‌ها','school-news',''),
    'admission'=>array('پیش‌ثبت‌نام مدرسه','school-admission','[daneshgaran_admission]'),
    'contact'=>array('ارتباط و درخواست مشاوره','contact-school','[daneshgaran_contact]'),
    'portal'=>array('سامانه دانشگران','school-portal','[daneshgaran_portal]'),
);}
add_action('admin_menu',function(){
    add_menu_page('سامانه دانشگران','دانشگران','edit_dgs_requests','dgs-setup','dgs_setup_page','dashicons-welcome-learn-more',27);
    add_submenu_page('dgs-setup','راه‌اندازی دانشگران','راه‌اندازی','manage_options','dgs-setup','dgs_setup_page');
    add_submenu_page('dgs-setup','درخواست‌های دریافتی','درخواست‌ها','edit_dgs_requests','edit.php?post_type=dgs_request');
});
function dgs_setup_page(){
    if(!current_user_can('manage_options')){echo '<div class="wrap"><h1>دانشگران</h1><p><a class="button button-primary" href="'.esc_url(admin_url('edit.php?post_type=dgs_request')).'">درخواست‌های دریافتی</a></p></div>';return;}
    $pages=get_option('dgs_pages',array());$s=get_option('dgs_settings',array()); ?>
    <div class="wrap" style="max-width:1000px"><h1>راه‌اندازی دانشگران — نسخه ۰.۲</h1>
    <?php if(isset($_GET['dgs_setup'])&&$_GET['dgs_setup']==='done'): ?><div class="notice notice-success"><p>راه‌اندازی انجام شد. برگه‌های موجود دوباره ساخته یا بازنویسی نشدند.</p></div><?php endif; ?>
    <?php if(isset($_GET['dgs_settings'])&&$_GET['dgs_settings']==='saved'): ?><div class="notice notice-success"><p>اطلاعات مدرسه ذخیره شد.</p></div><?php endif; ?>
    <p>این نسخه شامل قالب عمومی، اساتید و کادر، کلاس‌های تقویتی، فرم درخواست‌ها، نقش‌های پایه و حساب اولیه است. مدیریت سال، کلاس، درس، کاربران و تخصیص‌ها از صفحه سامانه در سایت در دسترس است. نمرات، حضور و غیاب، شهریه، آزمون و تکلیف در نسخه‌های بعد پیاده‌سازی می‌شوند.</p>
    <div style="background:white;border:1px solid #ddd;border-radius:12px;padding:20px;margin:20px 0"><h2>۱. ایجاد برگه‌های ضروری</h2><p>اطلاعات موجود حذف نمی‌شود. ایجاد اساتید، دانش‌آموزان و آمار ساختگی انجام نمی‌شود. تصویر اولیهٔ مدرسه صرفاً نمایشی است و باید با تصویر واقعی جایگزین شود.</p>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="dgs_setup"><?php wp_nonce_field('dgs_setup'); ?><p><label><input type="checkbox" name="set_home" value="1" checked> خانه دانشگران به‌عنوان صفحه اصلی و اخبار به‌عنوان صفحه نوشته‌ها تنظیم شود.</label></p><button class="button button-primary">ایجاد و آماده‌سازی صفحات</button></form>
    <?php if($pages): ?><table class="widefat striped" style="margin-top:20px"><thead><tr><th>برگه</th><th>مشاهده</th><th>ویرایش</th></tr></thead><tbody><?php foreach(dgs_page_definitions() as $key=>$def){if(empty($pages[$key]))continue;$id=(int)$pages[$key];echo '<tr><td>'.esc_html($def[0]).'</td><td><a target="_blank" rel="noopener" href="'.esc_url(get_permalink($id)).'">باز کردن صفحه</a></td><td><a href="'.esc_url(get_edit_post_link($id)).'">ویرایش برگه</a>';if(defined('ELEMENTOR_VERSION')&&$key!=='news'&&$key!=='portal')echo ' | <a href="'.esc_url(admin_url('post.php?post='.$id.'&action=elementor')).'">ویرایش با المنتور</a>';echo '</td></tr>';} ?></tbody></table><?php endif; ?></div>
    <div style="background:white;border:1px solid #ddd;border-radius:12px;padding:20px"><h2>۲. اطلاعات واقعی مدرسه</h2><form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="dgs_settings"><?php wp_nonce_field('dgs_settings'); ?><table class="form-table">
    <?php foreach(array('school_name'=>'نام مدرسه','phone'=>'تلفن دفتر','email'=>'ایمیل','address'=>'نشانی','office_hours'=>'ساعات کاری مدرسه','map_url'=>'لینک نقشه مدرسه') as $key=>$label)echo '<tr><th><label for="dgs-'.$key.'">'.esc_html($label).'</label></th><td><input class="regular-text" id="dgs-'.$key.'" type="'.($key==='email'?'email':'text').'" name="'.$key.'" value="'.esc_attr($s[$key]??($key==='school_name'?'مدرسه دانشگران':'')).'"></td></tr>'; ?>
    </table><button class="button button-primary">ذخیره اطلاعات مدرسه</button></form></div>
    <h2>۳. ورود محتوا</h2><p><a class="button" href="<?php echo esc_url(admin_url('post-new.php?post_type=dgs_teacher')); ?>">افزودن استاد یا عضو کادر</a> <a class="button" href="<?php echo esc_url(admin_url('post-new.php?post_type=dgs_class')); ?>">افزودن کلاس تقویتی</a> <a class="button" href="<?php echo esc_url(admin_url('post-new.php?post_type=dgs_star')); ?>">افزودن دانش‌آموز برگزیده</a> <a class="button" href="<?php echo esc_url(admin_url('post-new.php')); ?>">افزودن خبر</a></p>
    <p>برای نمایش اساتید، گروه teacher و برای مدیر و کادر، گروه staff را انتخاب کنید. برای اخبار، دسته «اخبار مدرسه» یا «اطلاعیه‌های آموزش و پرورش» را انتخاب کنید. برای نمایش اطلاعات دانش‌آموز برگزیده، رضایت انتشار تصویر و اطلاعات او را از ولی دریافت کنید.</p>
    <p>در صورت فعال‌بودن Rocket، آدرس برگه‌های سامانه، ارتباط، پیش‌ثبت‌نام و کلاس‌های تقویتی را از کش صفحه خارج کنید. پس از تغییر قالب، کش را یک‌بار پاک کنید.</p></div>
<?php }
function dgs_import_campus(){
    $stored=get_option('dgs_media',array());if(!empty($stored['campus'])&&get_post((int)$stored['campus']))return $stored;
    $source=get_template_directory().'/assets/campus.png';if(!is_file($source))return $stored;
    $uploads=wp_upload_dir();if(!empty($uploads['error']))return $stored;$target=trailingslashit($uploads['path']).wp_unique_filename($uploads['path'],'daneshgaran-campus-sample.png');if(!copy($source,$target))return $stored;
    $id=wp_insert_attachment(array('post_mime_type'=>'image/png','post_title'=>'تصویر نمایشی مدرسه — جایگزین با عکس واقعی','post_status'=>'inherit'),$target);
    if(is_wp_error($id)||!$id)return $stored;
    $size=wp_getimagesize($target);if($size)wp_update_attachment_metadata($id,array('width'=>$size[0],'height'=>$size[1],'file'=>_wp_relative_upload_path($target)));
    update_post_meta($id,'_wp_attachment_image_alt','تصویر نمایشی محیط مدرسه');$stored['campus']=$id;update_option('dgs_media',$stored);return $stored;
}
function dgs_elementor_widget($name,$settings){return array('id'=>substr(md5(wp_json_encode($settings).wp_rand()),0,7),'elType'=>'widget','widgetType'=>$name,'settings'=>$settings,'elements'=>array());}
function dgs_elementor_section($widgets,$extra=array()){
    $cols=array();foreach($widgets as $items)$cols[]=array('id'=>substr(md5(wp_rand().'col'),0,7),'elType'=>'column','settings'=>array('_column_size'=>100/count($widgets)),'elements'=>$items);
    return array('id'=>substr(md5(wp_rand().'section'),0,7),'elType'=>'section','settings'=>array_merge(array('content_width'=>array('unit'=>'px','size'=>1200),'css_classes'=>'dg-elementor-section','padding'=>array('unit'=>'px','top'=>'20','right'=>'22','bottom'=>'20','left'=>'22','isLinked'=>false)), $extra),'elements'=>$cols);
}
function dgs_elementor_data($key,$content,$media){
    if($key!=='home')return array(dgs_elementor_section(array(array(dgs_elementor_widget('text-editor',array('editor'=>$content))))));
    $heading=dgs_elementor_widget('heading',array('title'=>'مدرسه دانشگران','header_size'=>'h1','title_color'=>'#452064'));
    $intro=dgs_elementor_widget('text-editor',array('editor'=>'<p>دبیرستان دوره دوم؛ پایه‌های دهم، یازدهم و دوازدهم</p><p>محیطی پویا برای آموزش باکیفیت، پرورش استعدادها و آمادگی برای آینده‌ای روشن.</p>'));
    $button=dgs_elementor_widget('button',array('text'=>'پیش‌ثبت‌نام مدرسه','link'=>array('url'=>dgs_page_url('admission')),'button_type'=>'','background_color'=>'#ffc629','button_text_color'=>'#342042','border_radius'=>array('unit'=>'px','top'=>'12','right'=>'12','bottom'=>'12','left'=>'12','isLinked'=>true)));
    $contact=dgs_elementor_widget('button',array('text'=>'درخواست مشاوره حضوری','link'=>array('url'=>dgs_page_url('contact').'#counseling'),'background_color'=>'#f0e8fa','button_text_color'=>'#60348b'));
    $image=dgs_elementor_widget('image',array('image'=>array('id'=>(int)($media['campus']??0),'url'=>dgs_hero_image()),'image_size'=>'full','image_border_radius'=>array('unit'=>'px','top'=>'16','right'=>'16','bottom'=>'16','left'=>'16','isLinked'=>true)));
    $rows=array(dgs_elementor_section(array(array($heading,$intro,$button,$contact),array($image)),array('css_classes'=>'dg-elementor-section dg-elementor-hero','background_background'=>'classic','background_color'=>'#f4effc','column_position'=>'middle')));
    $rows[]=dgs_elementor_section(array(array(dgs_elementor_widget('heading',array('title'=>'معرفی مدرسه دانشگران','header_size'=>'h2')),dgs_elementor_widget('text-editor',array('editor'=>'<p>در مدرسه دانشگران، آموزش هدفمند و همراهی مستمر، زمینه رشد علمی و فردی دانش‌آموزان را فراهم می‌کند. با برنامه‌های مدرسه، اساتید و محیط آموزشی ما آشنا شوید.</p>')),dgs_elementor_widget('button',array('text'=>'بیشتر درباره مدرسه','link'=>array('url'=>dgs_page_url('about')),'background_color'=>'#f0e8fa','button_text_color'=>'#60348b')))));
    foreach(array('teachers','featured','news','staff','gallery','roles') as $section)$rows[]=dgs_elementor_section(array(array(dgs_elementor_widget('shortcode',array('shortcode'=>'[daneshgaran_section name="'.$section.'"]')))),array('css_classes'=>'dg-elementor-dynamic','padding'=>array('unit'=>'px','top'=>'0','right'=>'0','bottom'=>'0','left'=>'0','isLinked'=>true)));
    return $rows;
}
function dgs_run_setup($set_home=false){
    if(get_template()!=='daneshgaran')return new WP_Error('theme_required','ابتدا قالب دانشگران را از نمایش ← پوسته‌ها فعال کنید، سپس راه‌انداز را اجرا کنید.');
    $pages=get_option('dgs_pages',array());$created=array();
    foreach(dgs_page_definitions() as $key=>$def){
        if(!empty($pages[$key])&&get_post_type((int)$pages[$key])==='page'&&get_post_status((int)$pages[$key])!=='trash')continue;
        $found=get_posts(array('post_type'=>'page','post_status'=>array('publish','draft','private'),'numberposts'=>1,'meta_key'=>'_dgs_page_key','meta_value'=>$key));
        if($found){$pages[$key]=$found[0]->ID;continue;}
        $id=wp_insert_post(wp_slash(array('post_type'=>'page','post_status'=>'publish','post_title'=>$def[0],'post_name'=>$def[1],'post_content'=>$def[2],'meta_input'=>array('_dgs_page_key'=>$key))),true);
        if(is_wp_error($id)||!$id)return new WP_Error('page_create','ساخت برگه '.$def[0].' انجام نشد.');$pages[$key]=$id;$created[$key]=$id;
    }
    update_option('dgs_pages',$pages);$media=dgs_import_campus();
    if(defined('ELEMENTOR_VERSION'))foreach($created as $key=>$id){if(in_array($key,array('news','portal'),true))continue;
        $data=dgs_elementor_data($key,dgs_page_definitions()[$key][2],$media);
        update_post_meta($id,'_elementor_data',wp_slash(wp_json_encode($data,JSON_UNESCAPED_UNICODE)));
        update_post_meta($id,'_elementor_edit_mode','builder');update_post_meta($id,'_elementor_template_type','wp-page');update_post_meta($id,'_elementor_version',ELEMENTOR_VERSION);update_post_meta($id,'_elementor_page_settings',array('hide_title'=>'yes'));
    }
    foreach(array('school-news'=>'اخبار مدرسه','ministry-news'=>'اطلاعیه‌های آموزش و پرورش') as $slug=>$title)if(!term_exists($slug,'category'))wp_insert_term($title,'category',array('slug'=>$slug));
    if($set_home){update_option('show_on_front','page');update_option('page_on_front',(int)$pages['home']);update_option('page_for_posts',(int)$pages['news']);}
    if(!get_option('dgs_settings'))update_option('dgs_settings',array('school_name'=>'مدرسه دانشگران'));
    if(!get_option('timezone_string'))update_option('timezone_string','Asia/Tehran');
    if(!get_option('permalink_structure'))update_option('permalink_structure','/%postname%/');
    flush_rewrite_rules(false);
    if(class_exists('\Elementor\Plugin')&&isset(\Elementor\Plugin::$instance->files_manager))\Elementor\Plugin::$instance->files_manager->clear_cache();
    return $pages;
}
add_action('admin_post_dgs_setup',function(){if(!current_user_can('manage_options'))wp_die('دسترسی ندارید.','',array('response'=>403));check_admin_referer('dgs_setup');$result=dgs_run_setup(isset($_POST['set_home'])&&$_POST['set_home']==='1');if(is_wp_error($result))wp_die(esc_html($result->get_error_message()));wp_safe_redirect(admin_url('admin.php?page=dgs-setup&dgs_setup=done'));exit;});
add_action('admin_post_dgs_settings',function(){if(!current_user_can('manage_options'))wp_die('دسترسی ندارید.','',array('response'=>403));check_admin_referer('dgs_settings');$settings=get_option('dgs_settings',array());foreach(array('school_name','phone','email','address','office_hours','map_url') as $key){if(!isset($_POST[$key])||!is_string($_POST[$key]))continue;$v=wp_unslash($_POST[$key]);$settings[$key]=$key==='email'?sanitize_email($v):($key==='map_url'?esc_url_raw($v,array('http','https')):sanitize_text_field($v));}update_option('dgs_settings',$settings);wp_safe_redirect(admin_url('admin.php?page=dgs-setup&dgs_settings=saved'));exit;});
