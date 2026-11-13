<?php
/**
 * Plugin Name: سامانه دانشگران
 * Description: سامانه مدرسه دانشگران؛ صفحات عمومی و درخواست‌ها، مدیریت کلاس‌ها، کاربران و تخصیص دانش‌آموز، دبیر و مشاور با دسترسی مبتنی بر نقش.
 * Version: 0.19.3
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Daneshgaran
 * Text Domain: daneshgaran-school
 * License: GPL-2.0-or-later
 */
if(!defined('ABSPATH'))exit;
define('DGS_VERSION','0.19.3');define('DGS_PATH',plugin_dir_path(__FILE__));define('DGS_URL',plugin_dir_url(__FILE__));
require_once DGS_PATH.'includes/content.php';
require_once DGS_PATH.'includes/requests.php';
require_once DGS_PATH.'includes/render.php';
require_once DGS_PATH.'includes/setup.php';
require_once DGS_PATH.'includes/education-data.php';
require_once DGS_PATH.'includes/education-ui.php';
require_once DGS_PATH.'includes/grades.php';
require_once DGS_PATH.'includes/attendance-data.php';
require_once DGS_PATH.'includes/attendance-ui.php';
require_once DGS_PATH.'includes/portal-menu.php';
require_once DGS_PATH.'includes/finance.php';
require_once DGS_PATH.'includes/quizzes-data.php';
require_once DGS_PATH.'includes/quizzes-ui.php';
require_once DGS_PATH.'includes/homework-data.php';
require_once DGS_PATH.'includes/homework-ui.php';
require_once DGS_PATH.'includes/school-panels-data.php';
require_once DGS_PATH.'includes/school-panels-ui.php';
require_once DGS_PATH.'includes/school-panels-roster.php';
require_once DGS_PATH.'includes/school-panels-excel.php';
function dgs_activate(){
    foreach(array('dgs_student'=>'دانش‌آموز','dgs_teacher'=>'معلم','dgs_counselor'=>'مشاور','dgs_deputy'=>'معاون مدرسه') as $slug=>$name){add_role($slug,$name,array('read'=>true));}
    foreach(array('administrator','dgs_deputy') as $name){$role=get_role($name);if(!$role)continue;foreach(array('edit_dgs_requests','edit_others_dgs_requests','edit_private_dgs_requests','edit_published_dgs_requests','read_private_dgs_requests','publish_dgs_requests','delete_dgs_requests','delete_others_dgs_requests','delete_private_dgs_requests','delete_published_dgs_requests') as $cap)$role->add_cap($cap);}
    dgs_register_content();dgs_edu_upgrade();flush_rewrite_rules(false);update_option('dgs_version',DGS_VERSION);
}
register_activation_hook(__FILE__,'dgs_activate');
// غیرفعال‌سازی و به‌روزرسانی اطلاعات کاربران و درخواست‌ها را حذف نمی‌کند.
register_deactivation_hook(__FILE__,function(){flush_rewrite_rules(false);});
function dgs_setting($name,$default=''){ $s=get_option('dgs_settings',array());return isset($s[$name])&&is_scalar($s[$name])?(string)$s[$name]:$default; }
function dgs_page_url($key){$p=get_option('dgs_pages',array());return !empty($p[$key])&&get_post_status((int)$p[$key])==='publish'?get_permalink((int)$p[$key]):home_url('/');}
function dgs_icon($name){return function_exists('dgs_portal_icon')?dgs_portal_icon($name):(function_exists('dgt_icon')?dgt_icon($name):'');}
add_action('admin_notices',function(){if(current_user_can('manage_options')&&!get_option('dgs_pages'))echo '<div class="notice notice-info"><p>سامانه دانشگران فعال شد. برای ساخت صفحات، <a href="'.esc_url(admin_url('admin.php?page=dgs-setup')).'">راه‌انداز اولیه</a> را باز کنید.</p></div>';});

require_once DGS_PATH.'includes/student-record.php';
require_once DGS_PATH.'includes/student-admission.php';
require_once DGS_PATH.'includes/study-data.php';
require_once DGS_PATH.'includes/study-ui.php';
require_once DGS_PATH.'includes/portal-appearance.php';
require_once DGS_PATH.'includes/management-pages-data.php';
require_once DGS_PATH.'includes/management-pages-ui.php';
require_once DGS_PATH.'includes/public-requests.php';

require_once DGS_PATH.'includes/login-page.php';

require_once DGS_PATH.'includes/teacher-data.php';
require_once DGS_PATH.'includes/teacher-ui.php';
require_once DGS_PATH.'includes/public-catalog.php';

require_once DGS_PATH.'includes/showcase-settings.php';
require_once DGS_PATH.'includes/showcase.php';

require_once DGS_PATH.'includes/elementor-layouts.php';
require_once DGS_PATH.'includes/elementor-design.php';

require_once DGS_PATH.'includes/gallery.php';
require_once DGS_PATH.'includes/gallery-elementor.php';
