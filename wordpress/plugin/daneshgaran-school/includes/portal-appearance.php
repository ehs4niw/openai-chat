<?php
if(!defined('ABSPATH'))exit;
function dgs_portal_icon($name){$paths=array(
 'overview'=>'M3 11l9-8 9 8M5 10v11h5v-7h4v7h5V10',
 'education'=>'M2 8l10-5 10 5-10 5zM6 11v6c4 3 8 3 12 0v-6M22 9v9',
 'users'=>'M4 21v-3c0-5 10-5 10 0v3M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 4a4 4 0 0 1 0 8M17 15c4 0 5 2 5 6',
 'students'=>'M4 21v-3c0-5 10-5 10 0v3M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 4a4 4 0 0 1 0 8M17 15c4 0 5 2 5 6',
 'classes'=>'M12 3L2 8l10 5 10-5zM2 12l10 5 10-5M2 16l10 5 10-5',
 'study'=>'M3 4c4-1 7 0 9 2 2-2 5-3 9-2v16c-4-1-7 0-9 2-2-2-5-3-9-2zM12 6v16',
 'timetable'=>'M4 5h16v16H4zM8 2v6M16 2v6M4 10h16M8 14h2M14 14h2M8 18h2M14 18h2',
 'homework'=>'M14 2H5v20h14V7zM14 2v6h5M8 14l4-4 4 4M12 10v9',
 'quizzes'=>'M8 4H4v18h16V4h-4M8 2h8v5H8zM8 14l3 3 6-6',
 'grades'=>'M3 21h19M6 17v-5M12 17V7M18 17V3',
 'attendance'=>'M4 5h16v16H4zM8 2v6M16 2v6M4 10h16M8 16l3 3 6-6',
 'finance'=>'M3 5h18v15H3zM3 10h18M6 15h5',
 'counseling'=>'M4 21v-3c0-5 10-5 10 0v3M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M17 4a4 4 0 0 1 0 8M17 15c4 0 5 2 5 6',
 'requests'=>'M14 2H5v20h14V7zM14 2v6h5M8 12h8M8 16h8',
 'password'=>'M14 14a6 6 0 1 0-4-4L2 18v4h4v-3h3v-3z',
 'records'=>'M14 2H5v20h14V7zM14 2v6h5M9 12a3 3 0 1 0 6 0 3 3 0 0 0-6 0M8 20c0-5 8-5 8 0',
 'logout'=>'M10 3H4v18h6M13 7l5 5-5 5M8 12h14',
 'upload'=>'M7 17H5a4 4 0 0 1-1-8 7 7 0 0 1 13-2 5 5 0 0 1 1 10h-1M8 13l4-4 4 4M12 9v12',
 'clipboard'=>'M8 4H4v18h16V4h-4M8 2h8v5H8zM8 11h8M8 15h8M8 19h5',
 'history'=>'M3 11a9 9 0 1 1 2 7M3 5v6h6M12 7v6l4 2',
 'clock'=>'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18M12 7v6l4 2',
 'check'=>'M4 12l5 5L20 5','refresh'=>'M20 7a8 8 0 0 0-14-2L3 9M3 3v6h6M4 17a8 8 0 0 0 14 2l3-4M21 21v-6h-6',
 'file'=>'M14 2H5v20h14V7zM14 2v6h5M8 12h8M8 16h8',
 'message'=>'M3 3h18v14H9l-6 5z',
 'send'=>'M22 2L2 10l8 4 4 8zM10 14L22 2');$path=$paths[$name]??$paths['study'];return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="'.$path.'"/></svg>';}
add_action('wp_footer',function(){if(!dgs_is_role_portal())return;$u=wp_get_current_user();$year=dgs_edu_request_year();$class=dgs_edu_role($u,'dgs_student')?dgs_hw_student_class($year):null;$role=current_user_can('dgs_manage_education')?'مدیر و معاون':(dgs_edu_role($u,'dgs_counselor')?'مشاور':(dgs_edu_role($u,'dgs_teacher')?'مدرس':($class?'دانش‌آموز پایه '.dgs_edu_grade_label($class->grade):'دانش‌آموز')));echo '<template id="dgs-portal-brand"><a class="dg-brand" href="'.esc_url(home_url('/')).'" aria-label="صفحه اصلی مدرسه دانشگران">'.file_get_contents(DGS_PATH.'assets/portal-logo.svg').'<strong>'.esc_html(dgs_setting('school_name','مدرسه دانشگران')).'</strong><small>دبیرستان دوره دوم</small><span class="dgs-brand-grades">پایه‌های دهم، یازدهم و دوازدهم</span></a></template><template id="dgs-portal-profile"><span class="dgs-profile-avatar">'.dgs_portal_icon('users').'</span><span class="dgs-profile-label"><strong>'.esc_html($u->display_name).'</strong><small>'.esc_html($role).'</small></span></template>';},10);
add_action('wp_enqueue_scripts',function(){if(!dgs_is_role_portal())return;$css=str_replace("url('portal-Estedad.ttf')","url('".esc_url_raw(DGS_URL.'assets/portal-Estedad.ttf')."')",file_get_contents(DGS_PATH.'assets/portal-appearance.css'));wp_register_style('dgs-portal-appearance',false,array(),DGS_VERSION);wp_enqueue_style('dgs-portal-appearance');wp_add_inline_style('dgs-portal-appearance',$css);wp_register_script('dgs-portal-appearance',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-portal-appearance');wp_add_inline_script('dgs-portal-appearance',file_get_contents(DGS_PATH.'assets/portal-appearance.js'));},50);
