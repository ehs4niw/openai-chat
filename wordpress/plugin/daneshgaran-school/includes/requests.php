<?php
if(!defined('ABSPATH'))exit;
function dgs_request_types(){return array('admission'=>'پیش‌ثبت‌نام مدرسه','class'=>'پیش‌ثبت‌نام کلاس تقویتی','counseling'=>'درخواست مشاوره حضوری','contact'=>'پیام ارتباط با مدرسه');}
function dgs_normalize_phone($value){$value=strtr((string)$value,array('۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'));$value=preg_replace('/\D/','',$value);if(strpos($value,'0098')===0)$value='0'.substr($value,4);elseif(strlen($value)===12&&strpos($value,'98')===0)$value='0'.substr($value,2);elseif(strlen($value)===10&&strpos($value,'9')===0)$value='0'.$value;return preg_match('/^09[0-9]{9}$/D',$value)?$value:'';}
function dgs_request_form($type='contact',$class_id=0){
    if(!isset(dgs_request_types()[$type]))return '';
    $ids=get_option('dgs_pages',array());$page_key=$type==='admission'?'admission':($type==='class'?'academy':'contact');$return=$class_id?get_permalink($class_id):dgs_page_url($page_key);
    $uid=wp_unique_id('dgs-form-');ob_start(); ?>
    <section class="dg-form-section"><h2><?php echo esc_html(dgs_request_types()[$type]); ?></h2>
    <?php if(isset($_GET['dgs_sent'])&&$_GET['dgs_sent']==='1'): ?><div class="dg-alert dg-success" role="status">درخواست شما ثبت شد. مسئول مدرسه برای پیگیری با شما تماس می‌گیرد.</div><?php endif; ?>
    <p class="dg-muted"><?php echo $type==='class'?'این فرم صرفاً پیش‌ثبت‌نام است؛ ثبت نهایی کلاس پس از بررسی مدرسه انجام می‌شود.':'اطلاعات تماس صحیح وارد کنید تا امکان پیگیری درخواست فراهم باشد.'; ?></p>
    <form class="dg-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input type="hidden" name="action" value="dgs_submit_request"><input type="hidden" name="dgs_type" value="<?php echo esc_attr($type); ?>"><input type="hidden" name="class_id" value="<?php echo (int)$class_id; ?>">
    <?php wp_nonce_field('dgs_request_'.$type,'dgs_nonce'); ?>
    <div class="dg-honeypot" aria-hidden="true"><label>وب‌سایت<input name="website_url" tabindex="-1" autocomplete="off"></label></div>
    <div class="dg-form-grid"><label for="<?php echo esc_attr($uid); ?>name">نام و نام خانوادگی <span>*</span><input id="<?php echo esc_attr($uid); ?>name" name="name" maxlength="100" autocomplete="name" required></label>
    <label for="<?php echo esc_attr($uid); ?>phone">شماره موبایل برای پیگیری <span>*</span><input id="<?php echo esc_attr($uid); ?>phone" name="phone" inputmode="tel" autocomplete="tel" maxlength="18" dir="ltr" required placeholder="09xxxxxxxxx"></label>
    <?php if($type!=='contact'): ?><label>پایه تحصیلی <span>*</span><select name="grade" required><option value="">انتخاب پایه</option><option value="10">دهم</option><option value="11">یازدهم</option><option value="12">دوازدهم</option></select></label><label>رشته تحصیلی<select name="field"><option value="">انتخاب رشته</option><option value="science">علوم تجربی</option><option value="math">ریاضی و فیزیک</option><option value="humanities">علوم انسانی</option></select></label><?php endif; ?>
    <?php if($type==='admission'): ?><label>نام و نام خانوادگی ولی<input name="parent_name" maxlength="100"></label><?php endif; ?>
    <label>ایمیل (اختیاری)<input name="email" type="email" autocomplete="email" dir="ltr" maxlength="190"></label></div>
    <label for="<?php echo esc_attr($uid); ?>message"><?php echo $type==='counseling'?'موضوع مشاوره و زمان‌های مناسب تماس':'توضیحات (اختیاری)'; ?><textarea id="<?php echo esc_attr($uid); ?>message" name="message" rows="4" maxlength="2000"></textarea></label>
    <label class="dg-consent"><input type="checkbox" name="consent" value="1" required>با ثبت و بررسی این اطلاعات توسط مسئولان مجاز مدرسه برای پیگیری درخواست موافقم.</label>
    <button class="dg-btn" type="submit">ثبت درخواست <?php echo dgs_icon('arrow'); ?></button>
    </form></section>
    <?php return ob_get_clean();
}
function dgs_validate_request($input){
    foreach(array('dgs_type','name','phone','grade','field','parent_name','email','message','website_url','consent','class_id') as $key)if(isset($input[$key])&&!is_scalar($input[$key]))return new WP_Error('invalid','اطلاعات فرم معتبر نیست.');
    $type=sanitize_key((string)($input['dgs_type']??''));if(!isset(dgs_request_types()[$type]))return new WP_Error('type','نوع درخواست معتبر نیست.');
    if(!empty($input['website_url']))return new WP_Error('spam','امکان ثبت درخواست وجود ندارد.');
    $name=sanitize_text_field((string)($input['name']??''));$phone=dgs_normalize_phone($input['phone']??'');
    if($name===''||strlen($name)>400||!$phone||($input['consent']??'')!=='1')return new WP_Error('required','نام، شماره موبایل معتبر و تأیید رضایت را وارد کنید.');
    $grade=(string)($input['grade']??'');if($type!=='contact'&&!in_array($grade,array('10','11','12'),true))return new WP_Error('grade','پایه تحصیلی را انتخاب کنید.');
    $field=sanitize_key((string)($input['field']??''));if(!in_array($field,array('','science','math','humanities'),true))return new WP_Error('field','رشته تحصیلی معتبر نیست.');
    $message=sanitize_textarea_field((string)($input['message']??''));if(strlen($message)>8000)return new WP_Error('message','متن توضیحات بیش از حد طولانی است.');
    $email=sanitize_email((string)($input['email']??''));if(!empty($input['email'])&&(!$email||!is_email($email)))return new WP_Error('email','ایمیل معتبر نیست.');
    $class_id=absint($input['class_id']??0);if($type==='class'&&(!$class_id||get_post_type($class_id)!=='dgs_class'||get_post_status($class_id)!=='publish'))return new WP_Error('class','کلاس انتخاب‌شده در دسترس نیست.');
    $parent=sanitize_text_field((string)($input['parent_name']??''));if(strlen($parent)>400)return new WP_Error('parent','نام ولی بیش از حد طولانی است.');
    return array('type'=>$type,'name'=>$name,'phone'=>$phone,'grade'=>$grade,'field'=>$field,'parent_name'=>$parent,'email'=>$email,'message'=>$message,'class_id'=>$type==='class'?$class_id:0,'created'=>current_time('mysql'));
}
function dgs_submit_request(){
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST')wp_die('روش درخواست معتبر نیست.','درخواست نامعتبر',array('response'=>405));
    $type=isset($_POST['dgs_type'])&&is_string($_POST['dgs_type'])?sanitize_key(wp_unslash($_POST['dgs_type'])):'';
    if(!isset($_POST['dgs_nonce'])||!is_string($_POST['dgs_nonce'])||!wp_verify_nonce(wp_unslash($_POST['dgs_nonce']),'dgs_request_'.$type))wp_die('اعتبار فرم تمام شده است. صفحه را تازه کنید و دوباره تلاش کنید.','اعتبار فرم',array('response'=>403,'back_link'=>true));
    $data=dgs_validate_request(wp_unslash($_POST));if(is_wp_error($data))wp_die(esc_html($data->get_error_message()),'بررسی اطلاعات',array('response'=>400,'back_link'=>true));
    $ip=(string)($_SERVER['REMOTE_ADDR']??'');$key='dgs_req_'.hash_hmac('sha256',$ip,wp_salt('nonce'));$count=(int)get_transient($key);
    if($count>=5)wp_die('تعداد درخواست‌ها زیاد است؛ کمی بعد دوباره تلاش کنید.','محدودیت درخواست',array('response'=>429,'back_link'=>true));
    $fingerprint='dgs_dup_'.hash_hmac('sha256',wp_json_encode($data['type'].'|'.$data['phone'].'|'.$data['class_id'].'|'.$data['message']),wp_salt('nonce'));
    $existing=(int)get_transient($fingerprint);
    if(!$existing||get_post_status($existing)!=='private'){
        $id=wp_insert_post(wp_slash(array('post_type'=>'dgs_request','post_status'=>'private','post_title'=>dgs_request_types()[$data['type']].' — '.$data['name'],'meta_input'=>array('_dgs_data'=>$data,'_dgs_status'=>'new'))),true);
        if(is_wp_error($id)||!$id)wp_die('درخواست ذخیره نشد؛ دوباره تلاش کنید.','خطای ذخیره',array('response'=>500,'back_link'=>true));
        set_transient($key,$count+1,HOUR_IN_SECONDS);set_transient($fingerprint,$id,5*MINUTE_IN_SECONDS);
    }
    $return=$data['class_id']?get_permalink($data['class_id']):dgs_page_url($data['type']==='admission'?'admission':'contact');
    wp_safe_redirect(add_query_arg('dgs_sent','1',$return),303);exit;
}
add_action('admin_post_nopriv_dgs_submit_request','dgs_submit_request');add_action('admin_post_dgs_submit_request','dgs_submit_request');
add_action('template_redirect',function(){
    $p=get_option('dgs_pages',array());$id=get_queried_object_id();
    if((isset($p['admission'])&&$id===(int)$p['admission'])||(isset($p['contact'])&&$id===(int)$p['contact'])||(isset($p['portal'])&&$id===(int)$p['portal'])||is_singular('dgs_class')){if(!defined('DONOTCACHEPAGE'))define('DONOTCACHEPAGE',true);nocache_headers();}
},1);
