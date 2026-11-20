<?php
if(!defined('ABSPATH'))exit;
function dgs_membership_table(){global $wpdb;return $wpdb->prefix.'dgs_membership_history';}
add_action('init',function(){
 if(get_option('dgs_membership_schema')==='1')return;
 $lock=(int)get_option('dgs_membership_migrating');if($lock&&$lock<time()-300)delete_option('dgs_membership_migrating');if(!add_option('dgs_membership_migrating',time(),'','no'))return;
 try{
 global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$table=dgs_membership_table();$cc=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE $table (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 student_id bigint(20) unsigned NOT NULL,
 class_id bigint(20) unsigned NOT NULL,
 school_year varchar(9) NOT NULL,
 start_day date NOT NULL,
 end_day date DEFAULT NULL,
 PRIMARY KEY  (id),
 KEY class_dates (class_id,start_day,end_day),
 KEY student_year (student_id,school_year)
 ) ENGINE=InnoDB $cc;");
 if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;
 $e=dgs_edu_tables();
 // Legacy creation dates are the only available baseline; saved attendance is authoritative.
 $ok=$wpdb->query("INSERT INTO $table (student_id,class_id,school_year,start_day) SELECT e.student_id,e.class_id,e.school_year,DATE(e.created_at) FROM {$e['enrollments']} e WHERE NOT EXISTS (SELECT 1 FROM $table h WHERE h.student_id=e.student_id AND h.school_year=e.school_year)");
 if($ok!==false)update_option('dgs_membership_schema','1',false);
 }finally{delete_option('dgs_membership_migrating');}
},25);
// All enrollment writes use this helper. Bulk callers already own their transaction.
function dgs_membership_set($student,$class,$year,$in_transaction=false){
 global $wpdb;$e=dgs_edu_tables();$table=dgs_membership_table();
 if(get_option('dgs_membership_schema')!=='1')return false;
 if(!$in_transaction&&$wpdb->query('START TRANSACTION')===false)return false;
 $ok=(bool)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->users} WHERE ID=%d FOR UPDATE",$student));
 $old=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$e['enrollments']} WHERE student_id=%d AND school_year=%s FOR UPDATE",$student,$year));
 $day=current_time('Y-m-d');
 if($ok&&(!$old||(int)$old->class_id!==(int)$class)){
  if($old){
   $known=$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE student_id=%d AND school_year=%s AND end_day IS NULL LIMIT 1",$student,$year));
   if(!$known)$ok=$wpdb->insert($table,array('student_id'=>$student,'class_id'=>$old->class_id,'school_year'=>$year,'start_day'=>substr($old->created_at,0,10)))!==false;
  }
  if($ok)$ok=$wpdb->query($wpdb->prepare("UPDATE $table SET end_day=%s WHERE student_id=%d AND school_year=%s AND end_day IS NULL",$day,$student,$year))!==false;
  if($ok){if(!$class)$ok=$wpdb->delete($e['enrollments'],array('student_id'=>$student,'school_year'=>$year))!==false;
   elseif($old)$ok=$wpdb->update($e['enrollments'],array('class_id'=>$class,'created_at'=>current_time('mysql')),array('id'=>$old->id))!==false;
   else $ok=$wpdb->insert($e['enrollments'],array('student_id'=>$student,'class_id'=>$class,'school_year'=>$year,'created_at'=>current_time('mysql')))!==false;
  }
  if($ok&&$class)$ok=$wpdb->insert($table,array('student_id'=>$student,'class_id'=>$class,'school_year'=>$year,'start_day'=>$day))!==false;
 }
 if(!$in_transaction){if(!$ok){$wpdb->query('ROLLBACK');return false;}if($wpdb->query('COMMIT')===false){$wpdb->query('ROLLBACK');return false;}}
 return $ok;
}
