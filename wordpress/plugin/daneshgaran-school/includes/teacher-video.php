<?php
if(!defined('ABSPATH'))exit;
function dgs_teacher_video_source($url){
 if(!is_string($url)||!filter_var($url,FILTER_VALIDATE_URL))return false;
 $parts=wp_parse_url($url);if(!$parts||!in_array(strtolower($parts['scheme']??''),array('http','https'),true)||isset($parts['user'])||isset($parts['pass']))return false;
 $host=strtolower($parts['host']??'');$path=$parts['path']??'';
 if(in_array($host,array('aparat.com','www.aparat.com'),true)){
  if(isset($parts['port'])||!preg_match('~^/v/([A-Za-z0-9]{3,64})/?$~D',$path,$m))return false;
  return array('type'=>'aparat','url'=>'https://www.aparat.com/v/'.$m[1],'embed'=>'https://www.aparat.com/video/video/embed/videohash/'.$m[1].'/vt/frame');
 }
 if(!preg_match('/\.(mp4|webm)$/i',$path))return false;
 return array('type'=>'file','url'=>esc_url_raw($url,array('http','https')));
}
function dgs_teacher_video_html($url,$id){
 $source=dgs_teacher_video_source($url);if(!$source)return '<div class="dc-video-empty">'.dgs_portal_icon('file').'<p>ویدئوی معرفی هنوز ثبت نشده است.</p></div>';
 if($source['type']==='aparat')return '<iframe class="dc-aparat-player" src="'.esc_url($source['embed']).'" title="'.esc_attr('ویدئوی معرفی '.get_the_title($id)).'" loading="lazy" allow="fullscreen; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin" style="display:block;width:100%;aspect-ratio:16/9;border:0;border-radius:12px"></iframe>';
 return wp_video_shortcode(array('src'=>$source['url'],'poster'=>has_post_thumbnail($id)?get_the_post_thumbnail_url($id,'large'):''));
}
