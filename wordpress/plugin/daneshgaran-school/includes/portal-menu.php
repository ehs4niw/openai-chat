<?php
if(!defined('ABSPATH'))exit;
function dgs_portal_links(){
    if(!is_user_logged_in())return array();$uid=get_current_user_id();
    if(current_user_can('dgs_manage_education'))return array('overview'=>array('مدیریت','home'),'education'=>array('مدیریت آموزشی','class'),'users'=>array('کاربران و دسترسی','users'),'timetable'=>array('برنامه هفتگی','calendar'),'grades'=>array('ارزیابی و نمرات','book'),'attendance'=>array('حضور و غیاب','calendar'),'quizzes'=>array('آزمون‌ها','book'),'homework'=>array('تکالیف و ارسال فایل','book'),'finance'=>array('مالی','book'),'study'=>array('گزارش مطالعه','book'),'counseling'=>array('تخصیص مشاور','users'),'requests'=>array('درخواست‌ها','book'));
    $links=array();if(dgs_edu_role($uid,'dgs_teacher'))$links=array('overview'=>array('پنل مدرس','home'),'classes'=>array('کلاس‌ها و درس‌های من','users'),'timetable'=>array('برنامه هفتگی','calendar'),'homework'=>array('تکالیف و ارسال فایل','book'),'quizzes'=>array('کوییزهای کلاس','book'),'grades'=>array('ثبت نمرات و کارنامه','book'));
    elseif(dgs_edu_role($uid,'dgs_counselor'))$links=array('overview'=>array('پنل مشاور','home'),'students'=>array('دانش‌آموزان من','users'),'study'=>array('مطالعه روزانه','book'),'homework'=>array('تکالیف دانش‌آموزان','book'),'grades'=>array('نمرات دانش‌آموزان','book'));
    elseif(dgs_edu_role($uid,'dgs_student'))$links=array('overview'=>array('پیشخوان','home'),'study'=>array('ثبت مطالعه روزانه','book'),'timetable'=>array('برنامه هفتگی','calendar'),'homework'=>array('تکالیف و ارسال فایل','book'),'quizzes'=>array('آزمون‌ها و کوییزها','book'),'grades'=>array('نمرات و کارنامه','book'),'attendance'=>array('سوابق حضور و غیاب','calendar'),'finance'=>array('شهریه و پرداخت‌های من','book'));
    if($links)$links['password']=array('تغییر رمز عبور','login');return $links;
}
function dgs_portal_tab(){$raw=isset($_GET['dg_tab'])&&is_string($_GET['dg_tab'])?sanitize_key($_GET['dg_tab']):'overview';if(current_user_can('dgs_manage_education')&&$raw==='records')return 'records';if(current_user_can('dgs_manage_education')&&in_array($raw,array('classes','subjects','assignments'),true))return 'education';return isset(dgs_portal_links()[$raw])?$raw:'overview';}
function dgs_is_role_portal(){$p=get_option('dgs_pages',array());return !empty($p['portal'])&&is_page((int)$p['portal'])&&(bool)dgs_portal_links();}
add_filter('body_class',function($classes){if(dgs_is_role_portal())$classes[]='dgs-role-portal';return $classes;});
add_action('wp_footer',function(){
    if(!dgs_is_role_portal())return;$year=dgs_edu_request_year();$tab=dgs_portal_tab();
    echo '<template data-dgs-panel-menu><ul class="dg-menu">';foreach(dgs_portal_links() as $key=>$item)echo '<li><a '.(($tab===$key||($tab==='records'&&$key==='education'))?'aria-current="page"':'').' href="'.esc_url(dgs_edu_url($key,$year)).'">'.dgs_portal_icon($key).'<span>'.esc_html($item[0]).'</span></a></li>';
    echo '<li><a href="'.esc_url(home_url('/')).'">'.dgs_portal_icon('overview').'<span>بازگشت به سایت</span></a></li><li><a href="'.esc_url(wp_logout_url(home_url('/'))).'">'.dgs_portal_icon('logout').'<span>خروج از حساب</span></a></li></ul></template>';
},10);
add_action('wp_enqueue_scripts',function(){if(!dgs_is_role_portal())return;
    wp_register_style('dgs-panel-menu',false,array(),DGS_VERSION);wp_enqueue_style('dgs-panel-menu');wp_add_inline_style('dgs-panel-menu','.dgs-role-portal .dg-content-page>h1,.dgs-role-portal .dg-footer,.dgs-role-portal.dga-nav-ready .dge-portal>.dge-tabs{display:none}.dgs-role-portal .dg-sidebar{background:linear-gradient(155deg,#7d359e,#58217e 65%,#662f94);overflow:hidden}.dgs-role-portal .dg-sidebar:after{content:"";position:absolute;width:180%;height:50px;bottom:25px;right:-50%;border-radius:50%;border-top:15px solid #ffc628;transform:rotate(-26deg);pointer-events:none;opacity:.85}.dgs-role-portal .dg-sidebar nav{position:relative;z-index:1}.dgs-role-portal .dg-sidebar-bottom{visibility:hidden}');
    wp_register_script('dgs-panel-menu',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-panel-menu');wp_add_inline_script('dgs-panel-menu',"(function(){var t=document.querySelector('[data-dgs-panel-menu]'),n=document.querySelector('.dg-sidebar nav');if(t&&n){n.replaceChildren(t.content.cloneNode(true));document.body.classList.add('dga-nav-ready');}})();");
});
