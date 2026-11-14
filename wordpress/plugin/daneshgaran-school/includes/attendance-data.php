<?php
if (!defined('ABSPATH')) exit;
function dgs_att_tables(){global $wpdb;return array('sessions'=>$wpdb->prefix.'dgs_att_sessions','records'=>$wpdb->prefix.'dgs_att_records');}
add_action('init',function(){
    if(get_option('dgs_att_schema')==='1')return;
    global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_att_tables();$cc=$wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$t['sessions']} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        class_id bigint(20) unsigned NOT NULL,
        school_day date NOT NULL,
        closed tinyint unsigned NOT NULL DEFAULT 0,
        closure_note varchar(250) NOT NULL DEFAULT '',
        revision bigint(20) unsigned NOT NULL DEFAULT 1,
        updated_by bigint(20) unsigned NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY class_day (class_id,school_day)
    ) ENGINE=InnoDB $cc;");
    dbDelta("CREATE TABLE {$t['records']} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id bigint(20) unsigned NOT NULL,
        student_id bigint(20) unsigned NOT NULL,
        school_day date NOT NULL,
        status varchar(10) NOT NULL,
        minutes smallint unsigned NOT NULL DEFAULT 0,
        note varchar(250) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        UNIQUE KEY session_student (session_id,student_id),
        UNIQUE KEY student_day (student_id,school_day),
        KEY student_session (student_id,session_id)
    ) ENGINE=InnoDB $cc;");
    foreach($t as $table)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;
    update_option('dgs_att_schema','1',false);
},10);
function dgs_att_gregorian($jy,$jm,$jd){
    $jy+=1595;$days=-355668+365*$jy+intdiv($jy,33)*8+intdiv(($jy%33)+3,4)+$jd+($jm<7?($jm-1)*31:($jm-7)*30+186);
    $gy=400*intdiv($days,146097);$days%=146097;if($days>36524){$gy+=100*intdiv(--$days,36524);$days%=36524;if($days>=365)$days++;}
    $gy+=4*intdiv($days,1461);$days%=1461;if($days>365){$gy+=intdiv($days-1,365);$days=($days-1)%365;}$gd=$days+1;
    $months=array(0,31,($gy%4===0&&$gy%100!==0)||$gy%400===0?29:28,31,30,31,30,31,31,30,31,30,31);for($gm=1;$gm<=12&&$gd>$months[$gm];$gm++)$gd-=$months[$gm];return sprintf('%04d-%02d-%02d',$gy,$gm,$gd);
}
function dgs_att_parse_day($raw,$year){
    if(!is_string($raw)||!dgs_edu_year($year))return '';$raw=str_replace('/','-',dgs_edu_digits($raw));
    if(!preg_match('/^(14\d{2})-(\d{1,2})-(\d{1,2})$/D',$raw,$m))return '';$jy=(int)$m[1];$jm=(int)$m[2];$jd=(int)$m[3];$ys=explode('-',$year);
    if($jm<1||$jm>12||$jd<1||$jd>31||$jy!==(int)$ys[$jm>=7?0:1])return '';
    $g=dgs_att_gregorian($jy,$jm,$jd);$p=explode('-',$g);return dgs_jalali_parts((int)$p[0],(int)$p[1],(int)$p[2])===array($jy,$jm,$jd)?$g:'';
}
function dgs_att_jdate($g){$p=explode('-',$g);$j=dgs_jalali_parts((int)$p[0],(int)$p[1],(int)$p[2]);return sprintf('%04d-%02d-%02d',...$j);}
function dgs_att_today(){return (new DateTimeImmutable('now',wp_timezone()))->format('Y-m-d');}
function dgs_att_roster($class,$day=''){
    global $wpdb;$t=dgs_edu_tables();$a=dgs_att_tables();
    if(!$day)return $wpdb->get_results($wpdb->prepare("SELECT u.ID,u.display_name FROM {$t['enrollments']} e JOIN {$wpdb->users} u ON u.ID=e.student_id WHERE e.class_id=%d ORDER BY u.ID",$class));
    // در ویرایش یک روز قدیمی، شاگردانی که بعداً از کلاس خارج شده‌اند نیز حفظ می‌شوند.
    return $wpdb->get_results($wpdb->prepare("SELECT u.ID,u.display_name FROM {$wpdb->users} u WHERE EXISTS (SELECT 1 FROM {$t['enrollments']} e WHERE e.class_id=%d AND e.student_id=u.ID) OR EXISTS (SELECT 1 FROM {$a['records']} r JOIN {$a['sessions']} s ON s.id=r.session_id WHERE s.class_id=%d AND s.school_day=%s AND r.student_id=u.ID) ORDER BY u.ID",$class,$class,$day));
}
function dgs_att_roster_hash($class,$day,$roster){return wp_hash($class.'|'.$day.'|'.implode(',',array_map(function($u){return $u->ID;},$roster)));}
function dgs_att_session($class,$day){global $wpdb;$t=dgs_att_tables();return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['sessions']} WHERE class_id=%d AND school_day=%s",$class,$day));}
function dgs_att_save($data){
    if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','ثبت و ویرایش حضور و غیاب فقط برای مدیر و معاون مجاز است.');
    foreach(array('year','day','class_id','revision','roster_hash','closed','closure_note') as $key)if(isset($data[$key])&&!is_scalar($data[$key]))return dgs_edu_error('input','درخواست معتبر نیست.');
    $year=dgs_edu_year((string)($data['year']??''));$day=dgs_att_parse_day($data['day']??null,$year);$class=dgs_edu_class($data['class_id']??0);
    if(!$day||!$class||$class->school_year!==$year)return dgs_edu_error('day','تاریخ شمسی و کلاس فعال در همان سال تحصیلی را انتخاب کنید.');
    if($day>dgs_att_today())return dgs_edu_error('future','ثبت حضور و غیاب برای روز آینده مجاز نیست.');
    if(get_option('dgs_att_schema')!=='1')return dgs_edu_error('schema','جدول حضور و غیاب آماده نشده است.');
    $roster=dgs_att_roster($class->id,$day);$hash=dgs_att_roster_hash($class->id,$day,$roster);
    if(!hash_equals($hash,(string)($data['roster_hash']??'')))return dgs_edu_error('roster','فهرست کلاس تغییر کرده است؛ صفحه را تازه کنید.');
    $closed=($data['closed']??'')==='1';$closure=sanitize_text_field((string)($data['closure_note']??''));if(dgs_edu_length($closure)>250||($closed&&$closure===''))return dgs_edu_error('closure','برای تعطیلی، دلیل حداکثر ۲۵۰ نویسه وارد کنید.');
    $rows=$data['attendance']??array();$valid=array();
    if(!$closed){
        if(!$roster||count($roster)>200||!is_array($rows)||count($rows)!==count($roster))return dgs_edu_error('roster','وضعیت تمام دانش‌آموزان کلاس را مشخص کنید (حداکثر ۲۰۰ نفر).');
        foreach($roster as $u){$row=$rows[$u->ID]??null;if(!is_array($row))return dgs_edu_error('row','وضعیت همه دانش‌آموزان لازم است.');
            foreach(array('status','minutes','note') as $key)if(!isset($row[$key])||!is_scalar($row[$key]))return dgs_edu_error('row','اطلاعات وضعیت معتبر نیست.');
            $status=(string)$row['status'];$minutes=dgs_edu_digits((string)$row['minutes']);$note=sanitize_text_field((string)$row['note']);
            if(!in_array($status,array('present','absent','late'),true))return dgs_edu_error('status','برای هر نفر حاضر، غایب یا تأخیر را انتخاب کنید.');
            if(!ctype_digit($minutes)||(int)$minutes>600||($status==='late'&&(int)$minutes<1)||($status!=='late'&&(int)$minutes!==0))return dgs_edu_error('minutes','دقایق تأخیر از ۱ تا ۶۰۰ و برای حاضر یا غایب صفر باشد.');
            if(dgs_edu_length($note)>250)return dgs_edu_error('note','توضیح باید حداکثر ۲۵۰ نویسه باشد.');
            $valid[]=array('school_day'=>$day,'student_id'=>$u->ID,'status'=>$status,'minutes'=>(int)$minutes,'note'=>$note);
        }
    }
    global $wpdb;$t=dgs_att_tables();$session=dgs_att_session($class->id,$day);$revision=(string)($data['revision']??'');
    if(!ctype_digit($revision)||(int)$revision!==($session?(int)$session->revision:0))return dgs_edu_error('stale','اطلاعات این روز توسط کاربر دیگری تغییر کرده؛ صفحه را تازه کنید.');
    $wpdb->query('START TRANSACTION');$row=array('closed'=>$closed?1:0,'closure_note'=>$closed?$closure:'','updated_by'=>get_current_user_id(),'updated_at'=>current_time('mysql'),'revision'=>(int)$revision+1);
    if($session){$ok=$wpdb->update($t['sessions'],$row,array('id'=>$session->id,'revision'=>(int)$revision));$sid=(int)$session->id;}
    else{$row['class_id']=$class->id;$row['school_day']=$day;$ok=$wpdb->insert($t['sessions'],$row);$sid=(int)$wpdb->insert_id;}
    if(!$ok){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ذخیره انجام نشد یا نسخه اطلاعات تغییر کرده؛ صفحه را تازه کنید.');}
    if($wpdb->delete($t['records'],array('session_id'=>$sid))===false){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ذخیره کامل نشد.');}
    foreach($valid as $row){$row['session_id']=$sid;if(!$wpdb->insert($t['records'],$row)){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ذخیره کامل نشد؛ دوباره تلاش کنید.');}}
    $wpdb->query('COMMIT');dgs_edu_audit($closed?'attendance_closed':'attendance_save',$sid);do_action('dgs_attendance_saved',$year,$day,$valid);return $closed?'تعطیلی این کلاس برای روز انتخاب‌شده ثبت شد.':'حضور و غیاب ذخیره شد.';
}
add_action('admin_post_dgs_attendance',function(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!current_user_can('dgs_manage_education'))wp_die('دسترسی مجاز نیست.','',array('response'=>403));
    check_admin_referer('dgs_attendance','dgs_att_nonce');$data=wp_unslash($_POST);$r=dgs_att_save($data);$token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r),300);
    $year=is_string($data['year']??null)?dgs_edu_year($data['year']):'';$day=is_string($data['day']??null)?sanitize_text_field($data['day']):'';
    wp_safe_redirect(add_query_arg(array('dg_tab'=>'attendance','dg_year'=>$year,'att_day'=>$day,'att_class'=>is_scalar($data['class_id']??null)?absint($data['class_id']):0,'dg_notice'=>$token),dgs_page_url('portal')),303);exit;
});
