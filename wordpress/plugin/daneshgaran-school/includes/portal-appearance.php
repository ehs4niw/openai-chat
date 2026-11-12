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
 'send'=>'M22 2L2 10l8 4 4 8zM10 14L22 2');$paths=array_merge($paths,array('edit'=>'M16 3l5 5-12 12H4v-5zM14 5l5 5','mail'=>'M3 5h18v14H3zM3 5l9 8 9-8','star'=>'M12 2l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z','arrow'=>'M20 12H4M10 6l-6 6 6 6','menu'=>'M3 6h18M3 12h18M3 18h18','bell'=>'M5 17h14l-2-4V9a5 5 0 0 0-10 0v4zM10 21h4','pin'=>'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0M9 10a3 3 0 1 0 6 0 3 3 0 0 0-6 0','phone'=>'M5 3h4l2 5-3 2c2 4 3 5 6 6l2-3 5 2v4c-1 4-7 1-11-2S2 8 3 5z','medal'=>'M8 2h8l-1 7H9zM12 9a6 6 0 1 0 0 12 6 6 0 0 0 0-12M9 20v3l3-2 3 2v-3','close'=>'M5 5l14 14M19 5L5 19','lock'=>'M6 10h12v12H6zM8 10V6a4 4 0 0 1 8 0v4','search'=>'M10 3a7 7 0 1 0 0 14 7 7 0 0 0 0-14M15 15l7 7','balance'=>'M12 2v20M4 22h16M3 7h18M6 7l-4 9h8zM18 7l-4 9h8z'));
 $aliases=array('home'=>'overview','book'=>'study','calendar'=>'timetable','news'=>'requests','login'=>'logout','class'=>'classes','chart'=>'grades','coin'=>'finance','cap'=>'education');$name=$aliases[$name]??$name;$path=$paths[$name]??$paths['study'];return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="'.$path.'"/></svg>';}
function dgs_brand_markup(){return '<a class="dg-brand dgs-school-brand" href="'.esc_url(home_url('/')).'" aria-label="صفحه اصلی مدرسه دانشگران">'.file_get_contents(DGS_PATH.'assets/portal-logo.svg').'<strong>'.esc_html(dgs_setting('school_name','مدرسه دانشگران')).'</strong><small>دبیرستان دوره دوم</small><span class="dgs-brand-grades">پایه‌های دهم، یازدهم و دوازدهم</span></a>';}
add_action('wp_footer',function(){if(get_template()!=='daneshgaran'&&!dgs_is_role_portal())return;echo '<template id="dgs-portal-brand">'.dgs_brand_markup().'</template>';if(!dgs_is_role_portal())return;$u=wp_get_current_user();$class=dgs_edu_role($u,'dgs_student')?dgs_hw_student_class(dgs_edu_request_year()):null;$role=current_user_can('dgs_manage_education')?'مدیر و معاون':(dgs_edu_role($u,'dgs_counselor')?'مشاور':(dgs_edu_role($u,'dgs_teacher')?'مدرس':($class?'دانش‌آموز پایه '.dgs_edu_grade_label($class->grade):'دانش‌آموز')));echo '<template id="dgs-portal-profile"><span class="dgs-profile-avatar">'.dgs_portal_icon('users').'</span><span class="dgs-profile-label"><strong>'.esc_html($u->display_name).'</strong><small>'.esc_html($role).'</small></span></template>';},10);
add_action('wp_enqueue_scripts',function(){if(get_template()!=='daneshgaran'&&!dgs_is_role_portal())return;$css=str_replace("url('portal-Estedad.ttf')","url('".esc_url_raw(DGS_URL.'assets/portal-Estedad.ttf')."')",file_get_contents(DGS_PATH.'assets/portal-appearance.css'));wp_register_style('dgs-portal-appearance',false,array(),DGS_VERSION);wp_enqueue_style('dgs-portal-appearance');wp_add_inline_style('dgs-portal-appearance',$css);wp_register_script('dgs-portal-appearance',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-portal-appearance');wp_add_inline_script('dgs-portal-appearance',file_get_contents(DGS_PATH.'assets/portal-appearance.js'));},50);

function dgs_visual_icon_name($text,$context='study'){foreach(array('پیشخوان'=>'overview','برنامه'=>'timetable','آموزشی|کلاس|دبیر|پایه'=>'education','شهریه|مالی|پرداخت|حساب|اقساط'=>'finance','حضور|غیاب|تأخیر'=>'attendance','نمر|کارنامه|میانگین'=>'grades','پرونده|مدرک'=>'records','انضباط'=>'balance','کوییز|آزمون|سؤال|پاسخ'=>'quizzes','تکلیف|ارسال'=>'homework','مطالعه'=>'study','مشاور|دانش‌آموز|کاربر'=>'users','برنامه'=>'timetable') as $pattern=>$key)if(preg_match('/'.$pattern.'/u',$text))return $key;return $context;}
function dgs_visual_heading_icons($html,$context='study'){
 return preg_replace_callback('~(<h[23]\b[^>]*>)(.*?)(</h[23]>)~su',function($m)use($context){if(stripos($m[2],'<svg')!==false||stripos($m[2],'<img')!==false)return $m[0];$body=preg_replace('/^\s*[☷▤▦▥▣♧☎⚖◷✓✎☁▷▶]+\s*/u','',$m[2]);$text=wp_strip_all_tags($body);$icon=dgs_visual_icon_name($text,$context);return $m[1].str_replace('<svg ','<svg class="dgs-context-icon" ',dgs_portal_icon($icon)).$body.$m[3];},$html);
}
add_filter('do_shortcode_tag',function($output,$tag){if(strpos($tag,'daneshgaran_')!==0||!is_string($output))return $output;$context=dgs_is_role_portal()?dgs_portal_tab():'study';return dgs_visual_action_icons(dgs_visual_heading_icons($output,$context));},20,2);
add_action('wp_head',function(){if(get_template()==='daneshgaran'&&!has_site_icon())echo '<link rel="icon" type="image/svg+xml" href="'.esc_url(DGS_URL.'assets/portal-logo.svg').'">';},100);
function dgs_visual_action_icons($html){return preg_replace_callback('~(<(button|a)\b[^>]*>)(.*?)(</\2>)~su',function($m){if(stripos($m[3],'<svg')!==false||stripos($m[3],'<img')!==false)return $m[0];$map=array('✓'=>'check','✎'=>'edit','⌕'=>'search','➤'=>'send','↓'=>'file','←'=>'arrow','▣'=>'file','▤'=>'file','↻'=>'refresh');foreach($map as $symbol=>$name){$q=preg_quote($symbol,'/');if(preg_match('/^\s*'.$q.'\s*/u',$m[3]))$body=preg_replace('/^\s*'.$q.'\s*/u','',$m[3],1);elseif(preg_match('/\s*'.$q.'\s*$/u',$m[3]))$body=preg_replace('/\s*'.$q.'\s*$/u','',$m[3],1);else continue;return $m[1].dgs_portal_icon($name).$body.$m[4];}return $m[0];},$html);}
