<?php
if(!defined('ABSPATH'))exit;
function dgs_cm_tables(){global $wpdb;return array('batches'=>$wpdb->prefix.'dgs_sms_batches','items'=>$wpdb->prefix.'dgs_sms_items');}
add_action('init',function(){
 if(get_option('dgs_cm_schema')==='1')return;global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_cm_tables();$cc=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t['batches']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 request_key varchar(64) NOT NULL,
 actor_id bigint(20) unsigned NOT NULL,
 school_year varchar(9) NOT NULL,
 purpose varchar(16) NOT NULL,
 audience varchar(255) NOT NULL,
 status varchar(16) NOT NULL DEFAULT 'draft',
 created_at datetime NOT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY request_key (request_key)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['items']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 batch_id bigint(20) unsigned NOT NULL,
 dedupe_key varchar(64) NOT NULL,
 student_id bigint(20) unsigned NOT NULL,
 phone varchar(16) NOT NULL,
 context_json longtext NOT NULL,
 payload_json longtext NOT NULL,
 preview text NOT NULL,
 status varchar(16) NOT NULL DEFAULT 'draft',
 provider_id varchar(64) NOT NULL DEFAULT '',
 error_code varchar(64) NOT NULL DEFAULT '',
 created_at datetime NOT NULL,
 updated_at datetime NOT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY dedupe_key (dedupe_key),
 KEY batch_status (batch_id,status),
 KEY work_queue (status,id)
 ) ENGINE=InnoDB $cc;");
 foreach($t as $table)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;update_option('dgs_cm_schema','1',false);
},17);
function dgs_cm_config(){ $s=get_option('dgs_sms_settings',array());return is_array($s)?$s:array(); }
function dgs_cm_purposes(){return array('notice'=>'اطلاعیه','report'=>'انتشار کارنامه','absence'=>'غیبت دانش‌آموز');}
function dgs_cm_sources(){return array('student'=>'نام دانش‌آموز','class'=>'کلاس','date'=>'تاریخ شمسی','month'=>'ماه کارنامه','school'=>'نام مدرسه','message'=>'توضیح دستی','score'=>'معدل کارنامه');}
function dgs_cm_secret($plain){
 if(!function_exists('openssl_encrypt'))return dgs_edu_error('crypto','افزونه OpenSSL هاست باید فعال باشد.');$iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($plain,'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,$iv,$tag);return $cipher===false?dgs_edu_error('crypto','ذخیره کلید انجام نشد.'):base64_encode($iv.$tag.$cipher);
}
function dgs_cm_key(){
 if(defined('DGS_IPPANEL_API_KEY'))return (string)DGS_IPPANEL_API_KEY;$raw=base64_decode(dgs_cm_config()['secret']??'',true);if(!$raw||strlen($raw)<29||!function_exists('openssl_decrypt'))return '';return openssl_decrypt(substr($raw,28),'aes-256-gcm',hash('sha256',wp_salt('auth'),true),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16))?:'';
}
function dgs_cm_map($raw){
 $map=array();foreach(preg_split('/\R/u',trim($raw)) as $line){if(trim($line)==='')continue;if(!preg_match('/^\s*([A-Za-z][A-Za-z0-9_]{0,49})\s*=\s*([a-z]+)\s*$/D',$line,$m)||!isset(dgs_cm_sources()[$m[2]])||isset($map[$m[1]]))return dgs_edu_error('map','هر خط متغیر به صورت نام_متغیر=منبع باشد؛ نام متغیر انگلیسی و یکتا باشد.');$map[$m[1]]=$m[2];}return count($map)>20?dgs_edu_error('map','حداکثر ۲۰ متغیر مجاز است.'):$map;
}
function dgs_cm_settings_save($d){
 if(!current_user_can('manage_options'))return dgs_edu_error('access','تنظیمات اتصال فقط برای مدیر اصلی سایت است.');foreach($d as $v)if(!is_scalar($v))return dgs_edu_error('input','درخواست معتبر نیست.');
 $old=dgs_cm_config();if((string)($d['revision']??'')!==(string)($old['revision']??0))return dgs_edu_error('stale','تنظیمات تغییر کرده؛ صفحه را تازه کنید.');$next=$old;
 $sender=dgs_edu_digits(trim($d['sender']??''));if($sender!==''&&!preg_match('/^\+?98[0-9]{3,18}$/D',$sender))return dgs_edu_error('sender','شماره خط را با پیش‌شماره۹۸ وارد کنید.');$next['sender']=$sender?'+'.ltrim($sender,'+'):'';
 $key=trim($d['api_key']??'');if($key!==''){if(strlen($key)<10||strlen($key)>512||preg_match('/[\r\n]/',$key))return dgs_edu_error('key','کلید اتصال معتبر نیست.');$encrypted=dgs_cm_secret($key);if(is_wp_error($encrypted))return $encrypted;$next['secret']=$encrypted;}
 if(($d['remove_key']??'')==='1')$next['secret']='';$next['enabled']=($d['enabled']??'')==='1';
 foreach(dgs_cm_purposes() as $p=>$label){$code=trim($d[$p.'_code']??'');$text=sanitize_textarea_field($d[$p.'_text']??'');$map=dgs_cm_map($d[$p.'_map']??'');if(is_wp_error($map))return $map;if($code!==''&&!preg_match('/^[A-Za-z0-9_-]{1,100}$/D',$code))return dgs_edu_error('pattern','کد پترن معتبر نیست.');if(dgs_edu_length($text)>2000)return dgs_edu_error('length','متن پترن حداکثر۲۰۰۰ نویسه باشد.');preg_match_all('/\{([A-Za-z][A-Za-z0-9_]*)\}/',$text,$matches);foreach($matches[1] as $variable)if(!isset($map[$variable]))return dgs_edu_error('map','متغیر متن پیش‌نمایش در نگاشت تعریف نشده است.');$next['patterns'][$p]=array('code'=>$code,'text'=>$text,'map'=>$map);}
 unset($next['auto_absence'],$next['auto_report']);
 $next['revision']=(int)($old['revision']??0)+1;
 // Preserve the existing value if another administrator saved concurrently.
 if($old){global $wpdb;$ok=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name='dgs_sms_settings' AND option_value=%s",maybe_serialize($next),maybe_serialize($old)));wp_cache_delete('dgs_sms_settings','options');wp_cache_delete('alloptions','options');if(!$ok)return dgs_edu_error('stale','تنظیمات هم‌زمان تغییر کرد.');}elseif(!add_option('dgs_sms_settings',$next,'','no'))return dgs_edu_error('stale','تنظیمات هم‌زمان تغییر کرد.');
 dgs_edu_audit('sms_settings',0);return 'تنظیمات IPPanel ذخیره شد. کلید اتصال در صفحه نمایش داده نمی‌شود.';
}
function dgs_cm_request($path,$body=null){
 $key=dgs_cm_key();if(!$key)return dgs_edu_error('not_configured','کلید اتصال تنظیم نشده است.');
 // Fixed HTTPS host, no redirects and no credentials in URLs or logs.
 $args=array('method'=>$body===null?'GET':'POST','timeout'=>20,'redirection'=>0,'headers'=>array('Authorization'=>$key,'Content-Type'=>'application/json'),'limit_response_size'=>1048576);
 if($body!==null)$args['body']=wp_json_encode($body,JSON_UNESCAPED_UNICODE);$r=wp_remote_request('https://edge.ippanel.com/v1'.$path,$args);
 if(is_wp_error($r))return dgs_edu_error('uncertain','پاسخ درگاه دریافت نشد؛ برای جلوگیری از تکرار، وضعیت را در IPPanel بررسی کنید.');$status=wp_remote_retrieve_response_code($r);$j=json_decode(wp_remote_retrieve_body($r),true);
 if($status>=500||$status===408||!is_array($j)||!isset($j['meta']['status']))return dgs_edu_error('uncertain','نتیجه درخواست درگاه نامشخص است؛ دوباره خودکار ارسال نمی‌شود.');
 if($status<200||$status>=300||$j['meta']['status']!==true)return dgs_edu_error('provider_'.preg_replace('/[^A-Za-z0-9_-]/','',substr((string)($j['meta']['message_code']??$status),0,40)),'درگاه درخواست را نپذیرفت؛ خط، کلید، اعتبار و پترن را بررسی کنید.');return $j['data']??array();
}
function dgs_cm_context($student,$year,$extra=array()){
 $u=get_userdata($student);global $wpdb;$e=dgs_edu_tables();$class=$wpdb->get_row($wpdb->prepare("SELECT c.* FROM {$e['enrollments']} en JOIN {$e['classes']} c ON c.id=en.class_id WHERE en.student_id=%d AND en.school_year=%s",$student,$year));
 return array_merge(array('student'=>$u?$u->display_name:'','class'=>$class?dgs_edu_class_label($class):'','date'=>dgs_att_jdate(dgs_mg_now()->format('Y-m-d')),'month'=>'','school'=>dgs_setting('school_name','مدرسه دانشگران'),'message'=>'','score'=>'','student_id'=>(int)$student,'class_id'=>$class?(int)$class->id:0,'year'=>$year),$extra);
}
function dgs_cm_payload($purpose,$ctx){
 $s=dgs_cm_config();$p=$s['patterns'][$purpose]??array();if(empty($s['sender'])||empty($p['code'])||empty($p['text']))return dgs_edu_error('pattern','خط، کد و متن پیش‌نمایش پترن را در تنظیمات تکمیل کنید.');$params=array();$replacements=array();foreach(($p['map']??array()) as $name=>$source){$v=(string)($ctx[$source]??'');if($v==='')return dgs_edu_error('variable','مقدار متغیر «'.$name.'» خالی است.');$params[$name]=$v;$replacements['{'.$name.'}']=$v;}
 $preview=strtr($p['text'],$replacements);return array('payload'=>array('sending_type'=>'pattern','from_number'=>$s['sender'],'code'=>$p['code'],'params'=>(object)$params),'preview'=>$preview);
}
function dgs_cm_guardian($student){$phone=dgs_normalize_phone((string)get_user_meta($student,'_dgs_guardian_phone',true));return $phone?'+98'.substr($phone,1):'';}
function dgs_cm_candidates($d,$year){
 global $wpdb;$e=dgs_edu_tables();$mode=$d['audience']??'class';if(!in_array($mode,array('individual','class','group'),true))return dgs_edu_error('audience','نوع گیرنده معتبر نیست.');$sql="SELECT en.student_id FROM {$e['enrollments']} en JOIN {$e['classes']} c ON c.id=en.class_id WHERE en.school_year=%s AND c.active=1";$args=array($year);
 if($mode==='individual'){$student=absint($d['student_id']??0);if(!$student)return dgs_edu_error('student','دانش‌آموز را انتخاب کنید.');$sql.=' AND en.student_id=%d';$args[]=$student;}
 if($mode==='class'){$class=absint($d['class_id']??0);if(!$class)return dgs_edu_error('class','کلاس را انتخاب کنید.');$sql.=' AND en.class_id=%d';$args[]=$class;}
 if($mode==='group'&&!empty($d['grade'])){if(!in_array((int)$d['grade'],array(10,11,12),true))return dgs_edu_error('grade','پایه معتبر نیست.');$sql.=' AND c.grade=%d';$args[]=(int)$d['grade'];}
 return array_map('intval',$wpdb->get_col($wpdb->prepare($sql.' ORDER BY en.student_id LIMIT 2001',$args)));
}
function dgs_cm_prepare($d){
 if(!dgs_manage_allowed())return dgs_edu_error('access','اجازه ارسال ندارید.');foreach($d as $v)if(!is_scalar($v))return dgs_edu_error('input','درخواست معتبر نیست.');$year=dgs_edu_year($d['year']??'');$purpose=$d['purpose']??'';$token=$d['request_token']??'';
 if(!$year||!isset(dgs_cm_purposes()[$purpose])||!preg_match('/^[a-f0-9-]{36}$/D',$token))return dgs_edu_error('input','سال، هدف یا شناسه درخواست معتبر نیست.');$students=dgs_cm_candidates($d,$year);if(is_wp_error($students))return $students;if(!$students||count($students)>2000)return dgs_edu_error('count','بین یک تا۲۰۰۰ دانش‌آموز انتخاب کنید.');$message=sanitize_textarea_field($d['message']??'');if(dgs_edu_length($message)>2000)return dgs_edu_error('message','متن حداکثر۲۰۰۰ نویسه باشد.');$period=$year?dgs_grade_period($d['period']??'',$year):'';
 $prepared=array();$dedupe=array();foreach($students as $student){$extra=array('message'=>$message,'manual'=>true);if($purpose==='report'){if(!$period)return dgs_edu_error('period','ماه کارنامه را انتخاب کنید.');$b=dgs_rc_bundle($student,$year);$m=is_wp_error($b)?null:($b['months'][$period]??null);if(!$m||!$m['complete'])continue;$extra['month']=dgs_mg_label($period);$extra['score']=dgs_rc_number($m['average']);$extra['period']=$period;}
 if($purpose==='absence'){$day=dgs_att_parse_day($d['day']??'',$year);if(!$day)return dgs_edu_error('day','تاریخ غیبت معتبر وارد کنید.');if(!dgs_cm_is_absent($student,$year,$day))continue;$extra['date']=dgs_att_jdate($day);$extra['day']=$day;}
 $ctx=dgs_cm_context($student,$year,$extra);$payload=dgs_cm_payload($purpose,$ctx);if(is_wp_error($payload))return $payload;$phone=dgs_cm_guardian($student);$fingerprint=$phone?hash('sha256',$phone.wp_json_encode($payload['payload'])):'missing-'.$student;if(isset($dedupe[$fingerprint]))continue;$dedupe[$fingerprint]=true;$prepared[]=array($student,$phone,$ctx,$payload);}
 if(!$prepared)return dgs_edu_error('empty','هیچ گیرنده واجد شرایطی وجود ندارد؛ انتشار کارنامه یا غیبت ثبت‌شده را بررسی کنید.');
 return dgs_mg_locked(function()use($prepared,$year,$purpose,$token,$d){global $wpdb;$t=dgs_cm_tables();$key=hash('sha256','manual|'.get_current_user_id().'|'.$token);$existing=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['batches']} WHERE request_key=%s",$key));if($existing)return (int)$existing;
 if(!$wpdb->insert($t['batches'],array('request_key'=>$key,'actor_id'=>get_current_user_id(),'school_year'=>$year,'purpose'=>$purpose,'audience'=>$d['audience']??'class','status'=>'draft','created_at'=>dgs_mg_utc())))throw new RuntimeException('پیش‌نمایش ذخیره نشد.');$batch=(int)$wpdb->insert_id;
 foreach($prepared as $i=>$r)if(!$wpdb->insert($t['items'],array('batch_id'=>$batch,'dedupe_key'=>hash('sha256',$key.'|'.$i),'student_id'=>$r[0],'phone'=>$r[1],'context_json'=>wp_json_encode($r[2]),'payload_json'=>wp_json_encode($r[3]['payload']),'preview'=>$r[3]['preview'],'status'=>$r[1]?'draft':'missing_phone','created_at'=>dgs_mg_utc(),'updated_at'=>dgs_mg_utc())))throw new RuntimeException('گیرندگان ذخیره نشدند.');return $batch;});
}
function dgs_cm_confirm($id){
 if(!dgs_manage_allowed())return dgs_edu_error('access','اجازه ارسال ندارید.');$s=dgs_cm_config();if(empty($s['enabled'])||!dgs_cm_key())return dgs_edu_error('disabled','اتصال IPPanel را فعال و کلید را وارد کنید.');
 return dgs_mg_locked(function()use($id){global $wpdb;$t=dgs_cm_tables();$batch=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['batches']} WHERE id=%d",$id));if(!$batch||!in_array($batch->audience,array('individual','class','group'),true)||(int)$batch->actor_id!==get_current_user_id())return dgs_edu_error('access','این پیش‌نمایش متعلق به حساب شما نیست.');if($batch->status!=='draft')return 'این درخواست قبلاً تعیین تکلیف شده است.';
 if(strtotime($batch->created_at.' UTC')<time()-3600)return dgs_edu_error('expired','پیش‌نمایش منقضی شده؛ دوباره پیش‌نمایش بگیرید.');$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['items']} WHERE batch_id=%d AND status='draft'",$id));if(!$count)return dgs_edu_error('empty','شماره معتبر برای ارسال وجود ندارد.');
 if($wpdb->update($t['items'],array('status'=>'queued','updated_at'=>dgs_mg_utc()),array('batch_id'=>$id,'status'=>'draft'))===false||$wpdb->update($t['batches'],array('status'=>'queued'),array('id'=>$id))===false)throw new RuntimeException('صف ارسال ذخیره نشد.');dgs_edu_audit('sms_confirm',$id);return 'ارسال تأیید شد و در صف IPPanel قرار گرفت.';});
}
function dgs_cm_is_absent($student,$year,$day){global $wpdb;$a=dgs_att_tables();$e=dgs_edu_tables();return (bool)$wpdb->get_var($wpdb->prepare("SELECT r.id FROM {$a['records']} r JOIN {$a['sessions']} s ON s.id=r.session_id JOIN {$e['classes']} c ON c.id=s.class_id WHERE r.student_id=%d AND s.school_day=%s AND c.school_year=%s AND s.closed=0 AND r.status='absent'",$student,$day,$year));}
function dgs_cm_eligible($item,$batch){
 $ctx=json_decode($item->context_json,true);$student=(int)$item->student_id;if(!dgs_edu_role($student,'dgs_student')||!dgs_cm_guardian($student)||!hash_equals($item->phone,dgs_cm_guardian($student)))return false;
 if($batch->purpose==='absence')return dgs_cm_is_absent($student,$batch->school_year,$ctx['day']??'');
 if($batch->purpose==='report'){$b=dgs_rc_bundle($student,$batch->school_year);return !is_wp_error($b)&&!empty($b['months'][$ctx['period']??'']['complete']);}
 global $wpdb;$e=dgs_edu_tables();return (bool)$wpdb->get_var($wpdb->prepare("SELECT en.id FROM {$e['enrollments']} en JOIN {$e['classes']} c ON c.id=en.class_id WHERE en.student_id=%d AND en.school_year=%s AND c.active=1 AND en.class_id=%d",$student,$batch->school_year,(int)($ctx['class_id']??0)));
}
// Worker has no logged-in user; report eligibility uses the same complete-publication predicate internally.
function dgs_cm_report_complete($student,$year,$period){
 global $wpdb;$e=dgs_edu_tables();$g=dgs_grade_table();$catalog=dgs_rc_catalog($year);$rows=$wpdb->get_results($wpdb->prepare("SELECT g.* FROM $g g JOIN {$e['classes']} c ON c.id=g.class_id WHERE g.student_id=%d AND c.school_year=%s AND g.period=%s",$student,$year,$period));if(!$rows)return false;return dgs_rc_month($rows,$catalog['expected'][(int)$rows[0]->class_id]??array(),$catalog['states']);
}
function dgs_cm_worker(){
 $s=dgs_cm_config();if(empty($s['enabled'])||!dgs_cm_key()||get_option('dgs_cm_schema')!=='1')return;
 global $wpdb;$t=dgs_cm_tables();$lock='dgs_sms_worker';$token=wp_generate_uuid4().'|'.time();if(!add_option($lock,$token,'','no')){$old=$wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s",$lock));$parts=explode('|',(string)$old);if((int)end($parts)<time()-240){$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",$lock,$old));wp_cache_delete($lock,'options');}return;}
 try{
 $wpdb->query($wpdb->prepare("UPDATE {$t['items']} SET status='unknown',error_code='interrupted' WHERE status='submitting' AND updated_at<%s",gmdate('Y-m-d H:i:s',time()-300)));
 $rows=$wpdb->get_results("SELECT * FROM {$t['items']} WHERE status='queued' ORDER BY id LIMIT 5");foreach($rows as $item){$batch=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['batches']} WHERE id=%d",$item->batch_id));if(!$batch)continue;
 $ctx=json_decode($item->context_json,true);if(!dgs_cm_manual_item($item,$batch)){$wpdb->update($t['items'],array('status'=>'canceled','error_code'=>'manual_only','updated_at'=>dgs_mg_utc()),array('id'=>$item->id,'status'=>'queued'));continue;}$eligible=$batch->purpose==='report'?dgs_cm_report_complete($item->student_id,$batch->school_year,$ctx['period']??''):null;$okay=$batch->purpose==='report'?(!empty($eligible['complete'])&&dgs_cm_guardian($item->student_id)===$item->phone&&($ctx['score']??'')===dgs_rc_number($eligible['average'])):dgs_cm_eligible($item,$batch);if(strtotime($item->created_at.' UTC')<time()-DAY_IN_SECONDS)$okay=false;
 if(!$okay){$wpdb->update($t['items'],array('status'=>'canceled','error_code'=>strtotime($item->created_at.' UTC')<time()-DAY_IN_SECONDS?'queue_expired':'recipient_changed','updated_at'=>dgs_mg_utc()),array('id'=>$item->id,'status'=>'queued'));continue;}
 if($wpdb->update($t['items'],array('status'=>'submitting','updated_at'=>dgs_mg_utc()),array('id'=>$item->id,'status'=>'queued'))!==1)continue;
 $payload=json_decode($item->payload_json,true);$payload['params']=(object)($payload['params']??array());$payload['recipients']=array($item->phone);$r=dgs_cm_request('/api/send',$payload);$status='accepted';$error='';$provider='';if(is_wp_error($r)){$error=$r->get_error_code();$status=$error==='uncertain'?'unknown':'failed';}else{$ids=$r['message_outbox_ids']??array();if(!is_array($ids)||count($ids)!==1||!is_scalar($ids[0])||!preg_match('/^[0-9]{1,64}$/D',(string)$ids[0])){$status='unknown';$error='missing_tracking';}else $provider=(string)$ids[0];}
 $wpdb->update($t['items'],array('status'=>$status,'provider_id'=>$provider,'error_code'=>$error,'updated_at'=>dgs_mg_utc()),array('id'=>$item->id,'status'=>'submitting'));
 }
 $poll=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['items']} WHERE status='accepted' AND provider_id<>'' AND updated_at<%s ORDER BY updated_at,id LIMIT 3",gmdate('Y-m-d H:i:s',time()-600)));foreach($poll as $item){if(!dgs_cm_sync_item($item))$wpdb->update($t['items'],array('updated_at'=>dgs_mg_utc()),array('id'=>$item->id));}
 }finally{$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",$lock,$token));wp_cache_delete($lock,'options');}
}
function dgs_cm_sync_item($row){
 global $wpdb;$t=dgs_cm_tables();$r=dgs_cm_request('/api/report/by_bulk?messages_outbox_id='.rawurlencode($row->provider_id));if(is_wp_error($r))return false;$raw=$r['state_id']??null;if(!is_scalar($raw)||!preg_match('/^[0-9]$/D',(string)$raw))return false;$state=(int)$raw;$status=$state===6?'sent':(in_array($state,array(3,4,7,8,9),true)?'failed':'accepted');return $wpdb->update($t['items'],array('status'=>$status,'error_code'=>in_array($state,array(3,4,7,8,9),true)?'state_'.$state:'','updated_at'=>dgs_mg_utc()),array('id'=>$row->id,'provider_id'=>$row->provider_id))!==false;
}
function dgs_cm_status_refresh($batch){
 if(!dgs_manage_allowed())return dgs_edu_error('access','دسترسی مجاز نیست.');global $wpdb;$t=dgs_cm_tables();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['items']} WHERE batch_id=%d AND provider_id<>'' AND status IN ('accepted','unknown') ORDER BY updated_at,id LIMIT 3",$batch));$count=0;foreach($rows as $row)if(dgs_cm_sync_item($row))$count++;return 'وضعیت '.dgs_fa_digits($count).' ارسال از IPPanel بررسی شد. «ارسال‌شده» تأیید تحویل به گوشی نیست.';
}
add_filter('cron_schedules',function($s){$s['dgs_sms_minute']=array('interval'=>60,'display'=>'صف پیامک مدرسه');return $s;});
add_action('init',function(){if(!wp_next_scheduled('dgs_sms_tick'))wp_schedule_event(time()+60,'dgs_sms_minute','dgs_sms_tick');},20);
add_action('dgs_sms_tick','dgs_cm_worker');
function dgs_cm_school_save($d){
 if(!dgs_manage_allowed())return dgs_edu_error('access','اجازه ویرایش ندارید.');foreach($d as $v)if(!is_scalar($v))return dgs_edu_error('input','درخواست معتبر نیست.');$old=get_option('dgs_settings',array());$old=is_array($old)?$old:array();if((string)($d['revision']??'')!==(string)($old['communication_revision']??0))return dgs_edu_error('stale','تنظیمات تغییر کرده؛ صفحه را تازه کنید.');$year=dgs_edu_year($d['school_year']??'');$name=sanitize_text_field($d['school_name']??'');$code=dgs_edu_digits(trim($d['school_code']??''));if(!$year||!$name||dgs_edu_length($name)>100||($code!==''&&!preg_match('/^[0-9]{1,30}$/D',$code)))return dgs_edu_error('school','نام، کد مدرسه یا سال تحصیلی معتبر نیست.');$start=trim($d['school_start']??'');$end=trim($d['school_end']??'');$gs=$start?dgs_att_parse_day($start,$year):'';$ge=$end?dgs_att_parse_day($end,$year):'';if(($start&&!$gs)||($end&&!$ge)||($gs&&$ge&&$gs>$ge))return dgs_edu_error('dates','تاریخ‌های شمسی باید معتبر، در همان سال تحصیلی و پایان پس از آغاز باشند.');$next=$old;$next['school_name']=$name;$next['school_code']=$code;$next['school_year']=$year;$next['school_start']=$gs?dgs_att_jdate($gs):'';$next['school_end']=$ge?dgs_att_jdate($ge):'';$next['communication_revision']=(int)($old['communication_revision']??0)+1;
 global $wpdb;$ok=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name='dgs_settings' AND option_value=%s",maybe_serialize($next),maybe_serialize($old)));wp_cache_delete('dgs_settings','options');wp_cache_delete('alloptions','options');if(!$ok)return dgs_edu_error('stale','ذخیره انجام نشد یا اطلاعات هم‌زمان تغییر کرد.');dgs_edu_audit('school_communication_settings',0);return 'اطلاعات مدرسه و تقویم سال تحصیلی ذخیره شد.';
}
add_action('admin_post_dgs_communication',function(){
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!dgs_manage_allowed())wp_die('دسترسی مجاز نیست.','',array('response'=>403));check_admin_referer('dgs_communication','cm_nonce');$d=wp_unslash($_POST);foreach($d as $v)if(!is_scalar($v))wp_die('درخواست معتبر نیست.','',array('response'=>400));$year=dgs_edu_year($d['year']??'')?:dgs_edu_current_year();$op=$d['operation']??'';$batch=absint($d['batch_id']??0);$section=$d['section']??'sms';if(!in_array($section,array('sms','users','school','gateway'),true))$section='sms';
 if($op==='prepare'){$r=dgs_cm_prepare($d);if(!is_wp_error($r)){$batch=$r;$r='پیش‌نمایش آماده شد؛ گیرندگان را بررسی و ارسال را تأیید کنید.';}}
 elseif($op==='confirm')$r=($d['confirm_cost']??'')==='1'?dgs_cm_confirm($batch):dgs_edu_error('confirm','ارسال با هزینه پنل را تأیید کنید.');
 elseif($op==='settings')$r=dgs_cm_settings_save($d);
 elseif($op==='school')$r=dgs_cm_school_save($d);
 elseif($op==='credit'){$r=dgs_cm_request('/api/payment/credit/mine');if(!is_wp_error($r)&&isset($r['credit'])&&is_numeric($r['credit'])){update_option('dgs_sms_credit',array('value'=>(string)$r['credit'],'at'=>dgs_mg_utc()),false);$r='اعتبار اتصال بررسی شد؛ پیامکی ارسال نشد.';}elseif(!is_wp_error($r))$r=dgs_edu_error('credit','پاسخ اعتبار قابل تشخیص نبود.');}
 elseif($op==='refresh')$r=dgs_cm_status_refresh($batch);
 elseif($op==='process'){dgs_cm_worker();$r='تا پنج پیام از صف بررسی شد؛ پردازش بعدی توسط زمان‌بند ادامه می‌یابد.';}
 elseif($op==='cancel'){global $wpdb;$t=dgs_cm_tables();$r=dgs_mg_locked(function()use($batch,$wpdb,$t){$wpdb->query($wpdb->prepare("UPDATE {$t['items']} SET status='canceled',updated_at=%s WHERE batch_id=%d AND status IN ('draft','queued')",dgs_mg_utc(),$batch));$wpdb->update($t['batches'],array('status'=>'canceled'),array('id'=>$batch));dgs_edu_audit('sms_cancel',$batch);return 'پیام‌های ارسال‌نشده لغو شدند؛ ارسال پذیرفته‌شده قابل برگشت نیست.';});}
 else $r=dgs_edu_error('operation','عملیات معتبر نیست.');
 $token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r),300);wp_safe_redirect(dgs_school_url('communication',$year,array('cm_section'=>$section,'cm_batch'=>$batch,'dg_notice'=>$token)),303);exit;
});

function dgs_cm_units($text){
 $gsm="@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
 $extra="^{}\\[]~|€";$units=0;$unicode=false;preg_match_all('/./us',$text,$m);foreach($m[0] as $c){if(str_contains($extra,$c))$units+=2;elseif(str_contains($gsm,$c))$units++;else{$unicode=true;break;}}
 if($unicode){$units=0;foreach($m[0] as $c)$units+=strlen($c)===4?2:1;}$single=$unicode?70:160;$multi=$unicode?67:153;return array('units'=>$units,'parts'=>$units<=$single?1:(int)ceil($units/$multi));
}

// Never submit an unconfirmed or legacy automatic item, even if migration has not run.
function dgs_cm_manual_item($item,$batch){
 $ctx=json_decode($item->context_json,true);return $batch->status==='queued'&&in_array($batch->audience,array('individual','class','group'),true)&&($ctx['manual']??null)===true;
}
function dgs_cm_manual_migrate(){
 if(get_option('dgs_cm_schema')!=='1'||get_option('dgs_sms_manual_only')==='1')return;
 global $wpdb;$t=dgs_cm_tables();$legacy="SELECT id FROM {$t['batches']} WHERE audience NOT IN ('individual','class','group')";
 if($wpdb->query("UPDATE {$t['items']} SET status='canceled',error_code='manual_only' WHERE batch_id IN ($legacy) AND status IN ('draft','queued')")===false)return;
 // A request already in flight may have reached the provider; keep it for review, never retry it.
 if($wpdb->query("UPDATE {$t['items']} SET status='unknown',error_code='manual_only_inflight' WHERE batch_id IN ($legacy) AND status='submitting'")===false)return;
 if($wpdb->query("UPDATE {$t['batches']} SET status='canceled' WHERE audience NOT IN ('individual','class','group') AND status IN ('draft','queued')")===false)return;
 $s=dgs_cm_config();if(isset($s['auto_absence'])||isset($s['auto_report'])){unset($s['auto_absence'],$s['auto_report']);$s['revision']=(int)($s['revision']??0)+1;update_option('dgs_sms_settings',$s,false);}
 delete_option('dgs_sms_auto_warning');update_option('dgs_sms_manual_only','1',false);
}
add_action('init','dgs_cm_manual_migrate',18);
