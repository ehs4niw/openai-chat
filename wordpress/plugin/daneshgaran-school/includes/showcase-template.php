<?php
if(!defined('ABSPATH'))exit;
get_header();
if(function_exists('dgs_elementor_converted')&&dgs_elementor_converted()){
 echo '<div class="dse-document">';while(have_posts()){the_post();the_content();}echo '</div>';
}else{$dsh_pages=get_option('dgs_pages',array());echo !empty($dsh_pages['about'])&&is_page((int)$dsh_pages['about'])?dgs_showcase_about():dgs_showcase_home();}
get_footer();
