<?php
if(!defined('ABSPATH'))exit;
function dgs_brand_text($text){return is_string($text)?str_replace('مدرسه دانشگران','موسسه آموزشی دانشگران',$text):$text;}
// Render stored Elementor text without rewriting saved pages, URLs, accounts or media.
function dgs_brand_html($html){if(!is_string($html))return $html;$parts=wp_html_split($html);foreach($parts as &$part)if(substr($part,0,1)!=='<')$part=dgs_brand_text($part);unset($part);return implode('',$parts);}
add_filter('the_content','dgs_brand_html',110);
add_filter('elementor/widget/render_content','dgs_brand_html',110);
add_filter('the_title','dgs_brand_text',110);
add_filter('option_blogname','dgs_brand_text');
function dgs_home_star_card($p,$label){
 $lines=array_slice(array_filter(array_map('trim',preg_split('/\r?\n/',(string)get_post_meta($p->ID,'_dgs_star_highlights',true)))),0,6);$average=get_post_meta($p->ID,'_dgs_star_average',true);$achievement=get_post_meta($p->ID,'_dgs_achievement',true);
 $out='<div class="dsh-star-content"><div class="dsh-star-highlights">';if($lines){$out.='<ul>';foreach($lines as $line)$out.='<li>'.dgs_portal_icon('check').esc_html($line).'</li>';$out.='</ul>';}elseif($achievement)$out.='<p>'.esc_html($achievement).'</p>';$out.='</div><div class="dsh-star-photo">'.dgs_avatar($p->ID,'medium').'</div><div class="dsh-star-details"><h3>'.esc_html($p->post_title).'</h3><p>'.esc_html(get_post_meta($p->ID,'_dgs_star_class',true)).'</p>';if($average!=='')$out.='<strong>میانگین '.$label.': '.esc_html(dgs_fa_digits($average)).'</strong>';if($achievement&&$lines)$out.='<p>'.esc_html($achievement).'</p>';
 $out.='</div><div class="dsh-award" aria-label="دانش‌آموز برگزیده"><svg viewBox="0 0 100 110" aria-hidden="true"><path d="M33 69L23 106 42 96 50 108 57 72M53 71L64 107 73 94 88 101 70 65" fill="#e2a217"/><path d="M25 73Q3 49 19 22M21 64L9 59M18 52L6 44M17 39L8 29M21 28L16 16M77 73Q99 49 83 22M81 64L93 59M84 52L96 44M85 39L94 29M81 28L86 16" fill="none" stroke="#e7af26" stroke-width="5"/><circle cx="51" cy="47" r="30" fill="#f7c54c" stroke="#d89814" stroke-width="3"/><circle cx="51" cy="47" r="24" fill="none" stroke="#fff0ae" stroke-width="2"/><text x="51" y="59" text-anchor="middle" fill="#fff8dd" font-size="38" font-family="Tahoma">۱</text></svg></div></div>';return $out;
}
// Classify existing editable sections at render time. No page regeneration needed.
add_action('elementor/frontend/section/before_render',function($section){
 if(!is_front_page())return;$data=$section->get_data();$widgets=dgs_gallery_section_widgets($data);$kind='';foreach($widgets as $w){$type=$w['widgetType']??'';$s=$w['settings']??array();if($type==='dgs_live'){$k=$s['kind']??'';if(in_array($k,array('teachers','staff','featured','news','gallery'),true))$kind=$k;}if($type==='heading'&&($s['header_size']??'')==='h1')$kind='hero';}
 if(!$kind){$picture=false;$heading=false;foreach($widgets as $w){if(($w['widgetType']??'')==='dgs_picture')$picture=true;if(($w['widgetType']??'')==='heading'&&strpos($w['settings']['title']??'','معرفی')!==false)$heading=true;}if($picture&&$heading)$kind='intro';}
 if($kind)$section->add_render_attribute('_wrapper','class','dhr-'.$kind);
});
add_filter('body_class',function($classes){if(is_front_page())$classes[]='dgs-home-refined';return $classes;});
add_action('wp_enqueue_scripts',function(){if(!is_front_page())return;wp_register_style('dgs-home-refinement',false,array('dgs-showcase','dgs-elementor-design','dgs-gallery'),DGS_VERSION);wp_enqueue_style('dgs-home-refinement');wp_add_inline_style('dgs-home-refinement',file_get_contents(DGS_PATH.'assets/home-refinement.css'));},100);
// Theme headers/footer use their own option helper; update the visible brand there too.
add_action('template_redirect',function(){if(get_template()==='daneshgaran'&&!is_feed()&&!is_robots()&&($_SERVER['REQUEST_METHOD']??'GET')==='GET')ob_start('dgs_brand_html');},99);
function dgs_home_teachers_link(){return '<a class="dg-btn dhr-teachers-more" href="'.esc_url(dgs_page_url('teachers')).'">'.dgs_portal_icon('users').' مشاهده اساتید</a>';}
