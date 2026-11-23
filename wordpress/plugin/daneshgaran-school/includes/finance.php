<?php
if(!defined('ABSPATH'))exit;
function dgs_fin_tables(){global $wpdb;return array('accounts'=>$wpdb->prefix.'dgs_fin_accounts','payments'=>$wpdb->prefix.'dgs_fin_payments','installments'=>$wpdb->prefix.'dgs_fin_installments');}
add_action('init',function(){
    if(get_option('dgs_fin_schema')==='1')return;global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_fin_tables();$cc=$wpdb->get_charset_collate();
    dbDelta("CREATE TABLE {$t['accounts']} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      student_id bigint(20) unsigned NOT NULL,
      school_year varchar(9) NOT NULL,
      class_id bigint(20) unsigned NOT NULL,
      tuition bigint(20) unsigned NOT NULL,
      discount bigint(20) unsigned NOT NULL DEFAULT 0,
      revision bigint(20) unsigned NOT NULL DEFAULT 1,
      updated_by bigint(20) unsigned NOT NULL,
      updated_at datetime NOT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY student_year (student_id,school_year),
      KEY class_accounts (class_id)
    ) ENGINE=InnoDB $cc;");
    dbDelta("CREATE TABLE {$t['payments']} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      account_id bigint(20) unsigned NOT NULL,
      amount bigint(20) unsigned NOT NULL,
      paid_on date NOT NULL,
      method varchar(20) NOT NULL,
      reference varchar(100) NOT NULL DEFAULT '',
      request_token varchar(36) NOT NULL,
      created_by bigint(20) unsigned NOT NULL,
      created_at datetime NOT NULL,
      voided tinyint unsigned NOT NULL DEFAULT 0,
      void_reason varchar(250) NOT NULL DEFAULT '',
      voided_by bigint(20) unsigned NOT NULL DEFAULT 0,
      voided_at datetime DEFAULT NULL,
      PRIMARY KEY  (id),
      UNIQUE KEY request_token (request_token),
      KEY account_payments (account_id,voided)
    ) ENGINE=InnoDB $cc;");
    dbDelta("CREATE TABLE {$t['installments']} (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      account_id bigint(20) unsigned NOT NULL,
      due_on date NOT NULL,
      amount bigint(20) unsigned NOT NULL,
      PRIMARY KEY  (id),
      KEY account_due (account_id,due_on)
    ) ENGINE=InnoDB $cc;");
    foreach($t as $table)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;update_option('dgs_fin_schema','1',false);
},11);
function dgs_fin_money($raw){if(!is_scalar($raw))return null;$v=str_replace(array(',','٬',' '),'',dgs_edu_digits((string)$raw));return preg_match('/^[0-9]{1,13}$/D',$v)&&(int)$v<=1000000000000?(int)$v:null;}
function dgs_fin_format($amount){return dgs_fa_digits(number_format((int)$amount));}
function dgs_fin_can_read($student){return current_user_can('dgs_manage_education')||(dgs_edu_role(get_current_user_id(),'dgs_student')&&get_current_user_id()===(int)$student);}
function dgs_fin_get($student,$year){if(!dgs_fin_can_read($student))return null;global $wpdb;$t=dgs_fin_tables();return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['accounts']} WHERE student_id=%d AND school_year=%s",$student,$year));}
function dgs_fin_paid($account){if(!$account||!dgs_fin_can_read($account->student_id))return 0;global $wpdb;$t=dgs_fin_tables();return (int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$t['payments']} WHERE account_id=%d AND voided=0",$account->id));}
function dgs_fin_plan($text,$year,$net){
    $out=array();$sum=0;$lines=preg_split('/\r\n|\r|\n/',trim($text));if(trim($text)==='')return $out;if(count($lines)>30)return dgs_edu_error('plan','حداکثر ۳۰ قسط مجاز است.');
    foreach($lines as $line){$parts=explode('|',$line);if(count($parts)!==2)return dgs_edu_error('plan','هر قسط را به صورت تاریخ | مبلغ بنویسید.');$day=dgs_att_parse_day(trim($parts[0]),$year);$amount=dgs_fin_money(trim($parts[1]));if(!$day||$amount===null||$amount<1)return dgs_edu_error('plan','تاریخ قسط باید در سال تحصیلی و مبلغ آن مثبت باشد.');$out[]=array('due_on'=>$day,'amount'=>$amount);$sum+=$amount;}
    if($sum!==$net)return dgs_edu_error('plan','جمع اقساط باید دقیقاً برابر شهریه پس از تخفیف باشد.');usort($out,function($a,$b){return strcmp($a['due_on'],$b['due_on']);});return $out;
}
function dgs_fin_execute($op,$data){
    if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','فقط مدیر و معاون اجازه تغییر اطلاعات مالی را دارند.');
    if(get_option('dgs_fin_schema')!=='1')return dgs_edu_error('schema','جدول‌های مالی آماده نیستند.');foreach($data as $v)if(!is_scalar($v))return dgs_edu_error('input','درخواست معتبر نیست.');
    global $wpdb;$t=dgs_fin_tables();$e=dgs_edu_tables();$student=absint($data['student_id']??0);$year=dgs_edu_year((string)($data['year']??''));
    if(!$year||!dgs_edu_role($student,'dgs_student'))return dgs_edu_error('student','دانش‌آموز و سال تحصیلی معتبر انتخاب کنید.');
    $account=dgs_fin_get($student,$year);$revision=(string)($data['revision']??'');if(!ctype_digit($revision))return dgs_edu_error('revision','نسخه حساب معتبر نیست.');
    // توکن فرم از ثبت تکراری ناشی از ارسال دوباره جلوگیری می‌کند؛ رسید بانکی جایگزین آن نیست.
    if($op==='payment'){
        $token=(string)($data['request_token']??'');if(!preg_match('/^[a-f0-9-]{36}$/D',$token))return dgs_edu_error('token','شناسه فرم معتبر نیست.');
        $prior=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['payments']} WHERE request_token=%s",$token));if($prior){if($account&&(int)$prior->account_id===(int)$account->id&&(int)$prior->amount===dgs_fin_money($data['amount']??''))return 'این پرداخت قبلاً ثبت شده؛ پرداخت دیگری ایجاد نشد.';return dgs_edu_error('duplicate','شناسه درخواست تکراری است؛ صفحه را تازه کنید.');}
    }
    if((int)$revision!==($account?(int)$account->revision:0))return dgs_edu_error('stale','حساب توسط کاربر دیگری تغییر کرده؛ صفحه را تازه کنید.');
    $paid=$account?dgs_fin_paid($account):0;$row=null;$plan=null;$payment=null;$void=null;
    if($op==='account'){
        $tuition=dgs_fin_money($data['tuition']??'');$discount=dgs_fin_money($data['discount']??'');if($tuition===null||$discount===null||$discount>$tuition||$tuition-$discount<$paid)return dgs_edu_error('amount','مبلغ و تخفیف معتبر وارد کنید؛ شهریه خالص نباید کمتر از پرداخت‌های معتبر باشد.');
        $class=$wpdb->get_var($wpdb->prepare("SELECT e.class_id FROM {$e['enrollments']} e JOIN {$e['classes']} c ON c.id=e.class_id WHERE e.student_id=%d AND e.school_year=%s",$student,$year));if(!$class&&!$account)return dgs_edu_error('class','ابتدا دانش‌آموز را به کلاس همان سال تخصیص دهید.');
        $plan=dgs_fin_plan((string)($data['schedule']??''),$year,$tuition-$discount);if(is_wp_error($plan))return $plan;$row=array('tuition'=>$tuition,'discount'=>$discount,'class_id'=>$class?:$account->class_id);
    }elseif($op==='payment'){
        if(!$account)return dgs_edu_error('account','ابتدا شهریه این دانش‌آموز را تعریف کنید.');$amount=dgs_fin_money($data['amount']??'');$day=dgs_att_parse_day((string)($data['paid_on']??''),$year);$method=(string)($data['method']??'');$ref=sanitize_text_field((string)($data['reference']??''));
        if($amount===null||$amount<1||$amount>(int)$account->tuition-(int)$account->discount-$paid)return dgs_edu_error('amount','مبلغ پرداخت باید مثبت و حداکثر برابر مانده بدهی باشد.');
        if(!$day||$day>dgs_att_today()||!in_array($method,array('card','bank','cash'),true)||dgs_edu_length($ref)>100)return dgs_edu_error('payment','تاریخ در سال تحصیلی و حداکثر امروز، روش پرداخت و رسید معتبر وارد کنید.');
        $payment=array('account_id'=>$account->id,'amount'=>$amount,'paid_on'=>$day,'method'=>$method,'reference'=>$ref,'request_token'=>$token,'created_by'=>get_current_user_id(),'created_at'=>current_time('mysql'));
    }elseif($op==='void'){
        if(!$account)return dgs_edu_error('account','حساب پیدا نشد.');$void=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['payments']} WHERE id=%d AND account_id=%d",absint($data['payment_id']??0),$account->id));$reason=sanitize_text_field((string)($data['reason']??''));if(!$void||$void->voided||$reason===''||dgs_edu_length($reason)>250)return dgs_edu_error('void','پرداخت معتبر و دلیل ابطال تا ۲۵۰ نویسه لازم است.');
    }else return dgs_edu_error('op','عملیات معتبر نیست.');
    $wpdb->query('START TRANSACTION');$change=array('revision'=>(int)$revision+1,'updated_by'=>get_current_user_id(),'updated_at'=>current_time('mysql'));if($row)$change=array_merge($change,$row);
    if($account){$ok=$wpdb->update($t['accounts'],$change,array('id'=>$account->id,'revision'=>(int)$revision));$aid=(int)$account->id;}else{$change['student_id']=$student;$change['school_year']=$year;$ok=$wpdb->insert($t['accounts'],$change);$aid=(int)$wpdb->insert_id;}
    if(!$ok){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ذخیره انجام نشد یا نسخه حساب تغییر کرده؛ صفحه را تازه کنید.');}
    if($plan!==null){$ok=$wpdb->delete($t['installments'],array('account_id'=>$aid));if($ok!==false)foreach($plan as $p){$p['account_id']=$aid;if(!$wpdb->insert($t['installments'],$p)){$ok=false;break;}}}
    elseif($payment)$ok=$wpdb->insert($t['payments'],$payment);
    elseif($void)$ok=$wpdb->update($t['payments'],array('voided'=>1,'void_reason'=>$reason,'voided_by'=>get_current_user_id(),'voided_at'=>current_time('mysql')),array('id'=>$void->id,'voided'=>0));
    if($ok===false||($void&&!$ok)){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ثبت کامل نشد؛ دوباره تلاش کنید.');}
    $wpdb->query('COMMIT');dgs_edu_audit('finance_'.$op,$aid);return $op==='account'?'شهریه، تخفیف و برنامه اقساط ذخیره شدند.':($op==='payment'?'پرداخت ثبت شد.':'پرداخت با حفظ سابقه باطل شد و به جمع دریافتی محسوب نمی‌شود.');
}
add_action('admin_post_dgs_finance',function(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!current_user_can('dgs_manage_education'))wp_die('دسترسی مجاز نیست.','',array('response'=>403));check_admin_referer('dgs_finance','dgs_fin_nonce');$data=wp_unslash($_POST);$op=is_string($data['operation']??null)?$data['operation']:'';$r=dgs_fin_execute($op,$data);$token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r),300);
    wp_safe_redirect(add_query_arg(array('dg_tab'=>'finance','dg_year'=>is_string($data['year']??null)?dgs_edu_year($data['year']):'','fin_student'=>is_scalar($data['student_id']??null)?absint($data['student_id']):0,'dg_notice'=>$token),dgs_page_url('portal')),303);exit;
});
function dgs_fin_url($year,$args=array()){return add_query_arg(array_merge(array('dg_tab'=>'finance','dg_year'=>$year),$args),dgs_page_url('portal'));}
function dgs_fin_form($op,$student,$year,$account){$out='<form class="dge-form dgf-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'">'.wp_nonce_field('dgs_finance','dgs_fin_nonce',true,false);foreach(array('action'=>'dgs_finance','operation'=>$op,'student_id'=>$student,'year'=>$year,'revision'=>$account?$account->revision:0) as $k=>$v)$out.='<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'">';return $out;}
function dgs_fin_methods(){return array('card'=>'کارت به کارت','bank'=>'انتقال بانکی','cash'=>'نقدی');}
function dgs_fin_heading($student=false){return '<header class="dgf-heading"><small>صفحه اصلی ‹ '.($student?'شهریه و پرداخت‌های من':'مدیریت شهریه').'</small><h2>'.dgs_portal_icon('finance').' '.($student?'شهریه و پرداخت‌های من':'مدیریت شهریه مدرسه').'</h2><p>'.($student?'مشاهده وضعیت شهریه، پرداخت‌های انجام‌شده و اقساط':'ثبت و پیگیری شهریه دانش‌آموزان، مشاهده پرداخت‌ها و مانده حساب‌ها').'</p></header>';}
function dgs_fin_cards($net,$paid){$out='<div class="dgf-cards">';foreach(array(array('شهریه قابل پرداخت پس از تخفیف',$net,'total','▤'),array('مبلغ دریافت‌شده',$paid,'paid','✓'),array('مانده بدهی',$net-$paid,'debt','▣')) as $v)$out.='<article class="dgf-'.$v[2].'"><h3><span>'.dgs_portal_icon($v[2]==='paid'?'check':'finance').'</span>'.$v[0].'</h3><strong>'.esc_html(dgs_fin_format($v[1])).'</strong><small>تومان</small></article>';return $out.'</div>';}
function dgs_fin_payments($account){if(!$account||!dgs_fin_can_read($account->student_id))return '';global $wpdb;$t=dgs_fin_tables();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['payments']} WHERE account_id=%d ORDER BY paid_on DESC,id DESC LIMIT 501",$account->id));$out='<section class="dgf-card"><h3>▤ سوابق پرداخت‌ها</h3>';if(count($rows)>500)$out.='<p>فقط ۵۰۰ ردیف نخست نمایش داده می‌شود؛ جمع دریافتی از کل پرداخت‌های معتبر محاسبه شده است.</p>';
    $out.=dgs_edu_table_start(array('تاریخ پرداخت','مبلغ (تومان)','روش پرداخت','رسید داخلی / بانکی','وضعیت'));$methods=dgs_fin_methods();foreach(array_slice($rows,0,500) as $r){$out.='<tr><td>'.esc_html(dgs_fa_digits(dgs_att_jdate($r->paid_on))).'</td><td>'.esc_html(dgs_fin_format($r->amount)).'</td><td>'.esc_html($methods[$r->method]??$r->method).'</td><td>FIN-'.(int)$r->id.($r->reference?'<br>'.esc_html($r->reference):'').'</td><td><span class="dgf-pill '.($r->voided?'dgf-void':'dgf-paid').'">'.($r->voided?'باطل‌شده':'ثبت‌شده توسط مدرسه').'</span>';
    if($r->voided)$out.='<small class="dgf-note">دلیل: '.esc_html($r->void_reason).'</small>';
    elseif(current_user_can('dgs_manage_education'))$out.='<details><summary>ابطال با دلیل</summary>'.dgs_fin_form('void',$account->student_id,$account->school_year,$account).'<input type="hidden" name="payment_id" value="'.(int)$r->id.'"><label>دلیل ابطال<input name="reason" maxlength="250" required></label><button class="dge-small-btn" data-dge-confirm="این پرداخت با حفظ سابقه باطل شود؟">تأیید ابطال</button></form></details>';
    $out.='</td></tr>';}$out.='</tbody></table></div><p class="dgf-total">جمع پرداخت معتبر: '.esc_html(dgs_fin_format(dgs_fin_paid($account))).' تومان</p>';if(!$rows)$out.='<p>پرداختی ثبت نشده است.</p>';return $out.'</section>';
}
function dgs_fin_installments($account){if(!$account||!dgs_fin_can_read($account->student_id))return '';global $wpdb;$t=dgs_fin_tables();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['installments']} WHERE account_id=%d ORDER BY due_on,id",$account->id));$remaining=dgs_fin_paid($account);$out='<section class="dgf-card"><h3>▦ برنامه اقساط شهریه</h3><p class="dgf-help">پرداخت‌های معتبر به ترتیب سررسید، از قدیمی‌ترین قسط محاسبه می‌شوند.</p>'.dgs_edu_table_start(array('ردیف','سررسید قسط','مبلغ (تومان)','پرداخت‌شده','وضعیت تسویه'));$i=0;
    foreach($rows as $r){$i++;$part=min($remaining,(int)$r->amount);$remaining-=$part;$settled=$part===(int)$r->amount;$label=$settled?'تسویه‌شده':($part?'بخشی پرداخت‌شده':'در انتظار پرداخت');if(!$settled&&$r->due_on<dgs_att_today())$label.=' — سررسید گذشته';$out.='<tr><td>'.esc_html(dgs_fa_digits($i)).'</td><td>'.esc_html(dgs_fa_digits(dgs_att_jdate($r->due_on))).'</td><td>'.esc_html(dgs_fin_format($r->amount)).'</td><td>'.esc_html(dgs_fin_format($part)).'</td><td><span class="dgf-pill '.($settled?'dgf-paid':'dgf-wait').'">'.$label.'</span></td></tr>';}
    $out.='</tbody></table></div>';if(!$rows)$out.='<p>برنامه اقساط هنوز تعریف نشده است.</p>';return $out.'</section>';
}
function dgs_fin_student($year){
    if(!dgs_edu_role(get_current_user_id(),'dgs_student'))return '';$student=get_current_user_id();$a=dgs_fin_get($student,$year);$out='<div class="dgf-finance">'.dgs_fin_heading(true).'<form method="get" class="dge-filter" action="'.esc_url(dgs_page_url('portal')).'"><input type="hidden" name="dg_tab" value="finance"><input type="hidden" name="dg_year" dir="ltr" pattern="14[0-9]{2}-14[0-9]{2}" value="'.esc_attr($year).'" required><button class="dg-btn dg-btn-outline">نمایش</button></form>';if(!$a)return $out.'<section class="dgf-card">شهریه این سال هنوز توسط مدرسه تعیین نشده است.</section></div>';
    $class=dgs_edu_class($a->class_id,false);$out.='<section class="dgf-banner"><div><strong>سال تحصیلی '.esc_html(dgs_fa_digits($year)).'</strong><p>پایه: '.esc_html($class?dgs_edu_grade_label($class->grade):'—').' | کلاس: '.esc_html($class?$class->title:'—').'</p><p>رشته: '.esc_html($class?(dgs_edu_fields()[$class->field]??''):'—').'</p>'.($a->discount?'<p>شهریه اولیه: '.esc_html(dgs_fin_format($a->tuition)).' — تخفیف: '.esc_html(dgs_fin_format($a->discount)).' تومان</p>':'').'</div><span aria-hidden="true">🎓</span></section>';
    return $out.dgs_fin_cards((int)$a->tuition-(int)$a->discount,dgs_fin_paid($a)).dgs_fin_payments($a).dgs_fin_installments($a).'<p class="dgf-help">اطلاعات پرداخت‌ها توسط مدرسه ثبت شده است؛ پرداخت اینترنتی هنوز فعال نیست.</p></div>';
}
function dgs_edu_view_finance($year){
    if(!current_user_can('dgs_manage_education'))return '';global $wpdb;$t=dgs_fin_tables();$e=dgs_edu_tables();$grade=absint(dgs_att_get('fin_grade'));$class_id=absint(dgs_att_get('fin_class'));$page=max(1,absint(dgs_att_get('fin_page','1')));$student=absint(dgs_att_get('fin_student'));$clauses='';$args=array($year,$year,$year,$year);
    if(in_array($grade,array(10,11,12),true)){$clauses.=' AND c.grade=%d';$args[]=$grade;}if($class_id){$clauses.=' AND c.id=%d';$args[]=$class_id;}
    $sql="SELECT ids.student_id,u.display_name,c.id AS class_id,c.title,c.grade,a.id AS account_id,a.tuition,a.discount,COALESCE(p.paid,0) AS paid FROM (SELECT student_id FROM {$e['enrollments']} WHERE school_year=%s UNION SELECT student_id FROM {$t['accounts']} WHERE school_year=%s) ids JOIN {$wpdb->users} u ON u.ID=ids.student_id LEFT JOIN {$t['accounts']} a ON a.student_id=ids.student_id AND a.school_year=%s LEFT JOIN {$e['enrollments']} en ON en.student_id=ids.student_id AND en.school_year=%s LEFT JOIN {$e['classes']} c ON c.id=COALESCE(a.class_id,en.class_id) LEFT JOIN (SELECT account_id,SUM(amount) AS paid FROM {$t['payments']} WHERE voided=0 GROUP BY account_id) p ON p.account_id=a.id WHERE 1=1 $clauses ORDER BY c.grade,c.title,u.display_name LIMIT 5001";
    $rows=$wpdb->get_results($wpdb->prepare($sql,$args));$out='<div class="dgf-finance">'.dgs_fin_heading().'<section class="dgf-card"><form class="dge-filter" method="get" action="'.esc_url(dgs_page_url('portal')).'"><input type="hidden" name="dg_tab" value="finance"><input type="hidden" name="dg_year" value="'.esc_attr($year).'" dir="ltr" pattern="14[0-9]{2}-14[0-9]{2}" required>'.dgs_edu_select('fin_grade','پایه تحصیلی',array(10=>'دهم',11=>'یازدهم',12=>'دوازدهم'),$grade,false).dgs_edu_select('fin_class','کلاس',dgs_edu_class_options(dgs_edu_classes($year,true)),$class_id,false).'<button class="dg-btn">جست‌وجو</button><a class="dg-btn dg-btn-outline" href="'.esc_url(dgs_fin_url($year)).'">پاک کردن فیلترها</a></form></section>';
    if(count($rows)>5000)return $out.'<p>بیش از ۵۰۰۰ پرونده در این بازه وجود دارد؛ پایه یا کلاس را برای گزارش دقیق‌تر محدود کنید.</p></div>';
    $net=0;$paid=0;$groups=array();$undefined=0;foreach($rows as $r){$amount=(int)$r->tuition-(int)$r->discount;$net+=$amount;$paid+=(int)$r->paid;if(!$r->account_id)$undefined++;$key=$r->class_id?:0;if(!isset($groups[$key]))$groups[$key]=array('name'=>dgs_edu_grade_label($r->grade).' — '.$r->title,'count'=>0,'net'=>0,'paid'=>0,'undefined'=>0);$groups[$key]['count']++;$groups[$key]['net']+=$amount;$groups[$key]['paid']+=(int)$r->paid;if(!$r->account_id)$groups[$key]['undefined']++;}
    $out.=dgs_fin_cards($net,$paid).'<p class="dgf-help">جمع‌ها برای فیلتر انتخاب‌شده است. تعداد دانش‌آموزان: '.esc_html(dgs_fa_digits(count($rows))).' — شهریه تعیین‌نشده: '.esc_html(dgs_fa_digits($undefined)).'</p><section class="dgf-card"><h3>▥ مقایسه وضعیت مالی کلاس‌ها</h3>'.dgs_edu_table_start(array('پایه و کلاس','تعداد دانش‌آموز','شهریه خالص (تومان)','دریافت‌شده','مانده','درصد دریافت'));
    foreach($groups as $g)$out.='<tr><td>'.esc_html($g['name']).($g['undefined']?'<small class="dgf-note">'.esc_html(dgs_fa_digits($g['undefined'])).' شهریه تعیین‌نشده</small>':'').'</td><td>'.esc_html(dgs_fa_digits($g['count'])).'</td><td>'.esc_html(dgs_fin_format($g['net'])).'</td><td>'.esc_html(dgs_fin_format($g['paid'])).'</td><td>'.esc_html(dgs_fin_format($g['net']-$g['paid'])).'</td><td><span class="dgf-pill dgf-paid">'.($g['net']?esc_html(dgs_fa_digits((int)round($g['paid']/$g['net']*100))).'٪':'—').'</span></td></tr>';
    $out.='</tbody></table></div></section><section class="dgf-card"><h3>لیست دانش‌آموزان و وضعیت شهریه</h3>'.dgs_edu_table_start(array('نام و نام خانوادگی','پایه و کلاس','شهریه اولیه','تخفیف','پرداخت‌شده','مانده','وضعیت','عملیات'));$pages=max(1,(int)ceil(count($rows)/20));$page=min($page,$pages);
    foreach(array_slice($rows,($page-1)*20,20) as $r){$balance=(int)$r->tuition-(int)$r->discount-(int)$r->paid;$status=!$r->account_id?'تعیین نشده':($balance?'بدهکار':'تسویه‌شده');$out.='<tr><td>'.esc_html($r->display_name).'</td><td>'.esc_html(dgs_edu_grade_label($r->grade).' — '.$r->title).'</td><td>'.($r->account_id?esc_html(dgs_fin_format($r->tuition)):'—').'</td><td>'.esc_html(dgs_fin_format($r->discount)).'</td><td>'.esc_html(dgs_fin_format($r->paid)).'</td><td>'.($r->account_id?esc_html(dgs_fin_format($balance)):'—').'</td><td><span class="dgf-pill '.($balance||!$r->account_id?'dgf-wait':'dgf-paid').'">'.$status.'</span></td><td><a class="dgf-account-link" href="'.esc_url(dgs_fin_url($year,array('fin_student'=>$r->student_id,'fin_grade'=>$grade,'fin_class'=>$class_id,'fin_page'=>$page))).'#dgf-account">مشاهده حساب</a></td></tr>';}
    $out.='</tbody></table></div><nav class="dge-pagination">';foreach(array(-1=>'قبلی',1=>'بعدی') as $step=>$label)if($page+$step>0&&$page+$step<=$pages)$out.='<a href="'.esc_url(dgs_fin_url($year,array('fin_page'=>$page+$step,'fin_grade'=>$grade,'fin_class'=>$class_id))).'">'.$label.'</a>';$out.='<span>'.esc_html(dgs_fa_digits($page.' / '.$pages)).'</span></nav></section>';
    if($student)$out.=dgs_fin_manage_account($student,$year);else $out.='<p class="dgf-help">برای تعیین شهریه یا ثبت پرداخت، روی «مشاهده حساب» دانش‌آموز بزنید. تمام مبالغ تومان است.</p>';return $out.'</div>';
}
function dgs_fin_manage_account($student,$year){
    if(!current_user_can('dgs_manage_education')||!dgs_edu_role($student,'dgs_student'))return '';
    global $wpdb;$t=dgs_fin_tables();$a=dgs_fin_get($student,$year);$user=get_userdata($student);$schedule='';if($a){$plan=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['installments']} WHERE account_id=%d ORDER BY due_on,id",$a->id));foreach($plan as $p)$schedule.=dgs_att_jdate($p->due_on).' | '.$p->amount."\n";}
    $out='<section id="dgf-account" class="dgf-card"><h3>حساب '.esc_html($user->display_name).'</h3><details '.(!$a?'open':'').'><summary>تعیین / ویرایش شهریه، تخفیف و اقساط</summary>'.dgs_fin_form('account',$student,$year,$a).'<div class="dge-grid"><label>شهریه اولیه (تومان)<input name="tuition" inputmode="numeric" value="'.esc_attr($a?$a->tuition:'').'" required></label><label>تخفیف (تومان)<input name="discount" inputmode="numeric" value="'.esc_attr($a?$a->discount:0).'" required></label></div><label>برنامه اقساط — هر خط: تاریخ شمسی | مبلغ تومان<textarea name="schedule" rows="5" dir="ltr" placeholder="1405-07-01 | 15000000">'.esc_textarea(trim($schedule)).'</textarea></label><p class="dgf-help">اختیاری؛ جمع اقساط باید برابر شهریه پس از تخفیف باشد. حداکثر ۳۰ قسط. تاریخ هر قسط در همین سال تحصیلی باشد. تغییر برنامه، پرداخت‌های قبلی را حذف نمی‌کند.</p><button class="dg-btn">ذخیره شهریه و اقساط</button></form></details></section>';
    if(!$a)return $out;$balance=(int)$a->tuition-(int)$a->discount-dgs_fin_paid($a);
    $out.='<section class="dgf-card"><h3>▣ ثبت پرداخت جدید</h3><div class="dgf-selected"><strong>'.esc_html($user->display_name).'</strong><span>مانده: '.esc_html(dgs_fin_format($balance)).' تومان</span></div>'.dgs_fin_form('payment',$student,$year,$a).'<input type="hidden" name="request_token" value="'.esc_attr(wp_generate_uuid4()).'"><div class="dge-grid"><label>تاریخ پرداخت (شمسی)<input name="paid_on" value="'.esc_attr(dgs_att_default_day($year)).'" dir="ltr" required></label><label>مبلغ پرداخت (تومان)<input name="amount" inputmode="numeric" required></label>'.dgs_edu_select('method','روش پرداخت',dgs_fin_methods()).'<label>شماره رسید بانکی (اختیاری)<input name="reference" maxlength="100" autocomplete="off"></label></div><button class="dg-btn" '.($balance<=0?'disabled':'').'>✓ ثبت پرداخت</button><p class="dgf-help">ثبت دستی مدرسه است؛ تأیید خودکار تراکنش بانکی یا پرداخت اینترنتی انجام نمی‌شود.</p></form></section>';
    return $out.dgs_fin_payments($a).dgs_fin_installments($a);
}
add_filter('body_class',function($classes){if(dgs_is_role_portal()&&dgs_portal_tab()==='finance')$classes[]='dgs-finance-page';return $classes;});
add_action('wp_enqueue_scripts',function(){if(!dgs_is_role_portal()||dgs_portal_tab()!=='finance')return;wp_register_style('dgs-finance',false,array(),DGS_VERSION);wp_enqueue_style('dgs-finance');wp_add_inline_style('dgs-finance',file_get_contents(DGS_PATH.'assets/finance.css'));});
