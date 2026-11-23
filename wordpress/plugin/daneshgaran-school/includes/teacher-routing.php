<?php
if(!defined('ABSPATH'))exit;
// Plugin replacement does not run the activation hook. Refresh only when the
// public route schema or permalink structure changes, after all types register.
add_action('init',function(){
 $signature='teacher-routes-1:'.get_option('permalink_structure','');
 if(get_option('dgs_teacher_route_signature')===$signature)return;
 flush_rewrite_rules(false);
 update_option('dgs_teacher_route_signature',$signature,false);
},99);
function dgs_teacher_route_fallback($vars,$request){
 if(!isset($vars['error'])||$vars['error']!=='404'||!is_string($request)||!preg_match('~^(?:index\.php/)?teacher/([^/]+)/?$~D',$request,$match))return $vars;
 $slug=rawurldecode($match[1]);if(strpos($slug,'/')!==false||strpos($slug,'\\')!==false)return $vars;
 $post=get_page_by_path($slug,OBJECT,'dgs_teacher');
 if(!$post||$post->post_status!=='publish')return $vars;
 return array('post_type'=>'dgs_teacher','name'=>$post->post_name);
}
// Supports existing Persian /index.php/teacher/ links even with stale host rules.
add_filter('request',function($vars){global $wp;return dgs_teacher_route_fallback($vars,isset($wp->request)?$wp->request:'');},20);
