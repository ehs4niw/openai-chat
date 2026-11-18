<?php
if(!defined('ABSPATH'))exit;
function dgs_licensed_font_assets(){
 $uploads=wp_upload_dir(null,false);if(!empty($uploads['error'])||!is_file($uploads['basedir'].'/daneshgaran-fonts/IRANYekanXVF.woff2'))return;
 $base=trailingslashit($uploads['baseurl']).'daneshgaran-fonts/';$src='url("'.esc_url($base.'IRANYekanXVF.woff2').'") format("woff2")';if(is_file($uploads['basedir'].'/daneshgaran-fonts/IRANYekanXVF.woff'))$src.=',url("'.esc_url($base.'IRANYekanXVF.woff').'") format("woff")';
 $css='@font-face{font-family:DgsIranYekanX;src:'.$src.';font-weight:100 1000;font-style:normal;font-display:swap}html{--dgs-site-font:DgsIranYekanX}';
 $css.='body.dg-site,body.dg-site .dg-shell,body.dg-site .dg-sidebar,body.dg-site .dg-topbar,body.dg-site .dg-footer{font-family:var(--dgs-site-font),Tahoma,sans-serif!important}';
 $css.='body.dg-site .dg-shell :is(h1,h2,h3,h4,h5,h6,p,a,label,input,select,textarea,button,table,th,td,small,strong,b,legend,summary,li,dt,dd),body.dg-site.dse-editable .elementor :is(.elementor-heading-title,.elementor-widget-text-editor,.elementor-button){font-family:var(--dgs-site-font),Tahoma,sans-serif!important}';
 wp_register_style('dgs-licensed-font',false,array(),DGS_VERSION);wp_enqueue_style('dgs-licensed-font');wp_add_inline_style('dgs-licensed-font',$css);
}
add_action('wp_enqueue_scripts','dgs_licensed_font_assets',PHP_INT_MAX);
