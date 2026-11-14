<?php
if (!defined('ABSPATH')) { exit; }
function dgt_setup() {
    load_theme_textdomain('daneshgaran', get_template_directory() . '/languages');
    add_theme_support('title-tag'); add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height'=>160,'width'=>200,'flex-height'=>true,'flex-width'=>true));
    add_theme_support('html5', array('search-form','gallery','caption','style','script'));
    add_theme_support('responsive-embeds'); add_theme_support('align-wide');
    register_nav_menus(array('primary'=>'منوی اصلی دانشگران','footer'=>'پیوندهای پایین صفحه'));
}
add_action('after_setup_theme','dgt_setup');
function dgt_assets() {
    $version=wp_get_theme()->get('Version');
    // CSS در خود پاسخ صفحه قرار می‌گیرد تا خطای دسترسی فایل استایل، ظاهر را از کار نیندازد.
    // نیازی به جاوااسکریپت، درخواست AJAX یا تایمر برای بارگذاری استایل نیست.
    wp_register_style('daneshgaran',false,array(),$version);
    wp_enqueue_style('daneshgaran');
    $css_file=get_template_directory().'/assets/site.css';
    if(is_readable($css_file)){
        $css=file_get_contents($css_file);
        $css=str_replace("url('Estedad.ttf')","url('".esc_url_raw(get_template_directory_uri().'/assets/Estedad.ttf')."')",$css);
        wp_add_inline_style('daneshgaran',$css);
    }
    wp_enqueue_script('daneshgaran',get_template_directory_uri().'/assets/site.js',array(),$version,true);
}
add_action('wp_enqueue_scripts','dgt_assets');
add_action('elementor/editor/after_enqueue_styles',function(){
    wp_register_style('daneshgaran-editor',false,array(),'0.1.2');
    wp_enqueue_style('daneshgaran-editor');
    $file=get_template_directory().'/assets/site.css';
    if(is_readable($file))wp_add_inline_style('daneshgaran-editor',str_replace("url('Estedad.ttf')","url('".esc_url_raw(get_template_directory_uri().'/assets/Estedad.ttf')."')",file_get_contents($file)));
});
function dgt_option($name,$default=''){ $v=get_option('dgs_settings',array());return isset($v[$name])&&is_scalar($v[$name])?(string)$v[$name]:$default; }
function dgt_page_url($key,$fallback='') {
    $pages=get_option('dgs_pages',array());
    return !empty($pages[$key]) && get_post_status((int)$pages[$key])==='publish' ? get_permalink((int)$pages[$key]) : ($fallback?:home_url('/'));
}
function dgt_icon($name='home') {
    if(function_exists('dgs_portal_icon'))return dgs_portal_icon($name);
    $paths=array(
      'home'=>'M3 11 12 3l9 8M5 10v11h5v-7h4v7h5V10',
      'book'=>'M12 5c-3-3-7-3-10-2v16c4-1 7-1 10 2 3-3 6-3 10-2V3c-3-1-7-1-10 2Zm0 0v16',
      'users'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8m8-7a4 4 0 0 1 0 8m5 9v-2a4 4 0 0 0-3-3.87',
      'news'=>'M4 3h16v18H4zM8 7h8M8 11h8M8 15h3M8 18h8',
      'calendar'=>'M4 5h16v16H4zM8 2v6m8-6v6M4 10h16m-12 4h2m4 0h2m-8 4h2',
      'mail'=>'M3 5h18v14H3zM3 5l9 8 9-8',
      'login'=>'M14 3h6v18h-6M2 12h13m-4-4 4 4-4 4',
      'star'=>'m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1Z',
      'arrow'=>'M19 12H5m6-6-6 6 6 6',
      'menu'=>'M3 6h18M3 12h18M3 18h18',
      'bell'=>'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4',
      'pin'=>'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0ZM9 10a3 3 0 1 0 6 0 3 3 0 0 0-6 0',
      'phone'=>'M5 3h4l2 5-3 2c2 4 3 5 6 6l2-3 5 2v4c-1 4-7 1-11-2S2 8 3 5Z',
    );
    return '<svg width="22" height="22" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="'.esc_attr($paths[$name]??$paths['book']).'"/></svg>';
}
function dgt_main_logo_url(){
    if(function_exists('dgs_main_logo_url'))return dgs_main_logo_url();
    $id=absint(get_theme_mod('custom_logo'));if($id&&wp_attachment_is_image($id)){$url=wp_get_attachment_image_url($id,'medium')?:wp_get_attachment_url($id);if($url)return esc_url_raw($url,array('http','https'));}
    static $uri=null;if($uri===null){$file=get_template_directory().'/assets/footer-logo.png';$data=is_readable($file)?file_get_contents($file):false;$uri=$data!==false?'data:image/png;base64,'.base64_encode($data):'';}return $uri;
}
function dgt_logo(){
    if(function_exists('dgs_brand_markup')){echo dgs_brand_markup();return;}
    echo '<a class="dg-brand dgs-school-brand" href="'.esc_url(home_url('/')).'" aria-label="مدرسه دانشگران"><img class="dgs-main-logo" src="'.esc_attr(dgt_main_logo_url()).'" alt="نشان مدرسه دانشگران" width="118" height="118"><strong>'.esc_html(dgt_option('school_name','مدرسه دانشگران')).'</strong><small>دبیرستان دوره دوم</small></a>';
}
function dgt_navigation(){
    if(has_nav_menu('primary')){wp_nav_menu(array('theme_location'=>'primary','container'=>false,'menu_class'=>'dg-menu','depth'=>2));return;}
    $nav=array('home'=>array('صفحه اصلی','home'),'about'=>array('معرفی مدرسه','book'),'teachers'=>array('اساتید','users'),'academy'=>array('آموزشگاه','calendar'),'news'=>array('اخبار و اطلاعیه‌ها','news'),'admission'=>array('پیش‌ثبت‌نام','star'),'contact'=>array('ارتباط با ما','mail'),'portal'=>array(is_user_logged_in()?'پنل من':'ورود به سامانه','login'));
    echo '<ul class="dg-menu">';$current=get_queried_object_id();$pages=get_option('dgs_pages',array());
    foreach($nav as $key=>$item){$active=!empty($pages[$key])&&(int)$pages[$key]===$current;echo '<li><a '.($active?'aria-current="page"':'').' href="'.esc_url(dgt_page_url($key)).'">'.dgt_icon($item[1]).'<span>'.esc_html($item[0]).'</span></a></li>';}
    echo '</ul>';
}
function dgt_customizer($wp_customize){
    $wp_customize->add_section('dg_school',array('title'=>'اطلاعات مدرسه دانشگران','priority'=>35));
    // Sidebar and footer now share the standard Site Identity > Logo control.
    $logo_control=$wp_customize->get_control('custom_logo');if($logo_control)$logo_control->label='لوگوی اصلی سایت (سایدبار و فوتر)';
    foreach(array('school_name'=>'نام مدرسه','phone'=>'تلفن دفتر','email'=>'ایمیل','address'=>'نشانی') as $key=>$label){
        $sanitize=$key==='email'?'sanitize_email':'sanitize_text_field';
        $wp_customize->add_setting('dgs_settings['.$key.']',array('type'=>'option','sanitize_callback'=>$sanitize,'capability'=>'manage_options'));
        $wp_customize->add_control('dgs_settings['.$key.']',array('section'=>'dg_school','label'=>$label,'type'=>$key==='email'?'email':'text'));
    }
}
add_action('customize_register','dgt_customizer');

add_action('wp_head',function(){echo '<meta name="daneshgaran-theme-version" content="'.esc_attr(wp_get_theme()->get('Version')).'">';},1);
