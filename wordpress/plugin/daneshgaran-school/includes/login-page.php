<?php
if(!defined('ABSPATH'))exit;
function dgs_login_roles(){return array('student'=>array('دانش‌آموز','education'),'teacher'=>array('معلم','teacher'),'counselor'=>array('مشاور','counselor'),'manager'=>array('مدیر و معاون','manager'));}
function dgs_login_role_matches($u,$role){if($role==='manager')return user_can($u,'dgs_manage_education')||user_can($u,'manage_options');return in_array('dgs_'.$role,(array)$u->roles,true);}
function dgs_login_icon($name){$paths=array('teacher'=>'M12 3h10v12H12M15 19v-4M4 21v-4c0-4 9-4 9 0v4M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M12 11l4-3','counselor'=>'M7 4h10a4 4 0 0 1 0 8h-4l-4 3v-3H7a4 4 0 0 1 0-8M4 22v-2c0-3 5-3 5 0v2M6.5 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4M16 22v-2c0-3 5-3 5 0v2M18.5 17a2 2 0 1 0 0-4 2 2 0 0 0 0 4','manager'=>'M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8M4 22v-5c0-6 16-6 16 0v5zM10 13l2 3 2-3M12 16l-2 5h4z');return isset($paths[$name])?'<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="'.$paths[$name].'"/></svg>':dgs_portal_icon($name);}
// Read trusted packaged images server-side so blocked public plugin asset URLs do not hide the login artwork.
function dgs_login_art_uri(){
 static $uri=null;if($uri!==null)return $uri;
 $file=DGS_PATH.'assets/login-reference.jpg';$data=is_readable($file)?file_get_contents($file):false;
 return $uri=$data!==false?'data:image/jpeg;base64,'.base64_encode($data):'';
}
function dgs_login_page(){
 $roles=dgs_login_roles();$chosen=isset($_GET['login_role'])&&is_string($_GET['login_role'])&&isset($roles[$_GET['login_role']])?$_GET['login_role']:'student';ob_start(); ?>
<div class="dgl-page"><header class="dgl-heading"><img src="<?php echo esc_url(DGS_URL.'assets/portal-logo.svg'); ?>" alt="" width="110" height="86"><strong><?php echo esc_html(dgs_setting('school_name','مدرسه دانشگران')); ?></strong><p>دبیرستان دوره دوم <span>│</span> پایه‌های دهم، یازدهم و دوازدهم</p><h1>ورود به سامانه مدرسه دانشگران</h1><p class="dgl-subheading">مدیریت آموزشی، ارتباطی و اجرایی مدرسه در یک سامانه یکپارچه</p></header>
<section class="dgl-card"><div class="dgl-form-side"><form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" class="dgl-form">
<input type="hidden" name="action" value="dgs_portal_login"><?php wp_nonce_field('dgs_portal_login','dgs_login_nonce'); ?>
<fieldset><legend>نقش خود را انتخاب کنید:</legend><div class="dgl-roles"><?php foreach($roles as $key=>$data): ?><label><input type="radio" name="login_role" value="<?php echo esc_attr($key); ?>" <?php checked($chosen,$key); ?> required><span><?php echo dgs_login_icon($data[1]); ?><b><?php echo esc_html($data[0]); ?></b></span></label><?php endforeach; ?></div></fieldset>
<?php if(isset($_GET['login_error'])): ?><p class="dgl-error" role="alert">ورود انجام نشد. نام کاربری، رمز عبور و نقش انتخاب‌شده را بررسی کنید؛ اگر چند بار تلاش کرده‌اید، کمی بعد دوباره امتحان کنید.</p><?php endif; ?>
<label class="dgl-label" for="dgl-user">نام کاربری / شماره موبایل</label><div class="dgl-input"><?php echo dgs_portal_icon('users'); ?><input id="dgl-user" name="log" autocomplete="username" maxlength="100" placeholder="نام کاربری یا شماره موبایل خود را وارد کنید" required></div>
<label class="dgl-label" for="dgl-pass">رمز عبور</label><div class="dgl-input"><?php echo dgs_portal_icon('lock'); ?><input id="dgl-pass" type="password" name="pwd" autocomplete="current-password" maxlength="4096" placeholder="رمز عبور خود را وارد کنید" required><button type="button" data-dgl-password aria-controls="dgl-pass" aria-label="نمایش رمز عبور" aria-pressed="false"><?php echo dgs_portal_icon('eye'); ?></button></div>
<div class="dgl-options"><label><input type="checkbox" name="rememberme" value="forever"> مرا به خاطر بسپار</label><a href="<?php echo esc_url(wp_lostpassword_url(dgs_page_url('portal'))); ?>">فراموشی رمز عبور؟</a></div>
<button class="dg-btn dgl-submit" type="submit"><?php echo dgs_portal_icon('login'); ?> ورود به سامانه</button><a class="dgl-back" href="<?php echo esc_url(home_url('/')); ?>"><?php echo dgs_portal_icon('home'); ?> بازگشت به وب‌سایت مدرسه دانشگران <?php echo dgs_portal_icon('arrow'); ?></a>
</form></div><div class="dgl-illustration" style="background-image:url('<?php echo esc_attr(dgs_login_art_uri()); ?>')" role="img" aria-label="تصویر طرح مدرسه دانشگران و کتاب‌های پایه دهم، یازدهم و دوازدهم"></div></section>
<div class="dgl-features"><?php foreach(array(array('timetable','برنامه‌های آموزشی','دسترسی به برنامه درسی و آزمون‌ها'),array('grades','پیگیری پیشرفت تحصیلی','مشاهده نمرات و گزارش‌های آموزشی'),array('users','ارتباط با مدرسه','ارتباط آسان با معلمان، مشاور و مدیریت'),array('clipboard','خدمات مدرسه','درخواست‌ها، اطلاعیه‌ها و امور آموزشی')) as $item)echo '<div>'.dgs_portal_icon($item[0]).'<strong>'.esc_html($item[1]).'</strong><p>'.esc_html($item[2]).'</p></div>'; ?></div><p class="dgl-signature"><b>مدرسه دانشگران</b><br>دبیرستان دوره دوم (پایه‌های دهم، یازدهم و دوازدهم)</p></div>
<?php return ob_get_clean(); }
function dgs_portal_login_submit(){
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST')wp_die('روش درخواست معتبر نیست.','',array('response'=>405));
 $role=isset($_POST['login_role'])&&is_string($_POST['login_role'])?sanitize_key(wp_unslash($_POST['login_role'])):'student';
 $fail=function()use($role){wp_safe_redirect(add_query_arg(array('login_error'=>'1','login_role'=>isset(dgs_login_roles()[$role])?$role:'student'),dgs_page_url('portal')),303);exit;};
 if(!isset($_POST['dgs_login_nonce'])||!is_string($_POST['dgs_login_nonce'])||!wp_verify_nonce(wp_unslash($_POST['dgs_login_nonce']),'dgs_portal_login')||!isset(dgs_login_roles()[$role]))$fail();
 if(!isset($_POST['log'],$_POST['pwd'])||!is_string($_POST['log'])||!is_string($_POST['pwd']))$fail();
 $login=dgs_edu_digits(trim(wp_unslash($_POST['log'])));$password=wp_unslash($_POST['pwd']);if($login===''||strlen($login)>400||$password===''||strlen($password)>4096)$fail();
 $ip=(string)($_SERVER['REMOTE_ADDR']??'');$ipkey='dgs_login_ip_'.hash_hmac('sha256',$ip,wp_salt());$userkey='dgs_login_user_'.hash_hmac('sha256',strtolower(dgs_edu_digits($login)),wp_salt());
 if((int)get_transient($ipkey)>=15||(int)get_transient($userkey)>=10)$fail();
 $phone=dgs_normalize_phone($login);if($phone){$users=get_users(array('meta_key'=>'_dgs_contact_phone','meta_value'=>$phone,'number'=>2,'fields'=>'all'));if(count($users)===1)$login=$users[0]->user_login;elseif(count($users)>1){set_transient($ipkey,(int)get_transient($ipkey)+1,15*MINUTE_IN_SECONDS);$fail();}}
 else $login=dgs_edu_digits($login);
 $gate=function($user)use($role){return $user instanceof WP_User&&!dgs_login_role_matches($user,$role)?new WP_Error('dgs_role','ورود انجام نشد.'):$user;};add_filter('authenticate',$gate,99);
 $user=wp_signon(array('user_login'=>$login,'user_password'=>$password,'remember'=>isset($_POST['rememberme'])&&$_POST['rememberme']==='forever'),is_ssl());remove_filter('authenticate',$gate,99);
 if(is_wp_error($user)){set_transient($ipkey,(int)get_transient($ipkey)+1,15*MINUTE_IN_SECONDS);set_transient($userkey,(int)get_transient($userkey)+1,15*MINUTE_IN_SECONDS);$fail();}
 delete_transient($userkey);wp_safe_redirect(dgs_page_url('portal'),303);exit;
}
add_action('admin_post_nopriv_dgs_portal_login','dgs_portal_login_submit');
add_action('admin_post_dgs_portal_login',function(){wp_safe_redirect(dgs_page_url('portal'),303);exit;});
add_filter('body_class',function($classes){$p=get_option('dgs_pages',array());if(!is_user_logged_in()&&!empty($p['portal'])&&is_page((int)$p['portal']))$classes[]='dgl-login-page';return $classes;});
add_action('wp_enqueue_scripts',function(){ $p=get_option('dgs_pages',array());if(is_user_logged_in()||empty($p['portal'])||!is_page((int)$p['portal']))return;wp_register_style('dgs-login',false,array(),DGS_VERSION);wp_enqueue_style('dgs-login');wp_add_inline_style('dgs-login',file_get_contents(DGS_PATH.'assets/login-page.css'));wp_register_script('dgs-login',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-login');wp_add_inline_script('dgs-login',file_get_contents(DGS_PATH.'assets/login-page.js'));});
// Bound the RTL document itself, including transformed/off-screen menu content.
add_action('wp_head',function(){if(get_template()!=='daneshgaran')return;echo '<style id="dgs-document-width">html{overflow-x:hidden!important;max-width:100%;}body.dg-site{overflow-x:hidden!important;width:auto!important;max-width:100%!important;min-width:0!important}body.dg-site .dg-shell{box-sizing:border-box!important;min-width:0!important;max-width:100%!important}body.dg-site .dg-honeypot{display:none!important}</style>';},101);
