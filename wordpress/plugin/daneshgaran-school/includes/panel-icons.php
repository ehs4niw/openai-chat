<?php
if(!defined('ABSPATH'))exit;
function dgs_distinct_icon($name){
 $paths=[
 'overview'=>'M3 11 12 3l9 8M5 10v11h5v-7h4v7h5V10',
 'education'=>'M3 4h18v13H3zM7 21h10M12 17v4M6 8h5M6 12h9',
 'timetable'=>'M4 5h16v16H4zM8 2v6M16 2v6M4 10h16M8 14h2M14 14h2M8 18h2',
 'attendance'=>'M5 3h14v18H5zM9 3v3h6V3M8 12l2 2 5-5M8 18h7',
 'teacher_attendance'=>'M3 5h18v16H3zM3 9h18M7 3v4M17 3v4M7 16l2 2 4-5M16 13v5',
 'quizzes'=>'M5 2h10l4 4v16H5zM15 2v5h4M9 11a2 2 0 1 1 3 2c-1 0-1 1-1 2M11 18h.01',
 'homework'=>'M3 3h13v18H3zM7 7h5M7 11h5M17 12l4 4-4 4M13 16h8',
 'grades'=>'M4 3h13v18H4zM8 7h5M8 11h4M15 13l3-3 3 3-6 6-3 1 1-3z',
 'oversight'=>'M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6zM7 12l3 3 6-6',
 'reports'=>'M4 2h12l4 4v16H4zM8 18v-4M12 18V9M16 18v-7M16 2v5h4',
 'study'=>'M3 4c4-1 7 0 9 2 2-2 5-3 9-2v16c-4-1-7 0-9 2-2-2-5-3-9-2zM12 6v16',
 'news'=>'M3 4h15v17H3zM18 9h3v10a2 2 0 0 1-2 2M7 8h7M7 12h7M7 16h4',
 'stars'=>'m12 2 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z',
 'gallery'=>'M3 3h18v18H3zM3 16l6-6 4 4 3-3 5 5M16 7h.01',
 'videos'=>'M3 5h13v14H3zM16 10l5-4v12l-5-4M8 9l4 3-4 3z',
 'finance'=>'M3 5h18v14H3zM3 9h18M6 15h4M17 21h4',
 'requests'=>'M4 3h16v18H4zM8 7h8M8 11h8M8 15h4M16 14v6M13 17h6',
 'account'=>'M12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8M4 21v-3c0-7 16-7 16 0v3',
 'sms'=>'M3 4h18v13H8l-5 4zM7 8h10M7 12h7',
 'users'=>'M4 21v-3c0-5 10-5 10 0v3M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 14h5v7h-5zM18 14v-2a1.5 1.5 0 0 1 3 0v2',
 'create'=>'M9 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8M2 21v-3c0-7 14-7 14 0v3M20 9v8M16 13h8',
 'school'=>'M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1zM9 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0',
 'gateway'=>'M3 8v8h4V8zM10 4v16h4V4zM17 7v10h4V7z',
 'group_education'=>'M2 8l10-5 10 5-10 5zM6 11v6c4 3 8 3 12 0v-6M22 9v9',
 'group_marks'=>'M4 21V3M4 21h18M8 17v-5M13 17V7M18 17V4',
 'group_counsel'=>'M4 4h16v12h-5l-3 5-3-5H4zM8 9h8M8 12h4',
 'group_affairs'=>'M9 2h6l1 4h5v16H3V6h5zM3 11h18M10 9h4v5h-4z',
 'group_settings'=>'M3 4h18M3 12h18M3 20h18M7 2v4M16 10v4M9 18v4',
 'site'=>'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20M2 12h20M12 2c6 5 6 15 0 20-6-5-6-15 0-20',
 'logout'=>'M9 3H3v18h6M12 7l6 5-6 5M7 12h11',
 'students'=>'M5 3h14v18H5zM12 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6M8 18c0-4 8-4 8 0',
 ];if(!isset($paths[$name]))return '';return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="'.esc_attr($paths[$name]).'"/></svg>';
}
function dgs_extension_title($title,$icon,$subtitle){return '<header class="dsp-heading"><h2>'.dgs_panel_menu_icon($icon).' '.esc_html($title).'</h2><p>'.esc_html($subtitle).'</p></header>';}
function dgs_panel_menu_icon($name){return dgs_distinct_icon($name)?:dgs_portal_icon($name);}
add_action('wp_enqueue_scripts',function(){if(!dgs_is_role_portal()&&!dgs_showcase_active()&&!dgs_video_active())return;if(dgs_video_active()){dgs_elementor_register_assets();wp_enqueue_style('dgs-showcase');}wp_register_style('dgs-panel-extension',false,array(),DGS_VERSION);wp_enqueue_style('dgs-panel-extension');wp_add_inline_style('dgs-panel-extension',file_get_contents(DGS_PATH.'assets/panel-extension.css'));wp_register_script('dgs-panel-extension',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-panel-extension');wp_add_inline_script('dgs-panel-extension',file_get_contents(DGS_PATH.'assets/panel-extension.js'));},130);
// One cache refresh after installing the new features; saved page layouts are kept.
add_action('admin_init',function(){if(!current_user_can('dgs_manage_education')||get_option('dgs_extension_0260')==='1')return;foreach(array('dgs_dated_marks','dgs_teacher_att_schema','dgs_quiz_marks_schema') as $key)if(get_option($key)!=='1')return;dgs_video_purge();$pages=get_option('dgs_pages',array());if(!empty($pages['portal']))do_action('litespeed_purge_post',(int)$pages['portal']);if(class_exists('\\Elementor\\Plugin')&&isset(\Elementor\Plugin::$instance->files_manager))\Elementor\Plugin::$instance->files_manager->clear_cache();update_option('dgs_extension_0260','1',false);},70);
