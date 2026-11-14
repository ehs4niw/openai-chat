<?php
if (!defined('ABSPATH')) exit;

function dgs_rc_tab() { return current_user_can('dgs_manage_education') ? 'reports' : 'grades'; }
function dgs_rc_url($year, $period='', $student=0, $extra=array()) {
    return add_query_arg(array_merge(array('dg_tab'=>dgs_rc_tab(),'dg_year'=>$year,'grade_period'=>$period,'rc_student'=>$student),$extra),dgs_page_url('portal'));
}
function dgs_rc_icon($key) {
    if($key==='table')return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>';
    return dgs_portal_icon($key);
}
function dgs_rc_number($value) { return $value === null ? '—' : dgs_mg_fmt($value); }
function dgs_rc_delta($current,$previous) {
    if ($current === null || $previous === null) return '<span class="rc-neutral">—</span>';
    $delta = $current-$previous; $class = $delta>0 ? 'rc-up' : ($delta<0 ? 'rc-down' : 'rc-neutral');
    return '<span class="'.$class.'"><bdi>'.esc_html(($delta>0?'+':($delta<0?'−':'')).dgs_mg_fmt(abs($delta))).'</bdi> <span aria-hidden="true">'.($delta>0?'↑':($delta<0?'↓':'—')).'</span></span>';
}
function dgs_rc_select($name,$label,$options,$selected) {
    $h='<label>'.esc_html($label).'<select name="'.esc_attr($name).'">';
    foreach ($options as $key=>$text) $h.='<option value="'.esc_attr($key).'" '.selected((string)$key,(string)$selected,false).'>'.esc_html($text).'</option>';
    return $h.'</select></label>';
}
function dgs_rc_years($student,$selected) {
    global $wpdb; $e=dgs_edu_tables(); $g=dgs_grade_table(); $values=array($selected,dgs_edu_current_year());
    if ($student) {
        $values=array_merge($values,$wpdb->get_col($wpdb->prepare("SELECT school_year FROM {$e['enrollments']} WHERE student_id=%d",$student)),$wpdb->get_col($wpdb->prepare("SELECT DISTINCT c.school_year FROM $g g JOIN {$e['classes']} c ON c.id=g.class_id WHERE g.student_id=%d",$student)));
    } elseif (current_user_can('dgs_manage_education')) $values=array_merge($values,$wpdb->get_col("SELECT DISTINCT school_year FROM {$e['classes']}"));
    else $values=array_merge($values,$wpdb->get_col($wpdb->prepare("SELECT DISTINCT school_year FROM {$e['counseling']} WHERE counselor_id=%d",get_current_user_id())));
    $values=array_values(array_unique(array_filter($values,'dgs_edu_year'))); rsort($values); $out=array(); foreach($values as $v)$out[$v]=dgs_fa_digits($v); return $out;
}
function dgs_rc_staff_selector($year,$student) {
    global $wpdb; $e=dgs_edu_tables(); $page=max(1,min(10000,absint(dgs_att_get('rc_page','1')))); $q=sanitize_text_field(dgs_att_get('rc_search')); $args=array($year);
    $from=" FROM {$e['enrollments']} en JOIN {$wpdb->users} u ON u.ID=en.student_id JOIN {$e['classes']} c ON c.id=en.class_id WHERE en.school_year=%s";
    if (!current_user_can('dgs_manage_education')) { $from.=" AND EXISTS (SELECT 1 FROM {$e['counseling']} a WHERE a.student_id=en.student_id AND a.school_year=en.school_year AND a.counselor_id=%d)"; $args[]=get_current_user_id(); }
    if ($q!=='') { $from.=' AND u.display_name LIKE %s'; $args[]='%'.$wpdb->esc_like($q).'%'; }
    $count=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*)'.$from,$args)); $pages=max(1,(int)ceil($count/20)); $page=min($page,$pages);
    $rows=$wpdb->get_results($wpdb->prepare('SELECT u.ID,u.display_name,c.grade,c.field'.$from.' ORDER BY u.display_name,u.ID LIMIT 20 OFFSET '.(($page-1)*20),$args));
    $h='<details class="rc-card rc-staff rc-no-export" '.(!$student?'open':'').'><summary>'.dgs_rc_icon('users').' انتخاب دانش‌آموز برای مشاهده کارنامه و ثبت بازخورد</summary><form class="rc-staff-search" method="get" action="'.esc_url(dgs_page_url('portal')).'"><input type="hidden" name="dg_tab" value="'.esc_attr(dgs_rc_tab()).'"><input type="hidden" name="dg_year" value="'.esc_attr($year).'"><label>جست‌وجوی نام دانش‌آموز<input name="rc_search" value="'.esc_attr($q).'" maxlength="100"></label><button class="rc-button">جست‌وجو</button></form><div class="rc-student-list">';
    foreach($rows as $r)$h.='<a '.((int)$r->ID===$student?'aria-current="true"':'').' href="'.esc_url(dgs_rc_url($year,'',$r->ID)).'">'.esc_html($r->display_name).'<small>'.esc_html(dgs_edu_grade_label($r->grade).' · '.(dgs_edu_fields()[$r->field]??'')).'</small></a>';
    if(!$rows)$h.='<p>دانش‌آموز تخصیص‌یافته‌ای پیدا نشد.</p>';
    $h.='</div><nav class="rc-pagination" aria-label="صفحات دانش‌آموزان">';
    if($page>1)$h.='<a href="'.esc_url(dgs_rc_url($year,'',0,array('rc_page'=>$page-1,'rc_search'=>$q))).'">صفحه قبل</a>';
    $h.='<span>'.esc_html(dgs_fa_digits($page.' / '.$pages)).'</span>';
    if($page<$pages)$h.='<a href="'.esc_url(dgs_rc_url($year,'',0,array('rc_page'=>$page+1,'rc_search'=>$q))).'">صفحه بعد</a>';
    return $h.'</nav></details>';
}
function dgs_rc_export_url($student,$year,$period,$mode='pdf') {
    return wp_nonce_url(add_query_arg(array('action'=>'dgs_report_export','student'=>$student,'year'=>$year,'period'=>$period,'mode'=>$mode),admin_url('admin-post.php')),'dgs_report_export_'.$student.'_'.$year.'_'.$period);
}
function dgs_rc_avatar($student) {
    // Use the WordPress avatar provider when it has a local image; never invent a student's portrait.
    $url=get_avatar_url($student,array('size'=>160,'default'=>'404')); $local=wp_parse_url($url,PHP_URL_HOST)===wp_parse_url(home_url('/'),PHP_URL_HOST);
    if($local&&$url)return '<img src="'.esc_url($url).'" alt="" width="128" height="128">';
    return '<span aria-hidden="true">'.dgs_rc_icon('users').'</span>';
}
function dgs_rc_feedback_card($bundle,$period,$export=false) {
    $student=$bundle['student']->ID; $year=$bundle['year']; $f=dgs_rc_feedback($student,$year,$period); $author=$f?get_userdata($f->author_id):false;
    $h='<section class="rc-card rc-feedback rc-pdf-block"><h3>'.dgs_rc_icon('message').' بازخورد استاد راهنما</h3>';
    if($f){$h.='<p class="rc-feedback-text">'.nl2br(esc_html($f->note)).'</p><footer>'.dgs_rc_icon('users').'<div><strong>'.esc_html($author?$author->display_name:'کاربر پیشین مدرسه').'</strong><small>'.($author&&user_can($author,'dgs_manage_education')?'مدیریت مدرسه':'استاد راهنما').'</small></div></footer>';}
    else $h.='<p class="rc-empty">هنوز بازخوردی برای این کارنامه ثبت نشده است.</p>';
    if(!$export&&dgs_rc_can_feedback($student,$year)){
        $h.='<details class="rc-feedback-editor rc-no-export"><summary>'.($f?'ویرایش بازخورد':'نوشتن بازخورد برای دانش‌آموز').'</summary><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">'.wp_nonce_field('dgs_report_feedback','rc_nonce',true,false);
        foreach(array('action'=>'dgs_report_feedback','student_id'=>$student,'year'=>$year,'period'=>$period,'revision'=>$f->revision??0) as $k=>$v)$h.='<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'">';
        $h.='<label>متن بازخورد<textarea name="note" maxlength="2000" rows="5" required>'.esc_textarea($f->note??'').'</textarea></label><small>پس از ذخیره، بازخورد با نام شما به دانش‌آموز نمایش داده می‌شود.</small><button class="rc-button">ثبت و نمایش بازخورد</button></form></details>';
    }
    return $h.'</section>';
}
function dgs_rc_trend($bundle,$period) {
    $periods=array($period); for($i=0;$i<3;$i++)array_unshift($periods,dgs_grade_previous($periods[0]));
    $values=array();foreach($periods as $p)if(!empty($bundle['months'][$p]['complete']))$values[]=$bundle['months'][$p]['average'];
    $low=$values?max(0,(int)floor(min($values)/50)*50-50):0;$high=$values?min(2000,(int)ceil(max($values)/50)*50+50):2000;
    if($high-$low<200){$low=max(0,$high-200);$high=min(2000,$low+200);}
    $names=dgs_study_months(); $accessible=array(); $points=array(); $h='<section class="rc-card rc-trend rc-pdf-block"><h3>'.dgs_rc_icon('trend').' روند پیشرفت معدل <small>(چهار ماه اخیر)</small></h3><svg viewBox="0 0 540 250" role="img" aria-labelledby="rc-trend-title"><title id="rc-trend-title">روند معدل چهار ماه اخیر؛ ماه بدون کارنامه منتشرشده خط تیره دارد</title>';
    $span=$high-$low;$step=$span<=300?50:($span<=600?100:($span<=1200?200:500));for($v=$low;$v<=$high;$v+=$step){$y=207-($v-$low)/($high-$low)*160;$h.='<line x1="62" x2="505" y1="'.$y.'" y2="'.$y.'" class="rc-gridline"/><text x="54" y="'.($y+5).'" text-anchor="end">'.esc_html(dgs_rc_number($v)).'</text>';}
    foreach($periods as $i=>$p){$m=$bundle['months'][$p]??null; $score=$m&&$m['complete']?$m['average']:null; $x=80+$i*132; $y=$score===null?null:207-($score-$low)/($high-$low)*160; $points[]=array('x'=>$x,'y'=>$y,'score'=>$score); $label=$names[(int)substr($p,5,2)]??$p; $accessible[]=$label.': '.dgs_rc_number($score);
        $h.='<text x="'.$x.'" y="236" text-anchor="middle">'.esc_html($label).'</text>';
    }
    // Missing months break the line rather than implying a zero or invented score.
    for($i=1;$i<count($points);$i++){ $a=$points[$i-1];$b=$points[$i]; if($a['y']===null||$b['y']===null)continue; $h.='<path d="M '.$a['x'].' 207 L '.$a['x'].' '.$a['y'].' L '.$b['x'].' '.$b['y'].' L '.$b['x'].' 207 Z" fill="#ead8f8"/><line x1="'.$a['x'].'" y1="'.$a['y'].'" x2="'.$b['x'].'" y2="'.$b['y'].'" class="rc-trend-line"/>'; }
    foreach($points as $p){$y=$p['y']??184; if($p['y']!==null)$h.='<circle cx="'.$p['x'].'" cy="'.$p['y'].'" r="5" class="rc-dot"/>';
        $h.='<text x="'.$p['x'].'" y="'.max(24,$y-16).'" text-anchor="middle" class="rc-chart-value">'.esc_html(dgs_rc_number($p['score'])).'</text>'; }
    return $h.'</svg><p class="rc-chart-accessible">'.esc_html(implode('؛ ',$accessible)).'</p></section>';
}
function dgs_rc_content($bundle,$period,$export=false) {
    $student=$bundle['student']; $year=$bundle['year']; $month=$bundle['months'][$period]??null; $complete=$month&&$month['complete'];
    $class=$bundle['catalog']['classes'][$complete?$month['class_id']:$bundle['current_class']]??null;
    $previous=$bundle['months'][dgs_grade_previous($period)]??null; if(!$previous||!$previous['complete'])$previous=null;
    $h='<div class="rc-report-content">';
    if($export)$h.='<div class="rc-export-brand rc-pdf-block"><strong>مدرسه دانشگران</strong><span>کارنامه تحلیلی و مقایسه‌ای · '.esc_html(dgs_mg_label($period)).' · سال تحصیلی '.esc_html(dgs_fa_digits($year)).'</span></div>';
    $ranking=$complete?dgs_rc_ranking($bundle,$period):array('rank'=>null,'count'=>0);
    $h.='<div class="rc-summary rc-pdf-block"><article class="rc-card rc-metric"><span>'.dgs_rc_icon('education').'</span><h3>معدل کل</h3><strong>'.esc_html(dgs_rc_number($complete?$month['average']:null)).'</strong></article><article class="rc-card rc-metric"><span>'.dgs_rc_icon('medal').'</span><h3>رتبه در پایه</h3><strong>'.esc_html($ranking['rank']===null?'—':dgs_fa_digits($ranking['rank'])).'</strong><small>'.($ranking['count']?'از '.esc_html(dgs_fa_digits($ranking['count'])).' کارنامه منتشرشده':'هنوز منتشر نشده').'</small></article><article class="rc-card rc-identity"><div><h3>'.esc_html($student->display_name).'</h3><p>'.esc_html($class?'پایه '.dgs_edu_grade_label($class->grade).' | '.(dgs_edu_fields()[$class->field]??''):'کلاس این سال تعیین نشده').'</p><small>شماره دانش‌آموزی: <bdi>'.esc_html(dgs_fa_digits(get_user_meta($student->ID,'_dgs_student_code',true)?:$student->ID)).'</bdi></small></div><div class="rc-avatar">'.dgs_rc_avatar($student->ID).'</div></article></div>';
    if(!$complete)return $h.'<section class="rc-card rc-empty rc-pdf-block"><h3>کارنامه این ماه هنوز منتشر نشده است</h3><p>پس از نهایی‌شدن همه دروس و تأیید مدیریت، نمرات، معدل و رتبه اینجا نمایش داده می‌شوند.</p></section></div>';
    $h.='<section class="rc-card rc-comparison"><h3>'.dgs_rc_icon('table').' مقایسه نمرات دروس</h3><div class="rc-table-scroll" tabindex="0" aria-label="جدول مقایسه نمرات دروس؛ قابل پیمایش در موبایل"><table class="rc-table"><thead><tr><th scope="col">ردیف</th><th scope="col">نام درس</th><th scope="col">نمره ماه قبل ('.esc_html(dgs_study_months()[(int)substr(dgs_grade_previous($period),5,2)]).')</th><th scope="col">نمره ماه جاری ('.esc_html(dgs_study_months()[(int)substr($period,5,2)]).')</th><th scope="col">تغییر (±)</th></tr></thead><tbody>';
    $i=0;foreach($month['rows'] as $subject=>$row){$old=$previous['rows'][$subject]??null;$before=$old?dgs_mg_cents($old->score):null;$score=dgs_mg_cents($row->score);$h.='<tr><td>'.esc_html(dgs_fa_digits(++$i)).'</td><th scope="row">'.esc_html($row->subject).'</th><td>'.esc_html(dgs_rc_number($before)).'</td><td>'.esc_html(dgs_rc_number($score)).'</td><td>'.dgs_rc_delta($score,$before).'</td></tr>';}
    $h.='</tbody><tfoot><tr><td></td><th scope="row">معدل کل</th><td>'.esc_html(dgs_rc_number($previous['average']??null)).'</td><td>'.esc_html(dgs_rc_number($month['average'])).'</td><td>'.dgs_rc_delta($month['average'],$previous['average']??null).'</td></tr></tfoot></table></div><p class="rc-footnote">میانگین ساده و بدون ضریب از همه دروس این کارنامه؛ رتبه در همان پایه و سال، فقط بین کارنامه‌های کامل و منتشرشده محاسبه می‌شود. معدل برابر، رتبه برابر دارد. خط تیره یعنی ماه قبل کارنامه کامل منتشرشده ندارد.</p></section>';
    $h.='<div class="rc-analysis">'.dgs_rc_feedback_card($bundle,$period,$export).dgs_rc_trend($bundle,$period).'</div>';
    return $h.'</div>';
}
function dgs_rc_archive($bundle) {
    $h='<section class="rc-card rc-archive rc-no-export"><h3>'.dgs_rc_icon('classes').' آرشیو کارنامه‌های منتشرشده</h3><div class="rc-table-scroll" tabindex="0"><table class="rc-table"><thead><tr><th scope="col">ردیف</th><th scope="col">دوره / ماه</th><th scope="col">ترم</th><th scope="col">سال تحصیلی</th><th scope="col">معدل کل</th><th scope="col">عملیات</th></tr></thead><tbody>'; $i=0;
    foreach($bundle['months'] as $p=>$m){if(!$m['complete'])continue;$h.='<tr><td>'.esc_html(dgs_fa_digits(++$i)).'</td><td><a href="'.esc_url(dgs_rc_url($bundle['year'],$p,$bundle['student']->ID)).'">'.esc_html(dgs_study_months()[(int)substr($p,5,2)]).'</a></td><td>'.esc_html(dgs_rc_terms()[dgs_rc_term($p)]).'</td><td>'.esc_html(dgs_fa_digits($bundle['year'])).'</td><td>'.esc_html(dgs_rc_number($m['average'])).'</td><td><a class="rc-button rc-button-small" target="_blank" rel="noopener" href="'.esc_url(dgs_rc_export_url($bundle['student']->ID,$bundle['year'],$p)).'">دانلود PDF '.dgs_rc_icon('download').'</a></td></tr>';}
    if(!$i)$h.='<tr><td colspan="6">هنوز کارنامه کاملی در این سال منتشر نشده است.</td></tr>';
    return $h.'</tbody></table></div></section>';
}
function dgs_rc_panel($year) {
    $staff=current_user_can('dgs_manage_education')||dgs_edu_role(get_current_user_id(),'dgs_counselor');
    if(!$staff&&!dgs_edu_role(get_current_user_id(),'dgs_student'))return '';
    $student=$staff?absint(dgs_att_get('rc_student')):get_current_user_id();
    $h='<div class="rc-page"><header class="rc-heading"><div><h2>'.dgs_rc_icon('grades').' کارنامه تحلیلی و مقایسه‌ای</h2><p>بررسی روند پیشرفت تحصیلی، مقایسه نمرات با ماه قبل و دریافت بازخورد استاد راهنما</p></div><div class="rc-actions" data-rc-actions></div></header>';
    if($staff)$h.=dgs_rc_staff_selector($year,$student);
    $bundle=$student?dgs_rc_bundle($student,$year):null;
    if(is_wp_error($bundle))return $h.'<p class="rc-card rc-empty">'.esc_html($bundle->get_error_message()).'</p></div>';
    $period=dgs_grade_period(dgs_att_get('grade_period'),$year); $term=dgs_att_get('rc_term'); if(!isset(dgs_rc_terms()[$term]))$term='';
    if(!$period||($term&&dgs_rc_term($period)!==$term)){
        $period='';if($bundle)foreach($bundle['months'] as $p=>$m)if($m['complete']&&(!$term||dgs_rc_term($p)===$term)){$period=$p;break;}
        if(!$period){$today=substr(dgs_att_jdate(dgs_mg_now()->format('Y-m-d')),0,7);$period=dgs_grade_period($today,$year)?:substr($year,0,4).'-07';if($term&&dgs_rc_term($period)!==$term)foreach(dgs_mg_periods($year) as $p=>$label)if(dgs_rc_term($p)===$term){$period=$p;break;}}
    }
    $term=$term?:dgs_rc_term($period);
    $h.='<form class="rc-card rc-filters rc-no-export" method="get" action="'.esc_url(dgs_page_url('portal')).'"><input type="hidden" name="dg_tab" value="'.esc_attr(dgs_rc_tab()).'"><input type="hidden" name="rc_student" value="'.(int)$student.'">'.dgs_rc_select('dg_year','سال تحصیلی:',dgs_rc_years($staff?0:$student,$year),$year).dgs_rc_select('rc_term','ترم:',dgs_rc_terms(),$term).'<label>ماه:<select name="grade_period">';
    foreach(dgs_mg_periods($year) as $p=>$label)$h.='<option data-rc-term="'.dgs_rc_term($p).'" value="'.esc_attr($p).'" '.selected($p,$period,false).'>'.esc_html(dgs_study_months()[(int)substr($p,5,2)]).'</option>';
    $h.='</select></label><button class="rc-button rc-filter-submit">نمایش</button></form>';
    if(!$bundle)return $h.'<p class="rc-card rc-empty">برای مشاهده کارنامه، یک دانش‌آموز را از فهرست بالا انتخاب کنید.</p></div>';
    $month=$bundle['months'][$period]??null; $complete=$month&&$month['complete'];
    if($complete){$links='<a class="rc-button" target="_blank" rel="noopener" href="'.esc_url(dgs_rc_export_url($student,$year,$period)).'">'.dgs_rc_icon('download').' دانلود PDF</a><a class="rc-button" target="_blank" rel="noopener" href="'.esc_url(dgs_rc_export_url($student,$year,$period,'print')).'">'.dgs_rc_icon('printer').' چاپ</a>';
        $h.='<div class="rc-mobile-actions rc-no-export" data-rc-move-actions>'.$links.'</div>';
    }
    $h.=dgs_rc_content($bundle,$period).dgs_rc_archive($bundle);
    if(!$staff)$h.='<details class="rc-card rc-raw rc-no-export"><summary>'.dgs_rc_icon('clipboard').' مشاهده نمره‌ها و رویدادهای ثبت‌شده</summary><p class="rc-footnote">این سوابق ثبت روزانه‌اند؛ نمره نهایی کارنامه فقط پس از انتشار مدیریت نمایش داده می‌شود.</p>'.dgs_mg_reader($year,$period).'</details>';
    return $h.'</div>';
}
function dgs_edu_view_reports($year) { return dgs_rc_panel($year); }

add_filter('body_class',function($classes){if(dgs_is_role_portal()&&(dgs_portal_tab()==='reports'||(dgs_portal_tab()==='grades'&&!current_user_can('dgs_manage_education')&&!dgs_edu_role(get_current_user_id(),'dgs_teacher'))))$classes[]='dgs-reportcard';return $classes;});
add_action('wp_enqueue_scripts',function(){
    if(!dgs_is_role_portal()||!(dgs_portal_tab()==='reports'||(dgs_portal_tab()==='grades'&&!current_user_can('dgs_manage_education')&&!dgs_edu_role(get_current_user_id(),'dgs_teacher'))))return;
    wp_enqueue_style('dgs-reportcard',DGS_URL.'assets/reportcard.css',array(),DGS_VERSION);
    wp_enqueue_script('dgs-reportcard',DGS_URL.'assets/reportcard.js',array(),DGS_VERSION,true);
},65);

add_action('admin_post_dgs_report_export',function(){
    $student=absint(dgs_att_get('student'));$year=dgs_edu_year(dgs_att_get('year'));$period=$year?dgs_grade_period(dgs_att_get('period'),$year):'';
    if(!$year||!$period||!dgs_rc_allowed($student,$year))wp_die('اجازه دریافت این کارنامه را ندارید.','',array('response'=>403));
    check_admin_referer('dgs_report_export_'.$student.'_'.$year.'_'.$period);
    $bundle=dgs_rc_bundle($student,$year);$month=is_wp_error($bundle)?null:($bundle['months'][$period]??null);
    if(!$month||!$month['complete'])wp_die('این کارنامه منتشر نشده یا برای بازبینی از انتشار خارج شده است.','',array('response'=>404));
    $mode=dgs_att_get('mode')==='print'?'print':'pdf';
    if(!defined('DONOTCACHEPAGE'))define('DONOTCACHEPAGE',true);nocache_headers();header('Cache-Control: private, no-store, max-age=0');header('X-Robots-Tag: noindex, nofollow');header('X-Content-Type-Options: nosniff');header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.esc_html('کارنامه '.$bundle['student']->display_name.' · '.dgs_mg_label($period)).'</title><link rel="stylesheet" href="'.esc_url(DGS_URL.'assets/reportcard.css?ver='.DGS_VERSION).'"></head><body class="rc-export" data-rc-export="'.esc_attr($mode).'" data-rc-filename="report-'.$student.'-'.$period.'.pdf"><div class="rc-export-tools rc-no-export"><p role="status" data-rc-status>در حال آماده‌سازی کارنامه…</p><button class="rc-button" type="button" data-rc-pdf>دانلود PDF</button><button class="rc-button" type="button" data-rc-print>چاپ</button><small>اگر دانلود خودکار شروع نشد، دکمه دانلود PDF را بزنید.</small></div><main class="rc-page" id="rc-export-document">'.dgs_rc_content($bundle,$period,true).'</main>';
    echo '<script src="'.esc_url(DGS_URL.'assets/vendor/html2canvas.min.js').'"></script><script src="'.esc_url(DGS_URL.'assets/vendor/pdf-lib.min.js').'"></script><script src="'.esc_url(DGS_URL.'assets/reportcard.js?ver='.DGS_VERSION).'"></script></body></html>';exit;
});
