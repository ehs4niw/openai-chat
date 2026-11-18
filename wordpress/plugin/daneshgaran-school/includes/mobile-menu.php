<?php
if(!defined('ABSPATH'))exit;
add_action('wp_enqueue_scripts',function(){if(get_template()!=='daneshgaran')return;wp_register_style('dgs-mobile-menu',false,array(),DGS_VERSION);wp_enqueue_style('dgs-mobile-menu');wp_add_inline_style('dgs-mobile-menu',file_get_contents(DGS_PATH.'assets/mobile-menu.css'));wp_register_script('dgs-mobile-menu',false,array(),DGS_VERSION,true);wp_enqueue_script('dgs-mobile-menu');wp_add_inline_script('dgs-mobile-menu',file_get_contents(DGS_PATH.'assets/mobile-menu.js'));},999);
