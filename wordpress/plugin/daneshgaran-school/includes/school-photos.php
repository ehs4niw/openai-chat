<?php
if(!defined('ABSPATH'))exit;
function dgs_approved_photo_url($kind){return DGS_URL.'assets/school-photos/'.($kind==='sign'?'sign':'entrance').'.webp';}
function dgs_approved_photo_markup($kind,$class,$alt,$priority=false){return '<div class="dsh-picture dgs-real-school-photo '.esc_attr($class).'"><img src="'.esc_url(dgs_approved_photo_url($kind)).'" width="941" height="1672" alt="'.esc_attr($alt).'" loading="'.($priority?'eager':'lazy').'" decoding="async"'.($priority?' fetchpriority="high"':'').'></div>';}
function dgs_school_photos_purge(){
 $pages=get_option('dgs_pages',array());foreach(array('home','about','gallery') as $key)if(!empty($pages[$key])){$id=(int)$pages[$key];clean_post_cache($id);delete_post_meta($id,'_elementor_element_cache');delete_post_meta($id,'_elementor_css');do_action('litespeed_purge_post',$id);if(function_exists('rocket_clean_post'))rocket_clean_post($id);}
 do_action('litespeed_purge_url',home_url('/'));
}
add_action('admin_init',function(){if(!current_user_can('manage_options')||get_option('dgs_school_photos_041')==='1')return;dgs_school_photos_purge();if(class_exists('\\Elementor\\Plugin')&&isset(\Elementor\Plugin::$instance->files_manager))\Elementor\Plugin::$instance->files_manager->clear_cache();update_option('dgs_school_photos_041','1',false);},60);
// Convert legacy static gallery rows at render time without changing saved layouts.
function dgs_school_gallery_data($data,$post_id){
 $pages=get_option('dgs_pages',array());if(!in_array((int)$post_id,array((int)($pages['home']??0),(int)($pages['about']??0)),true)||!is_array($data))return $data;
 $result=dgs_gallery_transform_data($data);$data=$result['data'];
 $walk=function(&$nodes)use(&$walk){foreach($nodes as &$node){if(($node['widgetType']??'')==='dgs_live'&&($node['settings']['kind']??'')==='gallery')$node['settings']['_element_cache']='no';if(!empty($node['elements']))$walk($node['elements']);}unset($node);};$walk($data);return $data;
}
add_filter('elementor/frontend/builder_content_data','dgs_school_gallery_data',30,2);
function dgs_ps_delete($kind,$id,$data){
 if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','دسترسی مدیریت لازم است.');
 $p=$kind==='gallery'?dgs_ps_post('gallery',$id):false;if(!$p)return dgs_edu_error('scope','تصویر معتبر گالری را انتخاب کنید.');
 $revision=$data['item_revision']??'';if(!is_string($revision)||!hash_equals(dgs_ps_revision($p),$revision))return dgs_edu_error('stale','اطلاعات تغییر کرده؛ صفحه را تازه کنید.');
 $lock='dgs_ps_'.$id;if(!add_option($lock,time(),'','no'))return dgs_edu_error('busy','تغییر دیگری در جریان است؛ دوباره تلاش کنید.');
 try{$p=dgs_ps_post('gallery',$id);if(!$p||!hash_equals(dgs_ps_revision($p),$revision))return dgs_edu_error('stale','اطلاعات تغییر کرده؛ صفحه را تازه کنید.');
  if(!wp_trash_post($id))return dgs_edu_error('delete','حذف تصویر انجام نشد.');dgs_gallery_invalidate($id);dgs_edu_audit('school_gallery_delete',$id);return $id;
 }finally{delete_option($lock);}
}
function dgs_ps_delete_form($p){return '<form class="dgs-gallery-delete" method="post" action="'.esc_url(admin_url('admin-post.php')).'" onsubmit="return confirm(\'این تصویر از گالری حذف شود؟ فایل اصلی در رسانه باقی می‌ماند.\')">'.wp_nonce_field('dgs_panel_showcase','showcase_nonce',true,false).'<input type="hidden" name="action" value="dgs_panel_showcase"><input type="hidden" name="kind" value="gallery"><input type="hidden" name="operation" value="delete"><input type="hidden" name="item_id" value="'.(int)$p->ID.'"><input type="hidden" name="item_revision" value="'.esc_attr(dgs_ps_revision($p)).'"><button class="dge-small-btn" type="submit">حذف تصویر</button></form>';}
add_action('wp_enqueue_scripts',function(){if(!dgs_showcase_active()&&!dgs_gallery_active()&&!(dgs_is_role_portal()&&dgs_portal_tab()==='gallery'))return;wp_register_style('dgs-school-photos',false,array(),DGS_VERSION);wp_enqueue_style('dgs-school-photos');wp_add_inline_style('dgs-school-photos',file_get_contents(DGS_PATH.'assets/school-photos.css'));},120);
