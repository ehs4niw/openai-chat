<?php
/**
 * Plugin Name: آزمایش اتصال اخبار دانشگران
 * Version: 1.0.1
 * Description: تست دستی و فقط خواندنی اتصال هاست به صفحه رسمی اخبار آموزش‌وپرورش.
 */
if(!defined('ABSPATH'))exit;
add_action('admin_menu',function(){if(current_user_can('manage_options'))add_management_page('تست اتصال اخبار','تست اتصال اخبار','manage_options','dgnc','dgnc_page');});
function dgnc_page(){
 if(!current_user_can('manage_options'))wp_die('دسترسی ندارید.');echo '<div class="wrap" dir="rtl"><h1>آزمایش اتصال اخبار آموزش‌وپرورش</h1><p>این ابزار فقط صفحه رسمی اخبار را می‌خواند؛ هیچ خبر، کاربر یا تنظیماتی را تغییر نمی‌دهد.</p>';
 if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 check_admin_referer('dgnc_test');$url='https://ea.medu.gov.ir/fa/%D8%A7%D8%AE%D8%A8%D8%A7%D8%B1';
 $ips=gethostbynamel('ea.medu.gov.ir');$valid=wp_http_validate_url($url);echo '<div class="notice notice-info"><p>نسخه ابزار: 1.0.1</p><p>DNS دامنه: '.esc_html($ips?implode('، ',$ips):'نام دامنه از این هاست به IP تبدیل نشد').'</p><p>تأیید نشانی توسط وردپرس: '.($valid?'بله':'خیر').'</p>';if($ips&&!$valid)echo '<p>نشانی به IP تبدیل شد، اما بررسی نشانی وردپرس آن را نپذیرفت؛ ممکن است IP غیرعمومی باشد.</p>';if(defined('WP_HTTP_BLOCK_EXTERNAL')&&WP_HTTP_BLOCK_EXTERNAL)echo '<p>محدودیت درخواست خارجی WP_HTTP_BLOCK_EXTERNAL فعال است.</p>';echo '</div>';
 $r=wp_safe_remote_get($url,array('timeout'=>25,'redirection'=>0,'limit_response_size'=>2*1024*1024,'headers'=>array('Accept'=>'text/html')));
 if(is_wp_error($r))echo '<div class="notice notice-error"><p>اتصال ناموفق: '.esc_html($r->get_error_code().' — '.$r->get_error_message()).'</p></div>';
 else{$code=wp_remote_retrieve_response_code($r);$body=wp_remote_retrieve_body($r);$count=0;$location=wp_remote_retrieve_header($r,'location');if($location)echo '<p>نشانی تغییرمسیر: <code>'.esc_html($location).'</code></p>';
 if(class_exists('DOMDocument')){$dom=new DOMDocument();$old=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="UTF-8">'.$body,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($old);$x=new DOMXPath($dom);$links=$x->query('//div[contains(concat(" ",normalize-space(@class)," ")," views-field-title ")]//a[contains(@href,"/fa/news/")]');$seen=array();foreach($links as $a)$seen[$a->getAttribute('href')]=true;$count=count($seen);}
 echo '<div class="notice notice-info"><p>کد پاسخ: <strong>'.(int)$code.'</strong></p><p>حجم پاسخ: '.strlen($body).' بایت</p><p>تعداد لینک خبر قابل‌شناسایی: '.$count.'</p><p>'.esc_html($code===200&&$count>0?'اتصال موفق است و ساختار اخبار هم شناسایی شد.':($code===200?'صفحه دریافت شد، اما ساختار اخبار تأیید نشد؛ برای بررسی تصویر نتیجه را بفرستید.':'پاسخ موفق دریافت نشد؛ تصویر این نتیجه را بفرستید.')).'</p></div>';
 }
 }
 echo '<form method="post">';wp_nonce_field('dgnc_test');echo '<button class="button button-primary">تست اتصال به آموزش‌وپرورش</button></form><p>پس از پایان بررسی می‌توانید این افزونه موقت را غیرفعال و حذف کنید.</p></div>';
}
