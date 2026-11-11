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
function dgs_quiz_row($id){global $wpdb;$t=dgs_quiz_tables();return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['quizzes']} WHERE id=%d",absint($id)));}
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
 if(get_option('dgs_quiz_schema')!=='1')return dgs_edu_error('schema','جدول آزمون آماده نیست.');foreach($data as $v)if(!is_scalar($v))return dgs_edu_error('input','اطلاعات معتبر نیست.');
 $id=absint($data['id']??0);$old=$id?dgs_quiz_row($id):null;if($id&&(!dgs_quiz_manage($old)||$old->status!=='draft'))return dgs_edu_error('access','این کوییز قابل ویرایش نیست.');
 $class=dgs_edu_class($data['class_id']??0);$subject=absint($data['subject_id']??0);$year=dgs_edu_year((string)($data['year']??''));if(!$class||$class->school_year!==$year||!dgs_grade_can_edit($class,$subject))return dgs_edu_error('access','کلاس و درس تخصیص‌یافته معتبر انتخاب کنید.');
 $rev=(string)($data['revision']??'');if(!ctype_digit($rev)||(int)$rev!==($old?(int)$old->revision:0))return dgs_edu_error('stale','نسخه کوییز تغییر کرده؛ صفحه را تازه کنید.');
 $title=sanitize_text_field((string)($data['title']??''));$open=dgs_quiz_date((string)($data['open_date']??''),(string)($data['open_time']??''),$year);$close=dgs_quiz_date((string)($data['close_date']??''),(string)($data['close_time']??''),$year);$duration=dgs_edu_digits((string)($data['duration']??''));$publish=($data['publish']??'')==='1';
 if($title===''||dgs_edu_length($title)>150||!$open||$close<=$open||!ctype_digit($duration)||(int)$duration<1||(int)$duration>180||($publish&&$close<=time()))return dgs_edu_error('schedule','عنوان، زمان شروع و پایان و مدت ۱ تا ۱۸۰ دقیقه را درست وارد کنید.');
 $raw=(string)($data['questions']??'');if(strlen($raw)>300000)return dgs_edu_error('size','حجم متن پرسش‌ها زیاد است.');$questions=json_decode($raw,true);if(!is_array($questions)||count($questions)>60||($publish&&!$questions))return dgs_edu_error('questions','کوییز منتشرشده باید ۱ تا ۶۰ سؤال داشته باشد.');
 $existing=$old?dgs_quiz_questions($id,true):array();$known=array();foreach($existing as $q)$known[(int)$q->id]=$q;$valid=array();$seen=array();$image_count=0;
 foreach(array_values($questions) as $i=>$item){
  if(!is_array($item))return dgs_edu_error('question','سؤال معتبر نیست.');foreach(array('id','prompt','correct','weight','explanation','remove_image') as $key)if(isset($item[$key])&&!is_scalar($item[$key]))return dgs_edu_error('question','فیلد سؤال معتبر نیست.');
  $qid=absint($item['id']??0);if($qid&&(!isset($known[$qid])||isset($seen[$qid])))return dgs_edu_error('question','شناسه سؤال معتبر نیست.');if($qid)$seen[$qid]=true;
  $prompt=sanitize_textarea_field((string)($item['prompt']??''));$explanation=sanitize_textarea_field((string)($item['explanation']??''));$options=$item['options']??null;$correct=(string)($item['correct']??'');$weight=dgs_quiz_weight($item['weight']??'');
  if($prompt===''||dgs_edu_length($prompt)>2000||dgs_edu_length($explanation)>2000||!is_array($options)||count($options)!==4||!in_array($correct,array('1','2','3','4'),true)||!$weight)return dgs_edu_error('question','متن سؤال، چهار گزینه، پاسخ درست و نمره مثبت (حداکثر ۱۰۰) الزامی است.');
  $clean=array();foreach(array_values($options) as $option){if(!is_string($option)||trim($option)===''||dgs_edu_length($option)>500)return dgs_edu_error('option','هر گزینه باید متن ۱ تا ۵۰۰ نویسه باشد.');$cleaned=sanitize_textarea_field($option);if(trim($cleaned)==='')return dgs_edu_error('option','گزینه خالی معتبر نیست.');$clean[]=$cleaned;}
  $row=array('position'=>$i+1,'prompt'=>$prompt,'options'=>wp_json_encode($clean),'correct'=>(int)$correct,'weight'=>$weight,'explanation'=>$explanation);
  if(!empty($item['remove_image']))$row+=array('image_mime'=>'','image_data'=>'');
  if(isset($files['qimage_'.$i])&&($files['qimage_'.$i]['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE){$image_count++;if($image_count>10)return dgs_edu_error('image','در هر ذخیره حداکثر ۱۰ تصویر بارگذاری کنید.');$image=dgs_quiz_image_payload($files['qimage_'.$i]);if(is_wp_error($image))return $image;$row=array_merge($row,$image);}
  if(!$qid)$row+=array('image_mime'=>'','image_data'=>'');$valid[]=array('id'=>$qid,'row'=>$row);
 }
 global $wpdb;$t=dgs_quiz_tables();$wpdb->query('START TRANSACTION');$quiz=array('class_id'=>$class->id,'subject_id'=>$subject,'school_year'=>$year,'title'=>$title,'opens_at'=>$open,'closes_at'=>$close,'duration'=>(int)$duration,'status'=>$publish?'published':'draft','revision'=>(int)$rev+1,'updated_at'=>current_time('mysql'));
 if($old){$ok=$wpdb->update($t['quizzes'],$quiz,array('id'=>$id,'revision'=>(int)$rev,'status'=>'draft'));}else{$quiz['teacher_id']=get_current_user_id();$ok=$wpdb->insert($t['quizzes'],$quiz);$id=(int)$wpdb->insert_id;}
 if(!$ok){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ذخیره نشد یا کوییز هم‌زمان تغییر کرده است.');}
 foreach($valid as $v){$row=$v['row'];if($v['id'])$ok=$wpdb->update($t['questions'],$row,array('id'=>$v['id'],'quiz_id'=>$id));else{$row['quiz_id']=$id;$ok=$wpdb->insert($t['questions'],$row);}if($ok===false){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ذخیره سؤال کامل نشد.');}}
 foreach($known as $qid=>$unused)if(!isset($seen[$qid])&&$wpdb->delete($t['questions'],array('id'=>$qid,'quiz_id'=>$id))===false){$wpdb->query('ROLLBACK');return dgs_edu_error('write','حذف سؤال انجام نشد.');}
 $wpdb->query('COMMIT');dgs_edu_audit($publish?'quiz_publish':'quiz_draft',$id);return array('id'=>$id,'published'=>$publish,'revision'=>(int)$rev+1);
}
function dgs_quiz_clean_answers($raw,$questions){
 if(!is_array($raw)||count($raw)>60)return dgs_edu_error('answers','پاسخ‌ها معتبر نیستند.');$allowed=array();foreach($questions as $q)$allowed[(int)$q->id]=true;$out=array();foreach($raw as $id=>$answer){if(!ctype_digit((string)$id)||!isset($allowed[(int)$id])||!is_scalar($answer)||!in_array((string)$answer,array('0','1','2','3','4'),true))return dgs_edu_error('answers','شماره سؤال یا گزینه معتبر نیست.');if((int)$answer)$out[(int)$id]=(int)$answer;}return $out;
}
function dgs_quiz_score($questions,$answers){$c=0;$w=0;$earned=0;$possible=0;foreach($questions as $q){$possible+=(int)$q->weight;$answer=(int)($answers[$q->id]??0);if(!$answer)continue;if($answer===(int)$q->correct){$c++;$earned+=(int)$q->weight;}else $w++;}$total=count($questions);return array('correct'=>$c,'wrong'=>$w,'blank'=>$total-$c-$w,'earned'=>$earned,'possible'=>$possible,'grade'=>$possible?round($earned/$possible*20,2):0,'percent'=>$total?round($c/$total*100,2):0);}
function dgs_quiz_finish($q,$a,$answers=null){
 global $wpdb;$t=dgs_quiz_tables();if($a->submitted_at)return $a;
 if($answers===null)$answers=json_decode($a->answers,true)?:array();$result=dgs_quiz_score(dgs_quiz_questions($q->id,true),$answers);$ok=$wpdb->update($t['attempts'],array('answers'=>wp_json_encode((object)$answers),'result'=>wp_json_encode($result),'submitted_at'=>min(time(),(int)$a->deadline),'revision'=>(int)$a->revision+1),array('id'=>$a->id,'revision'=>$a->revision,'submitted_at'=>0));if(!$ok)return dgs_edu_error('stale','پاسخ‌ها هم‌زمان تغییر کردند؛ صفحه را تازه کنید.');return dgs_quiz_attempt($q->id,$a->student_id);
}
function dgs_quiz_student_action($op,$data){
 if(!dgs_edu_role(get_current_user_id(),'dgs_student'))return dgs_edu_error('access','این عملیات فقط برای دانش‌آموز مجاز است.');foreach($data as $k=>$v)if(!is_scalar($v))return dgs_edu_error('input','اطلاعات معتبر نیست.');
 $q=dgs_quiz_row($data['id']??0);$uid=get_current_user_id();$a=$q?dgs_quiz_attempt($q->id,$uid):null;$now=time();if(!$q||$q->status!=='published'||(!$a&&!dgs_quiz_member($q)))return dgs_edu_error('access','به این کوییز دسترسی ندارید.');
 if($op==='start'){
  if(!$a){if($now<(int)$q->opens_at||$now>=(int)$q->closes_at)return dgs_edu_error('time','اکنون در بازه شرکت در کوییز نیستید.');global $wpdb;$t=dgs_quiz_tables();$wpdb->insert($t['attempts'],array('quiz_id'=>$q->id,'student_id'=>$uid,'started_at'=>$now,'deadline'=>min($now+(int)$q->duration*60,(int)$q->closes_at),'answers'=>'{}','result'=>'{}'));$a=dgs_quiz_attempt($q->id,$uid);if(!$a)return dgs_edu_error('write','شروع آزمون ثبت نشد.');}
 }elseif(!in_array($op,array('save','submit','result'),true)||!$a)return dgs_edu_error('attempt','آزمون یا عملیات معتبر نیست.');
 if(!$a->submitted_at&&$now>=(int)$a->deadline){$a=dgs_quiz_finish($q,$a);if(is_wp_error($a))return $a;}
 if(($op==='save'||$op==='submit')&&!$a->submitted_at){
  $revision=(string)($data['revision']??'');if(!ctype_digit($revision)||(int)$revision!==(int)$a->revision)return dgs_edu_error('stale','نسخه پاسخ‌ها تغییر کرده؛ با بارگذاری دوباره ادامه دهید.');$raw=(string)($data['answers']??'');if(strlen($raw)>10000)return dgs_edu_error('answers','پاسخ‌ها معتبر نیستند.');$answers=dgs_quiz_clean_answers(json_decode($raw,true),dgs_quiz_questions($q->id));if(is_wp_error($answers))return $answers;
  if($op==='submit'){$a=dgs_quiz_finish($q,$a,$answers);if(is_wp_error($a))return $a;}
  else{global $wpdb;$t=dgs_quiz_tables();$ok=$wpdb->update($t['attempts'],array('answers'=>wp_json_encode((object)$answers),'revision'=>(int)$a->revision+1),array('id'=>$a->id,'revision'=>$a->revision,'submitted_at'=>0));if(!$ok)return dgs_edu_error('stale','ذخیره انجام نشد یا نسخه پاسخ‌ها تغییر کرده است.');$a=dgs_quiz_attempt($q->id,$uid);}
 }
 if($op==='result')return dgs_quiz_result_payload($q,$a);
 if($a->submitted_at)return array('over'=>true,'id'=>(int)$q->id);
 if($op==='save')return array('over'=>false,'revision'=>(int)$a->revision,'now'=>time(),'deadline'=>(int)$a->deadline);
 $questions=dgs_quiz_questions($q->id);$public=array();foreach($questions as $question)$public[]=array('id'=>(int)$question->id,'prompt'=>$question->prompt,'options'=>json_decode($question->options,true),'image'=>$question->image_mime?dgs_quiz_image_url($q->id,$question->id):'');
 return array('id'=>(int)$q->id,'title'=>$q->title,'questions'=>$public,'answers'=>json_decode($a->answers,true)?:new stdClass(),'revision'=>(int)$a->revision,'deadline'=>(int)$a->deadline,'now'=>time(),'over'=>false);
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
 $result=$op==='editor_save'?dgs_quiz_save($data,$_FILES):dgs_quiz_student_action($op,$data);
 if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message()),400);wp_send_json_success($result);
});
