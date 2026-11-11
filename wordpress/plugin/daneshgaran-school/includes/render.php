<?php
if(!defined('ABSPATH'))exit;
function dgs_empty($text){return '<div class="dg-empty-inline">'.esc_html($text).'</div>';}
function dgs_avatar($post_id,$size='medium'){
    return has_post_thumbnail($post_id)?get_the_post_thumbnail($post_id,$size,array('loading'=>'lazy')):'<span class="dg-avatar-fallback" aria-hidden="true">'.dgs_icon('users').'</span>';
}
function dgs_heading($title,$icon='book',$link='',$label='مشاهده همه'){
    return '<div class="dg-section-heading"><h2>'.dgs_icon($icon).esc_html($title).'</h2>'.($link?'<a class="dg-text-link" href="'.esc_url($link).'">'.esc_html($label).dgs_icon('arrow').'</a>':'').'</div>';
}
function dgs_teachers($group='teacher',$limit=8){
    $args=array('post_type'=>'dgs_teacher','post_status'=>'publish','numberposts'=>$limit,'orderby'=>'menu_order title','order'=>'ASC','meta_query'=>array(array('key'=>'_dgs_group','value'=>$group)));
    if($group==='teacher')$args['meta_query']=array('relation'=>'OR',array('key'=>'_dgs_group','value'=>'teacher'),array('key'=>'_dgs_group','compare'=>'NOT EXISTS'));
    $posts=get_posts($args);if(!$posts)return dgs_empty($group==='staff'?'معرفی مدیریت و کادر آموزشی مدرسه به‌زودی در این بخش قرار می‌گیرد.':'معرفی اساتید مدرسه به‌زودی در این بخش قرار می‌گیرد.');
    $out='<div class="dg-people-grid '.($group==='staff'?'dg-staff-grid':'').'">';foreach($posts as $post){$out.='<article class="dg-person"><a href="'.esc_url(get_permalink($post)).'" class="dg-person-image">'.dgs_avatar($post->ID).'</a><h3><a href="'.esc_url(get_permalink($post)).'">'.esc_html($post->post_title).'</a></h3><span class="dg-person-subject">'.esc_html(get_post_meta($post->ID,'_dgs_specialty',true)).'</span>'.($group==='staff'?'<p>'.esc_html(wp_trim_words($post->post_excerpt,18)).'</p>':'').'</article>';}$out.='</div>';return $out;
}
function dgs_news($limit=4,$category=''){
    $args=array('post_type'=>'post','post_status'=>'publish','numberposts'=>$limit,'ignore_sticky_posts'=>true);if($category)$args['category_name']=$category;
    $posts=get_posts($args);if(!$posts)return dgs_empty('اخبار و اطلاعیه‌های تازه مدرسه در این بخش منتشر می‌شوند.');
    $out='<div class="dg-news-list">';foreach($posts as $post){$out.='<article class="dg-news-row"><div><small>'.esc_html(dgs_display_date(get_post_timestamp($post))).'</small><h3><a href="'.esc_url(get_permalink($post)).'">'.esc_html($post->post_title).'</a></h3></div>';if(has_post_thumbnail($post))$out.='<a class="dg-news-thumb" href="'.esc_url(get_permalink($post)).'">'.get_the_post_thumbnail($post,'thumbnail',array('loading'=>'lazy')).'</a>';$out.='</article>';}$out.='</div>';return $out;
}
function dgs_featured(){
    $uid=wp_unique_id('dg-featured-');$periods=array('week'=>'هفته','month'=>'ماه','term'=>'ترم','year'=>'سال');$out='<div data-dg-tabs><div class="dg-tablist" data-dg-tablist role="tablist" aria-label="بازه دانش‌آموز برگزیده">';$i=0;
    foreach($periods as $period=>$label){$out.='<button type="button" id="'.esc_attr($uid.$period.'-tab').'" role="tab" aria-selected="'.($i===0?'true':'false').'" aria-controls="'.esc_attr($uid.$period).'" tabindex="'.($i===0?'0':'-1').'">'.esc_html($label).'</button>';$i++;}$out.='</div>';$i=0;
    foreach($periods as $period=>$label){$out.='<div id="'.esc_attr($uid.$period).'" role="tabpanel" aria-labelledby="'.esc_attr($uid.$period.'-tab').'" '.($i===0?'':'hidden').'>';$posts=get_posts(array('post_type'=>'dgs_star','post_status'=>'publish','numberposts'=>1,'meta_key'=>'_dgs_period','meta_value'=>$period));
        if(!$posts)$out.=dgs_empty('دانش‌آموز برگزیدهٔ '.$label.' پس از اعلام مدرسه اینجا معرفی می‌شود.');else{$p=$posts[0];$out.='<article class="dg-featured-student"><span class="dg-award">'.dgs_icon('star').'</span><div class="dg-featured-photo">'.dgs_avatar($p->ID).'</div><div><h3>'.esc_html($p->post_title).'</h3><p>'.esc_html(get_post_meta($p->ID,'_dgs_star_class',true)).'</p><strong>'.esc_html(get_post_meta($p->ID,'_dgs_achievement',true)).'</strong></div></article>';}
        $out.='</div>';$i++;
    }return $out.'</div>';
}
function dgs_hero_image(){
    $s=get_option('dgs_media',array());if(!empty($s['campus'])&&wp_attachment_is_image((int)$s['campus']))return wp_get_attachment_url((int)$s['campus']);
    return get_template_directory_uri().'/assets/campus.png';
}
function dgs_section($name){
    $out='';
    switch($name){
        case 'teachers':$out=dgs_heading('با اساتید دانشگران آشنا شوید','users',dgs_page_url('teachers'),'مشاهده اساتید').dgs_teachers('teacher',6);break;
        case 'featured':$out=dgs_heading('دانش‌آموز برگزیده','star').dgs_featured();break;
        case 'news':$out='<div class="dg-two-col"><div>'.dgs_heading('اخبار مدرسه','news',dgs_page_url('news')).dgs_news(3,'school-news').'</div><div>'.dgs_heading('اطلاعیه‌های آموزش و پرورش','news',dgs_page_url('news')).dgs_news(3,'ministry-news').'</div></div>';break;
        case 'staff':$out=dgs_heading('مدیریت و کادر آموزشی','users').dgs_teachers('staff',3);break;
        case 'gallery':$out=dgs_heading('فضای یادگیری در دانشگران','book',dgs_page_url('about'),'معرفی مدرسه');$out.='<div class="dg-campus-card"><img src="'.esc_url(dgs_hero_image()).'" alt="تصویر نمایشی مدرسه" loading="lazy"><div><h3>محیطی برای یادگیری و رشد</h3><p>معرفی کلاس‌ها، فضاهای آموزشی، کتابخانه و امکانات مدرسه.</p><a class="dg-btn dg-btn-outline" href="'.esc_url(dgs_page_url('about')).'">بیشتر درباره مدرسه</a></div></div>';break;
        case 'roles':$out=dgs_heading('ورود به سامانه مدرسه','users').'<div class="dg-role-grid">';foreach(array('دانش‌آموز'=>array('book','مشاهده اطلاعات و گزارش‌های شخصی'),'معلم'=>array('users','دسترسی به کلاس‌ها و فعالیت‌های آموزشی'),'مدیر، معاون و مشاور'=>array('home','ورود به حساب کاربری مدرسه')) as $title=>$data)$out.='<article class="dg-role-card">'.dgs_icon($data[0]).'<h3>ورود '.$title.'</h3><p>'.esc_html($data[1]).'</p><a class="dg-btn" href="'.esc_url(dgs_page_url('portal')).'">ورود به پنل '.dgs_icon('arrow').'</a></article>';$out.='</div>';break;
    }
    return $out?'<section class="dg-card dg-home-section">'.$out.'</section>':'';
}
function dgs_home(){ob_start(); ?>
<section class="dg-card dg-hero"><div class="dg-hero-copy"><span class="dg-kicker">دبیرستان دوره دوم • پایه‌های دهم، یازدهم و دوازدهم</span><h1><?php echo esc_html(dgs_setting('school_name','مدرسه دانشگران')); ?></h1><p>محیطی پویا برای آموزش باکیفیت، پرورش استعدادها و آمادگی برای آینده‌ای روشن.</p><div class="dg-hero-actions"><a class="dg-btn" href="<?php echo esc_url(dgs_page_url('admission')); ?>">پیش‌ثبت‌نام مدرسه <?php echo dgs_icon('arrow'); ?></a><a class="dg-btn dg-btn-outline" href="<?php echo esc_url(dgs_page_url('contact')); ?>#counseling">درخواست مشاوره حضوری</a></div></div><div class="dg-hero-image"><img src="<?php echo esc_url(dgs_hero_image()); ?>" alt="تصویر نمایشی مدرسه دانشگران" fetchpriority="high"></div></section>
<section class="dg-card dg-home-section dg-intro"><?php echo dgs_heading('معرفی مدرسه دانشگران','book'); ?><p>در مدرسه دانشگران، آموزش هدفمند و همراهی مستمر، زمینه رشد علمی و فردی دانش‌آموزان را فراهم می‌کند. با برنامه‌های مدرسه، اساتید و محیط آموزشی ما آشنا شوید.</p><a class="dg-text-link" href="<?php echo esc_url(dgs_page_url('about')); ?>">بیشتر درباره مدرسه <?php echo dgs_icon('arrow'); ?></a></section>
<?php foreach(array('teachers','featured','news','staff','gallery','roles') as $section)echo dgs_section($section);return ob_get_clean();}
function dgs_academy(){
    $posts=get_posts(array('post_type'=>'dgs_class','post_status'=>'publish','numberposts'=>24));$out='<div class="dg-page-intro"><span class="dg-kicker">یادگیری عمیق‌تر، مسیر روشن‌تر</span><h2>آموزشگاه و کلاس‌های تقویتی</h2><p>برنامه کلاس‌ها و استاد مربوطه را ببینید و درخواست پیش‌ثبت‌نام خود را ثبت کنید.</p></div>';
    if(!$posts)return $out.dgs_empty('برنامه کلاس‌های تقویتی پس از اعلام مدرسه منتشر می‌شود.');
    $out.='<div class="dg-course-grid">';foreach($posts as $post){$out.='<article class="dg-course-card">';if(has_post_thumbnail($post))$out.=get_the_post_thumbnail($post,'medium_large',array('loading'=>'lazy'));$out.='<div><span class="dg-kicker">'.esc_html(get_post_meta($post->ID,'_dgs_grade',true)).'</span><h3>'.esc_html($post->post_title).'</h3><p>مدرس: '.esc_html(get_post_meta($post->ID,'_dgs_teacher_name',true)).'</p><p>'.esc_html(get_post_meta($post->ID,'_dgs_schedule',true)).'</p><a class="dg-btn" href="'.esc_url(get_permalink($post)).'">برنامه و پیش‌ثبت‌نام</a></div></article>';}$out.='</div>';return $out;
}
function dgs_contact(){
    $out='<div class="dg-contact-grid"><section class="dg-contact-info"><h2>ارتباط با مدرسه</h2>';foreach(array('phone'=>'تلفن دفتر','email'=>'ایمیل','address'=>'نشانی') as $key=>$label){$v=dgs_setting($key);if($v)$out.='<p><strong>'.esc_html($label).': </strong>'.esc_html($v).'</p>';}$out.='<p>برای آشنایی با شرایط مدرسه یا هماهنگی مشاوره حضوری، درخواست خود را ثبت کنید.</p><button type="button" class="dg-btn" data-dg-dialog="dg-counseling-dialog">درخواست مشاوره حضوری</button></section>'.dgs_request_form('contact').'</div>';
    $out.='<dialog id="dg-counseling-dialog" class="dg-dialog"><button type="button" class="dg-dialog-close" data-dg-dismiss aria-label="بستن">×</button>'.dgs_request_form('counseling').'</dialog><section id="counseling" class="dg-counseling-section">'.dgs_heading('درخواست مشاوره حضوری','calendar').'<p>برای ثبت درخواست، روی دکمه بالا بزنید؛ زمان جلسه پس از تماس مدرسه هماهنگ می‌شود.</p></section>';return $out;
}
function dgs_portal(){
    if(is_user_logged_in()&&function_exists('dgs_edu_portal'))return dgs_edu_portal();
    if(!is_user_logged_in()){return '<div class="dg-portal-login"><h2>ورود به سامانه دانشگران</h2><p>با نام کاربری و رمز عبور حساب مدرسه وارد شوید.</p>'.wp_login_form(array('echo'=>false,'redirect'=>dgs_page_url('portal'),'label_username'=>'نام کاربری یا ایمیل','label_password'=>'رمز عبور','label_remember'=>'مرا به خاطر بسپار','label_log_in'=>'ورود به سامانه')).'<a href="'.esc_url(wp_lostpassword_url(dgs_page_url('portal'))).'">بازیابی رمز عبور</a></div>';}
    $u=wp_get_current_user();$roles=array('administrator'=>'مدیر کل','dgs_deputy'=>'معاون مدرسه','dgs_teacher'=>'معلم','dgs_student'=>'دانش‌آموز','dgs_counselor'=>'مشاور');$role='حساب کاربری';foreach($roles as $key=>$label)if(in_array($key,$u->roles,true)){$role=$label;break;}
    $out='<section class="dg-portal-welcome"><span class="dg-kicker">'.esc_html($role).'</span><h2>سلام '.esc_html($u->display_name).'، خوش آمدید</h2><p>به سامانه مدرسه دانشگران خوش آمدید.</p></section><div class="dg-account-card"><h3>اطلاعات حساب من</h3><dl><dt>نام نمایشی</dt><dd>'.esc_html($u->display_name).'</dd><dt>نام کاربری</dt><dd>'.esc_html($u->user_login).'</dd></dl></div><div class="dg-portal-actions">';
    if(current_user_can('manage_options'))$out.='<a class="dg-btn" href="'.esc_url(admin_url('admin.php?page=dgs-setup')).'">مدیریت و راه‌اندازی مدرسه</a>';
    if(current_user_can('edit_dgs_requests'))$out.='<a class="dg-btn dg-btn-outline" href="'.esc_url(admin_url('edit.php?post_type=dgs_request')).'">درخواست‌های دریافتی</a>';
    $out.='<a class="dg-btn dg-btn-outline" href="'.esc_url(wp_logout_url(home_url('/'))).'">خروج از حساب</a></div>';return $out;
}
add_shortcode('daneshgaran_home','dgs_home');
add_shortcode('daneshgaran_section',function($attrs){$a=shortcode_atts(array('name'=>''),$attrs);return dgs_section(sanitize_key($a['name']));});
add_shortcode('daneshgaran_teachers',function(){return dgs_teachers('teacher',48);});
add_shortcode('daneshgaran_staff',function(){return dgs_teachers('staff',12);});
add_shortcode('daneshgaran_academy','dgs_academy');
add_shortcode('daneshgaran_admission',function(){return dgs_request_form('admission');});
add_shortcode('daneshgaran_contact','dgs_contact');
add_shortcode('daneshgaran_portal','dgs_portal');

// تبدیل تاریخ نمایش به هجری شمسی؛ زمان‌های ذخیره‌شده وردپرس بدون تغییر می‌مانند.
function dgs_jalali_parts($gy,$gm,$gd){
    $gdm=array(0,31,59,90,120,151,181,212,243,273,304,334);$gy2=$gm>2?$gy+1:$gy;
    $days=355666+365*$gy+intdiv($gy2+3,4)-intdiv($gy2+99,100)+intdiv($gy2+399,400)+$gd+$gdm[$gm-1];
    $jy=-1595+33*intdiv($days,12053);$days%=12053;$jy+=4*intdiv($days,1461);$days%=1461;
    if($days>365){$jy+=intdiv($days-1,365);$days=($days-1)%365;}
    $jm=$days<186?1+intdiv($days,31):7+intdiv($days-186,30);$jd=1+($days<186?$days%31:($days-186)%30);return array($jy,$jm,$jd);
}
function dgs_fa_digits($value){return strtr((string)$value,array('0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'));}
function dgs_display_date($timestamp=null){
    $dt=(new DateTimeImmutable('@'.($timestamp??time())))->setTimezone(wp_timezone());
    list($jy,$jm,$jd)=dgs_jalali_parts((int)$dt->format('Y'),(int)$dt->format('n'),(int)$dt->format('j'));
    $months=array('','فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند');$week=array('یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه');
    return $week[(int)$dt->format('w')].' '.dgs_fa_digits($jd).' '.$months[$jm].' '.dgs_fa_digits($jy);
}
