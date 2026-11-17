<?php
if(!defined('ABSPATH'))exit;
function dgs_about_refined_active(){ $p=get_option('dgs_pages',array());return !empty($p['about'])&&is_page((int)$p['about']); }
function dgs_about_icon($name){
 $paths=array('leaf'=>'M20 3C7 2 2 8 5 15c5 7 16 2 15-12M4 21L17 7','sport'=>'M3 8v8M6 5v14M6 12h12M18 5v14M21 8v8','lab'=>'M9 2h6M10 2v7L4 19q-1 3 2 3h12q3 0 2-3L14 9V2M7 15h10','monitor'=>'M2 3h20v14H2zM12 17v5M7 22h10','settings'=>'M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1zM12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8');
 return isset($paths[$name])?'<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="'.$paths[$name].'"/></svg>':dgs_showcase_icon($name);
}
function dgs_about_title_icon($title){foreach(array('پیش‌ثبت'=>'timetable','ارتباط'=>'phone','امکانات و خدمات'=>'settings','فضای سبز'=>'leaf','ورزش'=>'sport','آزمایشگاه'=>'lab','هوشمند'=>'monitor','کتابخانه'=>'study','سالن مطالعه'=>'users','درباره'=>'building','رسالت'=>'target','پایه‌های'=>'education','مدیر'=>'person','معاونت'=>'users','اعضای کادر'=>'education') as $word=>$icon)if(mb_strpos($title,$word)!==false)return $icon;return '';}
add_action('elementor/frontend/section/before_render',function($section){
 if(!dgs_about_refined_active())return;$widgets=dgs_gallery_section_widgets($section->get_data());$titles=array();$kinds=array();$hero=false;foreach($widgets as $w){if(($w['widgetType']??'')==='heading'){$titles[]=wp_strip_all_tags($w['settings']['title']??'');if(($w['settings']['header_size']??'')==='h1')$hero=true;}if(($w['widgetType']??'')==='dgs_live')$kinds[]=$w['settings']['kind']??'';}
 $text=implode(' ',$titles);$class=$hero?'abr-hero':(in_array('director',$kinds,true)?'abr-director':(mb_strpos($text,'رسالت')!==false?'abr-overview':(mb_strpos($text,'پیش‌ثبت')!==false?'abr-bottom':(mb_strpos($text,'کلاس‌های هوشمند')!==false||mb_strpos($text,'آزمایشگاه')!==false?'abr-facilities':(mb_strpos($text,'امکانات و خدمات')!==false?'abr-facilities-title':'')))));
 if($class)$section->add_render_attribute('_wrapper','class',$class);
});
add_filter('elementor/widget/render_content',function($html,$widget){
 if(!dgs_about_refined_active())return $html;$name=$widget->get_name();$s=$widget->get_settings_for_display();
 if($name==='heading'){$title=wp_strip_all_tags($s['title']??'');$icon=dgs_about_title_icon($title);if($icon)$html=preg_replace_callback('/<h[23]\b[^>]*>/u',function($m)use($icon){return $m[0].dgs_about_icon($icon);},$html,1);}
 $parent=$widget->get_parent();$widgets=$parent?dgs_gallery_section_widgets($parent->get_data()):array();$titles='';foreach($widgets as $w)if(($w['widgetType']??'')==='heading')$titles.=' '.($w['settings']['title']??'');
 if($name==='dgs_icon'&&dgs_about_title_icon($titles))return '';
 if($name==='text-editor'&&mb_strpos($titles,'پایه‌های تحصیلی')!==false)return '<p class="abr-grade-intro">متوسطه دوم (دوره دوم دبیرستان)</p><div class="dsh-grade-chips"><span>پایه دهم</span><span>پایه یازدهم</span><span>پایه دوازدهم</span></div>';
 return $html;
},120,2);
add_filter('body_class',function($classes){if(dgs_about_refined_active())$classes[]='dgs-about-refined';return $classes;});
add_action('wp_enqueue_scripts',function(){if(!dgs_about_refined_active())return;wp_register_style('dgs-about-refinement',false,array('dgs-showcase','dgs-elementor-design','dgs-gallery'),DGS_VERSION);wp_enqueue_style('dgs-about-refinement');wp_add_inline_style('dgs-about-refinement',file_get_contents(DGS_PATH.'assets/about-refinement.css'));},100);
