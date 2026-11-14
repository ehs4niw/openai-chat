<?php
if(!defined('ABSPATH'))exit;
function dgs_portal_icon($name){$paths=array(
 'overview'=>'M3 11l9-8 9 8M5 10v11h5v-7h4v7h5V10',
 'education'=>'M2 8l10-5 10 5-10 5zM6 11v6c4 3 8 3 12 0v-6M22 9v9',
 'users'=>'M4 21v-3c0-5 10-5 10 0v3M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 4a4 4 0 0 1 0 8M17 15c4 0 5 2 5 6',
 'students'=>'M4 21v-3c0-5 10-5 10 0v3M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 4a4 4 0 0 1 0 8M17 15c4 0 5 2 5 6',
 'classes'=>'M12 3L2 8l10 5 10-5zM2 12l10 5 10-5M2 16l10 5 10-5',
 'study'=>'M3 4c4-1 7 0 9 2 2-2 5-3 9-2v16c-4-1-7 0-9 2-2-2-5-3-9-2zM12 6v16',
 'timetable'=>'M4 5h16v16H4zM8 2v6M16 2v6M4 10h16M8 14h2M14 14h2M8 18h2M14 18h2',
 'homework'=>'M14 2H5v20h14V7zM14 2v6h5M8 14l4-4 4 4M12 10v9',
 'quizzes'=>'M8 4H4v18h16V4h-4M8 2h8v5H8zM8 14l3 3 6-6',
 'grades'=>'M3 21h19M6 17v-5M12 17V7M18 17V3',
 'attendance'=>'M4 5h16v16H4zM8 2v6M16 2v6M4 10h16M8 16l3 3 6-6',
 'finance'=>'M3 5h18v15H3zM3 10h18M6 15h5',
 'counseling'=>'M4 21v-3c0-5 10-5 10 0v3M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 4a4 4 0 0 1 0 8M17 15c4 0 5 2 5 6',
 'requests'=>'M14 2H5v20h14V7zM14 2v6h5M8 12h8M8 16h8',
 'password'=>'M14 14a6 6 0 1 0-4-4L2 18v4h4v-3h3v-3z',
 'records'=>'M14 2H5v20h14V7zM14 2v6h5M9 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0M8 20c0-5 8-5 8 0',
 'logout'=>'M10 3H4v18h6M13 7l5 5-5 5M8 12h14',
 'upload'=>'M7 17H5a4 4 0 0 1-1-8 7 7 0 0 1 13-2 5 5 0 0 1 1 10h-1M8 13l4-4 4 4M12 9v12',
 'clipboard'=>'M8 4H4v18h16V4h-4M8 2h8v5H8zM8 11h8M8 15h8M8 19h5',
 'history'=>'M3 11a9 9 0 1 1 2 7M3 5v6h6M12 7v6l4 2',
 'clock'=>'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18M12 7v6l4 2',
 'check'=>'M4 12l5 5L20 5','refresh'=>'M20 7a8 8 0 0 0-14-2L3 9M3 3v6h6M4 17a8 8 0 0 0 14 2l3-4M21 21v-6h-6',
 'file'=>'M14 2H5v20h14V7zM14 2v6h5M8 12h8M8 16h8',
 'message'=>'M3 3h18v14H9l-6 5z',
 'send'=>'M22 2L2 10l8 4 4 8zM10 14L22 2');$paths=array_merge($paths,array('eye'=>'M2 12c5-9 15-9 20 0-5 9-15 9-20 0M9 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0','download'=>'M12 2v13M7 10l5 5 5-5M4 16v6h16v-6','printer'=>'M7 8V2h10v6M7 17H3V8h18v9h-4M7 14h10v8H7zM17 11h1','warning'=>'M12 2L1 22h22zM12 8v6M12 18h.01','info'=>'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18M12 10v7M12 6h.01','trend'=>'M3 18l7-7 4 4 7-11M15 4h6v6','trophy'=>'M7 3h10v8a5 5 0 0 1-10 0zM7 5H3v4c0 3 2 4 5 4M17 5h4v4c0 3-2 4-5 4M12 16v6M7 22h10','attachment'=>'M8 13l7-7a3 3 0 0 1 4 4L9 20a5 5 0 0 1-7-7L13 2M6 15l10-10','edit'=>'M16 3l5 5-12 12H4v-5zM14 5l5 5','mail'=>'M3 5h18v14H3zM3 5l9 8 9-8','star'=>'M12 2l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z','arrow'=>'M20 12H4M10 6l-6 6 6 6','menu'=>'M3 6h18M3 12h18M3 18h18','bell'=>'M5 17h14l-2-4V9a5 5 0 0 0-10 0v4zM10 21h4','pin'=>'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0M9 10a3 3 0 1 0 6 0 3 3 0 0 0-6 0','phone'=>'M5 3h4l2 5-3 2c2 4 3 5 6 6l2-3 5 2v4c-1 4-7 1-11-2S2 8 3 5z','medal'=>'M8 2h8l-1 7H9zM12 9a6 6 0 1 0 0 12 6 6 0 0 0 0-12M9 20v3l3-2 3 2v-3','close'=>'M5 5l14 14M19 5L5 19','lock'=>'M6 10h12v12H6zM8 10V6a4 4 0 0 1 8 0v4','search'=>'M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14M15 15l7 7','balance'=>'M12 2v20M4 22h16M3 7h18M6 7l-4 9h8zM18 7l-4 9h8z'));
 $aliases=array('requests'=>'clipboard','oversight'=>'clipboard','home'=>'overview','book'=>'study','calendar'=>'timetable','news'=>'requests','login'=>'logout','class'=>'classes','chart'=>'grades','coin'=>'finance','cap'=>'education');$name=$aliases[$name]??$name;$path=$paths[$name]??$paths['study'];return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="'.$path.'"/></svg>';}
// Main brand is independent of the browser site icon. Custom Logo remains editable
// through WordPress Site Identity; the bundled transparent emblem is the default.
function dgs_main_logo_url(){
 $id=absint(get_theme_mod('custom_logo'));if($id&&wp_attachment_is_image($id)){
  $url=wp_get_attachment_image_url($id,'medium')?:wp_get_attachment_url($id);
  if($url)return esc_url_raw($url,array('http','https'));
 }
 static $uri=null;if($uri===null){$file=DGS_PATH.'assets/school-brand-256.png';$data=is_readable($file)?file_get_contents($file):false;$uri=$data!==false?'data:image/png;base64,'.base64_encode($data):'';}return $uri;
}
// Sidebar color variant is independent of the main/footer logo.
function dgs_sidebar_logo_url(){
 $id=absint(get_theme_mod('dgs_sidebar_logo'));if($id&&wp_attachment_is_image($id)){
  $url=wp_get_attachment_image_url($id,'medium')?:wp_get_attachment_url($id);
  if($url)return esc_url_raw($url,array('http','https'));
 }
 static $uri=null;if($uri===null){$file=DGS_PATH.'assets/sidebar-logo-option3-256.png';$data=is_readable($file)?file_get_contents($file):false;$uri=$data!==false?'data:image/png;base64,'.base64_encode($data):dgs_main_logo_url();}return $uri;
}
add_action('customize_register',function($customizer){
 if(!class_exists('WP_Customize_Media_Control'))return;
 $customizer->add_setting('dgs_sidebar_logo',array('sanitize_callback'=>'absint','capability'=>'edit_theme_options'));
 $customizer->add_control(new WP_Customize_Media_Control($customizer,'dgs_sidebar_logo',array('section'=>'dg_school','label'=>'لوگوی سایدبار (پیش‌فرض: طرح ۳)','mime_type'=>'image')));
},20);
function dgs_brand_markup(){return '<a class="dg-brand dgs-school-brand" href="'.esc_url(home_url('/')).'" aria-label="صفحه اصلی مدرسه دانشگران"><img class="dgs-main-logo" src="'.esc_attr(dgs_sidebar_logo_url()).'" alt="نشان مدرسه دانشگران" width="118" height="118" decoding="async"><strong>'.esc_html(dgs_setting('school_name','مدرسه دانشگران')).'</strong><small>دبیرستان دوره دوم</small><span class="dgs-brand-grades">پایه‌های دهم، یازدهم و دوازدهم</span></a>';}
add_action('wp_footer',function(){if(get_template()!=='daneshgaran'&&!dgs_is_role_portal())return;echo '<template id="dgs-portal-brand">'.dgs_brand_markup().'</template>';if(!dgs_is_role_portal())return;$u=wp_get_current_user();$class=dgs_edu_role($u,'dgs_student')?dgs_hw_student_class(dgs_edu_request_year()):null;$role=current_user_can('dgs_manage_education')?'مدیر و معاون':(dgs_edu_role($u,'dgs_counselor')?'مشاور':(dgs_edu_role($u,'dgs_teacher')?'مدرس':($class?'دانش‌آموز پایه '.dgs_edu_grade_label($class->grade):'دانش‌آموز')));echo '<template id="dgs-portal-profile"><span class="dgs-profile-avatar">'.dgs_portal_icon('users').'</span><span class="dgs-profile-label"><strong>'.esc_html($u->display_name).'</strong><small>'.esc_html($role).'</small></span></template>';},10);
add_action('wp_enqueue_scripts',function(){if(get_template()!=='daneshgaran'&&!dgs_is_role_portal())return;$css=str_replace("url('portal-Estedad.ttf')","url('".esc_url_raw(DGS_URL.'assets/portal-Estedad.ttf')."')",file_get_contents(DGS_PATH.'assets/portal-appearance.css'));wp_register_style('dgs-portal-appearance',false,array(),DGS_VERSION);wp_enqueue_style('dgs-portal-appearance');wp_add_inline_style('dgs-portal-appearance',$css);wp_register_script('dgs-portal-appearance',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-portal-appearance');wp_add_inline_script('dgs-portal-appearance',file_get_contents(DGS_PATH.'assets/portal-appearance.js'));},50);

function dgs_visual_icon_name($text,$context='study'){foreach(array('نظارت و انتشار|درخواست|پیش‌ثبت'=>'clipboard','پیشخوان'=>'overview','برنامه'=>'timetable','آموزشی|کلاس|دبیر|پایه'=>'education','شهریه|مالی|پرداخت|حساب|اقساط'=>'finance','حضور|غیاب|تأخیر'=>'attendance','نمر|کارنامه|میانگین'=>'grades','پرونده|مدرک'=>'records','انضباط'=>'balance','کوییز|آزمون|سؤال|پاسخ'=>'quizzes','تکلیف|ارسال'=>'homework','مطالعه'=>'study','مشاور|دانش‌آموز|کاربر'=>'users','برنامه'=>'timetable') as $pattern=>$key)if(preg_match('/'.$pattern.'/u',$text))return $key;return $context;}
function dgs_visual_heading_icons($html,$context='study'){
 return preg_replace_callback('~(<h[23]\b[^>]*>)(.*?)(</h[23]>)~su',function($m)use($context){if(stripos($m[2],'<svg')!==false||stripos($m[2],'<img')!==false)return $m[0];$body=preg_replace('/^\s*[☷▤▦▥▣♧☎⚖◷✓✎☁▷▶]+\s*/u','',$m[2]);$text=wp_strip_all_tags($body);$icon=dgs_visual_icon_name($text,$context);return $m[1].str_replace('<svg ','<svg class="dgs-context-icon" ',dgs_portal_icon($icon)).$body.$m[3];},$html);
}
add_filter('do_shortcode_tag',function($output,$tag){if(strpos($tag,'daneshgaran_')!==0||!is_string($output)||in_array($tag,array('daneshgaran_teachers','daneshgaran_academy','daneshgaran_home','daneshgaran_about'),true))return $output;$context=dgs_is_role_portal()?dgs_portal_tab():'study';return dgs_visual_action_icons(dgs_visual_heading_icons($output,$context));},20,2);
function dgs_visual_action_icons($html){return preg_replace_callback('~(<(button|a)\b[^>]*>)(.*?)(</\2>)~su',function($m){if(stripos($m[3],'<svg')!==false||stripos($m[3],'<img')!==false)return $m[0];$map=array('✓'=>'check','✎'=>'edit','⌕'=>'search','➤'=>'send','↓'=>'file','←'=>'arrow','▣'=>'file','▤'=>'file','↻'=>'refresh');foreach($map as $symbol=>$name){$q=preg_quote($symbol,'/');if(preg_match('/^\s*'.$q.'\s*/u',$m[3]))$body=preg_replace('/^\s*'.$q.'\s*/u','',$m[3],1);elseif(preg_match('/\s*'.$q.'\s*$/u',$m[3]))$body=preg_replace('/\s*'.$q.'\s*$/u','',$m[3],1);else continue;return $m[1].dgs_portal_icon($name).$body.$m[4];}return $m[0];},$html);}
// Only the selected Daneshgaran icon is mapped; a future custom site icon remains untouched.
function dgs_uses_school_site_icon(){if(get_template()!=='daneshgaran')return false;$id=(int)get_option('site_icon');if(!$id)return false;$file=get_attached_file($id);return is_string($file)&&(bool)preg_match('/(?:^|-)daneshgaran-site-icon-512(?:-[0-9]+x[0-9]+)?(?:-[0-9]+)?\.png$/i',wp_basename($file));}
function dgs_school_site_icon_url($size){$supported=array(32,180,192,270,512);if(!in_array((int)$size,$supported,true))return '';return add_query_arg('ver',DGS_VERSION,set_url_scheme(DGS_URL.'assets/site-icons/icon-'.(int)$size.'.png',is_ssl()?'https':null));}
// Preserve WordPress's generated size-specific URLs for custom site icons.
// Never label the original full-size upload as a 32px or 192px thumbnail.
add_filter('get_site_icon_url',function($url,$size,$blog_id){
 if(get_template()!=='daneshgaran'||($blog_id&&is_multisite()&&(int)$blog_id!==get_current_blog_id()))return $url;
 $id=(int)get_option('site_icon');$image=$url;
 if(!$id||dgs_uses_school_site_icon())$image=dgs_school_site_icon_url($size)?:$url;
 if(!$image)return $url;
 $scheme=wp_parse_url(home_url('/'),PHP_URL_SCHEME)?:'https';
 return add_query_arg('dgs_icon',DGS_VERSION,set_url_scheme($image,$scheme));
},100,3);
// Let WordPress print its normal PNG/image tags on all three head hooks. A second
// shortcut .ico candidate can still be forbidden even after the PNG is repaired.

// One shared footer style on every public theme route, including Elementor pages.
add_action('wp_head',function(){
    if(get_template()!=='daneshgaran'||dgs_is_role_portal())return;
    $p=get_option('dgs_pages',array());if(!empty($p['portal'])&&is_page((int)$p['portal']))return;
    echo '<style id="dgs-shared-footer-0141">'.file_get_contents(DGS_PATH.'assets/shared-footer.css').'</style>';
},100);
// Refresh cached public HTML once when the updated plugin is opened by management.
add_action('admin_init',function(){
    if(!current_user_can('manage_options')||get_option('dgs_footer_cache_version')===DGS_VERSION)return;
    if(function_exists('rocket_clean_domain'))rocket_clean_domain();
    update_option('dgs_footer_cache_version',DGS_VERSION,false);
});
