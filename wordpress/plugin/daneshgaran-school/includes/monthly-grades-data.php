<?php
if(!defined('ABSPATH'))exit;
function dgs_mg_tables(){global $wpdb;return array('entries'=>$wpdb->prefix.'dgs_mark_entries','states'=>$wpdb->prefix.'dgs_mark_states','deadlines'=>$wpdb->prefix.'dgs_mark_deadlines','audit'=>$wpdb->prefix.'dgs_mark_audit','requests'=>$wpdb->prefix.'dgs_mark_requests');}
add_action('init',function(){
 if(get_option('dgs_mg_schema')==='1')return;global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_mg_tables();$cc=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t['entries']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 school_year varchar(9) NOT NULL,
 class_id bigint(20) unsigned NOT NULL,
 subject_id bigint(20) unsigned NOT NULL,
 student_id bigint(20) unsigned NOT NULL,
 period varchar(7) NOT NULL,
 kind varchar(10) NOT NULL,
 amount decimal(10,2) NOT NULL,
 note text NOT NULL,
 actor_id bigint(20) unsigned NOT NULL,
 created_at datetime NOT NULL,
 updated_at datetime NOT NULL,
 revision bigint(20) unsigned NOT NULL DEFAULT 1,
 deleted tinyint unsigned NOT NULL DEFAULT 0,
 PRIMARY KEY (id),
 KEY pair_period (class_id,subject_id,period,deleted),
 KEY student_period (student_id,period)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['states']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 school_year varchar(9) NOT NULL,
 class_id bigint(20) unsigned NOT NULL,
 subject_id bigint(20) unsigned NOT NULL,
 period varchar(7) NOT NULL,
 status varchar(10) NOT NULL DEFAULT 'open',
 reopened tinyint unsigned NOT NULL DEFAULT 0,
 revision bigint(20) unsigned NOT NULL DEFAULT 1,
 finalized_by bigint(20) unsigned NOT NULL DEFAULT 0,
 finalized_at datetime DEFAULT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY pair_period (class_id,subject_id,period)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['deadlines']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 school_year varchar(9) NOT NULL,
 period varchar(7) NOT NULL,
 ends_at datetime NOT NULL,
 updated_by bigint(20) unsigned NOT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY year_period (school_year,period)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['audit']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 class_id bigint(20) unsigned NOT NULL,
 subject_id bigint(20) unsigned NOT NULL,
 period varchar(7) NOT NULL,
 entry_id bigint(20) unsigned NOT NULL DEFAULT 0,
 actor_id bigint(20) unsigned NOT NULL,
 operation varchar(24) NOT NULL,
 before_data longtext NOT NULL,
 after_data longtext NOT NULL,
 reason text NOT NULL,
 created_at datetime NOT NULL,
 PRIMARY KEY (id),
 KEY pair_period (class_id,subject_id,period),
 KEY entry_history (entry_id)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['requests']} (
 request_key varchar(64) NOT NULL,
 created_at datetime NOT NULL,
 PRIMARY KEY (request_key)
 ) ENGINE=InnoDB $cc;");
 foreach($t as $name)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($name)))!==$name)return;update_option('dgs_mg_schema','1',false);
},15);
function dgs_mg_now(){return apply_filters('dgs_mg_clock',new DateTimeImmutable('now',new DateTimeZone('Asia/Tehran')));}
function dgs_mg_utc(){return dgs_mg_now()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
function dgs_mg_next($p){$y=(int)substr($p,0,4);$m=(int)substr($p,5,2)+1;if($m===13){$m=1;$y++;}return sprintf('%04d-%02d',$y,$m);}
function dgs_mg_month_end($p){$next=dgs_mg_next($p);return (new DateTimeImmutable(dgs_att_gregorian((int)substr($next,0,4),(int)substr($next,5,2),1).' 00:00:00',new DateTimeZone('Asia/Tehran')))->modify('-1 second');}
function dgs_mg_deadline($year,$p){global $wpdb;$t=dgs_mg_tables();$s=$wpdb->get_var($wpdb->prepare("SELECT ends_at FROM {$t['deadlines']} WHERE school_year=%s AND period=%s",$year,$p));return $s?(new DateTimeImmutable($s,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Tehran')):dgs_mg_month_end($p);}
function dgs_mg_effective($year){$p=substr(dgs_att_jdate(dgs_mg_now()->format('Y-m-d')),0,7);if(!dgs_grade_period($p,$year))return '';if(dgs_mg_now()->getTimestamp()>dgs_mg_deadline($year,$p)->getTimestamp())$p=dgs_mg_next($p);return dgs_grade_period($p,$year);}
function dgs_mg_cents($v){$s=(string)$v;$neg=str_starts_with($s,'-');$s=ltrim($s,'+-');$p=explode('.',$s,2);$n=(int)$p[0]*100+(int)substr(str_pad($p[1]??'',2,'0'),0,2);return $neg?-$n:$n;}
function dgs_mg_decimal($c){return ($c<0?'-':'').intdiv(abs($c),100).'.'.str_pad((string)(abs($c)%100),2,'0',STR_PAD_LEFT);}
function dgs_mg_number($v,$grade=false){if(!is_scalar($v))return null;$v=str_replace(array('٫','/','−'),array('.','.','-'),dgs_edu_digits(trim((string)$v)));if(!preg_match('/^[+-]?\d{1,8}(?:\.\d{1,2})?$/D',$v))return null;$c=dgs_mg_cents($v);if($grade&&($c<0||$c>2000))return null;return $c;}
function dgs_mg_score($sum,$count,$events){if($events>=2000)return 2000;if($events<=-2000)return 0;if(!$count)return max(0,min(2000,$events));$n=$sum+$events*$count;if($n<=0)return 0;if($n>=2000*$count)return 2000;return intdiv(2*$n+$count,2*$count);}
function dgs_mg_roster($c,$year){global $wpdb;$e=dgs_edu_tables();return $wpdb->get_results($wpdb->prepare("SELECT u.ID,u.display_name FROM {$e['enrollments']} en JOIN {$wpdb->users} u ON u.ID=en.student_id WHERE en.class_id=%d AND en.school_year=%s ORDER BY u.display_name,u.ID",$c,$year));}
function dgs_mg_summary($c,$s,$p,$year){global $wpdb;$t=dgs_mg_tables();$rows=$wpdb->get_results($wpdb->prepare("SELECT student_id,COUNT(*) AS entries,SUM(CASE WHEN kind='grade' THEN 1 ELSE 0 END) AS cnt,SUM(CASE WHEN kind='grade' THEN amount ELSE 0 END) AS marks,SUM(CASE WHEN kind='event' THEN amount ELSE 0 END) AS events FROM {$t['entries']} WHERE class_id=%d AND subject_id=%d AND period=%s AND deleted=0 GROUP BY student_id",$c,$s,$p));$map=array();foreach($rows as $r)$map[(int)$r->student_id]=$r;$out=array();foreach(dgs_mg_roster($c,$year) as $u){$r=$map[$u->ID]??null;$cnt=$r?(int)$r->cnt:0;$marks=$r?dgs_mg_cents($r->marks):0;$events=$r?dgs_mg_cents($r->events):0;$out[$u->ID]=array('id'=>(int)$u->ID,'name'=>$u->display_name,'entries'=>$r?(int)$r->entries:0,'count'=>$cnt,'marks'=>$marks,'events'=>$events,'mean'=>$cnt?intdiv(2*$marks+$cnt,2*$cnt):0,'score'=>dgs_mg_score($marks,$cnt,$events));}return $out;}
function dgs_mg_state($c,$s,$p,$year,$create=false){global $wpdb;$t=dgs_mg_tables();$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['states']} WHERE class_id=%d AND subject_id=%d AND period=%s",$c,$s,$p));if(!$r&&$create){$g=dgs_grade_table();$legacy=$wpdb->get_var($wpdb->prepare("SELECT id FROM $g WHERE class_id=%d AND subject_id=%d AND period=%s LIMIT 1",$c,$s,$p));if($wpdb->insert($t['states'],array('school_year'=>$year,'class_id'=>$c,'subject_id'=>$s,'period'=>$p,'status'=>$legacy?'legacy':'open'))===false)throw new RuntimeException('وضعیت ماه ساخته نشد.');return dgs_mg_state($c,$s,$p,$year);}return $r;}
function dgs_mg_audit($ctx,$op,$entry=0,$before=null,$after=null,$reason=''){global $wpdb;$t=dgs_mg_tables();if($wpdb->insert($t['audit'],array('class_id'=>$ctx['class_id'],'subject_id'=>$ctx['subject_id'],'period'=>$ctx['period'],'entry_id'=>$entry,'actor_id'=>!empty($ctx['system'])?0:get_current_user_id(),'operation'=>$op,'before_data'=>wp_json_encode($before),'after_data'=>wp_json_encode($after),'reason'=>$reason,'created_at'=>dgs_mg_utc()))===false)throw new RuntimeException('ثبت سابقه تغییرات انجام نشد.');}
function dgs_mg_locked($callback){global $wpdb;$key='dgs_mg_write_mutex';$token=wp_generate_uuid4().':'.time();if(!add_option($key,$token,'','no')){$old=$wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name=%s",$key));$parts=explode(':',(string)$old);if((int)end($parts)<time()-180){$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",$key,$old));wp_cache_delete($key,'options');}return dgs_edu_error('busy','عملیات دیگری در حال ذخیره است؛ چند لحظه بعد دوباره تلاش کنید.');}
 try{if($wpdb->query('START TRANSACTION')===false)throw new RuntimeException('آغاز ذخیره امن ممکن نشد.');$r=$callback();if(is_wp_error($r)){$wpdb->query('ROLLBACK');return $r;}if($wpdb->query('COMMIT')===false)throw new RuntimeException('تأیید نهایی ذخیره ممکن نشد.');return $r;}catch(Throwable $e){$wpdb->query('ROLLBACK');return dgs_edu_error('write',$e->getMessage());}finally{$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",$key,$token));wp_cache_delete($key,'options');wp_cache_delete('notoptions','options');}}
function dgs_mg_context($d){$year=is_string($d['year']??null)?dgs_edu_year($d['year']):'';$c=dgs_edu_class(is_scalar($d['class_id']??null)?$d['class_id']:0);$s=is_scalar($d['subject_id']??null)?absint($d['subject_id']):0;$p=is_string($d['period']??null)?dgs_grade_period($d['period'],$year):'';if(!$year||!$c||$c->school_year!==$year||!$p||!dgs_grade_can_edit($c,$s))return dgs_edu_error('access','کلاس، درس، ماه یا دسترسی معتبر نیست.');global $wpdb;$t=dgs_edu_tables();if(!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['teaching']} WHERE class_id=%d AND subject_id=%d",$c->id,$s)))return dgs_edu_error('assignment','درس در این کلاس دبیر تخصیص‌یافته ندارد.');return array('year'=>$year,'class_id'=>(int)$c->id,'subject_id'=>$s,'period'=>$p);}
function dgs_mg_snapshot($ctx){global $wpdb;$g=dgs_grade_table();foreach(dgs_mg_summary($ctx['class_id'],$ctx['subject_id'],$ctx['period'],$ctx['year']) as $r){$old=$wpdb->get_row($wpdb->prepare("SELECT * FROM $g WHERE student_id=%d AND subject_id=%d AND period=%s",$r['id'],$ctx['subject_id'],$ctx['period']));if($old&&(int)$old->class_id!==$ctx['class_id'])throw new RuntimeException('این دانش‌آموز در همین درس و ماه سابقه کلاس دیگری دارد؛ مدیریت باید تعارض را رفع کند.');$row=array('class_id'=>$ctx['class_id'],'subject_id'=>$ctx['subject_id'],'student_id'=>$r['id'],'period'=>$ctx['period'],'score'=>dgs_mg_decimal($r['score']),'published'=>0,'updated_by'=>!empty($ctx['system'])?0:get_current_user_id(),'updated_at'=>dgs_mg_utc());$ok=$old?$wpdb->update($g,$row,array('id'=>$old->id)):$wpdb->insert($g,$row);if($ok===false)throw new RuntimeException('ذخیره نمره نهایی کامل نشد.');}}
function dgs_mg_finalize_inner($ctx,$auto=false){if($auto)$ctx['system']=true;global $wpdb;$t=dgs_mg_tables();$st=dgs_mg_state($ctx['class_id'],$ctx['subject_id'],$ctx['period'],$ctx['year'],true);if($st->status==='legacy')return dgs_edu_error('legacy','نمرات قبلی محفوظ‌اند؛ مدیریت ابتدا انتقال به دفتر ثبت را تأیید کند.');if($st->status==='closed')return 'این ماه قبلاً نهایی شده است.';if($auto&&($st->reopened||dgs_mg_now()->getTimestamp()<=dgs_mg_deadline($ctx['year'],$ctx['period'])->getTimestamp()))return 'این ماه برای نهایی‌سازی خودکار بسته نیست.';dgs_mg_snapshot($ctx);if($wpdb->update($t['states'],array('status'=>'closed','reopened'=>0,'revision'=>(int)$st->revision+1,'finalized_by'=>$auto?0:get_current_user_id(),'finalized_at'=>dgs_mg_utc()),array('id'=>$st->id))===false)throw new RuntimeException('قفل ماه ذخیره نشد.');dgs_mg_audit($ctx,$auto?'auto_finalize':'finalize',0,$st,array('status'=>'closed'));return 'نمرات ماه نهایی شد؛ انتشار کارنامه با مدیریت است.';}
function dgs_mg_touch($ctx,$state){global $wpdb;$t=dgs_mg_tables();if($state->status==='closed')dgs_mg_snapshot($ctx);if($wpdb->query($wpdb->prepare("UPDATE {$t['states']} SET revision=revision+1 WHERE id=%d",$state->id))===false)throw new RuntimeException('نسخه ماه ذخیره نشد.');}
function dgs_mg_write($d){
 if(get_option('dgs_mg_schema')!=='1')return dgs_edu_error('schema','دفتر نمرات آماده نیست.');
 foreach(array('operation','year','class_id','subject_id','period','reason','note','score','event_type','event_amount','entry_id','revision','request_token','student_id','all') as $k)if(isset($d[$k])&&!is_scalar($d[$k]))return dgs_edu_error('input','ورودی معتبر نیست.');
 $ctx=dgs_mg_context($d);if(is_wp_error($ctx))return $ctx;$manager=current_user_can('dgs_manage_education');$op=(string)($d['operation']??'add');$reason=sanitize_textarea_field((string)($d['reason']??''));$note=sanitize_textarea_field((string)($d['note']??''));if($manager&&$reason==='')$reason=$note;if(dgs_edu_length($reason)>2000||dgs_edu_length($note)>2000)return dgs_edu_error('length','توضیح حداکثر ۲۰۰۰ نویسه باشد.');
 if(!$manager&&$op==='add'){$p=dgs_mg_effective($ctx['year']);if(!$p)return dgs_edu_error('period','ثبت جدید در سال انتخابی باز نیست.');$ctx['period']=$p;}
 if($manager&&in_array($op,array('add','edit','delete','reopen','import'),true)&&$reason==='')return dgs_edu_error('reason','مدیریت باید دلیل ثبت یا اصلاح را بنویسد.');
 $token=(string)($d['request_token']??'');if(!preg_match('/^[a-f0-9-]{36}$/D',$token))return dgs_edu_error('token','فرم معتبر نیست؛ صفحه را تازه کنید.');
 return dgs_mg_locked(function()use($ctx,$manager,$op,$reason,$note,$d,$token){
  global $wpdb;$t=dgs_mg_tables();$key=hash('sha256',get_current_user_id().':'.$token);if($wpdb->get_var($wpdb->prepare("SELECT request_key FROM {$t['requests']} WHERE request_key=%s",$key)))return 'این درخواست قبلاً ثبت شده است؛ ثبت تکراری ساخته نشد.';
  $st=dgs_mg_state($ctx['class_id'],$ctx['subject_id'],$ctx['period'],$ctx['year'],true);
  if(in_array($op,array('finalize','reopen','import'),true)&&(!isset($d['revision'])||(int)$d['revision']!==(int)$st->revision))return dgs_edu_error('stale','وضعیت ماه تغییر کرده است؛ صفحه را تازه کنید.');
  if(!$manager&&$op==='finalize'&&$ctx['period']!==dgs_mg_effective($ctx['year'])&&!($st->status==='open'&&$st->reopened))return dgs_edu_error('period','این ماه برای نهایی‌سازی دبیر باز نیست.');
  if($op==='reopen'){
   if(!$manager)return dgs_edu_error('access','بازگشایی فقط توسط مدیریت است.');if($st->status==='legacy')return dgs_edu_error('legacy','ابتدا انتقال نمرات قدیمی را انجام دهید.');
   if($wpdb->update($t['states'],array('status'=>'open','reopened'=>1,'revision'=>(int)$st->revision+1),array('id'=>$st->id))===false)throw new RuntimeException('بازگشایی انجام نشد.');
   $g=dgs_grade_table();if($wpdb->update($g,array('published'=>0),array('class_id'=>$ctx['class_id'],'subject_id'=>$ctx['subject_id'],'period'=>$ctx['period']))===false)throw new RuntimeException('توقف انتشار انجام نشد.');dgs_mg_audit($ctx,'reopen',0,$st,array('status'=>'open'),$reason);$result='ماه برای اصلاح باز شد؛ پس از اصلاح دوباره نهایی و منتشر شود.';
  }elseif($op==='import'){
   if(!$manager||$st->status!=='legacy')return dgs_edu_error('legacy','انتقال در این وضعیت مجاز نیست.');$g=dgs_grade_table();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM $g WHERE class_id=%d AND subject_id=%d AND period=%s",$ctx['class_id'],$ctx['subject_id'],$ctx['period']));foreach($rows as $r){$row=array('school_year'=>$ctx['year'],'class_id'=>$ctx['class_id'],'subject_id'=>$ctx['subject_id'],'student_id'=>$r->student_id,'period'=>$ctx['period'],'kind'=>'grade','amount'=>$r->score,'note'=>'انتقال نمره ماهانه قبلی: '.$reason,'actor_id'=>get_current_user_id(),'created_at'=>dgs_mg_utc(),'updated_at'=>dgs_mg_utc());if($wpdb->insert($t['entries'],$row)===false)throw new RuntimeException('انتقال نمرات کامل نشد.');dgs_mg_audit($ctx,'legacy_import',$wpdb->insert_id,$r,$row,$reason);}$wpdb->update($t['states'],array('status'=>'open'),array('id'=>$st->id));$result=dgs_mg_finalize_inner($ctx);
  }elseif($op==='finalize'){$result=dgs_mg_finalize_inner($ctx);
  }else{
   if($st->status==='legacy')return dgs_edu_error('legacy','نمرات ماه قبلی محفوظ‌اند؛ مدیریت باید انتقال را تأیید کند.');
   if(!$manager&&($st->status==='closed'||(!$st->reopened&&dgs_mg_now()->getTimestamp()>dgs_mg_deadline($ctx['year'],$ctx['period'])->getTimestamp())))return dgs_edu_error('locked','این ماه برای ویرایش دبیر بسته شده است.');
   if(!isset($d['revision'])||(int)$d['revision']!==(int)$st->revision)return dgs_edu_error('stale','ثبت‌های این ماه تغییر کرده‌اند؛ صفحه را تازه کنید.');
   if($op==='add'){
    $score=trim((string)($d['score']??''));$event=trim((string)($d['event_amount']??''));$values=array();if($score!==''){$c=dgs_mg_number($score,true);if($c===null)return dgs_edu_error('score','نمره بین صفر و ۲۰ با حداکثر دو رقم اعشار باشد.');$values['grade']=$c;}
    if($event!==''){$v=dgs_mg_number($event);$type=(string)($d['event_type']??'');if($v===null||$v<=0||!in_array($type,array('positive','negative'),true))return dgs_edu_error('event','نوع رویداد و مقدار مثبتِ آن را وارد کنید.');if($type==='negative'&&$note==='')return dgs_edu_error('note','برای رویداد منفی دلیل الزامی است.');$values['event']=$type==='negative'?-$v:$v;}
    if(!$values)return dgs_edu_error('empty','حداقل یک نمره یا رویداد وارد کنید.');$roster=dgs_mg_roster($ctx['class_id'],$ctx['year']);$ids=array_map(function($u){return (int)$u->ID;},$roster);$targets=($d['all']??'')==='1'?$ids:array(absint($d['student_id']??0));if(!$targets||count($targets)>500||array_diff($targets,$ids))return dgs_edu_error('student','دانش‌آموز باید عضو همین کلاس باشد.');
    foreach($targets as $uid)foreach($values as $kind=>$amount){$row=array('school_year'=>$ctx['year'],'class_id'=>$ctx['class_id'],'subject_id'=>$ctx['subject_id'],'student_id'=>$uid,'period'=>$ctx['period'],'kind'=>$kind,'amount'=>dgs_mg_decimal($amount),'note'=>$note,'actor_id'=>get_current_user_id(),'created_at'=>dgs_mg_utc(),'updated_at'=>dgs_mg_utc());if($wpdb->insert($t['entries'],$row)===false)throw new RuntimeException('ثبت کامل نشد.');dgs_mg_audit($ctx,'create',$wpdb->insert_id,null,$row,$reason);}$result='ثبت انجام شد؛ فیلدها برای ثبت بعدی خالی شدند.';
   }elseif(in_array($op,array('edit','delete'),true)){
    $r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['entries']} WHERE id=%d AND class_id=%d AND subject_id=%d AND period=%s AND deleted=0",absint($d['entry_id']??0),$ctx['class_id'],$ctx['subject_id'],$ctx['period']));if(!$r||(!$manager&&(int)$r->actor_id!==get_current_user_id()))return dgs_edu_error('entry','این ثبت برای ویرایش مجاز نیست.');
    if($op==='delete')$update=array('deleted'=>1);else{$value=dgs_mg_number($d['amount']??null,$r->kind==='grade');if($value===null||($r->kind==='event'&&$value===0)||($r->kind==='event'&&$value<0&&$note===''))return dgs_edu_error('amount','مقدار معتبر و دلیل رویداد منفی را وارد کنید.');$update=array('amount'=>dgs_mg_decimal($value),'note'=>$note);}$update['revision']=(int)$r->revision+1;$update['updated_at']=dgs_mg_utc();if($wpdb->update($t['entries'],$update,array('id'=>$r->id,'revision'=>$r->revision))!==1)throw new RuntimeException('این ثبت هم‌زمان تغییر کرده است.');dgs_mg_audit($ctx,$op,$r->id,$r,$update,$reason);$result=$op==='delete'?'ثبت حذف شد؛ سابقه آن محفوظ است.':'ثبت اصلاح شد؛ تاریخ اولیه تغییر نکرد.';
   }else return dgs_edu_error('operation','عملیات معتبر نیست.');dgs_mg_touch($ctx,$st);
  }
  if(is_wp_error($result))return $result;
  // Persist idempotency with the transaction so retries cannot append twice.
  if($wpdb->insert($t['requests'],array('request_key'=>$key,'created_at'=>dgs_mg_utc()))===false)throw new RuntimeException('شناسه ثبت ذخیره نشد.');return $result;
 });
}
function dgs_mg_deadline_save($d){if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','مهلت فقط توسط مدیریت تعیین می‌شود.');$year=is_string($d['year']??null)?dgs_edu_year($d['year']):'';$p=is_string($d['period']??null)?dgs_grade_period($d['period'],$year):'';$raw=is_string($d['deadline']??null)?$d['deadline']:'';$day=$year?dgs_att_parse_day($raw,$year):'';if(!$p||!$day||substr(dgs_att_jdate($day),0,7)!==$p)return dgs_edu_error('deadline','تاریخ مهلت باید در همان ماه انتخابی باشد.');$end=new DateTimeImmutable($day.' 23:59:59',new DateTimeZone('Asia/Tehran'));return dgs_mg_locked(function()use($year,$p,$end){global $wpdb;$t=dgs_mg_tables();$old=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['deadlines']} WHERE school_year=%s AND period=%s",$year,$p));$row=array('school_year'=>$year,'period'=>$p,'ends_at'=>$end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'updated_by'=>get_current_user_id());$ok=$old?$wpdb->update($t['deadlines'],$row,array('id'=>$old->id)):$wpdb->insert($t['deadlines'],$row);if($ok===false)throw new RuntimeException('مهلت ذخیره نشد.');dgs_mg_audit(array('class_id'=>0,'subject_id'=>0,'period'=>$p),'deadline',0,$old,$row);return 'مهلت ماه ذخیره شد؛ ثبت‌های بعد از آن برای ماه بعد محاسبه می‌شوند.';});}
function dgs_mg_auto_finalize(){if(get_option('dgs_mg_schema')!=='1')return;$year=dgs_edu_request_year();if(!$year)return;$now=dgs_mg_now();$current=substr(dgs_att_jdate($now->format('Y-m-d')),0,7);$start=get_option('dgs_mg_started_period');if(!$start){update_option('dgs_mg_started_period',$current,false);$start=$current;}global $wpdb;$e=dgs_edu_tables();$pairs=$wpdb->get_results($wpdb->prepare("SELECT a.class_id,a.subject_id FROM {$e['teaching']} a JOIN {$e['classes']} c ON c.id=a.class_id WHERE c.school_year=%s AND c.active=1",$year));$done=0;foreach(dgs_mg_periods($year) as $p=>$label){if($p<$start||$p>$current||$now->getTimestamp()<=dgs_mg_deadline($year,$p)->getTimestamp())continue;foreach($pairs as $pair){$st=dgs_mg_view_state(array('class_id'=>$pair->class_id,'subject_id'=>$pair->subject_id,'period'=>$p,'year'=>$year));if($st->status!=='open'||$st->reopened)continue;$ctx=array('year'=>$year,'class_id'=>(int)$pair->class_id,'subject_id'=>(int)$pair->subject_id,'period'=>$p);dgs_mg_locked(function()use($ctx){return dgs_mg_finalize_inner($ctx,true);});if(++$done>=100)return;}}}
add_action('init',function(){if(!get_option('dgs_mg_started_period'))update_option('dgs_mg_started_period',substr(dgs_att_jdate(dgs_mg_now()->format('Y-m-d')),0,7),false);if(!wp_next_scheduled('dgs_mg_hourly'))wp_schedule_event(time()+60,'hourly','dgs_mg_hourly');},25);
add_action('dgs_mg_hourly','dgs_mg_auto_finalize');
function dgs_mg_periods($year){$out=array();foreach(explode('-',$year) as $y)for($m=1;$m<=12;$m++){$p=sprintf('%04d-%02d',(int)$y,$m);if(dgs_grade_period($p,$year))$out[$p]=dgs_study_months()[$m].' '.dgs_fa_digits($y);}ksort($out);return $out;}
function dgs_mg_view_state($ctx){$s=dgs_mg_state($ctx['class_id'],$ctx['subject_id'],$ctx['period'],$ctx['year']);if($s)return $s;global $wpdb;$g=dgs_grade_table();$old=$wpdb->get_var($wpdb->prepare("SELECT id FROM $g WHERE class_id=%d AND subject_id=%d AND period=%s LIMIT 1",$ctx['class_id'],$ctx['subject_id'],$ctx['period']));return (object)array('id'=>0,'revision'=>1,'status'=>$old?'legacy':'open','reopened'=>0);}
function dgs_mg_history($ctx,$student,$page=1){global $wpdb;$t=dgs_mg_tables();return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['entries']} WHERE class_id=%d AND subject_id=%d AND period=%s AND student_id=%d AND deleted=0 ORDER BY id DESC LIMIT 50 OFFSET %d",$ctx['class_id'],$ctx['subject_id'],$ctx['period'],$student,(max(1,(int)$page)-1)*50));}
function dgs_mg_publish($d,$ctx){if(!dgs_manage_allowed()||($d['approve']??'')!=='1')return dgs_edu_error('access','انتشار فقط با تأیید مدیریت انجام می‌شود.');$result=dgs_mg_locked(function()use($d,$ctx){global $wpdb;$g=dgs_grade_table();$data=dgs_os_data($ctx);if(!$data['expected']||$data['missing']||$data['unconfigured'])return dgs_edu_error('missing','همه کلاس‌ها و درس‌ها باید نمره ماهانه کامل داشته باشند.');if(!is_string($d['snapshot']??null)||!hash_equals($data['hash'],$d['snapshot']))return dgs_edu_error('stale','گزارش تغییر کرده است؛ پیش‌نمایش را تازه کنید.');$seen=array();foreach($data['records'] as $r){$key=$r->class_id.':'.$r->subject_id.':'.$r->period;$st=dgs_mg_state($r->class_id,$r->subject_id,$r->period,$ctx['year']);if($st&&$st->status==='open')return dgs_edu_error('open','یک ماه باز یا نهایی‌نشده وجود دارد؛ ابتدا نهایی‌سازی کنید.');if($wpdb->update($g,array('published'=>1),array('id'=>$r->id))===false)throw new RuntimeException('انتشار کامل نشد.');if(!isset($seen[$key])){$seen[$key]=true;dgs_mg_audit(array('class_id'=>$r->class_id,'subject_id'=>$r->subject_id,'period'=>$r->period),'publish',0,null,array('published'=>1));}}return 'کارنامه منتشر شد؛ نمرات نهایی برای دانش‌آموزان قابل مشاهده است.';});return $result;}
add_action('admin_post_dgs_monthly_marks',function(){
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||(!current_user_can('dgs_manage_education')&&!dgs_edu_role(get_current_user_id(),'dgs_teacher')))wp_die('دسترسی مجاز نیست.','',array('response'=>403));check_admin_referer('dgs_monthly_marks','dgs_mg_nonce');$d=wp_unslash($_POST);$r=($d['operation']??'')==='deadline'?dgs_mg_deadline_save($d):dgs_mg_write($d);$year=is_string($d['year']??null)?dgs_edu_year($d['year']):'';$token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r),300);
 if(is_wp_error($r)&&($d['operation']??'')==='add'){$saved=array();foreach(array('score','event_type','event_amount','note','reason','all') as $k)if(is_scalar($d[$k]??null))$saved[$k]=function_exists('mb_substr')?mb_substr((string)$d[$k],0,2000):substr((string)$d[$k],0,4000);set_transient('dgs_mg_form_'.get_current_user_id().'_'.$token,$saved,300);}
 $p=is_string($d['period']??null)?dgs_grade_period($d['period'],$year):'';if(!current_user_can('dgs_manage_education')&&($d['operation']??'')==='add')$p=dgs_mg_effective($year);
 wp_safe_redirect(dgs_school_url('grades',$year,array('grade_pair'=>absint(is_scalar($d['class_id']??null)?$d['class_id']:0).':'.absint(is_scalar($d['subject_id']??null)?$d['subject_id']:0),'grade_period'=>$p,'mg_student'=>absint(is_scalar($d['student_id']??null)?$d['student_id']:0),'dg_notice'=>$token)),303);exit;
});

add_action('wp',function(){if(dgs_is_role_portal())dgs_mg_auto_finalize();},25);
