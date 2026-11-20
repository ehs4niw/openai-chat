<?php
if(!defined('ABSPATH'))exit;
function dgs_staff_resume_schema(){return array('birth'=>'تاریخ تولد شمسی (خصوصی)','gender'=>'جنسیت (خصوصی)','address'=>'نشانی (خصوصی)','years'=>'سال سابقه مدیریتی / آموزشی','video'=>'لینک ویدئو آپارات یا MP4/WebM','education'=>'سوابق تحصیلی (هر مدرک یک خط)','experience'=>'سوابق مدیریتی و آموزشی (هر مورد یک خط)','skills'=>'مهارت‌ها (هر مورد یک خط)');}
function dgs_staff_resume_validate($d){
 $out=array();foreach(dgs_staff_resume_schema() as $key=>$label){$value=$d['staff_'.$key]??'';if(!is_scalar($value))return dgs_edu_error('profile','اطلاعات پرونده معتبر نیست.');$out[$key]=sanitize_textarea_field((string)$value);if(dgs_edu_length($out[$key])>5000)return dgs_edu_error('length','متن پرونده بیش از حد طولانی است.');}
 foreach(array('full_name','phone') as $key){$value=$d['staff_'.$key]??'';if(!is_scalar($value))return dgs_edu_error('profile','اطلاعات حساب معتبر نیست.');$out[$key]=sanitize_text_field((string)$value);}if(dgs_edu_length($out['full_name'])>100)return dgs_edu_error('name','نام حداکثر۱۰۰ نویسه باشد.');if($out['phone']!==''){$phone=dgs_normalize_phone($out['phone']);if(!$phone)return dgs_edu_error('phone','شماره موبایل معتبر وارد کنید.');$out['phone']=$phone;}
 $out['years']=dgs_edu_digits($out['years']);if($out['years']!==''&&(!ctype_digit($out['years'])||(int)$out['years']>70))return dgs_edu_error('years','سابقه باید صفر تا۷۰ سال باشد.');
 if($out['video']!==''){$v=dgs_teacher_video_source($out['video']);if(!$v)return dgs_edu_error('video','لینک آپارات یا فایل MP4/WebM معتبر وارد کنید.');$out['video']=$v['url'];}
 if($out['birth']!==''&&!dgs_record_birth($out['birth']))return dgs_edu_error('birth','تاریخ تولد شمسی معتبر وارد کنید.');if(!in_array($out['gender'],array('','male','female'),true))return dgs_edu_error('gender','جنسیت معتبر نیست.');return $out;
}
function dgs_staff_resume_fields($uid){
 $private=$uid?get_user_meta($uid,'_dgs_staff_private',true):array();$private=is_array($private)?$private:array();$post=$uid?(int)get_user_meta($uid,'_dgs_staff_post',true):0;$h='';
 if($uid){$u=get_userdata($uid);$h.='<p>نام: '.esc_html($u?$u->display_name:'').' · کد ملی / نام کاربری: <bdi>'.esc_html($u?$u->user_login:'').'</bdi> · تلفن همراه: <bdi>'.esc_html(get_user_meta($uid,'_dgs_contact_phone',true)).'</bdi></p>';}
 if($uid)$h.='<label>نام و نام خانوادگی<input name="staff_full_name" maxlength="100" required value="'.esc_attr($u?$u->display_name:'').'"></label><label>تلفن همراه (خصوصی)<input name="staff_phone" value="'.esc_attr(get_user_meta($uid,'_dgs_contact_phone',true)).'"></label>';
 foreach(dgs_staff_resume_schema() as $key=>$label){$value=in_array($key,array('birth','gender','address'),true)?($private[$key]??''):($post?get_post_meta($post,'_dgs_'.($key==='years'?'teaching_years':$key),true):'');if($key==='gender'){$h.=dgs_edu_select('staff_gender',$label,array(''=>'انتخاب نشده','male'=>'مرد','female'=>'زن'),$value);continue;}$h.='<label>'.esc_html($label);if(in_array($key,array('address','education','experience','skills'),true))$h.='<textarea name="staff_'.$key.'" rows="3" maxlength="5000">'.esc_textarea($value).'</textarea>';else $h.='<input name="staff_'.$key.'" value="'.esc_attr($value).'" maxlength="500" '.($key==='birth'?'placeholder="1360-02-15"':'').'>';$h.='</label>';}
 return $h;
}
function dgs_staff_resume_store($uid,$post,$d){
 if(!empty($d['full_name']))wp_update_user(array('ID'=>$uid,'display_name'=>$d['full_name'],'nickname'=>$d['full_name']));if(array_key_exists('phone',$d))update_user_meta($uid,'_dgs_contact_phone',$d['phone']);
 $private=array_intersect_key($d,array_flip(array('birth','gender','address')));update_user_meta($uid,'_dgs_staff_private',wp_slash($private));foreach(array('years'=>'teaching_years','video'=>'video','education'=>'education','experience'=>'experience','skills'=>'skills') as $key=>$meta)update_post_meta($post,'_dgs_'.$meta,wp_slash($d[$key]??''));
}
function dgs_staff_resume_public($id){
 if(get_post_type($id)!=='dgs_teacher'||get_post_status($id)!=='publish'||get_post_meta($id,'_dgs_group',true)!=='staff')return dgs_empty('رزومه منتشر نشده است.');
 $name=get_the_title($id);$position=get_post_meta($id,'_dgs_specialty',true);$years=get_post_meta($id,'_dgs_teaching_years',true);
 $h='<div class="dc-page dc-profile dc-staff-profile"><div class="dc-breadcrumb"><a href="'.esc_url(home_url('/')).'">صفحه اصلی</a> ‹ رزومه مدیریت و عوامل آموزشی</div>'.dgs_catalog_heading('رزومه مدیر و معاون','','users').'<section class="dc-profile-hero"><div class="dc-profile-person"><div class="dc-profile-photo">'.dgs_avatar($id,'medium_large').'</div><div><h2>'.esc_html($name).'</h2><span class="dc-tag">'.esc_html($position).'</span>'.($years!==''?'<p>سابقه مدیریتی و آموزشی: '.esc_html(dgs_fa_digits($years)).' سال</p>':'').'</div></div><div class="dc-profile-video">'.dgs_teacher_video_html(get_post_meta($id,'_dgs_video',true),$id).'</div></section><div class="dc-profile-grid"><section class="dc-card"><h2>'.dgs_portal_icon('users').' درباره '.esc_html($name).'</h2>'.wpautop(esc_html(get_post_field('post_content',$id))).'</section>';
 foreach(array('education'=>'سوابق تحصیلی','experience'=>'سوابق مدیریتی و آموزشی','skills'=>'مهارت‌ها') as $key=>$label)$h.='<section class="dc-card"><h2>'.dgs_portal_icon($key==='education'?'education':'file').esc_html($label).'</h2>'.dgs_catalog_lines(get_post_meta($id,'_dgs_'.$key,true)).'</section>';
 return $h.'</div></div>';
}
