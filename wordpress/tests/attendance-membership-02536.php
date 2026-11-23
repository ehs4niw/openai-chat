<?php
if(getenv('DGS_TEST_ALLOW_FIXTURES')!=='1'||empty($argv[1]))exit(2);
require $argv[1];require_once ABSPATH.'wp-admin/includes/user.php';
$users=[];$classes=[];$checks=0;
function a36($ok,$label){global $checks;if(!$ok)throw new Exception($label);$checks++;echo "PASS $label\n";}
function ids36($class,$day){return array_map('intval',array_column(dgs_att_roster($class,$day),'ID'));}
function save36($class,$day,$year){$rows=[];foreach(dgs_att_roster($class,$day) as $u)$rows[$u->ID]=['status'=>'present','minutes'=>'0','note'=>''];$s=dgs_att_session($class,$day);return ['year'=>$year,'day'=>dgs_att_jdate($day),'class_id'=>$class,'revision'=>$s?$s->revision:0,'roster_hash'=>dgs_att_roster_hash($class,$day,dgs_att_roster($class,$day)),'attendance'=>$rows];}
try{
 global $wpdb;$e=dgs_edu_tables();$a=dgs_att_tables();$h=dgs_membership_table();$year='1405-1406';$today=dgs_att_today();$yesterday=(new DateTimeImmutable($today))->modify('-1 day')->format('Y-m-d');$prefix='att36_'.wp_generate_password(8,false);
 wp_set_current_user(get_user_by('login','showcase_local_admin')->ID);$admin=get_current_user_id();
 a36(get_option('dgs_membership_schema')==='1','history schema ready');
 foreach(['dgs_student','dgs_student','dgs_student','dgs_teacher'] as $i=>$role){$id=wp_insert_user(['user_login'=>$prefix.$i,'display_name'=>$prefix.$i,'user_pass'=>wp_generate_password(),'role'=>$role]);if(is_wp_error($id))throw new Exception($id->get_error_message());$users[]=$id;}list($s1,$s2,$new,$teacher)=$users;
 foreach(['A','B'] as $title){$wpdb->insert($e['classes'],['school_year'=>$year,'grade'=>10,'field'=>'science','title'=>$prefix.$title,'active'=>1,'created_at'=>current_time('mysql')]);$classes[]=(int)$wpdb->insert_id;}list($A,$B)=$classes;
 foreach([$s1,$s2] as $s)a36(dgs_membership_set($s,$A,$year),'initial enrollment');$wpdb->query($wpdb->prepare("UPDATE $h SET start_day=%s WHERE class_id=%d",$yesterday,$A));
 a36(dgs_membership_set($new,$A,$year),'new arrival');a36(!in_array($new,ids36($A,$yesterday),true),'new arrival absent from earlier unsaved roster');
 a36(!is_wp_error(dgs_att_save(save36($A,$yesterday,$year))),'save yesterday');
 a36(!is_wp_error(dgs_school_assign_one(['student_id'=>$s1,'class_id'=>$B,'year'=>$year,'replace'=>'1'])),'transfer student through real assignment handler');a36(in_array($s1,ids36($A,$yesterday),true),'old saved roster retains departed student');a36(!in_array($s1,ids36($B,$yesterday),true),'destination excludes days before transfer');a36(!in_array($s1,ids36($A,$today),true),'source excludes student from transfer date');a36(in_array($s1,ids36($B,$today),true),'destination includes transfer date');
 a36(!is_wp_error(dgs_att_save(save36($A,$today,$year))),'save source today');a36(dgs_membership_set($s2,$B,$year),'same-day transfer after attendance');a36(!in_array($s2,ids36($B,$today),true),'daily record stays in original class after same-day move');a36(in_array($s2,ids36($A,$today),true),'same-day saved source roster stable');
 $wpdb->insert($e['teaching'],['class_id'=>$B,'subject_id'=>999999,'teacher_id'=>$teacher]);wp_set_current_user($teacher);
 $pages=get_option('dgs_pages');$GLOBALS['wp_query']->is_page=true;$GLOBALS['wp_query']->queried_object=get_post($pages['portal']);$GLOBALS['wp_query']->queried_object_id=(int)$pages['portal'];$_GET=['dg_tab'=>'attendance','att_class'=>(string)$B,'att_day'=>dgs_att_jdate($today),'dg_year'=>$year];$html=dgs_edu_portal();file_put_contents('/tmp/teacher036.html',$html);a36(str_contains($html,'name="action" value="dgs_attendance"'),'teacher route renders editable form');a36(!str_contains($html,$prefix.'A</option>'),'teacher class filter');
 $denied=dgs_att_save(save36($A,$today,$year));a36(is_wp_error($denied)&&$denied->get_error_code()==='access','teacher cannot save another class');$data=save36($B,$today,$year);a36(!is_wp_error(dgs_att_save($data)),'teacher saves own class');a36(is_wp_error(dgs_att_save($data)),'stale revision rejected');
 $future=(new DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');$denied=dgs_att_save(save36($B,$future,$year));a36(is_wp_error($denied)&&$denied->get_error_code()==='future','future date rejected');
 wp_set_current_user($s1);a36(is_wp_error(dgs_att_save(save36($B,$today,$year))),'student cannot save');a36(!str_contains(dgs_edu_portal(),'name="action" value="dgs_attendance"'),'student remains read-only');
 wp_set_current_user($admin);$before=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $h WHERE student_id=%d",$s1));a36(dgs_membership_set($s1,$B,$year),'repeat assignment succeeds');a36($before===(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $h WHERE student_id=%d",$s1)),'repeat assignment creates no history duplicate');
 a36(dgs_membership_set($s1,0,$year),'remove current enrollment');a36(in_array($s1,ids36($A,$yesterday),true),'removal preserves historical attendance');
 $wpdb->query('START TRANSACTION');a36(dgs_membership_set($new,$B,$year,true),'transactional enrollment change');$wpdb->query('ROLLBACK');a36((int)$wpdb->get_var($wpdb->prepare("SELECT class_id FROM {$e['enrollments']} WHERE student_id=%d",$new))===$A,'rollback restores current enrollment');a36((int)$wpdb->get_var($wpdb->prepare("SELECT class_id FROM $h WHERE student_id=%d AND end_day IS NULL",$new))===$A,'rollback restores history');
 $blocked=dgs_school_assign_student(['student_ids'=>[$s1,$new],'class_id'=>$B,'year'=>$year]);a36(is_wp_error($blocked),'bulk transfer requires confirmation');a36(!(int)$wpdb->get_var($wpdb->prepare("SELECT class_id FROM {$e['enrollments']} WHERE student_id=%d",$s1)),'bulk preflight leaves unassigned student unchanged');
 a36(!is_wp_error(dgs_school_assign_student(['student_ids'=>[$s1,$new],'class_id'=>$B,'year'=>$year,'replace'=>'1'])),'bulk transfer saves membership and history');
 $enrollment=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$e['enrollments']} WHERE student_id=%d",$new));a36(!is_wp_error(dgs_edu_execute('unenroll',['year'=>$year,'enrollment_id'=>$enrollment])),'remove assignment through real handler');
 a36(!is_wp_error(dgs_edu_execute('enroll',['year'=>$year,'class_id'=>$A,'students'=>[$new]])),'legacy enrollment handler writes history');
 a36(in_array($h,dgs_student_delete_tables(),true),'permanent deletion includes membership history');
 echo "$checks checks passed\n";
}finally{
 wp_set_current_user(0);foreach($classes as $c){$sessions=$wpdb->get_col($wpdb->prepare("SELECT id FROM {$a['sessions']} WHERE class_id=%d",$c));foreach($sessions as $sid)$wpdb->delete($a['records'],['session_id'=>$sid]);$wpdb->delete($a['sessions'],['class_id'=>$c]);$wpdb->delete($e['teaching'],['class_id'=>$c]);$wpdb->delete($e['classes'],['id'=>$c]);}foreach($users as $u){$wpdb->delete($h,['student_id'=>$u]);$wpdb->delete($e['enrollments'],['student_id'=>$u]);wp_delete_user($u);}
}
