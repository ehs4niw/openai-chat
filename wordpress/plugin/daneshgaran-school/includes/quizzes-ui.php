<?php
if(!defined('ABSPATH'))exit;
function dgs_quiz_url($year,$args=array()){return add_query_arg(array_merge(array('dg_tab'=>'quizzes','dg_year'=>$year),$args),dgs_page_url('portal'));}
function dgs_quiz_heading($title,$subtitle){return '<header class="dgq-heading"><small>خانه ‹ آزمون‌ها و کوییزها</small><h2>'.dgs_icon('book').' '.esc_html($title).'</h2><p>'.esc_html($subtitle).'</p></header>';}
function dgs_quiz_script_data($id,$data){return '<script type="application/json" id="'.esc_attr($id).'">'.wp_json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';}
function dgs_edu_view_quizzes($year){return dgs_quiz_portal($year);}
function dgs_quiz_portal($year){
 $manager=current_user_can('dgs_manage_education')||dgs_edu_role(get_current_user_id(),'dgs_teacher');$student=dgs_edu_role(get_current_user_id(),'dgs_student');if(!$manager&&!$student)return '';
 $id=absint(dgs_att_get('quiz_id'));$view=dgs_att_get('quiz_view');$q=$id?dgs_quiz_row($id):null;
 $out='<div class="dgq-quizzes">';if(dgs_att_get('quiz_saved')==='1')$out.='<p class="dgq-success">کوییز ذخیره شد.</p>';
 if($manager&&$view==='edit')return $out.dgs_quiz_builder($year,$q,$id).'</div>';
 if($id&&$view==='result')return $out.dgs_quiz_result_view($year,$q).'</div>';
 if($student&&$id&&$view==='take')return $out.dgs_quiz_take($year,$q).'</div>';
 return $out.dgs_quiz_list($year,$manager).'</div>';
}
function dgs_quiz_list($year,$manager){
 global $wpdb;$t=dgs_quiz_tables();$e=dgs_edu_tables();$uid=get_current_user_id();$args=array($year);$scope='';
 if($manager&&!current_user_can('dgs_manage_education')){$scope=" AND q.teacher_id=%d AND EXISTS (SELECT 1 FROM {$e['teaching']} a WHERE a.class_id=q.class_id AND a.subject_id=q.subject_id AND a.teacher_id=%d)";$args[]=$uid;$args[]=$uid;}
 elseif(!$manager){$scope=" AND q.status='published' AND (EXISTS (SELECT 1 FROM {$e['enrollments']} e WHERE e.class_id=q.class_id AND e.student_id=%d AND e.school_year=q.school_year) OR EXISTS (SELECT 1 FROM {$t['attempts']} a WHERE a.quiz_id=q.id AND a.student_id=%d))";$args[]=$uid;$args[]=$uid;}
 $rows=$wpdb->get_results($wpdb->prepare("SELECT q.*,c.title AS class_title,s.title AS subject,u.display_name AS teacher,(SELECT COUNT(*) FROM {$t['questions']} x WHERE x.quiz_id=q.id) AS qn FROM {$t['quizzes']} q JOIN {$e['classes']} c ON c.id=q.class_id JOIN {$e['subjects']} s ON s.id=q.subject_id LEFT JOIN {$wpdb->users} u ON u.ID=q.teacher_id WHERE q.school_year=%s AND q.status<>'deleted' $scope ORDER BY q.opens_at DESC,q.id DESC LIMIT 101",$args));
 $out='<div class="dgq-list-hero"><span aria-hidden="true">'.dgs_icon('book').'</span>'.dgs_quiz_heading($manager?'کوییزهای کلاس':'آزمون‌ها و کوییزهای من',$manager?'ساخت کوییز تک‌درس برای کلاس‌های تخصیص‌یافته و مشاهده نتایج':'آزمون‌های کلاس شما با بازه دسترسی و زمان پاسخ‌گویی مشخص').'</div>';
 if($manager)$out.='<a class="dg-btn" href="'.esc_url(dgs_quiz_url($year,array('quiz_view'=>'edit'))).'">＋ ساخت کوییز برای کلاس</a>';
 $out.='<form method="get" class="dge-filter" action="'.esc_url(dgs_page_url('portal')).'"><input type="hidden" name="dg_tab" value="quizzes"><label>سال تحصیلی<input name="dg_year" value="'.esc_attr($year).'" dir="ltr" required></label><button class="dg-btn dg-btn-outline">نمایش</button></form>';
 if(count($rows)>100)$out.='<p>۱۰۰ کوییز آخر نمایش داده می‌شود.</p>';$groups=array('active'=>array(),'future'=>array(),'ended'=>array(),'draft'=>array());$now=time();
 foreach(array_slice($rows,0,100) as $q){$a=$manager?null:dgs_quiz_attempt($q->id,$uid);if($a&&!$a->submitted_at&&$now>=(int)$a->deadline){$done=dgs_quiz_finish($q,$a);if(!is_wp_error($done))$a=$done;}
  $state=$q->status==='draft'?'draft':(($a&&$a->submitted_at)||$now>=(int)$q->closes_at?'ended':($now<(int)$q->opens_at?'future':'active'));$groups[$state][]=array($q,$a);
 }
 $labels=array('active'=>'▷ قابل شرکت / در حال برگزاری','future'=>'◷ آزمون‌های آینده','ended'=>'✓ آزمون‌های پایان‌یافته','draft'=>'پیش‌نویس‌ها');$out.='<nav class="dgq-state-nav">';foreach($labels as $key=>$label)if($key!=='draft'||$manager)$out.='<a href="#dgq-'.$key.'">'.$label.' ('.esc_html(dgs_fa_digits(count($groups[$key]))).')</a>';$out.='</nav>';
 foreach($labels as $state=>$label){if($state==='draft'&&!$manager)continue;$out.='<section id="dgq-'.$state.'"><h3 class="dgq-group-title">'.$label.'</h3>';foreach($groups[$state] as $item){list($q,$a)=$item;$out.='<article class="dgq-list-card"><div class="dgq-subject">'.dgs_icon('book').'<strong>'.esc_html($q->subject).'</strong><small>'.esc_html($q->class_title).'</small></div><div class="dgq-list-info"><span class="dgq-chip dgq-chip-'.esc_attr($state).'">'.esc_html(dgs_quiz_state_label($state)).'</span><h3>'.esc_html($q->title).'</h3><p>دبیر: '.esc_html($q->teacher??'').'</p>'.dgs_quiz_schedule_boxes($q).'</div><div class="dgq-list-meta"><span>'.esc_html(dgs_fa_digits($q->qn)).' سؤال</span><span>'.esc_html(dgs_fa_digits($q->duration)).' دقیقه</span></div><div class="dgq-list-action">';
   if($manager){$view=$q->status==='draft'?'edit':'result';$out.='<a class="dg-btn '.($view==='edit'?'dg-btn-outline':'').'" href="'.esc_url(dgs_quiz_url($year,array('quiz_id'=>$q->id,'quiz_view'=>$view))).'">'.($view==='edit'?'ویرایش پیش‌نویس':'نتایج و مشخصات').'</a>'.($view==='result'?'<a class="dg-btn dg-btn-outline" href="'.esc_url(dgs_quiz_url($year,array('quiz_id'=>$q->id,'quiz_view'=>'edit'))).'">ویرایش</a>':'');}
   elseif($a&&$a->submitted_at)$out.='<a class="dg-btn" href="'.esc_url(dgs_quiz_url($year,array('quiz_id'=>$q->id,'quiz_view'=>'result'))).'">مشاهده نتیجه</a>';
   elseif($state==='active')$out.='<a class="dg-btn" href="'.esc_url(dgs_quiz_url($year,array('quiz_id'=>$q->id,'quiz_view'=>'take'))).'">'.($a?'ادامه آزمون':'شروع آزمون').' ▷</a>';
   else $out.='<span class="dgq-muted">'.($state==='future'?'هنوز شروع نشده':'بازه شرکت پایان یافته').'</span>';
   $out.='</div></article>';}
   if(!$groups[$state])$out.='<p class="dgq-muted">موردی در این بخش وجود ندارد.</p>';$out.='</section>';
 }return $out;
}
function dgs_quiz_state_label($state){$l=array('active'=>'در حال برگزاری','future'=>'آزمون آینده','ended'=>'پایان‌یافته','draft'=>'پیش‌نویس');return $l[$state]??'';}
function dgs_quiz_fmt($ts){return dgs_fa_digits(dgs_att_jdate(wp_date('Y-m-d',(int)$ts)).' '.wp_date('H:i',(int)$ts));}
function dgs_quiz_months(){return array(1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند');}
function dgs_quiz_dt_field($prefix,$legend,$ts,$year){
 $j=explode('-',dgs_att_jdate(wp_date('Y-m-d',$ts)));$jm=(int)$j[1];$jd=(int)$j[2];$ys=explode('-',$year);$jy=$jm>=7?$ys[0]:$ys[1];$hour=(int)wp_date('G',$ts);$min=(int)wp_date('i',$ts);$months=dgs_quiz_months();
 $out='<fieldset class="dgq-dt" data-dt-prefix="'.esc_attr($prefix).'"><legend>'.esc_html($legend).'</legend><div class="dgq-dt-row">';
 $out.='<label>روز<select data-dt="day">';for($d=1;$d<=31;$d++)$out.='<option value="'.$d.'" '.selected($jd,$d,false).'>'.esc_html(dgs_fa_digits($d)).'</option>';$out.='</select></label>';
 $out.='<label>ماه<select data-dt="month">';foreach(array(7,8,9,10,11,12,1,2,3,4,5,6) as $m)$out.='<option value="'.$m.'" '.selected($jm,$m,false).'>'.esc_html($months[$m]).'</option>';$out.='</select></label>';
 $out.='<div class="dgq-dt-year"><small>سال</small><strong data-dt="year">'.esc_html(dgs_fa_digits($jy)).'</strong></div>';
 $out.='<label>ساعت<select data-dt="hour">';for($h=0;$h<24;$h++)$out.='<option value="'.sprintf('%02d',$h).'" '.selected($hour,$h,false).'>'.esc_html(dgs_fa_digits(sprintf('%02d',$h))).'</option>';$out.='</select></label>';
 $mins=range(0,59);
 $out.='<label>دقیقه<select data-dt="minute">';foreach($mins as $m)$out.='<option value="'.sprintf('%02d',$m).'" '.selected($min,$m,false).'>'.esc_html(dgs_fa_digits(sprintf('%02d',$m))).'</option>';$out.='</select></label>';
 return $out.'</div><input type="hidden" name="'.esc_attr($prefix).'_date" value="'.esc_attr(sprintf('%04d-%02d-%02d',(int)$jy,$jm,$jd)).'"><input type="hidden" name="'.esc_attr($prefix).'_time" value="'.esc_attr(sprintf('%02d:%02d',$hour,$min)).'"></fieldset>';
}
function dgs_quiz_file_card($kind,$title,$hint,$q,$required){
 $prop=$kind==='exam'?'exam_file':'answer_file';$nameprop=$kind==='exam'?'exam_name':'answer_name';$file=$q?(string)($q->$prop??''):'';$name=$q?(string)($q->$nameprop??''):'';
 $out='<section class="dgq-card dgq-file-card" data-dgq-file="'.esc_attr($kind).'" data-has-file="'.($file!==''?'1':'0').'"><h3>'.esc_html($title).($required?' <span class="dgq-req">*</span>':'').'</h3><p class="dgq-help">'.esc_html($hint).'</p>';
 $out.='<div class="dgq-file-current"'.($file===''?' hidden':'').'><span class="dgq-file-icon">PDF</span><div class="dgq-file-meta"><strong>'.esc_html($name!==''?$name:'فایل بارگذاری‌شده').'</strong><small>این فایل هم‌اکنون ذخیره شده است.</small></div><div class="dgq-file-actions">'.($file!==''&&$q?'<a class="dg-btn dg-btn-outline" target="_blank" rel="noopener" href="'.esc_url(dgs_quiz_file_url($q->id,$kind,false)).'">مشاهده</a>':'').'<button type="button" class="dg-btn dg-btn-outline dgq-danger" data-file-remove>حذف فایل</button></div></div>';
 $out.='<label class="dgq-dropzone"><input type="file" accept="application/pdf,.pdf" data-file-input><span class="dgq-drop-title" data-file-label data-text-empty="برای انتخاب فایل PDF کلیک کنید" data-text-replace="جایگزینی با فایل جدید (اختیاری)">'.($file===''?'برای انتخاب فایل PDF کلیک کنید':'جایگزینی با فایل جدید (اختیاری)').'</span><small>فقط PDF، حداکثر ۲۰ مگابایت (سقف فعلی سرور: '.esc_html(size_format(wp_max_upload_size())).')</small></label>';
 $out.='<div class="dgq-file-picked" hidden><span class="dgq-file-icon">PDF</span><div class="dgq-file-meta"><strong data-file-name></strong><small data-file-size></small></div><button type="button" class="dgq-small" data-file-clear>انصراف از انتخاب</button></div>';
 return $out.'</section>';
}
function dgs_quiz_builder($year,$q,$requested_id){
 if($requested_id&&(!$q||!dgs_quiz_manage($q)||$q->school_year!==$year))return '<p class="dge-error">به ویرایش این پیش‌نویس دسترسی ندارید.</p>';
 $pairs=dgs_grade_pairs($year);$options=array();foreach($pairs as $p)$options[$p->class_id.':'.$p->subject_id]=$p->title.' — '.$p->subject;
 if(!$pairs)return '<p>ابتدا کلاس و درس را به دبیر تخصیص دهید.</p>';
 if(get_option('dgs_quiz_files')!=='1')return '<p class="dge-error">جدول‌های آزمون هنوز به‌روزرسانی نشده‌اند؛ صفحه را یک بار تازه کنید.</p>';
 $start=$q?(int)$q->opens_at:time()+3600;$end=$q?(int)$q->closes_at:time()+7200;
 $rows=$q?dgs_quiz_questions($q->id,true):array();$key='';$legacy=false;foreach($rows as $item){$key.=(string)(int)$item->correct;if(json_decode($item->options,true)!==array('1','2','3','4'))$legacy=true;}
 $count=$rows?count($rows):20;if(!$rows)$key=str_repeat('0',$count);if(dgs_quiz_has_pdf($q))$legacy=false;
 $out=dgs_quiz_heading('ساخت کوییز برای کلاس','فایل PDF آزمون را بارگذاری کنید و کلید پاسخ چهارگزینه‌ای را وارد کنید').'<a class="dg-btn dg-btn-outline" href="'.esc_url(dgs_quiz_url($year)).'">← بازگشت به کوییزها</a>';
 if($legacy)$out.='<div class="dgq-banner">این پیش‌نویس با ویرایشگر قدیمی ساخته شده است. با ذخیره‌کردن در این صفحه، متن سؤال‌ها و گزینه‌های ثبت‌شده با «کلید پاسخ + فایل PDF» جایگزین می‌شود (کلید فعلی حفظ می‌شود). اگر نمی‌خواهید، بدون ذخیره از صفحه خارج شوید.</div>';
 $out.='<form id="dgq-builder" novalidate><div class="dgq-builder-layout"><div class="dgq-builder-main">';
 $out.=dgs_quiz_info_card($pairs,$q,$year,$start,$end);
 $out.=dgs_quiz_file_card('exam','فایل آزمون (PDF)','سؤال‌ها در این فایل هستند و دانش‌آموز آن را کنار پاسخ‌نامه می‌بیند.',$q,true);
 $out.='<section class="dgq-card"><h3>سؤال‌ها و کلید</h3><label>تعداد سؤال<input type="number" id="dgq-count" min="1" max="60" value="'.(int)$count.'" inputmode="numeric"></label><label>ورود سریع کلید<input id="dgq-quick" dir="ltr" autocomplete="off" inputmode="numeric" placeholder="مثلاً ۴,۱,۲,۲,۳,۱,۴ (پشت‌سرهم)"></label><p class="dgq-help" id="dgq-quick-msg" role="status">شمارهٔ گزینهٔ درست هر سؤال را پشت‌سرهم بنویسید؛ کلید زیر خودکار پر می‌شود.</p><div class="dgq-key-title">کلید پاسخ‌نامه <span class="dgq-req">*</span></div><div class="dgq-key-grid" id="dgq-key-grid" role="group" aria-label="کلید پاسخ"></div></section>';
 $out.=dgs_quiz_file_card('answer','پاسخنامهٔ تشریحی (اختیاری)','این فایل فقط پس از پایان بازه آزمون برای دانش‌آموز نمایش داده می‌شود.',$q,false);
 $out.='</div><aside class="dgq-builder-side"><section class="dgq-card dgq-summary"><h3 data-sum="title">آزمون بدون عنوان</h3><dl><div><dt>تعداد سؤال</dt><dd data-sum="count"></dd></div><div><dt>مدت</dt><dd data-sum="duration"></dd></div><div><dt>شروع</dt><dd data-sum="start"></dd></div><div><dt>پایان</dt><dd data-sum="end"></dd></div><div><dt>انتشار نتیجه</dt><dd>پس از پایان بازه</dd></div><div><dt>فایل آزمون</dt><dd data-sum="exam"></dd></div><div><dt>کلید ثبت‌شده</dt><dd data-sum="key"></dd></div></dl><p id="dgq-builder-status" role="status"></p><button class="dg-btn" type="button" id="dgq-publish">ذخیره و انتشار ✓</button><button type="button" class="dg-btn dg-btn-outline" id="dgq-save-draft">ذخیره به‌عنوان پیش‌نویس</button><a class="dg-btn dg-btn-outline" href="'.esc_url(dgs_quiz_url($year)).'">انصراف</a></section></aside></div></form>';
 return $out.dgs_quiz_script_data('dgq-builder-data',array('id'=>$q?(int)$q->id:0,'revision'=>$q?(int)$q->revision:0,'year'=>$year,'count'=>(int)$count,'key'=>$key,'published'=>$q&&$q->status==='published','pairs'=>array_values($pairs),'months'=>array_values(dgs_quiz_months())));
}
function dgs_quiz_take($year,$q){
 if(!$q||$q->school_year!==$year||(!dgs_quiz_member($q)&&!dgs_quiz_attempt($q->id,get_current_user_id())))return '<p class="dge-error">به این کوییز دسترسی ندارید.</p>';
 $a=dgs_quiz_attempt($q->id,get_current_user_id());$pdf=dgs_quiz_has_pdf($q);$out=dgs_quiz_heading('شرکت در کوییز','پاسخ‌ها را انتخاب و در پایان، آزمون را ثبت کنید. فقط یک نوبت شرکت مجاز است.');
 $out.='<section class="dgq-card dgq-start-card" id="dgq-start-card"><h3>'.esc_html($q->title).'</h3><p>مدت: '.esc_html(dgs_fa_digits($q->duration)).' دقیقه؛ زمان واقعی باقی‌مانده از پایان بازه دسترسی بیشتر نمی‌شود.</p><button class="dg-btn" id="dgq-start" type="button">'.($a?'بازیابی و ادامه آزمون':'شروع آزمون').' ▷</button></section>';
 if($pdf){
  $out.='<div id="dgq-take" hidden><div class="dgq-take-layout dgq-take-pdf"><section class="dgq-card dgq-pdf-card"><div class="dgq-pdf-head"><strong>'.esc_html($q->title).'</strong><span class="dgq-pdf-links"><a id="dgq-pdf-open" class="dg-btn dg-btn-outline" target="_blank" rel="noopener" href="#">باز کردن در تب جدید</a><a id="dgq-pdf-download" class="dg-btn dg-btn-outline" href="#">دانلود PDF</a></span></div><iframe id="dgq-pdf-frame" title="فایل آزمون"></iframe><p class="dgq-help">اگر فایل در مرورگر شما نمایش داده نشد (در برخی گوشی‌ها رایج است)، از «باز کردن در تب جدید» یا «دانلود PDF» استفاده کنید و برای ثبت پاسخ‌ها به همین صفحه برگردید.</p></section><aside><section class="dgq-card dgq-clock-card"><h3>◷ زمان باقی‌مانده</h3><strong id="dgq-timer">--:--</strong></section><section class="dgq-card"><h3>☷ پاسخ‌نامه</h3><div id="dgq-sheet"></div><p id="dgq-answered-count"></p><div class="dgq-progress"><i id="dgq-progress-fill"></i></div><p class="dgq-save-state" id="dgq-save-state" role="status"></p><button id="dgq-submit" class="dg-btn" type="button">✓ پایان و ثبت آزمون</button><button id="dgq-retry" class="dg-btn dg-btn-outline" type="button" hidden>تلاش دوباره برای ذخیره</button></section></aside></div></div><p id="dgq-take-error" class="dgq-error" role="alert" hidden></p>';
 }else{
  $out.='<div id="dgq-take" hidden><div class="dgq-take-layout"><section class="dgq-card dgq-current"><div class="dgq-question-header"><strong>'.esc_html($q->title).'</strong><span id="dgq-current-number"></span></div><h3 id="dgq-current-prompt"></h3><img id="dgq-current-image" alt="تصویر سؤال" hidden><fieldset id="dgq-current-options"><legend class="screen-reader-text">انتخاب پاسخ سؤال</legend></fieldset><button type="button" class="dgq-clear" id="dgq-clear">پاک‌کردن پاسخ این سؤال</button><p class="dgq-save-state" id="dgq-save-state" role="status"></p><div class="dgq-actions"><button class="dg-btn dg-btn-outline" type="button" id="dgq-prev">→ سؤال قبلی</button><button class="dg-btn" type="button" id="dgq-next">سؤال بعدی ←</button></div></section><aside><section class="dgq-card dgq-clock-card"><h3>◷ زمان باقی‌مانده</h3><strong id="dgq-timer">--:--</strong></section><section class="dgq-card"><h3>☷ نقشه سؤالات</h3><div id="dgq-question-map"></div><p id="dgq-answered-count"></p><div class="dgq-progress"><i id="dgq-progress-fill"></i></div><button id="dgq-submit" class="dg-btn" type="button">✓ پایان و ثبت آزمون</button><button id="dgq-retry" class="dg-btn dg-btn-outline" type="button" hidden>تلاش دوباره برای ذخیره</button></section></aside></div></div><p id="dgq-take-error" class="dgq-error" role="alert" hidden></p>';
 }
 return $out.dgs_quiz_script_data('dgq-take-data',array('id'=>(int)$q->id,'year'=>$year,'pdf'=>$pdf));
}
function dgs_quiz_review_cards($q,$payload,$answers){
 $out='<div class="dgq-review-legend"><span class="dgq-opt is-correct">پاسخ صحیح</span><span class="dgq-opt is-wrong">پاسخ نادرست شما</span><span class="dgq-opt">گزینهٔ دیگر</span></div><div class="dgq-review">';$i=0;$labels=array('ok'=>'صحیح','bad'=>'نادرست','blank'=>'بدون پاسخ');
 foreach($payload['questions'] as $question){
  $i++;$answer=(int)($answers[$question->id]??0);$correct=(int)$question->correct;$options=json_decode($question->options,true);if(!is_array($options))$options=array();$generic=$options===array('1','2','3','4');
  $state=!$answer?'blank':($answer===$correct?'ok':'bad');
  $out.='<article class="dgq-rev dgq-rev-'.$state.'"><header><b class="dgq-rev-no">'.esc_html(dgs_fa_digits($i)).'</b><span class="dgq-rev-status">'.esc_html($labels[$state]).'</span></header>';
  if(!$generic&&trim((string)$question->prompt)!=='')$out.='<div class="dgq-rev-prompt">'.nl2br(esc_html($question->prompt)).'</div>';
  if($question->image_mime)$out.='<img class="dgq-result-image" src="'.esc_url(dgs_quiz_image_url($q->id,$question->id)).'" alt="تصویر سؤال">';
  $out.='<ol class="dgq-rev-options'.($generic?' is-compact':'').'">';
  for($n=1;$n<=4;$n++){
   $class='dgq-opt'.($n===$correct?' is-correct':'').($n===$answer&&$n!==$correct?' is-wrong':'').($n===$answer?' is-mine':'');$mark='';
   if($n===$correct&&$n===$answer)$mark='✓ پاسخ شما (صحیح)';elseif($n===$correct)$mark='✓ پاسخ صحیح';elseif($n===$answer)$mark='× پاسخ شما';
   $out.='<li class="'.esc_attr($class).'"><b>'.esc_html(dgs_fa_digits($n)).'</b>'.($generic?'':'<span>'.esc_html((string)($options[$n-1]??'')).'</span>').($mark!==''?'<em>'.esc_html($mark).'</em>':'').'</li>';
  }
  $out.='</ol>';
  if(trim((string)$question->explanation)!=='')$out.='<p class="dgq-rev-note">'.nl2br(esc_html($question->explanation)).'</p>';
  $out.='</article>';
 }
 return $out.'</div>';
}
function dgs_quiz_files_card($q,$a,$manager){
 $released=$manager||($a&&$a->submitted_at&&time()>=(int)$q->closes_at);
 if(!$released)return '';
 $answer=(string)($q->answer_file??'')!=='';$out='<section class="dgq-card dgq-answer-file"><div class="dgq-section-head"><h3>فایل پاسخ‌نامه آزمون</h3>';
 if($answer)$out.='<div class="dgq-file-actions"><a class="dg-btn dg-btn-outline" target="_blank" rel="noopener" href="'.esc_url(dgs_quiz_file_url($q->id,'answer',false)).'">مشاهده در تب جدید</a><a class="dg-btn" href="'.esc_url(dgs_quiz_file_url($q->id,'answer',true)).'">دانلود پاسخ‌نامه</a></div>';
 $out.='</div>';
 if($answer)$out.='<iframe loading="lazy" title="فایل پاسخ‌نامه آزمون" src="'.esc_url(dgs_quiz_file_url($q->id,'answer',false)).'"></iframe><p class="dgq-help">اگر در گوشی نمایش داده نشد، فایل را در تب جدید باز یا دانلود کنید.</p>';
 else $out.='<p class="dgq-muted">فایل پاسخ‌نامه هنوز توسط مدرس بارگذاری نشده است.</p>';
 if(dgs_quiz_has_pdf($q))$out.='<a class="dg-btn dg-btn-outline dgq-exam-download" href="'.esc_url(dgs_quiz_file_url($q->id,'exam',true)).'">دانلود فایل سؤال‌های آزمون</a>';
 return $out.'</section>';
}
function dgs_quiz_result_view($year,$q){
 if(!$q||$q->school_year!==$year)return '<p class="dge-error">کوییز پیدا نشد.</p>';$manager=dgs_quiz_manage($q);$uid=get_current_user_id();$out=dgs_quiz_heading('نتیجه کوییز',$q->title).'<a class="dg-btn dg-btn-outline" href="'.esc_url(dgs_quiz_url($year)).'">← بازگشت به آزمون‌ها</a>';
 global $wpdb;$t=dgs_quiz_tables();
 if($manager){$out.='<div class="dgq-actions"><a class="dg-btn" href="'.esc_url(dgs_quiz_url($year,array('quiz_id'=>$q->id,'quiz_view'=>'edit'))).'">ویرایش آزمون</a>';if(time()>=(int)$q->closes_at)$out.='<button type="button" class="dg-btn dg-btn-outline dgq-danger" data-quiz-delete="'.(int)$q->id.'" data-revision="'.(int)$q->revision.'">حذف آزمون از فهرست</button>';$out.='</div>';$rows=$wpdb->get_results($wpdb->prepare("SELECT a.*,u.display_name FROM {$t['attempts']} a JOIN {$wpdb->users} u ON u.ID=a.student_id WHERE a.quiz_id=%d ORDER BY u.display_name LIMIT 201",$q->id));$selected=absint(dgs_att_get('quiz_student'));$out.='<section id="dgq-participants-report" class="dgq-card"><div class="dgq-section-head"><h3>نتایج شرکت‌کنندگان — '.esc_html($q->title).'</h3><button type="button" class="dg-btn dg-btn-outline" data-quiz-print-target="dgq-participants-report">چاپ نتایج / ذخیره PDF</button></div><p>ویرایش فایل، کلید و زمان‌بندی در همه وضعیت‌ها امکان‌پذیر است. حداکثر ۲۰۰ شرکت‌کننده در گزارش نمایش داده می‌شود.</p>'.dgs_edu_table_start(array('دانش‌آموز','وضعیت','نمره از ۲۰','عملیات'));$chosen=null;
  foreach(array_slice($rows,0,200) as $a){if(!$a->submitted_at&&time()>=(int)$a->deadline){$done=dgs_quiz_finish($q,$a);if(!is_wp_error($done)){$done->display_name=$a->display_name;$a=$done;}}$r=json_decode($a->result,true);if((int)$a->student_id===$selected)$chosen=$a;$out.='<tr><td>'.esc_html($a->display_name).'</td><td>'.($a->submitted_at?'نهایی‌شده':'در حال پاسخ‌گویی').'</td><td>'.($a->submitted_at?esc_html(dgs_fa_digits($r['grade']??0)):'—').'</td><td><a href="'.esc_url(dgs_quiz_url($year,array('quiz_id'=>$q->id,'quiz_view'=>'result','quiz_student'=>$a->student_id))).'">بررسی پاسخ‌ها</a></td></tr>';}
  $out.='</tbody></table></div></section>';if(!$chosen)return $out;$a=$chosen;
 }else{
  if(!dgs_edu_role($uid,'dgs_student'))return '<p class="dge-error">دسترسی مجاز نیست.</p>';$a=dgs_quiz_attempt($q->id,$uid);if(!$a)return $out.'<p>شما در این کوییز شرکت نکرده‌اید.</p>';if(!$a->submitted_at&&time()>=(int)$a->deadline){$a=dgs_quiz_finish($q,$a);if(is_wp_error($a))return $out.'<p>صفحه را تازه کنید.</p>';}
 }
 $payload=dgs_quiz_result_payload($q,$a);if(is_wp_error($payload))return $out.'<p>'.esc_html($payload->get_error_message()).'</p>';
 if($payload['pending'])return $out.'<section class="dgq-card dgq-pending-result"><span class="dgq-pending-icon" aria-hidden="true">✓</span><h3>پاسخ‌های شما با موفقیت ثبت شد</h3><p>پس از پایان بازه آزمون، نمره و فایل پاسخ‌نامه در همین صفحه نمایش داده می‌شود.</p><div class="dgq-release-box"><span>زمان انتشار نتیجه</span><strong dir="ltr">'.esc_html(dgs_fa_digits(dgs_att_jdate(wp_date('Y-m-d',$q->closes_at)).' '.wp_date('H:i',$q->closes_at))).'</strong></div></section>';
 $r=$payload['result'];$answers=$payload['answers']?:array();$subject=get_userdata($q->teacher_id);$meta=dgs_edu_subjects();$subject_title='';foreach($meta as $s)if((int)$s->id===(int)$q->subject_id)$subject_title=$s->title;
 $out.='<div id="dgq-result-report"><div class="dgq-banner">✓ کوییز با موفقیت ثبت شد. '.($manager?'دانش‌آموز: '.esc_html(get_userdata($a->student_id)->display_name):'نتیجه و بررسی پاسخ‌های شما در ادامه قابل مشاهده است.').'</div><div class="dgq-result-grid"><section class="dgq-card"><dl><dt>درس</dt><dd>'.esc_html($subject_title).'</dd><dt>دبیر</dt><dd>'.esc_html($subject?$subject->display_name:'').'</dd><dt>شروع بازه</dt><dd>'.esc_html(dgs_quiz_fmt($q->opens_at)).'</dd><dt>پایان بازه</dt><dd>'.esc_html(dgs_quiz_fmt($q->closes_at)).'</dd><dt>شروع پاسخ‌گویی</dt><dd>'.esc_html(dgs_quiz_fmt($a->started_at)).'</dd><dt>ثبت نهایی</dt><dd>'.esc_html(dgs_quiz_fmt($a->submitted_at)).'</dd><dt>مدت آزمون</dt><dd>'.esc_html(dgs_fa_digits($q->duration)).' دقیقه</dd><dt>نوع</dt><dd>کوییز تک‌درس</dd></dl></section><section class="dgq-card dgq-result-score"><div class="dgq-ring" style="--dgq-pct:'.((float)$r['grade']*5).'%"><strong>'.esc_html(dgs_fa_digits($r['grade'])).'<small>از ۲۰</small></strong></div><div><p class="dgq-green">✓ '.esc_html(dgs_fa_digits($r['correct'])).' پاسخ صحیح</p><p class="dgq-red">× '.esc_html(dgs_fa_digits($r['wrong'])).' پاسخ نادرست</p><p>'.esc_html(dgs_fa_digits($r['blank'])).' بدون پاسخ</p></div></section></div><section class="dgq-card"><h3>▥ خلاصه عملکرد شما</h3><div class="dgq-performance">';
 foreach(array('correct'=>array('صحیح','green'),'wrong'=>array('نادرست','red'),'blank'=>array('بدون پاسخ','gray')) as $key=>$v)$out.='<div><strong>'.esc_html(dgs_fa_digits($r[$key])).'</strong><i class="dgq-'.$v[1].'-bar" style="height:'.(count($payload['questions'])?max(3,$r[$key]/count($payload['questions'])*110):3).'px"></i><span>'.$v[0].'</span></div>';
 $out.='<aside>نمره نهایی <strong>'.esc_html(dgs_fa_digits($r['grade'])).' از ۲۰</strong><small>پاسخ صحیح: '.esc_html(dgs_fa_digits($r['percent'])).'٪</small></aside></div><p class="dgq-help">نمره بر اساس وزن سؤال‌های صحیح و بدون نمره منفی محاسبه شده است. درصد پاسخ صحیح، نسبت تعداد پاسخ صحیح به کل سؤال‌هاست.</p></section>';$out.=dgs_quiz_answer_table($q,$a,$payload).dgs_quiz_files_card($q,$a,$manager);return $out.'</div><button class="dg-btn" type="button" data-quiz-print>دریافت / چاپ گزارش ↓</button>';
}
add_filter('body_class',function($classes){if(dgs_is_role_portal()&&dgs_portal_tab()==='quizzes')$classes[]='dgs-quizzes-page';return $classes;});
add_action('wp_enqueue_scripts',function(){
 if(!dgs_is_role_portal()||dgs_portal_tab()!=='quizzes')return;
 wp_register_style('dgs-quizzes',false,array(),DGS_VERSION);wp_enqueue_style('dgs-quizzes');wp_add_inline_style('dgs-quizzes',file_get_contents(DGS_PATH.'assets/quizzes.css'));
 wp_register_script('dgs-quizzes',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-quizzes');wp_add_inline_script('dgs-quizzes','window.dgsQuizConfig='.wp_json_encode(array('ajax'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('dgs_quiz'),'portal'=>dgs_page_url('portal'))).';'.file_get_contents(DGS_PATH.'assets/quizzes.js'));
});

function dgs_quiz_schedule_boxes($q){$out='<div class="dgq-schedule-boxes">';foreach(array('opens_at'=>'شروع دسترسی','closes_at'=>'پایان دسترسی') as $field=>$label)$out.='<div><span>'.$label.'</span><time dir="ltr">'.esc_html(dgs_fa_digits(dgs_att_jdate(wp_date('Y-m-d',$q->$field)))).'</time><strong dir="ltr">'.esc_html(dgs_fa_digits(wp_date('H:i',$q->$field))).'</strong></div>';return $out.'</div>';}

function dgs_quiz_info_card($pairs,$q,$year,$start,$end){
 $selected=$q?$q->class_id.':'.$q->subject_id:$pairs[0]->class_id.':'.$pairs[0]->subject_id;list($class_id,$subject_id)=array_map('intval',explode(':',$selected));$classes=array();$subjects=array();
 foreach($pairs as $p){$classes[$p->class_id]=dgs_edu_grade_label($p->grade).' — '.$p->title;if((int)$p->class_id===$class_id)$subjects[$p->subject_id]=$p->subject;}
 $out='<section class="dgq-card dgq-info-card"><h3>☷ اطلاعات کوییز</h3><div class="dgq-info-top">'.dgs_edu_select('quiz_class','کلاس',$classes,$class_id).dgs_edu_select('quiz_subject','درس',$subjects,$subject_id).'<label>عنوان کوییز<input name="title" maxlength="150" value="'.esc_attr($q?$q->title:'').'" placeholder="مثلاً کوییز فصل دوم" required></label><input type="hidden" name="pair" value="'.esc_attr($selected).'"></div><div class="dgq-info-times">'.dgs_quiz_dt_field('open','تاریخ و ساعت شروع',$start,$year).dgs_quiz_dt_field('close','پایان بازه دسترسی',$end,$year).'</div><div class="dgq-info-bottom"><label>مدت پاسخ‌گویی (دقیقه)<input type="number" name="duration" min="1" max="180" value="'.($q?(int)$q->duration:20).'" required></label><p class="dgq-help">ساعت‌ها ۲۴ساعته و به وقت '.esc_html(wp_timezone_string()).' هستند.</p></div><div class="dgq-info-summary"><strong>◷ خلاصه کوییز زمان‌دار</strong><div><span id="dgq-info-pair"></span><span id="dgq-info-duration"></span><span id="dgq-info-window"></span></div></div></section>';
 return $out;
}

function dgs_quiz_answer_table($q,$a,$payload){
 $manager=dgs_quiz_manage($q);if(!$q||!$a||!$a->submitted_at||!is_array($payload)||!empty($payload['pending']))return '';
 if(!$manager&&(!dgs_edu_role(get_current_user_id(),'dgs_student')||(int)$a->student_id!==get_current_user_id()||time()<(int)$q->closes_at))return '';
 $answers=$payload['answers']?:array();$out='<section class="dgq-card dgq-my-answers"><h3>'.($manager?'پاسخ‌های ثبت‌شدهٔ دانش‌آموز':'پاسخ‌های ثبت‌شدهٔ شما').'</h3>'.dgs_edu_table_start(array('شماره سؤال','گزینه انتخاب‌شده','گزینه صحیح','وضعیت'));$i=0;
 foreach($payload['questions'] as $question){$i++;$answer=(int)($answers[$question->id]??0);$correct=(int)$question->correct;$status=!$answer?'بدون پاسخ':($answer===$correct?'صحیح':'نادرست');$color=!$answer?'':($answer===$correct?'dgq-green':'dgq-red');$out.='<tr><td>'.esc_html(dgs_fa_digits($i)).'</td><td>'.($answer?'گزینه '.esc_html(dgs_fa_digits($answer)):'بدون پاسخ').'</td><td>گزینه '.esc_html(dgs_fa_digits($correct)).'</td><td class="'.$color.'">'.esc_html($status).'</td></tr>';}
 return $out.'</tbody></table></div></section>';
}
