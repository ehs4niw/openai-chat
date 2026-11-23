<?php if(!defined('ABSPATH'))exit;get_header();echo is_singular('dgs_teacher')?dgs_teacher_profile(get_queried_object_id()):dgs_catalog_news();get_footer();
