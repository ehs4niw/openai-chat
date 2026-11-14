<?php
if(!defined('ABSPATH'))exit;

function dgs_um_root($user=null){
 $u=$user===null?wp_get_current_user():(is_numeric($user)?get_userdata((int)$user):$user);
 return $u instanceof WP_User&&((in_array('administrator',(array)$u->roles,true)&&user_can($u,'manage_options'))||(is_multisite()&&is_super_admin($u->ID)));
}
function dgs_um_disabled($id){return get_user_meta((int)$id,'_dgs_disabled',true)==='1';}
function dgs_um_staff($user){$u=is_numeric($user)?get_userdata((int)$user):$user;return $u instanceof WP_User&&(dgs_um_root($u)||count(array_intersect(array('dgs_deputy','dgs_teacher'),(array)$u->roles))>0);}
function dgs_um_password_required($user=null){
 $u=$user===null?wp_get_current_user():(is_numeric($user)?get_userdata((int)$user):$user);
 return $u instanceof WP_User&&$u->exists()&&dgs_um_staff($u)&&(get_user_meta($u->ID,'_dgs_password_policy',true)!=='1'||get_user_meta($u->ID,'_dgs_force_password',true)==='1');
}
function dgs_um_password_ready($id){update_user_meta($id,'_dgs_password_policy','1');delete_user_meta($id,'_dgs_force_password');}
add_action('user_register',function($id){if(dgs_um_staff($id))update_user_meta($id,'_dgs_force_password','1');});
add_action('set_user_role',function($id,$role,$old_roles){if(in_array($role,array('administrator','dgs_deputy','dgs_teacher'),true)&&!in_array($role,(array)$old_roles,true)){update_user_meta($id,'_dgs_force_password','1');delete_user_meta($id,'_dgs_password_policy');}},10,3);
add_action('after_password_reset',function($user,$password){if(dgs_edu_length($password)>=12&&strlen($password)<=128&&$password!==$user->user_login&&!wp_check_password($password,$user->user_pass,$user->ID))dgs_um_password_ready($user->ID);elseif(dgs_um_staff($user))update_user_meta($user->ID,'_dgs_force_password','1');},10,2);

// Login and existing sessions are both blocked; disabling keeps roles and all school records intact.
add_filter('authenticate',function($user){return $user instanceof WP_User&&dgs_um_disabled($user->ID)?new WP_Error('dgs_disabled','این حساب غیرفعال است؛ با مدیریت مدرسه تماس بگیرید.'):$user;},200);
add_filter('determine_current_user',function($id){return $id&&dgs_um_disabled($id)?0:$id;},99);
add_filter('show_admin_bar',function($show){return dgs_um_root()&&!dgs_um_password_required()?$show:false;},100);
add_filter('rest_authentication_errors',function($result){if(is_user_logged_in()&&dgs_um_password_required())return new WP_Error('dgs_password_required','ابتدا رمز اولیه را در پنل مدرسه تغییر دهید.',array('status'=>403));return $result;},100);

function dgs_um_account_url(){return dgs_school_url('account',dgs_edu_current_year());}
function dgs_um_users_url($year,$extra=array()){return dgs_school_url('communication',$year,array_merge(array('cm_section'=>'users'),$extra));}
function dgs_um_admin_endpoint_allowed($script,$action){return ($script==='admin-post.php'&&str_starts_with($action,'dgs_'))||($script==='admin-ajax.php'&&(str_starts_with($action,'dgs_')||$action==='heartbeat'));}
add_action('init',function(){
 if(!is_user_logged_in()||!dgs_um_password_required()||($_SERVER['REQUEST_METHOD']??'GET')!=='POST')return;
 $script=basename($_SERVER['SCRIPT_NAME']??'');$action=isset($_POST['action'])&&is_string($_POST['action'])?sanitize_key(wp_unslash($_POST['action'])):'';
 if($script==='wp-login.php'||($script==='admin-post.php'&&$action==='dgs_change_password'))return;
 wp_die('ابتدا رمز اولیه حساب خود را در بخش مشخصات حساب تغییر دهید.','تغییر رمز اولیه',array('response'=>403));
},1);
add_action('admin_init',function(){
 if(!is_user_logged_in())return;$script=basename($_SERVER['SCRIPT_NAME']??'');$action=isset($_REQUEST['action'])&&is_string($_REQUEST['action'])?sanitize_key(wp_unslash($_REQUEST['action'])):'';
 if(dgs_um_password_required()){
  if($script==='admin-post.php'&&$action==='dgs_change_password')return;
  if($script==='admin-ajax.php'){wp_die('ابتدا رمز اولیه را تغییر دهید.','',array('response'=>403));}
  wp_safe_redirect(dgs_um_account_url());exit;
 }
 if(dgs_um_root())return;
 if(dgs_um_admin_endpoint_allowed($script,$action))return;
 if(in_array($script,array('admin-ajax.php','admin-post.php','async-upload.php'),true))wp_die('دسترسی پیشخوان وردپرس مجاز نیست.','',array('response'=>403));
 wp_safe_redirect(dgs_page_url('portal'));exit;
},0);
add_action('template_redirect',function(){
 if(!is_user_logged_in())return;
 if(dgs_um_password_required()&&!(is_page((int)(get_option('dgs_pages',array())['portal']??0))&&dgs_att_get('dg_tab')==='account')){wp_safe_redirect(dgs_um_account_url());exit;}
 if(!current_user_can('dgs_manage_education')||!dgs_is_role_portal())return;
 $tab=dgs_att_get('dg_tab');$form='';
 if($tab==='education'&&dgs_att_get('sd_add')==='1')$form='student';
 if($tab==='education'&&dgs_att_get('dt_add')==='1')$form='teacher';
 if($form){wp_safe_redirect(dgs_um_users_url(dgs_edu_request_year(),array('um_form'=>$form)));exit;}
 if($tab==='users'){wp_safe_redirect(dgs_um_users_url(dgs_edu_request_year(),array('school_role'=>dgs_att_get('school_role','dgs_student'),'student_search'=>dgs_att_get('student_search'),'users_page'=>max(1,absint(dgs_att_get('users_page','1'))))));exit;}
},1);

function dgs_um_roles(){return array('dgs_student'=>'دانش‌آموز','dgs_teacher'=>'استاد','dgs_counselor'=>'مشاور','dgs_deputy'=>'معاون');}
function dgs_um_create_simple($d){
 if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','اجازه ساخت حساب ندارید.');foreach($d as $v)if(!is_scalar($v))return dgs_edu_error('input','اطلاعات معتبر نیست.');
 $role=$d['role']??'';if(!in_array($role,array('dgs_counselor','dgs_deputy'),true))return dgs_edu_error('role','از فرم جداگانه استاد یا دانش‌آموز استفاده کنید.');
 if($role==='dgs_deputy'&&!dgs_um_root())return dgs_edu_error('access','ساخت معاون فقط برای مدیر کل مجاز است.');
 $name=sanitize_text_field($d['name']??'');$national=dgs_edu_digits($d['national_id']??'');$phone=dgs_normalize_phone($d['phone']??'');
 if(!$name||dgs_edu_length($name)>100||!dgs_edu_national_id($national)||!$phone)return dgs_edu_error('fields','نام، کد ملی معتبر و شماره همراه را بررسی کنید.');
 if(!get_role($role))return dgs_edu_error('role','نقش حساب در سامانه آماده نیست.');
 if(username_exists($national))return dgs_edu_error('exists','این کد ملی قبلاً حساب دارد.');
 $id=wp_insert_user(array('user_login'=>$national,'user_pass'=>$national,'display_name'=>$name,'nickname'=>$name,'role'=>$role,'user_email'=>''));if(is_wp_error($id))return dgs_edu_error('create','حساب ساخته نشد.');
 update_user_meta($id,'_dgs_contact_phone',$phone);update_user_meta($id,'_dgs_created_by',get_current_user_id());dgs_edu_audit('account_create_'.$role,$id);return 'حساب '.dgs_um_roles()[$role].' ساخته شد؛ نام کاربری و رمز اولیه کد ملی است.';
}
function dgs_um_set_status($d){
 if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','دسترسی ندارید.');foreach($d as $v)if(!is_scalar($v))return dgs_edu_error('input','درخواست معتبر نیست.');
 $id=absint($d['user_id']??0);$user=get_userdata($id);$disabled=$d['disabled']??'';
 if(!$user||$id===get_current_user_id()||dgs_um_root($user)||!array_intersect(array_keys(dgs_um_roles()),(array)$user->roles)||!in_array($disabled,array('0','1'),true))return dgs_edu_error('target','تغییر وضعیت این حساب مجاز نیست.');
 if(in_array('dgs_deputy',(array)$user->roles,true)&&!dgs_um_root())return dgs_edu_error('access','وضعیت معاون را فقط مدیر کل تغییر می‌دهد.');
 $reason=sanitize_textarea_field($d['reason']??'');if(!$reason||dgs_edu_length($reason)>500)return dgs_edu_error('reason','دلیل تغییر وضعیت را حداکثر۵۰۰ نویسه وارد کنید.');
 $r=dgs_mg_locked(function()use($d,$id,$disabled,$reason){$rev=(int)get_user_meta($id,'_dgs_status_revision',true);if((string)($d['revision']??'')!==(string)$rev)return dgs_edu_error('stale','وضعیت حساب تغییر کرده؛ صفحه را تازه کنید.');
 $old=dgs_um_disabled($id);update_user_meta($id,'_dgs_disabled',$disabled);update_user_meta($id,'_dgs_status_revision',$rev+1);update_user_meta($id,'_dgs_status_reason',$reason);if(get_user_meta($id,'_dgs_disabled',true)!==$disabled||(int)get_user_meta($id,'_dgs_status_revision',true)!==$rev+1)throw new RuntimeException('وضعیت حساب ذخیره نشد.');dgs_mg_audit(array('class_id'=>0,'subject_id'=>0,'period'=>substr(dgs_att_jdate(dgs_mg_now()->format('Y-m-d')),0,7)),'account_status',0,array('user_id'=>$id,'disabled'=>$old),array('user_id'=>$id,'disabled'=>$disabled==='1'),$reason);return $disabled==='1'?'حساب غیرفعال شد؛ ورود مسدود و سوابق حفظ شدند.':'حساب دوباره فعال شد؛ صاحب حساب می‌تواند وارد شود.';});
 if(is_wp_error($r))wp_cache_delete($id,'user_meta');if(!is_wp_error($r)&&$disabled==='1')WP_Session_Tokens::get_instance($id)->destroy_all();return $r;
}
add_action('admin_post_dgs_user_management',function(){
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!current_user_can('dgs_manage_education'))wp_die('دسترسی ندارید.','',array('response'=>403));check_admin_referer('dgs_user_management','um_nonce');$d=wp_unslash($_POST);$r=($d['operation']??'')==='status'?dgs_um_set_status($d):dgs_um_create_simple($d);$token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r),300);$year=is_string($d['year']??null)?dgs_edu_year($d['year']):'';wp_safe_redirect(dgs_um_users_url($year?:dgs_edu_current_year(),array('dg_notice'=>$token,'school_role'=>is_string($d['role']??null)?sanitize_key($d['role']):'dgs_student')),303);exit;
});

function dgs_um_form($op,$year,$extra=array()){
 $h='<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="um-form">'.wp_nonce_field('dgs_user_management','um_nonce',true,false);
 foreach(array_merge(array('action'=>'dgs_user_management','operation'=>$op,'year'=>$year),$extra) as $k=>$v)$h.='<input type="hidden" name="'.esc_attr($k).'" value="'.esc_attr($v).'">';return $h;
}
function dgs_um_simple_form($role,$year){
 if($role==='dgs_deputy'&&!dgs_um_root())return '<p class="um-card">ساخت معاون فقط برای مدیر کل مجاز است.</p>';
 return '<section class="um-card"><h3>'.dgs_portal_icon('users').' افزودن '.esc_html(dgs_um_roles()[$role]).'</h3>'.dgs_um_form('create',$year,array('role'=>$role)).'<div class="um-fields"><label>نام و نام خانوادگی<input name="name" maxlength="100" required></label><label>کد ملی<input name="national_id" minlength="10" maxlength="10" inputmode="numeric" dir="ltr" required></label><label>شماره موبایل<input name="phone" inputmode="tel" dir="ltr" required></label></div><p>نام کاربری و رمز اولیه کد ملی است.'.($role==='dgs_deputy'?' معاون در اولین ورود باید رمز را تغییر دهد؛ پرونده مفصل لازم نیست.':'').'</p><button class="dg-btn">ساخت حساب '.esc_html(dgs_um_roles()[$role]).'</button></form></section>';
}
function dgs_um_users_page($year){
 if(!current_user_can('dgs_manage_education'))return '';
 $form=dgs_att_get('um_form');$h='<div class="um-users"><header class="um-title"><h2>'.dgs_portal_icon('users').' کاربران و دسترسی</h2><p>ساخت حساب‌ها، مشاهده نقش و مدیریت وضعیت ورود</p></header><nav class="um-create-grid">';
 foreach(array('teacher'=>array('افزودن استاد','پرونده کامل و رزومه','education'),'student'=>array('افزودن دانش‌آموز','حساب و پرونده دانش‌آموز','users'),'counselor'=>array('افزودن مشاور','حساب مستقل مشاور','message'),'deputy'=>array('افزودن معاون','حساب ساده؛ ویژه مدیر کل','users')) as $key=>$info){
  if($key==='deputy'&&!dgs_um_root())continue;
  $h.='<a class="um-create-card" '.($form===$key?'aria-current="page"':'').' href="'.esc_url(dgs_um_users_url($year,array('um_form'=>$key))).'">'.dgs_portal_icon($info[2]).'<strong>'.esc_html($info[0]).'</strong><small>'.esc_html($info[1]).'</small></a>';
 }
 $h.='</nav>';
 if($form){$h.='<a class="um-back" href="'.esc_url(dgs_um_users_url($year)).'">بازگشت به فهرست کاربران</a>';if($form==='teacher')$h.=dgs_teacher_editor();elseif($form==='student')$h.=dgs_admission_page($year);elseif(in_array($form,array('counselor','deputy'),true))$h.=dgs_um_simple_form('dgs_'.$form,$year);else $h.='<p>فرم انتخاب‌شده معتبر نیست.</p>';return $h.'</div>';}
 $roles=dgs_um_roles();$role=dgs_att_get('school_role','dgs_student');if(!isset($roles[$role]))$role='dgs_student';$state=dgs_att_get('um_state','all');if(!in_array($state,array('all','active','disabled'),true))$state='all';$page=max(1,min(10000,absint(dgs_att_get('users_page','1'))));$search=sanitize_text_field(dgs_att_get('student_search'));
 $args=array('role'=>$role,'number'=>20,'paged'=>$page,'orderby'=>'display_name','order'=>'ASC','count_total'=>true);
 if($search!==''){$args['search']='*'.$search.'*';$args['search_columns']=array('user_login','display_name');}
 if($state==='disabled')$args['meta_query']=array(array('key'=>'_dgs_disabled','value'=>'1'));
 elseif($state==='active')$args['meta_query']=array('relation'=>'OR',array('key'=>'_dgs_disabled','compare'=>'NOT EXISTS'),array('key'=>'_dgs_disabled','value'=>'1','compare'=>'!='));
 $q=new WP_User_Query($args);$h.='<section class="um-card"><h3>فهرست کاربران</h3><form method="get" action="'.esc_url(dgs_page_url('portal')).'" class="um-filter"><input type="hidden" name="dg_tab" value="communication"><input type="hidden" name="cm_section" value="users"><input type="hidden" name="dg_year" value="'.esc_attr($year).'">'.dgs_edu_select('school_role','نقش',$roles,$role).dgs_edu_select('um_state','وضعیت',array('all'=>'همه','active'=>'فعال','disabled'=>'غیرفعال'),$state).'<label>نام یا نام کاربری<input name="student_search" maxlength="100" value="'.esc_attr($search).'"></label><button class="dg-btn dg-btn-outline">جست‌وجو</button></form>'.dgs_edu_table_start(array('نام و نام خانوادگی','نام کاربری','نقش','شماره تماس','وضعیت','عملیات'));
 foreach($q->get_results() as $u){if(dgs_um_root($u))continue;$disabled=dgs_um_disabled($u->ID);$h.='<tr><td>'.esc_html($u->display_name).'</td><td><bdi>'.esc_html(dgs_fa_digits($u->user_login)).'</bdi></td><td>'.esc_html($roles[$role]).'</td><td><bdi>'.esc_html(dgs_fa_digits(get_user_meta($u->ID,'_dgs_contact_phone',true))).'</bdi></td><td><span class="um-status '.($disabled?'is-disabled':'is-active').'">'.($disabled?'غیرفعال':'فعال').'</span></td><td>';
  if((int)$u->ID!==get_current_user_id()&&($role!=='dgs_deputy'||dgs_um_root()))$h.='<details class="um-status-editor"><summary>'.($disabled?'فعال‌کردن حساب':'غیرفعال‌کردن حساب').'</summary>'.dgs_um_form('status',$year,array('user_id'=>$u->ID,'role'=>$role,'revision'=>(int)get_user_meta($u->ID,'_dgs_status_revision',true),'disabled'=>$disabled?'0':'1')).'<label>دلیل تغییر وضعیت<textarea name="reason" maxlength="500" rows="2" required></textarea></label><p>ورود حساب تغییر می‌کند؛ پرونده، نمرات و سایر سوابق حفظ می‌شوند.</p><button class="dg-btn dg-btn-outline" data-um-confirm="وضعیت ورود این حساب تغییر کند؟">تأیید تغییر وضعیت</button></form></details>';
  $h.='</td></tr>';
 }
 $h.='</tbody></table></div><nav class="dge-pagination">';$pages=max(1,(int)ceil($q->get_total()/20));foreach(array(-1=>'قبلی',1=>'بعدی') as $step=>$label)if($page+$step>0&&$page+$step<=$pages)$h.='<a href="'.esc_url(dgs_um_users_url($year,array('users_page'=>$page+$step,'school_role'=>$role,'um_state'=>$state,'student_search'=>$search))).'">'.$label.'</a>';
 return $h.'<span>'.esc_html(dgs_fa_digits($page.' / '.$pages)).'</span></nav></section></div>';
}
function dgs_edu_view_account($year){
 $u=wp_get_current_user();if(!$u->exists())return '';$forced=dgs_um_password_required($u);$roles=array_merge(dgs_um_roles(),array('administrator'=>'مدیر کل'));$names=array();foreach($u->roles as $r)if(isset($roles[$r]))$names[]=$roles[$r];
 $h='<section class="um-card um-account"><h2>'.dgs_portal_icon('users').' مشخصات حساب و تغییر رمز</h2>'.($forced?'<p class="um-required" role="alert">برای اولین ورود مدیر، معاون و استاد، تغییر رمز اولیه الزامی است. پس از تغییر رمز دوباره وارد شوید.</p>':'').'<dl><dt>نام و نام خانوادگی</dt><dd>'.esc_html($u->display_name).'</dd><dt>نام کاربری</dt><dd><bdi>'.esc_html(dgs_fa_digits($u->user_login)).'</bdi></dd><dt>نقش</dt><dd>'.esc_html(implode('، ',$names)).'</dd></dl>';
 $pw=dgs_edu_password_form();if($forced)$pw=str_replace('<details class="dge-box">','<details class="dge-box" open>',$pw);return $h.$pw.'<a href="'.esc_url(wp_logout_url(dgs_page_url('portal'))).'">خروج از حساب</a></section>';
}
add_action('wp_enqueue_scripts',function(){if(!dgs_is_role_portal())return;if(dgs_portal_tab()==='account'||(dgs_portal_tab()==='communication'&&dgs_att_get('cm_section')==='users')){wp_enqueue_style('dgs-user-management',DGS_URL.'assets/user-management.css',array(),DGS_VERSION);wp_enqueue_script('dgs-user-management',DGS_URL.'assets/user-management.js',array(),DGS_VERSION,true);}},66);
