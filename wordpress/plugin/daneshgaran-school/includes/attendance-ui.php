<?php
if(!defined('ABSPATH'))exit;
function dgs_att_get($key,$default=''){return isset($_GET[$key])&&is_string($_GET[$key])?sanitize_text_field(wp_unslash($_GET[$key])):$default;}
function dgs_att_months(){return array(1=>'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند');}
function dgs_att_url($year,$args=array()){return add_query_arg(array_merge(array('dg_tab'=>'attendance','dg_year'=>$year),$args),dgs_page_url('portal'));}
function dgs_att_default_day($year){$j=dgs_att_jdate(dgs_att_today());return dgs_att_parse_day($j,$year)?$j:substr($year,0,4).'-07-01';}
function dgs_att_range($day,$mode){
    $d=new DateTimeImmutable($day,wp_timezone());
    if($mode==='week'){$offset=((int)$d->format('w')+1)%7;$start=$d->modify('-'.$offset.' days');return array($start->format('Y-m-d'),$start->modify('+6 days')->format('Y-m-d'));}
    if($mode==='month'){$j=explode('-',dgs_att_jdate($day));$start=dgs_att_gregorian((int)$j[0],(int)$j[1],1);$m=(int)$j[1]+1;$y=(int)$j[0];if($m===13){$m=1;$y++;}$next=dgs_att_gregorian($y,$m,1);return array($start,(new DateTimeImmutable($next,wp_timezone()))->modify('-1 day')->format('Y-m-d'));}
    return array($day,$day);
}
function dgs_att_badge($status){$labels=array('present'=>'✓ حاضر','absent'=>'× غایب','late'=>'◷ تأخیر','closed'=>'— تعطیل','unknown'=>'ثبت نشده');return '<span class="dga-badge dga-'.esc_attr($status).'">'.esc_html($labels[$status]??$labels['unknown']).'</span>';}
function dgs_att_heading($student=false){return '<header class="dga-heading"><div><span class="dga-eyebrow">'.($student?'صفحه اصلی ‹ سوابق حضور و غیاب':'مدیریت مدرسه ‹ حضور و غیاب').'</span><h2>'.dgs_icon('calendar').' '.($student?'سوابق حضور و غیاب':'ثبت و گزارش حضور و غیاب').'</h2><p>'.($student?'مشاهده وضعیت حضور، غیبت و تأخیر شما در روزهای مدرسه':'ثبت، ویرایش و گزارش‌گیری روزانه از حضور و غیاب دانش‌آموزان').'</p></div>'.(!$student?'<span class="dga-lock">🔒 ویرایش فقط برای مدیر و معاون</span>':'').'</header>';}
function dgs_att_cards($counts,$student=false){$out='<div class="dga-stats">';foreach(array('present'=>array('حاضر','✓'),'absent'=>array('غایب','×'),'late'=>array('تأخیر','◷')) as $status=>$a)$out.='<article class="dga-'.$status.'"><span class="dga-stat-icon">'.$a[1].'</span><div><span>'.$a[0].'</span><strong data-att-count="'.$status.'">'.esc_html(dgs_fa_digits($counts[$status]??0)).'</strong><small>'.($student?'روز ثبت‌شده':'نفر در روز انتخاب‌شده').'</small></div></article>';return $out.'</div>';}
function dgs_edu_view_attendance($year){return dgs_att_manager($year);}
function dgs_att_manager($year){
    if(!current_user_can('dgs_manage_education'))return '';
    global $wpdb;$t=dgs_att_tables();$e=dgs_edu_tables();$raw=dgs_att_get('att_day',dgs_att_default_day($year));$day=dgs_att_parse_day($raw,$year);if(!$day)$day=dgs_att_parse_day(dgs_att_default_day($year),$year);$jday=dgs_att_jdate($day);
    $mode=dgs_att_get('att_mode','day');if(!in_array($mode,array('day','week','month'),true))$mode='day';$grade=absint(dgs_att_get('att_grade'));if(!in_array($grade,array(10,11,12),true))$grade=0;
    $classes=dgs_edu_classes($year);$class_id=absint(dgs_att_get('att_class'));$class=null;foreach($classes as $c)if((int)$c->id===$class_id&&(!$grade||(int)$c->grade===$grade)){$class=$c;$grade=(int)$c->grade;}
    if(!$class)foreach($classes as $c)if(!$grade||(int)$c->grade===$grade){$class=$c;$class_id=(int)$c->id;break;}
    $out='<div class="dga-attendance" data-attendance-root>'.dgs_att_heading().'<section class="dga-card dga-filters"><form method="get" action="'.esc_url(dgs_page_url('portal')).'" class="dge-filter"><input type="hidden" name="dg_tab" value="attendance"><label class="dga-year-label">سال تحصیلی<input name="dg_year" dir="ltr" pattern="14[0-9]{2}-14[0-9]{2}" value="'.esc_attr($year).'" required></label><label>تاریخ شمسی<input name="att_day" value="'.esc_attr($jday).'" dir="ltr" placeholder="1405-08-20" required></label>'.dgs_edu_select('att_grade','پایه',array(''=>'همه پایه‌ها',10=>'دهم',11=>'یازدهم',12=>'دوازدهم'),$grade,false);
    $options=array();foreach($classes as $c)if(!$grade||(int)$c->grade===$grade)$options[$c->id]=dgs_edu_grade_label($c->grade).' — '.$c->title;
    $out.=dgs_edu_select('att_class','کلاس',$options,$class_id,false).'<button class="dg-btn">نمایش</button></form><nav class="dga-view-tabs" aria-label="بازه گزارش">';foreach(array('day'=>'روزانه','week'=>'هفتگی','month'=>'ماهانه') as $key=>$label)$out.='<a '.($mode===$key?'aria-current="page"':'').' href="'.esc_url(dgs_att_url($year,array('att_day'=>$jday,'att_class'=>$class_id,'att_grade'=>$grade,'att_mode'=>$key))).'">'.$label.'</a>';
    $out.='</nav></section>';
    if(!$class)return $out.'<section class="dga-card">ابتدا کلاس و دانش‌آموزان آن را تعریف کنید.</section></div>';
    if($mode==='day')$out.=dgs_att_editor($class,$day,$year);else $out.=dgs_att_report($class,$day,$mode,$year);
    $out.=dgs_att_insights($classes,$grade,$day,$year).'<footer class="dga-actions"><button type="button" class="dg-btn dg-btn-outline" data-att-print>چاپ گزارش حضور و غیاب</button><button type="button" class="dg-btn dg-btn-outline" disabled title="پس از اتصال سرویس پیامک اضافه می‌شود">اطلاع‌رسانی به والدین — به‌زودی</button></footer></div>';return $out;
}
function dgs_att_editor($class,$day,$year){
    global $wpdb;$t=dgs_att_tables();$session=dgs_att_session($class->id,$day);$roster=dgs_att_roster($class->id,$day);$rows=$session?$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['records']} WHERE session_id=%d",$session->id),OBJECT_K):array();$records=array();$counts=array('present'=>0,'absent'=>0,'late'=>0);
    foreach($rows as $r){$records[$r->student_id]=$r;if(isset($counts[$r->status]))$counts[$r->status]++;}
    $out=dgs_att_cards($counts).'<section class="dga-card"><div class="dga-section-head"><h3>'.dgs_icon('users').' ثبت حضور و غیاب کلاس '.esc_html($class->title).'</h3><button type="button" class="dga-outline" data-att-all>✓ علامت‌گذاری همه به عنوان حاضر</button></div>';
    if(count($roster)>200)return $out.'<p>این فرم حداکثر ۲۰۰ دانش‌آموز را پشتیبانی می‌کند.</p></section>';
    $out.='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="dge-form dga-editor">'.wp_nonce_field('dgs_attendance','dgs_att_nonce',true,false);
    foreach(array('action'=>'dgs_attendance','year'=>$year,'class_id'=>$class->id,'day'=>dgs_att_jdate($day),'revision'=>$session?$session->revision:0,'roster_hash'=>dgs_att_roster_hash($class->id,$day,$roster)) as $k=>$v)$out.='<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'">';
    $out.='<div class="dga-closure"><label><input type="checkbox" name="closed" value="1" data-att-closed '.checked($session&&!empty($session->closed),true,false).'> این روز برای این کلاس تعطیل / بدون کلاس است</label><label>دلیل تعطیلی<input name="closure_note" maxlength="250" value="'.esc_attr($session?$session->closure_note:'').'" placeholder="مثلاً تعطیلی رسمی یا لغو کلاس"></label></div><p class="dga-help">هیچ‌کس خودکار حاضر ثبت نمی‌شود. انتخاب تعطیلی، وضعیت‌های قبلی این کلاس در همین روز را جایگزین می‌کند.</p>';
    $out.='<div class="dge-table-wrap"><table class="dge-table dga-register"><thead><tr><th>ردیف</th><th>نام و نام خانوادگی</th><th>وضعیت حضور</th><th>دقایق تأخیر</th><th>توضیحات</th></tr></thead><tbody>';
    $n=0;foreach($roster as $u){$n++;$r=$records[$u->ID]??null;$prefix='attendance['.$u->ID.']';$out.='<tr data-att-row><td>'.esc_html(dgs_fa_digits($n)).'</td><td class="dga-name">'.esc_html($u->display_name).'</td><td><div class="dga-choices" role="group" aria-label="وضعیت '.esc_attr($u->display_name).'">';
        foreach(array('present'=>'✓ حاضر','late'=>'◷ تأخیر','absent'=>'× غایب') as $status=>$label)$out.='<label><input type="radio" name="'.$prefix.'[status]" value="'.$status.'" '.checked($r?$r->status:'',$status,false).'><span class="dga-'.$status.'">'.$label.'</span></label>';
        $out.='</div></td><td><input type="number" min="0" max="600" step="1" name="'.$prefix.'[minutes]" value="'.($r?(int)$r->minutes:0).'" aria-label="دقایق تأخیر '.esc_attr($u->display_name).'" data-att-minutes></td><td><input name="'.$prefix.'[note]" value="'.esc_attr($r?$r->note:'').'" maxlength="250" aria-label="توضیح '.esc_attr($u->display_name).'" placeholder="یادداشت در صورت نیاز"></td></tr>';
    }
    $out.='</tbody></table></div><div class="dga-save-line"><button class="dg-btn" '.($day>dgs_att_today()?'disabled':'').'>✓ ذخیره حضور و غیاب</button><span>جمع‌بندی کلاس: '.esc_html(dgs_fa_digits(count($roster))).' دانش‌آموز <span data-att-pending></span></span></div></form></section>';return $out;
}
function dgs_att_report($class,$day,$mode,$year){
    global $wpdb;$t=dgs_att_tables();list($start,$end)=dgs_att_range($day,$mode);$first=dgs_att_parse_day(substr($year,0,4).'-07-01',$year);$last=dgs_att_gregorian((int)substr($year,5,4),7,1);$start=max($start,$first);$end=min($end,(new DateTimeImmutable($last,wp_timezone()))->modify('-1 day')->format('Y-m-d'));
    $rows=$wpdb->get_results($wpdb->prepare("SELECT s.school_day,s.closed,s.closure_note,r.status,r.minutes,r.note,u.display_name FROM {$t['sessions']} s LEFT JOIN {$t['records']} r ON r.session_id=s.id LEFT JOIN {$wpdb->users} u ON u.ID=r.student_id WHERE s.class_id=%d AND s.school_day BETWEEN %s AND %s ORDER BY s.school_day DESC,u.display_name LIMIT 2001",$class->id,$start,$end));
    $out='<section class="dga-card"><h3>گزارش '.($mode==='week'?'هفتگی':'ماهانه').' کلاس '.esc_html($class->title).'</h3><p>'.esc_html(dgs_fa_digits(dgs_att_jdate($start).' تا '.dgs_att_jdate($end))).' — روزهای بدون ثبت به عنوان حضور محسوب نمی‌شوند.</p>';
    if(count($rows)>2000)$out.='<p class="dge-error">گزارش طولانی است؛ فقط ۲۰۰۰ ردیف نخست نمایش داده می‌شود.</p>';
    $out.=dgs_edu_table_start(array('تاریخ','دانش‌آموز','وضعیت','دقایق تأخیر','توضیحات'));
    foreach(array_slice($rows,0,2000) as $r)$out.='<tr><td>'.esc_html(dgs_fa_digits(dgs_att_jdate($r->school_day))).'</td><td>'.esc_html($r->display_name??'کل کلاس').'</td><td>'.dgs_att_badge($r->closed?'closed':$r->status).'</td><td>'.($r->status==='late'?esc_html(dgs_fa_digits($r->minutes)):'—').'</td><td>'.esc_html($r->closed?$r->closure_note:($r->note??'')).'</td></tr>';
    $out.='</tbody></table></div>';if(!$rows)$out.='<p>در این بازه سابقه‌ای ثبت نشده است.</p>';return $out.'</section>';
}
function dgs_att_insights($classes,$grade,$day,$year){
    global $wpdb;$t=dgs_att_tables();$e=dgs_edu_tables();$start=(new DateTimeImmutable($day,wp_timezone()))->modify('-29 days')->format('Y-m-d');
    $warnings=$wpdb->get_results($wpdb->prepare("SELECT r.student_id,u.display_name,c.title,COUNT(*) AS n FROM {$t['records']} r JOIN {$t['sessions']} s ON s.id=r.session_id JOIN {$e['classes']} c ON c.id=s.class_id JOIN {$wpdb->users} u ON u.ID=r.student_id WHERE s.school_day BETWEEN %s AND %s AND c.school_year=%s AND r.status='absent' AND s.closed=0 GROUP BY r.student_id,u.display_name,c.title HAVING COUNT(*)>=3 ORDER BY n DESC LIMIT 30",$start,$day,$year));
    $out='<div class="dga-insights"><section class="dga-card"><h3>⚠ هشدار غیبت‌های تکراری</h3><p class="dga-help">۳ غیبت یا بیشتر در ۳۰ روز منتهی به تاریخ انتخاب‌شده؛ حداکثر ۳۰ مورد.</p>';if(!$warnings)$out.='<p class="dga-empty">موردی برای این بازه ثبت نشده است.</p>';
    foreach($warnings as $w)$out.='<div class="dga-list-row"><strong>'.esc_html($w->display_name).'</strong><span>'.esc_html($w->title).'</span><span class="dga-badge dga-absent">'.esc_html(dgs_fa_digits($w->n)).' غیبت</span></div>';
    $daily=$wpdb->get_results($wpdb->prepare("SELECT s.class_id,s.closed,COUNT(r.id) AS n,SUM(CASE WHEN r.status IN ('present','late') THEN 1 ELSE 0 END) AS attended FROM {$t['sessions']} s LEFT JOIN {$t['records']} r ON r.session_id=s.id WHERE s.school_day=%s GROUP BY s.id,s.class_id,s.closed",$day),OBJECT_K);
    $out.='</section><section class="dga-card dga-comparison"><h3>▥ مقایسه وضعیت حضور کلاس‌ها</h3><p class="dga-help">روز انتخاب‌شده؛ حاضر و تأخیر از مجموع وضعیت‌های ثبت‌شده. کلاس بدون ثبت نرخ صفر ندارد.</p><div class="dga-bars">';$missing=array();$shown=0;
    foreach($classes as $c){if($grade&&(int)$c->grade!==$grade)continue;$s=$daily[$c->id]??null;$count=$s?(int)$s->n:0;$att=$s?(int)$s->attended:0;
        if(!$s||(!$s->closed&&$count<(int)$c->students_count))$missing[]=$c;
        if($shown++<12){$rate=$count?(int)round($att/$count*100):null;$out.='<div class="dga-bar-col"><strong>'.($s&&$s->closed?'تعطیل':($rate===null?'—':esc_html(dgs_fa_digits($rate)).'٪')).'</strong><div class="dga-bar-track"><i style="height:'.($rate===null?0:$rate).'%"></i></div><span>'.esc_html(dgs_edu_grade_label($c->grade).' '.$c->title).'</span></div>';}
    }
    $out.='</div><p class="dga-help">نمودار حداکثر ۱۲ کلاس از پایه انتخاب‌شده را نشان می‌دهد.</p></section><section class="dga-card"><h3>◷ کلاس‌های بدون ثبت کامل حضور و غیاب</h3>';if(!$missing)$out.='<p class="dga-empty">ثبت کلاس‌های این پایه کامل است.</p>';
    foreach($missing as $c)$out.='<a class="dga-list-row" href="'.esc_url(dgs_att_url($year,array('att_day'=>dgs_att_jdate($day),'att_class'=>$c->id))).'"><strong>'.esc_html($c->title).'</strong><span>'.esc_html(dgs_edu_grade_label($c->grade)).'</span><span class="dga-badge dga-late">ثبت نشده / ناقص</span></a>';
    return $out.'</section></div>';
}
function dgs_att_student_days($student,$year,$start,$end){
    // شناسه دانش‌آموز از نشست گرفته می‌شود؛ پارامتر URL حق انتخاب پرونده دیگران نمی‌دهد.
    if(!dgs_edu_role(get_current_user_id(),'dgs_student')||get_current_user_id()!==(int)$student)return array();
    global $wpdb;$t=dgs_att_tables();$e=dgs_edu_tables();
    return $wpdb->get_results($wpdb->prepare("SELECT s.school_day,s.closed,s.closure_note,c.title,r.status,r.minutes,r.note FROM {$t['sessions']} s JOIN {$e['classes']} c ON c.id=s.class_id LEFT JOIN {$t['records']} r ON r.session_id=s.id AND r.student_id=%d WHERE c.school_year=%s AND s.school_day BETWEEN %s AND %s AND (r.student_id=%d OR (s.closed=1 AND EXISTS (SELECT 1 FROM {$e['enrollments']} e WHERE e.class_id=s.class_id AND e.student_id=%d AND e.school_year=%s))) ORDER BY s.school_day",$student,$year,$start,$end,$student,$student,$year));
}
function dgs_att_student($year){
    if(!dgs_edu_role(get_current_user_id(),'dgs_student'))return '';
    $default=substr(dgs_att_default_day($year),0,7);$period=dgs_att_get('att_month',$default);$start=dgs_att_parse_day($period.'-01',$year);if(!$start){$period=$default;$start=dgs_att_parse_day($period.'-01',$year);}list($start,$end)=dgs_att_range($start,'month');
    $y=(int)substr($period,0,4);$m=(int)substr($period,5,2);$days=(int)((new DateTimeImmutable($start,wp_timezone()))->diff(new DateTimeImmutable($end,wp_timezone()))->days)+1;$months=dgs_att_months();$title=$months[$m].' '.dgs_fa_digits($y);
    $records=dgs_att_student_days(get_current_user_id(),$year,$start,$end);$byday=array();$counts=array('present'=>0,'absent'=>0,'late'=>0);foreach($records as $r){$j=dgs_att_jdate($r->school_day);$byday[(int)substr($j,8,2)]=$r;if(!$r->closed&&isset($counts[$r->status]))$counts[$r->status]++;}
    $out='<div class="dga-attendance" data-attendance-root>'.dgs_att_heading(true).'<section class="dga-card"><form method="get" action="'.esc_url(dgs_page_url('portal')).'" class="dge-filter"><input type="hidden" name="dg_tab" value="attendance"><label class="dga-year-label">سال تحصیلی<input name="dg_year" dir="ltr" pattern="14[0-9]{2}-14[0-9]{2}" value="'.esc_attr($year).'" required></label>';
    $options=array();foreach(array(7,8,9,10,11,12,1,2,3,4,5,6) as $month){$my=(int)substr($year,$month>=7?0:5,4);$options[sprintf('%04d-%02d',$my,$month)]=$months[$month].' '.dgs_fa_digits($my);}$out.=dgs_edu_select('att_month','فیلتر بازه زمانی',$options,$period).'<button class="dg-btn">⌕ نمایش سوابق</button></form></section>'.dgs_att_cards($counts,true);
    $out.='<div class="dga-calendar-layout"><section class="dga-card dga-calendar-card"><header class="dga-section-head">';
    $keys=array_keys($options);$index=array_search($period,$keys,true);foreach(array(-1,1) as $offset){$link=isset($keys[$index+$offset])?dgs_att_url($year,array('att_month'=>$keys[$index+$offset])):'';if($offset===1)$out.='<h3>'.esc_html($title).'</h3>';$out.=$link?'<a class="dga-month-nav" aria-label="'.($offset<0?'ماه قبل':'ماه بعد').'" href="'.esc_url($link).'">'.($offset<0?'‹':'›').'</a>':'<span></span>';}
    $out.='</header><div class="dga-calendar" role="table" aria-label="تقویم '.esc_attr($title).'">';foreach(array('شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه') as $w)$out.='<strong role="columnheader">'.$w.'</strong>';
    $offset=((int)(new DateTimeImmutable($start,wp_timezone()))->format('w')+1)%7;for($i=0;$i<$offset;$i++)$out.='<span class="dga-day-empty"></span>';
    for($d=1;$d<=$days;$d++){$r=$byday[$d]??null;$status=$r?($r->closed?'closed':$r->status):'unknown';$labels=array('present'=>'حاضر','late'=>'تأخیر','absent'=>'غایب','closed'=>'تعطیل','unknown'=>'ثبت نشده');$out.='<a role="cell" class="dga-day dga-'.esc_attr($status).'" href="#dga-day-'.$d.'" aria-label="'.esc_attr(dgs_fa_digits($d).' '.$months[$m].' — '.$labels[$status]).'">'.esc_html(dgs_fa_digits($d)).'</a>';}
    $out.='</div></section><aside class="dga-card dga-legend"><h3>ⓘ راهنمای تقویم</h3>';foreach(array('present'=>'حضور به‌موقع در مدرسه','late'=>'حضور با تأخیر ثبت‌شده','absent'=>'عدم حضور در مدرسه','closed'=>'تعطیل / بدون کلاس (ثبت مدیریت)','unknown'=>'بدون ثبت؛ وضعیت مشخص نیست') as $status=>$text)$out.='<div>'.dgs_att_badge($status).'<small>'.$text.'</small></div>';
    $out.='</aside></div><section class="dga-card"><h3>▤ جزئیات حضور و غیاب</h3><p class="dga-help">روزهای ماه انتخاب‌شده، وضعیت حضور، دقایق تأخیر و توضیحات ثبت‌شده</p>'.dgs_edu_table_start(array('تاریخ','روز هفته','وضعیت','میزان تأخیر','کلاس / برنامه','توضیحات'));
    $week=array('یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه');
    for($d=$days;$d>=1;$d--){$r=$byday[$d]??null;$g=dgs_att_gregorian($y,$m,$d);$status=$r?($r->closed?'closed':$r->status):'unknown';$out.='<tr id="dga-day-'.$d.'"><td>'.esc_html(dgs_fa_digits(sprintf('%04d/%02d/%02d',$y,$m,$d))).'</td><td>'.$week[(int)(new DateTimeImmutable($g,wp_timezone()))->format('w')].'</td><td>'.dgs_att_badge($status).'</td><td>'.($r&&$r->status==='late'?esc_html(dgs_fa_digits($r->minutes)).' دقیقه':'—').'</td><td>'.esc_html($r?$r->title:'—').'</td><td>'.esc_html($r?($r->closed?$r->closure_note:$r->note):'—').'</td></tr>';}
    return $out.'</tbody></table></div></section><button class="dg-btn dg-btn-outline" type="button" data-att-print>چاپ سوابق حضور و غیاب</button></div>';
}
add_action('wp_enqueue_scripts',function(){
    $p=get_option('dgs_pages',array());if(empty($p['portal'])||!is_page((int)$p['portal'])||dgs_att_get('dg_tab')!=='attendance')return;
    wp_register_style('dgs-attendance',false,array(),DGS_VERSION);wp_enqueue_style('dgs-attendance');wp_add_inline_style('dgs-attendance',file_get_contents(DGS_PATH.'assets/attendance.css'));
    wp_register_script('dgs-attendance',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-attendance');wp_add_inline_script('dgs-attendance',file_get_contents(DGS_PATH.'assets/attendance.js'));
});

add_filter('body_class',function($classes){$p=get_option('dgs_pages',array());if(!empty($p['portal'])&&is_page((int)$p['portal'])&&dgs_att_get('dg_tab')==='attendance'&&(current_user_can('dgs_manage_education')||dgs_edu_role(get_current_user_id(),'dgs_student')))$classes[]='dgs-attendance-page';return $classes;});
