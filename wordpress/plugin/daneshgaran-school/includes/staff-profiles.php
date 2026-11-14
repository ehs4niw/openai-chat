<?php
if(!defined('ABSPATH'))exit;
function dgs_staff_validate($d){
 foreach(array('staff_position','public_title','public_bio','portrait_id','public_profile') as $key)if(!is_scalar($d[$key]??''))return dgs_edu_error('profile','اطلاعات معرفی معتبر نیست.');
 $position=$d['staff_position']??'deputy';$title=sanitize_text_field($d['public_title']??'');$bio=sanitize_textarea_field($d['public_bio']??'');$photo=absint($d['portrait_id']??0);
 if(!in_array($position,array('director','deputy'),true)||dgs_edu_length($title)>100||dgs_edu_length($bio)>2000||($photo&&!wp_attachment_is_image($photo)))return dgs_edu_error('profile','سمت، تصویر یا طول توضیحات معرفی معتبر نیست.');
 return array('position'=>$position,'title'=>$title,'bio'=>$bio,'photo'=>$photo,'published'=>($d['public_profile']??'')==='1');
}
function dgs_staff_fields($id=0){
 $postId=$id?(int)get_user_meta($id,'_dgs_staff_post',true):0;$post=$postId?get_post($postId):null;$position=$id?get_user_meta($id,'_dgs_staff_position',true):'deputy';$position=$position?:'deputy';
 $h='<fieldset class="um-staff-fields"><legend>سمت و معرفی عمومی (اختیاری)</legend>';
 if(dgs_um_root())$h.=dgs_edu_select('staff_position','سمت',array('director'=>'مدیر مدرسه','deputy'=>'معاون مدرسه'),$position);
 else $h.='<input type="hidden" name="staff_position" value="'.esc_attr($position).'">';
 return $h.'<label>عنوان تکمیلی سمت<input name="public_title" maxlength="100" value="'.esc_attr($id?get_user_meta($id,'_dgs_staff_title',true):'').'" placeholder="مثلاً معاون آموزشی"></label><label>توضیح کوتاه<textarea name="public_bio" maxlength="2000" rows="4">'.esc_textarea($post?$post->post_content:'').'</textarea></label><label>تصویر از رسانه (شناسه، اختیاری)<input name="portrait_id" type="number" min="0" value="'.esc_attr($postId?(int)get_post_thumbnail_id($postId):0).'"></label><label>یا بارگذاری عکس (تا۳ مگابایت)<input name="staff_photo" type="file" accept="image/jpeg,image/png,image/webp"></label><label><input name="public_profile" type="checkbox" value="1" '.checked($post&&$post->post_status==='publish',true,false).'> معرفی این شخص در صفحه اصلی و معرفی مدرسه نمایش داده شود</label></fieldset>';
}
function dgs_staff_save($id,$profile){
 $user=get_userdata($id);if(!current_user_can('dgs_manage_education')||!$user||!in_array('dgs_deputy',(array)$user->roles,true)||(!dgs_um_root()&&get_current_user_id()!==$id))return dgs_edu_error('access','اجازه ویرایش این معرفی را ندارید.');
 if(!dgs_um_root())$profile['position']=get_user_meta($id,'_dgs_staff_position',true)?:'deputy';
 $postId=(int)get_user_meta($id,'_dgs_staff_post',true);if($postId&&(get_post_type($postId)!=='dgs_teacher'||(int)get_post_meta($postId,'_dgs_staff_user',true)!==$id))return dgs_edu_error('link','ارتباط معرفی نیازمند بررسی مدیر کل است.');
 if($profile['published']&&$profile['position']==='director'){
  $others=get_posts(array('post_type'=>'dgs_teacher','post_status'=>'publish','numberposts'=>1,'post__not_in'=>$postId?array($postId):array(),'meta_key'=>'_dgs_staff_position','meta_value'=>'director'));
  if($others)return dgs_edu_error('director','ابتدا معرفی مدیر قبلی را از انتشار خارج کنید؛ فقط یک مدیر فعال نمایش داده می‌شود.');
 }
 $row=array('post_type'=>'dgs_teacher','post_title'=>$user->display_name,'post_content'=>$profile['bio'],'post_excerpt'=>$profile['bio'],'post_status'=>$profile['published']?'publish':'draft');if($postId)$row['ID']=$postId;
 $postId=wp_insert_post(wp_slash($row),true);if(is_wp_error($postId)||!$postId)return dgs_edu_error('write','معرفی ذخیره نشد؛ حساب و سوابق قبلی حفظ هستند.');
 $label=$profile['position']==='director'?'مدیر مدرسه':'معاون مدرسه';update_post_meta($postId,'_dgs_group','staff');update_post_meta($postId,'_dgs_staff_user',$id);update_post_meta($postId,'_dgs_staff_position',$profile['position']);update_post_meta($postId,'_dgs_specialty',wp_slash($label.($profile['title']?' · '.$profile['title']:'')));
 if($profile['photo'])set_post_thumbnail($postId,$profile['photo']);else delete_post_thumbnail($postId);
 update_user_meta($id,'_dgs_staff_post',$postId);update_user_meta($id,'_dgs_staff_position',$profile['position']);update_user_meta($id,'_dgs_staff_title',$profile['title']);if(function_exists('dgs_catalog_invalidate'))dgs_catalog_invalidate($postId);dgs_edu_audit('staff_profile',$id);return true;
}
function dgs_staff_editor($id,$year){
 if(!current_user_can('dgs_manage_education')||(!dgs_um_root()&&$id!==get_current_user_id())||!in_array('dgs_deputy',(array)(get_userdata($id)->roles??array()),true))return '<p class="um-card">اجازه ویرایش این معرفی را ندارید.</p>';
 return '<section class="um-card"><h3>معرفی عمومی '.esc_html(get_userdata($id)->display_name).'</h3><form class="um-form" method="post" enctype="multipart/form-data" action="'.esc_url(admin_url('admin-post.php')).'">'.wp_nonce_field('dgs_staff_profile','staff_nonce',true,false).'<input type="hidden" name="action" value="dgs_staff_profile"><input type="hidden" name="user_id" value="'.$id.'">'.dgs_staff_fields($id).'<button class="dg-btn">ذخیره معرفی عمومی</button></form></section>';
}
function dgs_staff_upload(){
 $file=$_FILES['staff_photo']??null;if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return 0;
 $info=is_string($file['tmp_name']??null)?@getimagesize($file['tmp_name']):false;
 if(($file['error']??1)!==0||!is_uploaded_file($file['tmp_name']??'')||($file['size']??0)>3*1024*1024||!$info||$info[0]*$info[1]>20000000||!in_array($info['mime'],array('image/jpeg','image/png','image/webp'),true))return dgs_edu_error('photo','تصویر سالم JPG/PNG/WebP تا۳ مگابایت انتخاب کنید.');
 require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/image.php';return media_handle_upload('staff_photo',0,array(),array('test_form'=>false,'mimes'=>array('jpg|jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp')));
}
add_action('admin_post_dgs_staff_profile',function(){
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!current_user_can('dgs_manage_education'))wp_die('دسترسی ندارید.','',array('response'=>403));check_admin_referer('dgs_staff_profile','staff_nonce');$d=wp_unslash($_POST);$id=is_scalar($d['user_id']??null)?absint($d['user_id']):0;if(!dgs_um_root()&&$id!==get_current_user_id())wp_die('دسترسی ندارید.','',array('response'=>403));$p=dgs_staff_validate($d);$upload=0;if(!is_wp_error($p)){$upload=dgs_staff_upload();if(is_wp_error($upload))$p=$upload;elseif($upload)$p['photo']=$upload;}$r=is_wp_error($p)?$p:dgs_staff_save($id,$p);if(is_wp_error($r)&&is_int($upload)&&$upload)wp_delete_attachment($upload,true);$token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():'معرفی عمومی ذخیره شد.'),300);wp_safe_redirect(dgs_um_users_url(dgs_edu_current_year(),array('dg_notice'=>$token,'school_role'=>'dgs_deputy')),303);exit;
});
// Rename stored page/menu headings without rewriting Elementor structures or URLs.
add_filter('the_title',function($title){if(!is_string($title))return $title;return str_replace(array('آموزشگاه و کلاس‌های تقویتی','کلاس‌های تقویتی','کلاس تقویتی','آموزشگاه'),'کلاس کنکور - تقویتی',$title);},20);
add_action('wp_enqueue_scripts',function(){if(dgs_is_role_portal())wp_enqueue_style('dgs-panel-sizing',DGS_URL.'assets/panel-sizing.css',array(),DGS_VERSION);},90);
add_action('init',function(){
 $roles=wp_roles();$changed=false;foreach(array('dgs_student'=>'دانش‌آموز و اولیا','dgs_deputy'=>'مدیر و معاون') as $key=>$label)if(isset($roles->roles[$key])&&$roles->roles[$key]['name']!==$label){$roles->roles[$key]['name']=$label;$roles->role_names[$key]=$label;$changed=true;}if($changed)update_option($roles->role_key,$roles->roles);
},21);
function dgs_class_labels_html($html){
 if(!is_string($html))return $html;$pieces=preg_split('/(<[^>]+>)/u',$html,-1,PREG_SPLIT_DELIM_CAPTURE);if(!$pieces)return $html;foreach($pieces as $i=>$piece)if($i%2===0)$pieces[$i]=str_replace(array('آموزشگاه و کلاس‌های تقویتی','کلاس‌های تقویتی','کلاس تقویتی','آموزشگاه'),'کلاس کنکور - تقویتی',$piece);return implode('',$pieces);
}
add_filter('the_content','dgs_class_labels_html',99);
add_filter('elementor/widget/render_content','dgs_class_labels_html',99);
