<?php
if(!defined('ABSPATH'))exit;
function dgs_edu_tables(){global $wpdb;return array('classes'=>$wpdb->prefix.'dgs_classes','subjects'=>$wpdb->prefix.'dgs_subjects','enrollments'=>$wpdb->prefix.'dgs_enrollments','teaching'=>$wpdb->prefix.'dgs_teaching','counseling'=>$wpdb->prefix.'dgs_counseling');}
function dgs_edu_migrate(){
    if(get_option('dgs_edu_schema')==='1')return;
    global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_edu_tables();$cc=$wpdb->get_charset_collate();
    $sql=array(
    "CREATE TABLE {$t['classes']} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      school_year varchar(9) NOT NULL,
      grade tinyint unsigned NOT NULL,
      field varchar(20) NOT NULL,
      title varchar(100) NOT NULL,
      active tinyint unsigned NOT NULL DEFAULT 1,
      created_at datetime NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY class_identity (school_year,grade,field,title),
      KEY year_active (school_year,active)
    ) ENGINE=InnoDB $cc;",
    "CREATE TABLE {$t['subjects']} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      title varchar(100) NOT NULL,
      active tinyint unsigned NOT NULL DEFAULT 1,
      PRIMARY KEY  (id),
      UNIQUE KEY subject_title (title)
    ) ENGINE=InnoDB $cc;",
    "CREATE TABLE {$t['enrollments']} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      class_id bigint(20) unsigned NOT NULL,
      student_id bigint(20) unsigned NOT NULL,
      school_year varchar(9) NOT NULL,
      created_at datetime NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY student_year (student_id,school_year),
      KEY class_students (class_id,student_id)
    ) ENGINE=InnoDB $cc;",
    "CREATE TABLE {$t['teaching']} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      class_id bigint(20) unsigned NOT NULL,
      subject_id bigint(20) unsigned NOT NULL,
      teacher_id bigint(20) unsigned NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY class_subject (class_id,subject_id),
      KEY teacher_classes (teacher_id,class_id)
    ) ENGINE=InnoDB $cc;",
    "CREATE TABLE {$t['counseling']} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      student_id bigint(20) unsigned NOT NULL,
      counselor_id bigint(20) unsigned NOT NULL,
      school_year varchar(9) NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY student_year (student_id,school_year),
      KEY counselor_students (counselor_id,school_year)
    ) ENGINE=InnoDB $cc;"
    );
    foreach($sql as $query)dbDelta($query);
    foreach($t as $table)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;
    update_option('dgs_edu_schema','1');
}
function dgs_edu_upgrade(){
    if(get_option('dgs_edu_schema')!=='1')dgs_edu_migrate();
    foreach(array('administrator','dgs_deputy') as $slug){$role=get_role($slug);if($role&&!$role->has_cap('dgs_manage_education'))$role->add_cap('dgs_manage_education');}
}
add_action('init','dgs_edu_upgrade',8);
function dgs_edu_year($value){$value=strtr((string)$value,array('۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9'));return preg_match('/^(14[0-9]{2})-(14[0-9]{2})$/D',$value,$m)&&((int)$m[2]===(int)$m[1]+1)?$value:'';}
function dgs_edu_current_year(){
    $stored=dgs_edu_year(dgs_setting('school_year',''));if($stored)return $stored;
    $now=new DateTimeImmutable('now',wp_timezone());list($jy,$jm)=dgs_jalali_parts((int)$now->format('Y'),(int)$now->format('n'),(int)$now->format('j'));$start=$jm>=7?$jy:$jy-1;return $start.'-'.($start+1);
}
function dgs_edu_request_year(){$y=isset($_GET['dg_year'])&&is_string($_GET['dg_year'])?dgs_edu_year(wp_unslash($_GET['dg_year'])):'';return $y?:dgs_edu_current_year();}
function dgs_edu_fields(){return array('science'=>'علوم تجربی','math'=>'ریاضی و فیزیک','humanities'=>'علوم انسانی');}
function dgs_edu_grade_label($grade){return array(10=>'دهم',11=>'یازدهم',12=>'دوازدهم')[(int)$grade]??'';}
function dgs_edu_role($user,$role){if(is_numeric($user))$user=get_userdata((int)$user);return $user instanceof WP_User&&in_array($role,(array)$user->roles,true)&&!user_can($user,'manage_options');}
function dgs_edu_class($id,$active=true){global $wpdb;$t=dgs_edu_tables();return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['classes']} WHERE id=%d".($active?' AND active=1':''),absint($id)));}
function dgs_edu_classes($year,$include_archived=false){global $wpdb;$t=dgs_edu_tables();return $wpdb->get_results($wpdb->prepare("SELECT c.*, (SELECT COUNT(*) FROM {$t['enrollments']} e WHERE e.class_id=c.id) AS students_count FROM {$t['classes']} c WHERE c.school_year=%s".($include_archived?'':' AND c.active=1').' ORDER BY c.grade,c.title,c.id',$year));}
function dgs_edu_subjects(){global $wpdb;$t=dgs_edu_tables();return $wpdb->get_results("SELECT * FROM {$t['subjects']} WHERE active=1 ORDER BY title,id");}
function dgs_edu_audit($event,$object){
    // فقط رویداد مدیریتی؛ هیچ رمز یا شماره‌ای در گزارش ذخیره نمی‌شود.
    $log=get_option('dgs_edu_audit',array());if(!is_array($log))$log=array();$log[]=array('actor'=>get_current_user_id(),'event'=>sanitize_key($event),'object'=>absint($object),'at'=>current_time('mysql'));update_option('dgs_edu_audit',array_slice($log,-150),false);
}
function dgs_edu_length($value){$n=preg_match_all('/./us',(string)$value,$matches);return $n===false?strlen((string)$value):$n;}
function dgs_edu_error($code,$message){return new WP_Error($code,$message);}
function dgs_edu_execute($op,$data){
    if(!current_user_can('dgs_manage_education'))return dgs_edu_error('forbidden','اجازه مدیریت آموزشی ندارید.');
    if(get_option('dgs_edu_schema')!=='1')return dgs_edu_error('schema','ساخت جدول‌های مدرسه انجام نشده است؛ با پشتیبانی هاست بررسی شود.');
    foreach($data as $key=>$value)if(!is_scalar($value)&&$key!=='students')return dgs_edu_error('input','اطلاعات درخواست معتبر نیست.');
    global $wpdb;$t=dgs_edu_tables();$year=dgs_edu_year((string)($data['year']??''));
    if(!$year)return dgs_edu_error('year','سال تحصیلی معتبر وارد کنید؛ مانند 1405-1406.');
    switch($op){
      case 'year':
        $settings=get_option('dgs_settings',array());$settings['school_year']=$year;update_option('dgs_settings',$settings);dgs_edu_audit('school_year',0);return 'سال تحصیلی فعال ذخیره شد.';
      case 'class_create':
        $grade=absint($data['grade']??0);$field=sanitize_key((string)($data['field']??''));$title=dgs_edu_class_label((object)array('grade'=>$grade,'field'=>$field));
        if($title===''||dgs_edu_length($title)>100||!in_array($grade,array(10,11,12),true)||!isset(dgs_edu_fields()[$field]))return dgs_edu_error('class','نام کلاس، پایه و رشته را درست وارد کنید.');
        $exists=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['classes']} WHERE school_year=%s AND grade=%d AND field=%s",$year,$grade,$field));
        if($exists)return dgs_edu_error('duplicate','این کلاس در این سال تحصیلی از قبل وجود دارد.');
        $ok=$wpdb->insert($t['classes'],array('school_year'=>$year,'grade'=>$grade,'field'=>$field,'title'=>$title,'active'=>1,'created_at'=>current_time('mysql')),array('%s','%d','%s','%s','%d','%s'));if(!$ok)return dgs_edu_error('write','کلاس ذخیره نشد.');dgs_edu_audit('class_create',$wpdb->insert_id);return 'کلاس ایجاد شد.';
      case 'class_delete':
        return dgs_edu_delete_empty_class(absint($data['class_id']??0),$year);
      case 'class_status':
        $class=dgs_edu_class($data['class_id']??0,false);if(!$class||$class->school_year!==$year)return dgs_edu_error('class','کلاس معتبر نیست.');
        $active=($data['active']??'')==='1'?1:0;$wpdb->update($t['classes'],array('active'=>$active),array('id'=>$class->id),array('%d'),array('%d'));dgs_edu_audit($active?'class_restore':'class_archive',$class->id);return $active?'کلاس فعال شد.':'کلاس بایگانی شد؛ سوابق و اعضا حذف نشده‌اند.';
      case 'subject_create':
        $title=sanitize_text_field((string)($data['title']??''));if($title===''||dgs_edu_length($title)>100)return dgs_edu_error('title','نام درس معتبر وارد کنید.');
        if($wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['subjects']} WHERE title=%s",$title)))return dgs_edu_error('duplicate','این درس از قبل ثبت شده است.');
        if(!$wpdb->insert($t['subjects'],array('title'=>$title,'active'=>1),array('%s','%d')))return dgs_edu_error('write','درس ذخیره نشد.');dgs_edu_audit('subject_create',$wpdb->insert_id);return 'درس ایجاد شد.';
      case 'user_create':
        $role=sanitize_key((string)($data['role']??''));if(!in_array($role,array('dgs_student','dgs_teacher','dgs_counselor'),true))return dgs_edu_error('role','نقش انتخاب‌شده مجاز نیست.');
        $name=sanitize_text_field((string)($data['name']??''));$username=dgs_edu_digits((string)($data['national_id']??''));$password=$username;$email='';
        if($name===''||dgs_edu_length($name)>100)return dgs_edu_error('user','نام و نام خانوادگی معتبر وارد کنید.');
        if(!dgs_edu_national_id($username))return dgs_edu_error('national_id','کد ملی ۱۰ رقمی معتبر وارد کنید.');
        $phone=dgs_normalize_phone((string)($data['phone']??''));if(!$phone)return dgs_edu_error('phone','شماره تماس همراه معتبر الزامی است.');
        if(username_exists($username)||($email&&email_exists($email)))return dgs_edu_error('exists','نام کاربری یا ایمیل قبلاً ثبت شده است؛ از حساب موجود استفاده کنید.');
        $uid=wp_insert_user(array('user_login'=>$username,'user_pass'=>$password,'display_name'=>$name,'nickname'=>$name,'user_email'=>$email,'role'=>$role));
        if(is_wp_error($uid))return dgs_edu_error('create','ساخت حساب انجام نشد؛ اطلاعات را بررسی کنید.');
        if($phone!=='')update_user_meta($uid,'_dgs_contact_phone',dgs_normalize_phone($phone));update_user_meta($uid,'_dgs_created_by',get_current_user_id());dgs_edu_audit('user_create',$uid);return 'حساب ایجاد شد؛ رمز اولیه را از مسیر امن به صاحب حساب تحویل دهید.';
      case 'enroll':
        $class=dgs_edu_class($data['class_id']??0);if(!$class||$class->school_year!==$year)return dgs_edu_error('class','کلاس فعال همان سال را انتخاب کنید.');
        $raw=$data['students']??array();if(!is_array($raw)||!$raw||count($raw)>100)return dgs_edu_error('students','بین یک تا ۱۰۰ دانش‌آموز انتخاب کنید.');
        foreach($raw as $id)if(!is_scalar($id)||!ctype_digit((string)$id)||(int)$id<1)return dgs_edu_error('student','شناسه دانش‌آموز معتبر نیست.');
        $ids=array_values(array_unique(array_map('absint',$raw)));foreach($ids as $uid){if(!dgs_edu_role($uid,'dgs_student'))return dgs_edu_error('student','فقط حساب‌های دارای نقش دانش‌آموز قابل تخصیص‌اند.');$old=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['enrollments']} WHERE student_id=%d AND school_year=%s",$uid,$year));if($old&&(int)$old->class_id!==(int)$class->id)return dgs_edu_error('assigned','یکی از دانش‌آموزان در همین سال کلاس دیگری دارد؛ ابتدا تخصیص قبلی را بردارید.');}
        $wpdb->query('START TRANSACTION');foreach($ids as $uid){$old=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['enrollments']} WHERE student_id=%d AND school_year=%s",$uid,$year));if($old)continue;if(!$wpdb->insert($t['enrollments'],array('class_id'=>$class->id,'student_id'=>$uid,'school_year'=>$year,'created_at'=>current_time('mysql')),array('%d','%d','%s','%s'))){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ثبت تخصیص کامل نشد؛ صفحه را تازه کنید.');}}$wpdb->query('COMMIT');dgs_edu_audit('enroll',$class->id);return 'دانش‌آموزان به کلاس تخصیص داده شدند.';
      case 'unenroll':
        $id=absint($data['enrollment_id']??0);$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['enrollments']} WHERE id=%d AND school_year=%s",$id,$year));if(!$row)return dgs_edu_error('missing','تخصیص پیدا نشد.');
        // در این مرحله هنوز نمره یا حضور ثبت نمی‌شود؛ در نسخه‌های بعد انتقال با حفظ سوابق توسعه می‌یابد.
        if(!$wpdb->delete($t['enrollments'],array('id'=>$id),array('%d')))return dgs_edu_error('write','حذف تخصیص انجام نشد.');dgs_edu_audit('unenroll',$row->student_id);return 'تخصیص کلاس برداشته شد؛ حساب کاربر حذف نشده است.';
      case 'teach':
        $class=dgs_edu_class($data['class_id']??0);$subject=absint($data['subject_id']??0);$teacher=absint($data['teacher_id']??0);
        if(!$class||$class->school_year!==$year||!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['subjects']} WHERE id=%d AND active=1",$subject))||!dgs_edu_role($teacher,'dgs_teacher'))return dgs_edu_error('assignment','کلاس، درس و معلم معتبر انتخاب کنید.');
        $old=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['teaching']} WHERE class_id=%d AND subject_id=%d",$class->id,$subject));
        if($old&&(int)$old->teacher_id!==$teacher&&($data['replace']??'')!=='1')return dgs_edu_error('replace','این درس در کلاس دبیر دارد؛ برای تغییر، گزینه جایگزینی دبیر را تأیید کنید.');
        $ok=$old?$wpdb->update($t['teaching'],array('teacher_id'=>$teacher),array('id'=>$old->id),array('%d'),array('%d')):$wpdb->insert($t['teaching'],array('class_id'=>$class->id,'subject_id'=>$subject,'teacher_id'=>$teacher),array('%d','%d','%d'));
        if($ok===false)return dgs_edu_error('write','تخصیص دبیر ذخیره نشد.');dgs_edu_audit('teach',$class->id);return 'تخصیص دبیر ذخیره شد.';
      case 'unteach':
        $id=absint($data['teaching_id']??0);$old=$wpdb->get_row($wpdb->prepare("SELECT a.* FROM {$t['teaching']} a JOIN {$t['classes']} c ON c.id=a.class_id WHERE a.id=%d AND c.school_year=%s",$id,$year));if(!$old)return dgs_edu_error('missing','تخصیص دبیر پیدا نشد.');$wpdb->delete($t['teaching'],array('id'=>$id),array('%d'));dgs_edu_audit('unteach',$old->class_id);return 'تخصیص دبیر برداشته شد.';
      case 'counsel':
        $student=absint($data['student_id']??0);$counselor=absint($data['counselor_id']??0);
        if(!dgs_edu_role($student,'dgs_student')||!dgs_edu_role($counselor,'dgs_counselor')||!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['enrollments']} WHERE student_id=%d AND school_year=%s",$student,$year)))return dgs_edu_error('counsel','دانش‌آموز دارای کلاس و مشاور معتبر انتخاب کنید.');
        $old=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['counseling']} WHERE student_id=%d AND school_year=%s",$student,$year));
        if($old&&(int)$old->counselor_id!==$counselor&&($data['replace']??'')!=='1')return dgs_edu_error('replace','دانش‌آموز در این سال مشاور دارد؛ گزینه جایگزینی را تأیید کنید.');
        $ok=$old?$wpdb->update($t['counseling'],array('counselor_id'=>$counselor),array('id'=>$old->id),array('%d'),array('%d')):$wpdb->insert($t['counseling'],array('student_id'=>$student,'counselor_id'=>$counselor,'school_year'=>$year),array('%d','%d','%s'));
        if($ok===false)return dgs_edu_error('write','تخصیص مشاور ذخیره نشد.');dgs_edu_audit('counsel',$student);return 'مشاور دانش‌آموز ذخیره شد.';
      case 'uncounsel':
        $id=absint($data['counseling_id']??0);if(!$wpdb->delete($t['counseling'],array('id'=>$id,'school_year'=>$year),array('%d','%s')))return dgs_edu_error('missing','تخصیص مشاور پیدا نشد.');dgs_edu_audit('uncounsel',$id);return 'تخصیص مشاور برداشته شد.';
    }
    return dgs_edu_error('operation','عملیات معتبر نیست.');
}
add_action('admin_post_dgs_education',function(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST')wp_die('روش درخواست معتبر نیست.','',array('response'=>405));
    if(!current_user_can('dgs_manage_education'))wp_die('دسترسی ندارید.','',array('response'=>403));
    check_admin_referer('dgs_education','dgs_edu_nonce');$data=wp_unslash($_POST);$op=isset($data['operation'])&&is_string($data['operation'])?sanitize_key($data['operation']):'';
    $result=dgs_edu_execute($op,$data);$token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($result),'text'=>is_wp_error($result)?$result->get_error_message():$result),5*MINUTE_IN_SECONDS);
    $tab=isset($data['return_tab'])&&is_string($data['return_tab'])?sanitize_key($data['return_tab']):'overview';if(!in_array($tab,array('overview','classes','subjects','users','assignments','counseling','education'),true))$tab='overview';
    $year=dgs_edu_year((string)($data['year']??''))?:dgs_edu_current_year();wp_safe_redirect(add_query_arg(array('dg_tab'=>$tab,'dg_year'=>$year,'dg_notice'=>$token),dgs_page_url('portal')),303);exit;
});

function dgs_edu_digits($value){return strtr(trim($value),array('۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'));}
function dgs_edu_national_id($id){if(!preg_match('/^[0-9]{10}$/D',$id)||preg_match('/^(.)\1{9}$/D',$id))return false;$sum=0;for($i=0;$i<9;$i++)$sum+=(int)$id[$i]*(10-$i);$r=$sum%11;return (int)$id[9]===($r<2?$r:11-$r);}
function dgs_edu_change_password($data){
    $u=wp_get_current_user();if(!$u->exists()||!(dgs_edu_role($u,'dgs_student')||dgs_edu_role($u,'dgs_teacher')||dgs_edu_role($u,'dgs_counselor')))return dgs_edu_error('access','دسترسی مجاز نیست.');
    foreach(array('current_password','new_password','confirm_password') as $key)if(!isset($data[$key])||!is_string($data[$key]))return dgs_edu_error('input','اطلاعات رمز معتبر نیست.');
    if(!wp_check_password($data['current_password'],$u->user_pass,$u->ID))return dgs_edu_error('current','رمز فعلی درست نیست.');
    $new=$data['new_password'];if(dgs_edu_length($new)<12||strlen($new)>128||$new===$u->user_login||$new===$data['current_password'])return dgs_edu_error('password','رمز جدید متفاوت و حداقل ۱۲ نویسه باشد.');
    if($new!==$data['confirm_password'])return dgs_edu_error('confirm','تکرار رمز جدید یکسان نیست.');
    wp_set_password($new,$u->ID);return true;
}
add_action('admin_post_dgs_change_password',function(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!is_user_logged_in())wp_die('درخواست مجاز نیست.',403);
    check_admin_referer('dgs_change_password','dgs_password_nonce');$result=dgs_edu_change_password(wp_unslash($_POST));
    if(is_wp_error($result)){wp_die(esc_html($result->get_error_message()).' <a href="'.esc_url(dgs_page_url('portal')).'">بازگشت به پنل</a>','تغییر رمز',array('response'=>400));}
    wp_logout();wp_safe_redirect(add_query_arg('dgs_password_changed','1',wp_login_url(dgs_page_url('portal'))),303);exit;
});

function dgs_edu_delete_empty_class($id,$year){
 if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','دسترسی مجاز نیست.');$class=dgs_edu_class($id,false);if(!$class||$class->school_year!==$year)return dgs_edu_error('class','کلاس همین سال را انتخاب کنید.');global $wpdb;$e=dgs_edu_tables();$a=dgs_att_tables();$q=dgs_quiz_tables();$h=dgs_hw_tables();$tables=array($e['enrollments'],$e['teaching'],dgs_grade_table(),$a['sessions'],$q['quizzes'],$h['tasks'],$h['submissions'],dgs_school_schedule_table());$sql="DELETE FROM {$e['classes']} WHERE id=%d AND school_year=%s";$args=array($id,$year);foreach($tables as $table){$sql.=" AND NOT EXISTS (SELECT 1 FROM $table WHERE class_id=%d)";$args[]=$id;}$ok=$wpdb->query($wpdb->prepare($sql,$args));if($ok===false)return dgs_edu_error('write','حذف کلاس انجام نشد.');if($ok!==1)return dgs_edu_error('linked','این کلاس دانش‌آموز، تخصیص دبیر یا سابقه آموزشی دارد؛ حذف آن باعث از بین رفتن ارتباط سوابق می‌شود. ابتدا وابستگی‌ها را بررسی کنید.');dgs_edu_audit('class_delete',$id);return 'کلاس خالی حذف شد؛ شناسه کلاس‌های دیگر تغییر نکرد.';
}
