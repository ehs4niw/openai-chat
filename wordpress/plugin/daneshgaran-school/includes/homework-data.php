<?php
if(!defined('ABSPATH'))exit;
function dgs_hw_tables(){global $wpdb;return array('tasks'=>$wpdb->prefix.'dgs_homework','submissions'=>$wpdb->prefix.'dgs_hw_submissions');}
add_action('init',function(){
 if(get_option('dgs_hw_schema')==='1')return;global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_hw_tables();$cc=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t['tasks']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 class_id bigint(20) unsigned NOT NULL,
 subject_id bigint(20) unsigned NOT NULL,
 school_year varchar(9) NOT NULL,
 creator_id bigint(20) unsigned NOT NULL,
 title varchar(150) NOT NULL,
 instructions text NOT NULL,
 due_at bigint(20) unsigned NOT NULL,
 revision bigint(20) unsigned NOT NULL DEFAULT 1,
 active tinyint unsigned NOT NULL DEFAULT 1,
 PRIMARY KEY  (id),
 KEY class_year (class_id,school_year)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['submissions']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 task_id bigint(20) unsigned NOT NULL DEFAULT 0,
 student_id bigint(20) unsigned NOT NULL,
 class_id bigint(20) unsigned NOT NULL,
 subject_id bigint(20) unsigned NOT NULL,
 school_year varchar(9) NOT NULL,
 recipient_id bigint(20) unsigned NOT NULL,
 title varchar(150) NOT NULL,
 message text NOT NULL,
 stored_file varchar(50) NOT NULL,
 file_name varchar(200) NOT NULL,
 file_mime varchar(100) NOT NULL,
 file_size bigint(20) unsigned NOT NULL,
 submitted_at bigint(20) unsigned NOT NULL,
 status varchar(12) NOT NULL DEFAULT 'pending',
 feedback text NOT NULL,
 score smallint unsigned DEFAULT NULL,
 reviewed_by bigint(20) unsigned NOT NULL DEFAULT 0,
 reviewed_at bigint(20) unsigned NOT NULL DEFAULT 0,
 revision bigint(20) unsigned NOT NULL DEFAULT 1,
 request_token varchar(36) NOT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY request_token (request_token),
 KEY student_year (student_id,school_year),
 KEY task_student (task_id,student_id),
 KEY recipient_year (recipient_id,school_year)
 ) ENGINE=InnoDB $cc;");
 foreach($t as $table)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;update_option('dgs_hw_schema','1',false);
},14);
function dgs_hw_task($id){global $wpdb;$t=dgs_hw_tables();return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['tasks']} WHERE id=%d",absint($id)));}
function dgs_hw_submission($id){global $wpdb;$t=dgs_hw_tables();return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['submissions']} WHERE id=%d",absint($id)));}
function dgs_hw_can_review($s){
 if(!$s)return false;if(current_user_can('dgs_manage_education'))return true;$uid=get_current_user_id();
 if(dgs_edu_role($uid,'dgs_teacher'))return dgs_grade_can_edit(dgs_edu_class($s->class_id),$s->subject_id);
 if(dgs_edu_role($uid,'dgs_counselor')&&(int)$s->recipient_id===$uid){global $wpdb;$e=dgs_edu_tables();return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$e['counseling']} WHERE student_id=%d AND counselor_id=%d AND school_year=%s",$s->student_id,$uid,$s->school_year));}return false;
}
function dgs_hw_can_read($s){return $s&&(dgs_hw_can_review($s)||(dgs_edu_role(get_current_user_id(),'dgs_student')&&(int)$s->student_id===get_current_user_id()));}
function dgs_hw_student_class($year){global $wpdb;$e=dgs_edu_tables();return $wpdb->get_row($wpdb->prepare("SELECT c.* FROM {$e['enrollments']} e JOIN {$e['classes']} c ON c.id=e.class_id WHERE e.student_id=%d AND e.school_year=%s",get_current_user_id(),$year));}
function dgs_hw_recipients($student,$class,$subject,$year){
 global $wpdb;$e=dgs_edu_tables();$out=array();$teachers=$wpdb->get_results($wpdb->prepare("SELECT a.teacher_id,u.display_name FROM {$e['teaching']} a JOIN {$wpdb->users} u ON u.ID=a.teacher_id WHERE a.class_id=%d AND a.subject_id=%d",$class,$subject));foreach($teachers as $u)if(dgs_edu_role($u->teacher_id,'dgs_teacher'))$out[(int)$u->teacher_id]='معلم مربوطه — '.$u->display_name;
 $counselors=$wpdb->get_results($wpdb->prepare("SELECT a.counselor_id,u.display_name FROM {$e['counseling']} a JOIN {$wpdb->users} u ON u.ID=a.counselor_id WHERE a.student_id=%d AND a.school_year=%s",$student,$year));foreach($counselors as $u)if(dgs_edu_role($u->counselor_id,'dgs_counselor'))$out[(int)$u->counselor_id]='مشاور اختصاصی — '.$u->display_name;return $out;
}
function dgs_hw_save_task($data){
 foreach($data as $v)if(!is_scalar($v))return dgs_edu_error('input','اطلاعات درخواست معتبر نیست.');
 if(isset($data['hw_pair'])){if(!is_string($data['hw_pair'])||!preg_match('/^(\d+):(\d+)$/D',$data['hw_pair'],$pair))return dgs_edu_error('class','کلاس و درس معتبر انتخاب کنید.');$data['class_id']=$pair[1];$data['subject_id']=$pair[2];}
 $year=dgs_edu_year((string)($data['year']??''));$class=dgs_edu_class($data['class_id']??0);$subject=absint($data['subject_id']??0);$old=empty($data['task_id'])?null:dgs_hw_task($data['task_id']);
 if(!$class||$class->school_year!==$year||!dgs_grade_can_edit($class,$subject)||(!empty($data['task_id'])&&!$old)||($old&&!dgs_grade_can_edit(dgs_edu_class($old->class_id),$old->subject_id)))return dgs_edu_error('access','کلاس و درس مجاز انتخاب کنید.');
 $title=sanitize_text_field((string)($data['title']??''));$instructions=sanitize_textarea_field((string)($data['instructions']??''));$due=dgs_quiz_date((string)($data['due_date']??''),(string)($data['due_time']??''),$year);$rev=(string)($data['revision']??'0');
 if(!$title||dgs_edu_length($title)>150||dgs_edu_length($instructions)>4000||!$due||(!$old&&$due<=time()))return dgs_edu_error('task','عنوان، توضیحات و مهلت معتبر را تکمیل کنید.');
 if(!ctype_digit($rev)||(int)$rev!==($old?(int)$old->revision:0))return dgs_edu_error('stale','نسخه تکلیف تغییر کرده؛ صفحه را تازه کنید.');
 if($old&&((int)$old->class_id!==(int)$class->id||(int)$old->subject_id!==$subject||$old->school_year!==$year))return dgs_edu_error('task','کلاس و درس تکلیف ثبت‌شده ثابت است؛ تکلیف تازه تعریف کنید.');
 global $wpdb;$t=dgs_hw_tables();$row=array('class_id'=>$class->id,'subject_id'=>$subject,'school_year'=>$year,'title'=>$title,'instructions'=>$instructions,'due_at'=>$due,'revision'=>(int)$rev+1,'active'=>($data['active']??'1')==='1'?1:0);
 if($old)$ok=$wpdb->update($t['tasks'],$row,array('id'=>$old->id,'revision'=>(int)$rev));else{$row['creator_id']=get_current_user_id();$ok=$wpdb->insert($t['tasks'],$row);}if(!$ok)return dgs_edu_error('write','ذخیره تکلیف انجام نشد یا نسخه آن تغییر کرد.');dgs_edu_audit('homework_task',$old?$old->id:$wpdb->insert_id);return 'تکلیف ذخیره شد.';
}
function dgs_hw_dir(){$base=dgs_quiz_file_dir();if(!$base)return '';$dir=$base.'/homework';return wp_mkdir_p($dir)?$dir:'';}
function dgs_hw_store($file){
 if(!is_array($file)||($file['error']??1)!==UPLOAD_ERR_OK||!is_uploaded_file($file['tmp_name']??''))return dgs_edu_error('file','بارگذاری کامل نشد؛ سقف حجم فایل در هاست را هم بررسی کنید.');$size=(int)$file['size'];if($size<1||$size>20*1024*1024)return dgs_edu_error('file','حجم فایل باید حداکثر ۲۰ مگابایت باشد.');
 $name=sanitize_file_name(wp_basename((string)$file['name']));$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$mime=array('pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation');if(!isset($mime[$ext]))return dgs_edu_error('file','فقط PDF، JPG، PNG، DOCX، XLSX و PPTX مجاز است.');
 $path=$file['tmp_name'];if($ext==='pdf'){if(file_get_contents($path,false,null,0,5)!=='%PDF-')return dgs_edu_error('file','PDF معتبر نیست.');}
 elseif(in_array($ext,array('jpg','jpeg','png'),true)){$info=@getimagesize($path);if(!$info||$info['mime']!==$mime[$ext]||$info[0]*$info[1]>20000000)return dgs_edu_error('file','تصویر معتبر تا ۲۰ میلیون پیکسل لازم است.');}
 else{require_once ABSPATH.'wp-admin/includes/class-pclzip.php';$zip=new PclZip($path);$entries=$zip->listContent();$folder=array('docx'=>'word/','xlsx'=>'xl/','pptx'=>'ppt/');$content=false;$match=false;if(!is_array($entries)||count($entries)>5000)return dgs_edu_error('file','ساختار فایل Office معتبر نیست.');foreach($entries as $entry){if($entry['filename']==='[Content_Types].xml')$content=true;if(strpos($entry['filename'],$folder[$ext])===0)$match=true;}if(!$content||!$match)return dgs_edu_error('file','فایل Office با پسوند انتخاب‌شده سازگار نیست.');}
 $dir=dgs_hw_dir();if(!$dir)return dgs_edu_error('file','ذخیره‌سازی فایل در هاست آماده نیست.');$stored=bin2hex(random_bytes(16)).'.'.$ext;if(!move_uploaded_file($path,$dir.'/'.$stored))return dgs_edu_error('file','ذخیره فایل انجام نشد.');chmod($dir.'/'.$stored,0644);return array('stored_file'=>$stored,'file_name'=>function_exists('mb_substr')?mb_substr($name,0,190):substr($name,0,190),'file_mime'=>$mime[$ext],'file_size'=>$size);
}
function dgs_hw_send($data,$file){
 if(!dgs_edu_role(get_current_user_id(),'dgs_student'))return dgs_edu_error('access','ارسال تکلیف فقط برای دانش‌آموز است.');foreach($data as $v)if(!is_scalar($v))return dgs_edu_error('input','درخواست معتبر نیست.');global $wpdb;$t=dgs_hw_tables();
 $uid=get_current_user_id();$token=(string)($data['request_token']??'');if(!preg_match('/^[a-f0-9-]{36}$/D',$token))return dgs_edu_error('token','صفحه ارسال را تازه کنید.');$prior=$wpdb->get_row($wpdb->prepare("SELECT id,student_id FROM {$t['submissions']} WHERE request_token=%s",$token));if($prior)return (int)$prior->student_id===$uid?'این ارسال قبلاً ثبت شده است.':dgs_edu_error('token','شناسه فرم معتبر نیست.');
 $year=dgs_edu_year((string)($data['year']??''));$class=$year?dgs_hw_student_class($year):null;$subject=absint($data['subject_id']??0);$recipient=absint($data['recipient_id']??0);$task=empty($data['task_id'])?null:dgs_hw_task($data['task_id']);
 if(!$class||!$class->active||(!empty($data['task_id'])&&!$task))return dgs_edu_error('class','کلاس فعال یا تکلیف معتبر پیدا نشد.');
 $e=dgs_edu_tables();if(!$wpdb->get_var($wpdb->prepare("SELECT a.id FROM {$e['teaching']} a JOIN {$e['subjects']} s ON s.id=a.subject_id WHERE a.class_id=%d AND a.subject_id=%d AND s.active=1",$class->id,$subject)))return dgs_edu_error('subject','درس مجاز کلاس را انتخاب کنید.');
 if(!isset(dgs_hw_recipients($uid,$class->id,$subject,$year)[$recipient]))return dgs_edu_error('recipient','گیرنده باید معلم همان درس یا مشاور تخصیص‌یافته شما باشد.');
 $title=sanitize_text_field((string)($data['title']??''));$message=sanitize_textarea_field((string)($data['message']??''));if(!$title||dgs_edu_length($title)>150||dgs_edu_length($message)>4000)return dgs_edu_error('title','عنوان تا ۱۵۰ و توضیح تا ۴۰۰۰ نویسه معتبر وارد کنید.');
 if($task){if(!$task->active||(int)$task->class_id!==(int)$class->id||(int)$task->subject_id!==$subject||$task->school_year!==$year)return dgs_edu_error('task','این تکلیف مربوط به کلاس و درس شما نیست.');$latest=$wpdb->get_row($wpdb->prepare("SELECT status FROM {$t['submissions']} WHERE task_id=%d AND student_id=%d ORDER BY id DESC LIMIT 1",$task->id,$uid));if(time()>(int)$task->due_at&&(!$latest||$latest->status!=='revision'))return dgs_edu_error('late','مهلت ارسال تمام شده است؛ برای ارسال دیرهنگام از مسئول درخواست اصلاح یا تمدید مهلت بگیرید.');}
 $stored=dgs_hw_store($file);if(is_wp_error($stored))return $stored;
 $row=array_merge($stored,array('task_id'=>$task?$task->id:0,'student_id'=>$uid,'class_id'=>$class->id,'subject_id'=>$subject,'school_year'=>$year,'recipient_id'=>$recipient,'title'=>$task?$task->title:$title,'message'=>$message,'submitted_at'=>time(),'status'=>'pending','feedback'=>'','request_token'=>$token));
 if(!$wpdb->insert($t['submissions'],$row)){@unlink(dgs_hw_dir().'/'.$stored['stored_file']);return dgs_edu_error('write','ارسال ثبت نشد یا فرم هم‌زمان ارسال شده است؛ سابقه را بررسی کنید.');}dgs_edu_audit('homework_send',$wpdb->insert_id);return 'تکلیف ارسال شد؛ در سابقه ارسال‌ها قابل پیگیری است.';
}
function dgs_hw_review($data){
 foreach($data as $v)if(!is_scalar($v))return dgs_edu_error('input','درخواست معتبر نیست.');$s=dgs_hw_submission($data['submission_id']??0);if(!dgs_hw_can_review($s))return dgs_edu_error('access','اجازه بررسی این ارسال را ندارید.');
 $status=(string)($data['status']??'');$feedback=sanitize_textarea_field((string)($data['feedback']??''));$raw=dgs_edu_digits((string)($data['score']??''));$score=null;$rev=(string)($data['revision']??'');if(!in_array($status,array('reviewed','revision'),true)||dgs_edu_length($feedback)>4000||($status==='revision'&&trim($feedback)===''))return dgs_edu_error('review','وضعیت معتبر و برای نیاز به اصلاح، بازخورد لازم است.');
 if($raw!==''){if(!preg_match('/^\d{1,2}(?:\.\d{1,2})?$/D',$raw)||(float)$raw>20)return dgs_edu_error('score','نمره اختیاری بین صفر تا ۲۰ باشد.');$score=(int)round((float)$raw*100);}
 if(!ctype_digit($rev)||(int)$rev!==(int)$s->revision)return dgs_edu_error('stale','بررسی دیگری ثبت شده؛ صفحه را تازه کنید.');global $wpdb;$t=dgs_hw_tables();$ok=$wpdb->update($t['submissions'],array('status'=>$status,'feedback'=>$feedback,'score'=>$score,'reviewed_by'=>get_current_user_id(),'reviewed_at'=>time(),'revision'=>(int)$rev+1),array('id'=>$s->id,'revision'=>(int)$rev));if(!$ok)return dgs_edu_error('write','بازخورد ذخیره نشد یا هم‌زمان تغییر کرد.');dgs_edu_audit('homework_review',$s->id);return 'نتیجه بررسی ذخیره شد.';
}
function dgs_hw_file_url($id,$download=false){return add_query_arg(array('action'=>'dgs_hw_file','id'=>$id,'download'=>$download?1:0,'nonce'=>wp_create_nonce('dgs_hw_file')),admin_url('admin-post.php'));}
add_action('admin_post_dgs_hw_file',function(){
 if(!isset($_GET['nonce'])||!is_string($_GET['nonce'])||!wp_verify_nonce($_GET['nonce'],'dgs_hw_file'))wp_die('پیوند معتبر نیست؛ صفحه را تازه کنید.','',array('response'=>403));$s=dgs_hw_submission(is_scalar($_GET['id']??null)?$_GET['id']:0);if(!dgs_hw_can_read($s))wp_die('دسترسی مجاز نیست.','',array('response'=>403));
 if(!preg_match('/^[a-f0-9]{32}\.(pdf|png|jpe?g|docx|xlsx|pptx)$/D',$s->stored_file))wp_die('فایل موجود نیست.','',array('response'=>404));$path=dgs_hw_dir().'/'.$s->stored_file;if(!is_file($path))wp_die('فایل موجود نیست.','',array('response'=>404));$inline=empty($_GET['download'])&&in_array($s->file_mime,array('application/pdf','image/png','image/jpeg'),true);nocache_headers();header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');header('Content-Type: '.$s->file_mime);header('Content-Disposition: '.($inline?'inline':'attachment').'; filename="homework-'.(int)$s->id.'.'.pathinfo($path,PATHINFO_EXTENSION).'"; filename*=UTF-8\'\''.rawurlencode($s->file_name));header('Content-Length: '.filesize($path));while(ob_get_level())ob_end_clean();readfile($path);exit;
});
add_action('admin_post_dgs_homework',function(){
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!is_user_logged_in())wp_die('دسترسی مجاز نیست.','',array('response'=>403));check_admin_referer('dgs_homework','dgs_hw_nonce');$data=wp_unslash($_POST);$op=is_string($data['operation']??null)?$data['operation']:'';$r=$op==='task'?dgs_hw_save_task($data):($op==='send'?dgs_hw_send($data,$_FILES['homework_file']??null):($op==='review'?dgs_hw_review($data):dgs_edu_error('op','عملیات معتبر نیست.')));
 $token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r),300);$year=is_string($data['year']??null)?dgs_edu_year($data['year']):'';wp_safe_redirect(add_query_arg(array('dg_tab'=>'homework','dg_year'=>$year,'hw_submission'=>$op==='review'&&is_scalar($data['submission_id']??null)?absint($data['submission_id']):0,'hw_panel'=>$op==='send'?'history':'','dg_notice'=>$token),dgs_page_url('portal')),303);exit;
});
