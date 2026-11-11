<?php
if(!defined('ABSPATH'))exit;
/* زمان پاسخ‌گویی، پاک‌سازی گزینه‌ها و تفکیک انتشار نتیجه با اقتباس از کد آزمون ارسالی کاربر؛ مستقل از bse/WooCommerce. */
function dgs_quiz_tables(){global $wpdb;return array('quizzes'=>$wpdb->prefix.'dgs_quizzes','questions'=>$wpdb->prefix.'dgs_quiz_questions','attempts'=>$wpdb->prefix.'dgs_quiz_attempts');}
add_action('init',function(){
 if(get_option('dgs_quiz_schema')==='1')return;global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_quiz_tables();$cc=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t['quizzes']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 class_id bigint(20) unsigned NOT NULL,
 subject_id bigint(20) unsigned NOT NULL,
 teacher_id bigint(20) unsigned NOT NULL,
 school_year varchar(9) NOT NULL,
 title varchar(150) NOT NULL,
 opens_at bigint(20) unsigned NOT NULL,
 closes_at bigint(20) unsigned NOT NULL,
 duration smallint unsigned NOT NULL,
 status varchar(12) NOT NULL DEFAULT 'draft',
 revision bigint(20) unsigned NOT NULL DEFAULT 1,
 updated_at datetime NOT NULL,
 PRIMARY KEY  (id),
 KEY year_class (school_year,class_id),
 KEY teacher_year (teacher_id,school_year)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['questions']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 quiz_id bigint(20) unsigned NOT NULL,
 position smallint unsigned NOT NULL,
 prompt text NOT NULL,
 options longtext NOT NULL,
 correct tinyint unsigned NOT NULL,
 weight int unsigned NOT NULL,
 explanation text NOT NULL,
 image_mime varchar(20) NOT NULL DEFAULT '',
 image_data longtext NOT NULL,
 PRIMARY KEY  (id),
 KEY quiz_position (quiz_id,position)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['attempts']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 quiz_id bigint(20) unsigned NOT NULL,
 student_id bigint(20) unsigned NOT NULL,
 started_at bigint(20) unsigned NOT NULL,
 deadline bigint(20) unsigned NOT NULL,
 submitted_at bigint(20) unsigned NOT NULL DEFAULT 0,
 answers longtext NOT NULL,
 result longtext NOT NULL,
 revision bigint(20) unsigned NOT NULL DEFAULT 1,
 PRIMARY KEY  (id),
 UNIQUE KEY quiz_student (quiz_id,student_id),
 KEY student_attempts (student_id)
 ) ENGINE=InnoDB $cc;");
 foreach($t as $table)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;update_option('dgs_quiz_schema','1',false);
},12);
/* ---- v0.7.0: فایل PDF آزمون / پاسخنامه و کلید پاسخ ---- */
add_action('init',function(){
 if(get_option('dgs_quiz_files')==='1'||get_option('dgs_quiz_schema')!=='1')return;
 global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_quiz_tables();$cc=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t['quizzes']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 class_id bigint(20) unsigned NOT NULL,
 subject_id bigint(20) unsigned NOT NULL,
 teacher_id bigint(20) unsigned NOT NULL,
 school_year varchar(9) NOT NULL,
 title varchar(150) NOT NULL,
 opens_at bigint(20) unsigned NOT NULL,
 closes_at bigint(20) unsigned NOT NULL,
 duration smallint unsigned NOT NULL,
 status varchar(12) NOT NULL DEFAULT 'draft',
 revision bigint(20) unsigned NOT NULL DEFAULT 1,
 updated_at datetime NOT NULL,
 exam_file varchar(64) NOT NULL DEFAULT '',
 exam_name varchar(150) NOT NULL DEFAULT '',
 answer_file varchar(64) NOT NULL DEFAULT '',
 answer_name varchar(150) NOT NULL DEFAULT '',
 PRIMARY KEY  (id),
 KEY year_class (school_year,class_id),
 KEY teacher_year (teacher_id,school_year)
 ) ENGINE=InnoDB $cc;");
 $cols=$wpdb->get_col("SHOW COLUMNS FROM {$t['quizzes']}",0);
 if(is_array($cols)&&in_array('exam_file',$cols,true)&&in_array('exam_name',$cols,true)&&in_array('answer_file',$cols,true)&&in_array('answer_name',$cols,true))update_option('dgs_quiz_files','1',false);
},13);
function dgs_quiz_has_pdf($q){return $q&&(string)($q->exam_file??'')!=='';}
function dgs_quiz_file_dir(){
 $u=wp_upload_dir(null,false);if(!empty($u['error']))return '';
 $dir=trailingslashit($u['basedir']).'dgs-private/quizzes';if(!wp_mkdir_p($dir))return '';
 $guards=array('index.html'=>'','.htaccess'=>"<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n");
 foreach($guards as $name=>$body){$path=$dir.'/'.$name;if(!file_exists($path))@file_put_contents($path,$body);}
 return $dir;
}
function dgs_quiz_pdf_store($file,$label){
 if(!is_array($file))return dgs_edu_error('pdf','فایل '.$label.' دریافت نشد.');
 $err=(int)($file['error']??UPLOAD_ERR_NO_FILE);
 if($err===UPLOAD_ERR_INI_SIZE||$err===UPLOAD_ERR_FORM_SIZE)return dgs_edu_error('pdf','حجم فایل '.$label.' از سقف مجاز سرور بیشتر است (سقف فعلی سرور: '.size_format(wp_max_upload_size()).').');
 if($err!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??''))return dgs_edu_error('pdf','بارگذاری فایل '.$label.' کامل نشد؛ دوباره تلاش کنید.');
 $size=(int)($file['size']??0);
 if($size<100||$size>20*1024*1024)return dgs_edu_error('pdf','حجم فایل '.$label.' باید حداکثر ۲۰ مگابایت باشد.');
 $name=sanitize_file_name(wp_basename((string)($file['name']??'')));
 if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='pdf')return dgs_edu_error('pdf','فایل '.$label.' باید با پسوند PDF باشد.');
 $head=file_get_contents($file['tmp_name'],false,null,0,5);
 if($head!=='%PDF-')return dgs_edu_error('pdf','فایل '.$label.' یک PDF معتبر نیست.');
 $dir=dgs_quiz_file_dir();if($dir==='')return dgs_edu_error('pdf','پوشه ذخیره‌سازی ساخته نشد؛ مجوز نوشتن پوشه wp-content/uploads را بررسی کنید.');
 $stored=bin2hex(random_bytes(16)).'.pdf';
 if(!@move_uploaded_file($file['tmp_name'],$dir.'/'.$stored))return dgs_edu_error('pdf','ذخیره فایل '.$label.' روی سرور انجام نشد؛ مجوز نوشتن پوشه uploads را بررسی کنید.');
 @chmod($dir.'/'.$stored,0644);
 return array('file'=>$stored,'name'=>function_exists('mb_substr')?mb_substr($name,0,150):substr($name,0,150));
}
function dgs_quiz_pdf_delete($stored){
 if(!preg_match('/^[a-f0-9]{32}\.pdf$/D',(string)$stored))return;
 $dir=dgs_quiz_file_dir();if($dir!==''&&is_file($dir.'/'.$stored))@unlink($dir.'/'.$stored);
}
function dgs_quiz_file_url($quiz,$kind,$download=false){return add_query_arg(array('action'=>'dgs_quiz_file','quiz'=>$quiz,'kind'=>$kind,'download'=>$download?1:0,'nonce'=>wp_create_nonce('dgs_quiz_file')),admin_url('admin-post.php'));}
add_action('admin_post_dgs_quiz_file',function(){
 if(!isset($_GET['nonce'])||!is_string($_GET['nonce'])||!wp_verify_nonce($_GET['nonce'],'dgs_quiz_file'))wp_die('اعتبار این پیوند تمام شده است؛ صفحه آزمون را تازه کنید.','',array('response'=>403));
 $q=dgs_quiz_row(is_scalar($_GET['quiz']??null)?$_GET['quiz']:0);$kind=is_string($_GET['kind']??null)?$_GET['kind']:'';
 if(!$q||!in_array($kind,array('exam','answer'),true))wp_die('فایل پیدا نشد.','',array('response'=>404));
 $stored=(string)($kind==='exam'?($q->exam_file??''):($q->answer_file??''));$name=(string)($kind==='exam'?($q->exam_name??''):($q->answer_name??''));
 $allowed=dgs_quiz_manage($q);
 if(!$allowed&&$q->status==='published'&&dgs_edu_role(get_current_user_id(),'dgs_student')){
  $a=dgs_quiz_attempt($q->id,get_current_user_id());$now=time();
  if($a){$allowed=$kind==='exam'?((!$a->submitted_at&&$now<(int)$a->deadline)||($a->submitted_at&&$now>=(int)$q->closes_at)):($a->submitted_at&&$now>=(int)$q->closes_at);}
 }
 if(!$allowed)wp_die('در این زمان به این فایل دسترسی ندارید.','',array('response'=>403));
 if(!preg_match('/^[a-f0-9]{32}\.pdf$/D',$stored))wp_die('فایلی برای این بخش بارگذاری نشده است.','',array('response'=>404));
 $dir=dgs_quiz_file_dir();$path=$dir!==''?$dir.'/'.$stored:'';
 if($path===''||!is_file($path)||!is_readable($path))wp_die('فایل روی سرور پیدا نشد یا خوانده نمی‌شود؛ مدیر سایت را مطلع کنید.','',array('response'=>404));
 $download=!empty($_GET['download']);if($name==='')$name='quiz-'.$q->id.'-'.$kind.'.pdf';
 nocache_headers();header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');header('Accept-Ranges: none');header('Content-Type: application/pdf');
 header('Content-Disposition: '.($download?'attachment':'inline').'; filename="quiz-'.(int)$q->id.'-'.$kind.'.pdf"; filename*=UTF-8\'\''.rawurlencode($name));
 header('Content-Length: '.filesize($path));
 while(ob_get_level())ob_end_clean();
 readfile($path);exit;
});
function dgs_quiz_row($id){global $wpdb;$t=dgs_quiz_tables();return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['quizzes']} WHERE id=%d AND status<>'deleted'",absint($id)));}
function dgs_quiz_manage($q){return $q&&(current_user_can('dgs_manage_education')||(dgs_edu_role(get_current_user_id(),'dgs_teacher')&&(int)$q->teacher_id===get_current_user_id()&&dgs_grade_can_edit(dgs_edu_class($q->class_id),$q->subject_id)));}
function dgs_quiz_attempt($q,$uid){global $wpdb;$t=dgs_quiz_tables();return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['attempts']} WHERE quiz_id=%d AND student_id=%d",$q,$uid));}
function dgs_quiz_member($q){if(!$q||!dgs_edu_role(get_current_user_id(),'dgs_student'))return false;global $wpdb;$t=dgs_edu_tables();return (bool)$wpdb->get_var($wpdb->prepare("SELECT e.id FROM {$t['enrollments']} e JOIN {$t['classes']} c ON c.id=e.class_id WHERE e.class_id=%d AND e.student_id=%d AND e.school_year=%s AND c.active=1",$q->class_id,get_current_user_id(),$q->school_year));}
function dgs_quiz_questions($id,$private=false){global $wpdb;$t=dgs_quiz_tables();return $wpdb->get_results($wpdb->prepare("SELECT id,position,prompt,options,weight,image_mime".($private?',correct,explanation':'')." FROM {$t['questions']} WHERE quiz_id=%d ORDER BY position,id",$id));}
function dgs_quiz_date($date,$clock,$year){$g=dgs_att_parse_day($date,$year);$clock=dgs_edu_digits($clock);if(!$g||!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/D',$clock))return 0;return (new DateTimeImmutable($g.' '.$clock.':00',wp_timezone()))->getTimestamp();}
function dgs_quiz_weight($raw){if(!is_scalar($raw))return 0;$s=str_replace('٫','.',dgs_edu_digits((string)$raw));if(!preg_match('/^(\d{1,3})(?:\.(\d{1,2}))?$/D',$s,$m))return 0;$v=(int)$m[1]*100+(int)str_pad($m[2]??'',2,'0');return $v>0&&$v<=10000?$v:0;}
function dgs_quiz_image_payload($file){
 if(!is_array($file)||($file['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??''))return dgs_edu_error('image','بارگذاری تصویر کامل نشد.');
 if(($file['size']??0)>2*1024*1024)return dgs_edu_error('image','حجم هر تصویر حداکثر ۲ مگابایت است.');$bytes=file_get_contents($file['tmp_name']);$info=@getimagesizefromstring($bytes);if(!$info||!in_array($info['mime']??'',array('image/png','image/jpeg'),true)||$info[0]*$info[1]>16000000)return dgs_edu_error('image','فقط تصویر واقعی PNG یا JPG تا ۱۶ میلیون پیکسل مجاز است.');return array('image_mime'=>$info['mime'],'image_data'=>base64_encode($bytes));
}
function dgs_quiz_save($data,$files=array()){
 if(get_option('dgs_quiz_schema')!=='1'||get_option('dgs_quiz_files')!=='1')return dgs_edu_error('schema','جدول آزمون آماده نیست؛ یک بار صفحه را تازه کنید و دوباره تلاش کنید.');
 foreach($data as $v)if(!is_scalar($v))return dgs_edu_error('input','اطلاعات معتبر نیست.');
 $id=absint($data['id']??0);$old=$id?dgs_quiz_row($id):null;if($id&&(!dgs_quiz_manage($old)))return dgs_edu_error('access','این کوییز قابل ویرایش نیست.');
 $class=dgs_edu_class($data['class_id']??0);$subject=absint($data['subject_id']??0);$year=dgs_edu_year((string)($data['year']??''));if(!$class||$class->school_year!==$year||!dgs_grade_can_edit($class,$subject))return dgs_edu_error('access','کلاس و درس تخصیص‌یافته معتبر انتخاب کنید.');
 $rev=(string)($data['revision']??'');if(!ctype_digit($rev)||(int)$rev!==($old?(int)$old->revision:0))return dgs_edu_error('stale','نسخه کوییز تغییر کرده؛ صفحه را تازه کنید.');
 $title=sanitize_text_field((string)($data['title']??''));$open=dgs_quiz_date((string)($data['open_date']??''),(string)($data['open_time']??''),$year);$close=dgs_quiz_date((string)($data['close_date']??''),(string)($data['close_time']??''),$year);$duration=dgs_edu_digits((string)($data['duration']??''));$publish=($old&&$old->status==='published')||($data['publish']??'')==='1';
 if($title===''||dgs_edu_length($title)>150||!$open||$close<=$open||!ctype_digit($duration)||(int)$duration<1||(int)$duration>180||($publish&&!$old&&$close<=time()))return dgs_edu_error('schedule','عنوان، زمان شروع و پایان و مدت ۱ تا ۱۸۰ دقیقه را درست وارد کنید.');
 /* کلید پاسخ: رشته‌ای از ارقام ۰ تا ۴ (۰ = هنوز تعیین نشده) به تعداد سؤال‌ها */
 $count=dgs_edu_digits((string)($data['qcount']??''));if(!ctype_digit($count)||(int)$count<1||(int)$count>60)return dgs_edu_error('questions','تعداد سؤال باید بین ۱ تا ۶۰ باشد.');
 $n=(int)$count;$key=dgs_edu_digits((string)($data['key']??''));if(!preg_match('/^[0-4]+$/D',$key)||strlen($key)!==$n)return dgs_edu_error('key','کلید پاسخ با تعداد سؤال‌ها هماهنگ نیست؛ صفحه را تازه کنید و کلید را دوباره بررسی کنید.');
 $missing=array();for($i=0;$i<$n;$i++)if($key[$i]==='0')$missing[]=$i+1;
 if($publish&&$missing)return dgs_edu_error('key','کلید پاسخ ناقص است؛ سؤال‌های بدون پاسخ: '.dgs_fa_digits(implode('، ',array_slice($missing,0,15))).(count($missing)>15?' و ...':'').'.');
 /* فایل‌ها */
 $old_exam=$old?(string)($old->exam_file??''):'';$old_answer=$old?(string)($old->answer_file??''):'';$remove_exam=($data['remove_exam']??'')==='1';$remove_answer=($data['remove_answer']??'')==='1';
 $sent=function($field)use($files){return isset($files[$field])&&is_array($files[$field])&&(int)($files[$field]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;};
 if($publish&&!($sent('exam_pdf')||($old_exam!==''&&!$remove_exam)))return dgs_edu_error('pdf','برای انتشار باید فایل PDF آزمون را بارگذاری کنید.');
 $stored=array();
 if($sent('exam_pdf')){$r=dgs_quiz_pdf_store($files['exam_pdf'],'آزمون');if(is_wp_error($r))return $r;$stored['exam']=$r;}
 if($sent('answer_pdf')){$r=dgs_quiz_pdf_store($files['answer_pdf'],'پاسخنامه');if(is_wp_error($r)){if(isset($stored['exam']))dgs_quiz_pdf_delete($stored['exam']['file']);return $r;}$stored['answer']=$r;}
 $cleanup=function()use(&$stored){foreach($stored as $item)dgs_quiz_pdf_delete($item['file']);};
 $exam_file=isset($stored['exam'])?$stored['exam']['file']:($remove_exam?'':$old_exam);$exam_name=isset($stored['exam'])?$stored['exam']['name']:($remove_exam?'':(string)($old->exam_name??''));
 $answer_file=isset($stored['answer'])?$stored['answer']['file']:($remove_answer?'':$old_answer);$answer_name=isset($stored['answer'])?$stored['answer']['name']:($remove_answer?'':(string)($old->answer_name??''));
 global $wpdb;$t=dgs_quiz_tables();$existing=$old?array_values(dgs_quiz_questions($id,true)):array();
 $wpdb->query('START TRANSACTION');$quiz=array('class_id'=>$class->id,'subject_id'=>$subject,'school_year'=>$year,'title'=>$title,'opens_at'=>$open,'closes_at'=>$close,'duration'=>(int)$duration,'status'=>$publish?'published':'draft','revision'=>(int)$rev+1,'updated_at'=>current_time('mysql'),'exam_file'=>$exam_file,'exam_name'=>$exam_name,'answer_file'=>$answer_file,'answer_name'=>$answer_name);
 if($old){$ok=$wpdb->update($t['quizzes'],$quiz,array('id'=>$id,'revision'=>(int)$rev));}else{$quiz['teacher_id']=get_current_user_id();$ok=$wpdb->insert($t['quizzes'],$quiz);$id=(int)$wpdb->insert_id;}
 if(!$ok){$wpdb->query('ROLLBACK');$cleanup();return dgs_edu_error('write','ذخیره نشد یا کوییز هم‌زمان تغییر کرده است.');}
 /* هر سؤال یک ردیف استاندارد است: متن «سؤال n»، گزینه‌های ۱ تا ۴، پاسخ صحیح از کلید و وزن برابر؛ منطق نمره‌دهی تغییری نکرده است. */
 for($i=0;$i<$n;$i++){
  $row=array('position'=>$i+1,'prompt'=>'سؤال '.($i+1),'options'=>wp_json_encode(array('1','2','3','4')),'correct'=>(int)$key[$i],'weight'=>100,'explanation'=>'','image_mime'=>'','image_data'=>'');
  if(isset($existing[$i]))$ok=$wpdb->update($t['questions'],$row,array('id'=>(int)$existing[$i]->id,'quiz_id'=>$id));else{$row['quiz_id']=$id;$ok=$wpdb->insert($t['questions'],$row);}
  if($ok===false){$wpdb->query('ROLLBACK');$cleanup();return dgs_edu_error('write','ذخیره کلید پاسخ کامل نشد.');}
 }
 foreach(array_slice($existing,$n) as $extra){if($wpdb->delete($t['questions'],array('id'=>(int)$extra->id,'quiz_id'=>$id))===false){$wpdb->query('ROLLBACK');$cleanup();return dgs_edu_error('write','حذف سؤال اضافه انجام نشد.');}}
 // تعديل کلید یا تعداد، نتیجه‌های قبلی را با حفظ پاسخ‌ها دوباره محاسبه می‌کند.
 $updated_questions=dgs_quiz_questions($id,true);$attempts=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['attempts']} WHERE quiz_id=%d",$id));
 foreach($attempts as $a){$change=array('revision'=>(int)$a->revision+1);if($a->submitted_at)$change['result']=wp_json_encode(dgs_quiz_score($updated_questions,json_decode($a->answers,true)?:array()));else $change['deadline']=min((int)$a->started_at+(int)$duration*60,$close);
  if(!$wpdb->update($t['attempts'],$change,array('id'=>$a->id,'revision'=>$a->revision))){$wpdb->query('ROLLBACK');$cleanup();return dgs_edu_error('stale','پاسخ‌ها هم‌زمان تغییر کردند؛ ذخیره را دوباره انجام دهید.');}}
 $wpdb->query('COMMIT');
 if($old_exam!==''&&$old_exam!==$exam_file)dgs_quiz_pdf_delete($old_exam);if($old_answer!==''&&$old_answer!==$answer_file)dgs_quiz_pdf_delete($old_answer);
 dgs_edu_audit($publish?'quiz_publish':'quiz_draft',$id);return array('id'=>$id,'published'=>$publish,'revision'=>(int)$rev+1);
}
function dgs_quiz_clean_answers($raw,$questions){
 if(!is_array($raw)||count($raw)>60)return dgs_edu_error('answers','پاسخ‌ها معتبر نیستند.');$allowed=array();foreach($questions as $q)$allowed[(int)$q->id]=true;$out=array();foreach($raw as $id=>$answer){if(!ctype_digit((string)$id)||!isset($allowed[(int)$id])||!is_scalar($answer)||!in_array((string)$answer,array('0','1','2','3','4'),true))return dgs_edu_error('answers','شماره سؤال یا گزینه معتبر نیست.');if((int)$answer)$out[(int)$id]=(int)$answer;}return $out;
}
function dgs_quiz_score($questions,$answers){$c=0;$w=0;$earned=0;$possible=0;foreach($questions as $q){$possible+=(int)$q->weight;$answer=(int)($answers[$q->id]??0);if(!$answer)continue;if($answer===(int)$q->correct){$c++;$earned+=(int)$q->weight;}else $w++;}$total=count($questions);return array('correct'=>$c,'wrong'=>$w,'blank'=>$total-$c-$w,'earned'=>$earned,'possible'=>$possible,'grade'=>$possible?round($earned/$possible*20,2):0,'percent'=>$total?round($c/$total*100,2):0);}
function dgs_quiz_finish($q,$a,$answers=null){
 global $wpdb;$t=dgs_quiz_tables();if($a->submitted_at)return $a;
 if($answers===null)$answers=json_decode($a->answers,true)?:array();$result=dgs_quiz_score(dgs_quiz_questions($q->id,true),$answers);$ok=$wpdb->update($t['attempts'],array('answers'=>wp_json_encode((object)$answers),'result'=>wp_json_encode($result),'submitted_at'=>min(time(),(int)$a->deadline),'revision'=>(int)$a->revision+1),array('id'=>$a->id,'revision'=>$a->revision,'submitted_at'=>0));if(!$ok)return dgs_edu_error('stale','پاسخ‌ها هم‌زمان تغییر کردند؛ صفحه را تازه کنید.');return dgs_quiz_attempt($q->id,$a->student_id);
}
function dgs_quiz_student_action_locked($op,$data){
 if(!dgs_edu_role(get_current_user_id(),'dgs_student'))return dgs_edu_error('access','این عملیات فقط برای دانش‌آموز مجاز است.');foreach($data as $k=>$v)if(!is_scalar($v))return dgs_edu_error('input','اطلاعات معتبر نیست.');
 $q=dgs_quiz_row($data['id']??0);$uid=get_current_user_id();$a=$q?dgs_quiz_attempt($q->id,$uid):null;$now=time();if(!$q||$q->status!=='published'||(!$a&&!dgs_quiz_member($q)))return dgs_edu_error('access','به این کوییز دسترسی ندارید.');
 if($op==='start'){
  if(!$a){if($now<(int)$q->opens_at||$now>=(int)$q->closes_at)return dgs_edu_error('time','اکنون در بازه شرکت در کوییز نیستید.');global $wpdb;$t=dgs_quiz_tables();$wpdb->insert($t['attempts'],array('quiz_id'=>$q->id,'student_id'=>$uid,'started_at'=>$now,'deadline'=>min($now+(int)$q->duration*60,(int)$q->closes_at),'answers'=>'{}','result'=>'{}'));$a=dgs_quiz_attempt($q->id,$uid);if(!$a)return dgs_edu_error('write','شروع آزمون ثبت نشد.');}
 }elseif(!in_array($op,array('save','submit','result'),true)||!$a)return dgs_edu_error('attempt','آزمون یا عملیات معتبر نیست.');
 if(!$a->submitted_at&&$now>=(int)$a->deadline){$a=dgs_quiz_finish($q,$a);if(is_wp_error($a))return $a;}
 if(($op==='save'||$op==='submit')&&!$a->submitted_at){
  if((string)($data['quiz_revision']??'')!==(string)$q->revision)return dgs_edu_error('quiz_changed','آزمون ویرایش شده است؛ صفحه را تازه کنید تا فایل، سؤال‌ها و زمان به‌روز شوند.');
  $revision=(string)($data['revision']??'');if(!ctype_digit($revision)||(int)$revision!==(int)$a->revision)return dgs_edu_error('stale','نسخه پاسخ‌ها تغییر کرده؛ با بارگذاری دوباره ادامه دهید.');$raw=(string)($data['answers']??'');if(strlen($raw)>10000)return dgs_edu_error('answers','پاسخ‌ها معتبر نیستند.');$answers=dgs_quiz_clean_answers(json_decode($raw,true),dgs_quiz_questions($q->id));if(is_wp_error($answers))return $answers;
  if($op==='submit'){$a=dgs_quiz_finish($q,$a,$answers);if(is_wp_error($a))return $a;}
  else{global $wpdb;$t=dgs_quiz_tables();$ok=$wpdb->update($t['attempts'],array('answers'=>wp_json_encode((object)$answers),'revision'=>(int)$a->revision+1),array('id'=>$a->id,'revision'=>$a->revision,'submitted_at'=>0));if(!$ok)return dgs_edu_error('stale','ذخیره انجام نشد یا نسخه پاسخ‌ها تغییر کرده است.');$a=dgs_quiz_attempt($q->id,$uid);}
 }
 if($op==='result')return dgs_quiz_result_payload($q,$a);
 if($a->submitted_at)return array('over'=>true,'id'=>(int)$q->id);
 if($op==='save')return array('over'=>false,'quiz_revision'=>(int)$q->revision,'revision'=>(int)$a->revision,'now'=>time(),'deadline'=>(int)$a->deadline);
 $questions=dgs_quiz_questions($q->id);$public=array();foreach($questions as $question)$public[]=array('id'=>(int)$question->id,'prompt'=>$question->prompt,'options'=>json_decode($question->options,true),'image'=>$question->image_mime?dgs_quiz_image_url($q->id,$question->id):'');
 return array('quiz_revision'=>(int)$q->revision,'id'=>(int)$q->id,'title'=>$q->title,'questions'=>$public,'answers'=>json_decode($a->answers,true)?:new stdClass(),'revision'=>(int)$a->revision,'deadline'=>(int)$a->deadline,'now'=>time(),'over'=>false,'pdf'=>dgs_quiz_has_pdf($q)?array('view'=>dgs_quiz_file_url($q->id,'exam',false),'download'=>dgs_quiz_file_url($q->id,'exam',true)):null);
}
function dgs_quiz_result_payload($q,$a){
 if(!$a||!$a->submitted_at)return dgs_edu_error('result','آزمون هنوز نهایی نشده است.');if(!dgs_quiz_manage($q)&&((int)$a->student_id!==get_current_user_id()||!dgs_edu_role(get_current_user_id(),'dgs_student')))return dgs_edu_error('access','نتیجه متعلق به شما نیست.');
 if(!dgs_quiz_manage($q)&&time()<(int)$q->closes_at)return array('pending'=>true,'release_at'=>(int)$q->closes_at);
 return array('pending'=>false,'result'=>json_decode($a->result,true),'answers'=>json_decode($a->answers,true),'questions'=>dgs_quiz_questions($q->id,true));
}
function dgs_quiz_image_url($quiz,$question){return add_query_arg(array('action'=>'dgs_quiz_image','quiz'=>$quiz,'question'=>$question,'nonce'=>wp_create_nonce('dgs_quiz_image')),admin_url('admin-post.php'));}
add_action('admin_post_dgs_quiz_image',function(){
 if(!isset($_GET['nonce'])||!is_string($_GET['nonce'])||!wp_verify_nonce($_GET['nonce'],'dgs_quiz_image'))wp_die('دسترسی مجاز نیست.','',array('response'=>403));$q=dgs_quiz_row(is_scalar($_GET['quiz']??null)?$_GET['quiz']:0);$allowed=dgs_quiz_manage($q);
 if(!$allowed&&$q&&dgs_edu_role(get_current_user_id(),'dgs_student')){$a=dgs_quiz_attempt($q->id,get_current_user_id());$allowed=$a&&((!$a->submitted_at&&time()<(int)$a->deadline)||($a->submitted_at&&time()>=(int)$q->closes_at));}
 if(!$allowed)wp_die('دسترسی مجاز نیست.','',array('response'=>403));global $wpdb;$t=dgs_quiz_tables();$image=$wpdb->get_row($wpdb->prepare("SELECT image_mime,image_data FROM {$t['questions']} WHERE quiz_id=%d AND id=%d",$q->id,is_scalar($_GET['question']??null)?absint($_GET['question']):0));if(!$image||!in_array($image->image_mime,array('image/png','image/jpeg'),true))wp_die('تصویر موجود نیست.','',array('response'=>404));nocache_headers();header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');header('Content-Type: '.$image->image_mime);echo base64_decode($image->image_data);exit;
});
add_action('wp_ajax_dgs_quiz',function(){
 check_ajax_referer('dgs_quiz','nonce');$data=wp_unslash($_POST);$op=is_string($data['op']??null)?$data['op']:'';
 $result=$op==='editor_save'?dgs_quiz_save($data,$_FILES):($op==='editor_delete'?dgs_quiz_delete($data):dgs_quiz_student_action($op,$data));
 if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message(),'code'=>$result->get_error_code()),400);wp_send_json_success($result);
});

function dgs_quiz_student_action($op,$data){
 // قفل کوتاه همان آزمون؛ ذخیره پاسخ با تغییر کلید یا زمان‌بندی تداخل نمی‌کند.
 if(!isset($data['id'])||!is_scalar($data['id']))return dgs_edu_error('input','شناسه آزمون معتبر نیست.');
 global $wpdb;$t=dgs_quiz_tables();$wpdb->query('START TRANSACTION');
 if($wpdb->query($wpdb->prepare("UPDATE {$t['quizzes']} SET id=id WHERE id=%d",absint($data['id'])))===false){$wpdb->query('ROLLBACK');return dgs_edu_error('write','درخواست کامل نشد.');}
 $result=dgs_quiz_student_action_locked($op,$data);$wpdb->query(is_wp_error($result)?'ROLLBACK':'COMMIT');return $result;
}
function dgs_quiz_delete($data){
 foreach($data as $v)if(!is_scalar($v))return dgs_edu_error('input','درخواست معتبر نیست.');
 $q=dgs_quiz_row($data['id']??0);if(!dgs_quiz_manage($q))return dgs_edu_error('access','اجازه حذف این آزمون را ندارید.');
 if(time()<(int)$q->closes_at)return dgs_edu_error('active','حذف فقط بعد از پایان بازه آزمون مجاز است.');
 $rev=(string)($data['revision']??'');if(!ctype_digit($rev)||(int)$rev!==(int)$q->revision)return dgs_edu_error('stale','نسخه آزمون تغییر کرده؛ صفحه را تازه کنید.');
 global $wpdb;$t=dgs_quiz_tables();$ok=$wpdb->update($t['quizzes'],array('status'=>'deleted','revision'=>(int)$rev+1),array('id'=>$q->id,'revision'=>(int)$rev));
 if(!$ok)return dgs_edu_error('stale','حذف انجام نشد یا آزمون هم‌زمان تغییر کرد.');dgs_edu_audit('quiz_delete',$q->id);return array('deleted'=>true);
}
