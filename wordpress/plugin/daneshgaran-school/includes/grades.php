<?php
if(!defined('ABSPATH'))exit;
function dgs_grade_table(){global $wpdb;return $wpdb->prefix.'dgs_grades';}
add_action('init',function(){
 if(get_option('dgs_grade_schema')==='1')return;
 global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$table=dgs_grade_table();$cc=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE $table (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 class_id bigint(20) unsigned NOT NULL,
 subject_id bigint(20) unsigned NOT NULL,
 student_id bigint(20) unsigned NOT NULL,
 period varchar(7) NOT NULL,
 score decimal(4,2) NOT NULL,
 published tinyint unsigned NOT NULL DEFAULT 0,
 updated_by bigint(20) unsigned NOT NULL,
 updated_at datetime NOT NULL,
 PRIMARY KEY  (id),
 UNIQUE KEY student_subject_month (student_id,subject_id,period),
 KEY class_period (class_id,period)
 ) ENGINE=InnoDB $cc;");
 if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))===$table)update_option('dgs_grade_schema','1',false);
},9);
function dgs_grade_period($value,$year){
 $value=dgs_edu_digits($value);if(!preg_match('/^(14[0-9]{2})-(0[1-9]|1[0-2])$/D',$value,$m))return '';
 $parts=explode('-',$year);return (int)$m[1]===(int)$parts[(int)$m[2]>=7?0:1]?$value:'';
}
function dgs_grade_previous($period){$y=(int)substr($period,0,4);$m=(int)substr($period,5,2)-1;if(!$m){$m=12;$y--;}return sprintf('%04d-%02d',$y,$m);}
function dgs_grade_can_edit($class,$subject){
 if(!is_user_logged_in()||!$class||!$class->active)return false;
 global $wpdb;$t=dgs_edu_tables();if(!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['subjects']} WHERE id=%d AND active=1",$subject)))return false;
 if(current_user_can('dgs_manage_education'))return true;
 return dgs_edu_role(get_current_user_id(),'dgs_teacher')&&(bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['teaching']} WHERE class_id=%d AND subject_id=%d AND teacher_id=%d",$class->id,$subject,get_current_user_id()));
}
function dgs_grade_save($data){
 if(function_exists('dgs_mg_write'))return dgs_edu_error('new_form','برای ثبت نمره از فرم جدید دفتر نمرات استفاده کنید.');
 global $wpdb;$t=dgs_edu_tables();$table=dgs_grade_table();
 foreach(array('class_id','subject_id','year','period') as $key)if(!isset($data[$key])||!is_scalar($data[$key]))return dgs_edu_error('input','اطلاعات درخواست معتبر نیست.');
 $year=dgs_edu_year((string)$data['year']);$class=dgs_edu_class($data['class_id']);$subject=absint($data['subject_id']);$period=$year?dgs_grade_period((string)$data['period'],$year):'';
 if(!$class||!$period||$class->school_year!==$year||!dgs_grade_can_edit($class,$subject))return dgs_edu_error('access','کلاس، درس، ماه یا دسترسی معتبر نیست.');
 if(get_option('dgs_grade_schema')!=='1')return dgs_edu_error('schema','جدول نمرات آماده نیست.');
 $scores=$data['scores']??null;if(!is_array($scores)||!$scores||count($scores)>200)return dgs_edu_error('input','فهرست نمرات معتبر نیست.');
 $allowed=array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT student_id FROM {$t['enrollments']} WHERE class_id=%d AND school_year=%s",$class->id,$year)));$valid=array();
 foreach($scores as $id=>$raw){
  if(!ctype_digit((string)$id)||!in_array((int)$id,$allowed,true)||!is_scalar($raw))return dgs_edu_error('student','دانش‌آموز خارج از کلاس در درخواست وجود دارد.');
  $score=dgs_edu_digits((string)$raw);$score=str_replace(array('٫','/'),'.',$score);if($score==='')continue;
  if(!preg_match('/^\d{1,2}(\.\d{1,2})?$/D',$score)||(float)$score>20)return dgs_edu_error('score','نمره باید بین صفر تا ۲۰ و حداکثر دو رقم اعشار باشد.');
  $old=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE student_id=%d AND subject_id=%d AND period=%s",$id,$subject,$period));
  if($old&&(int)$old->class_id!==(int)$class->id)return dgs_edu_error('history','برای این دانش‌آموز در همین درس و ماه، سابقه کلاس دیگری وجود دارد؛ مدیریت باید سوابق را بررسی کند.');
  $valid[(int)$id]=array('score'=>$score,'old'=>$old);
 }
 if(!$valid)return dgs_edu_error('empty','حداقل یک نمره وارد کنید؛ خانه خالی نمره قبلی را حذف نمی‌کند.');
 $publish=current_user_can('dgs_manage_education')&&isset($data['publish'])&&$data['publish']==='1';
 $wpdb->query('START TRANSACTION');
 foreach($valid as $id=>$entry){$row=array('class_id'=>$class->id,'subject_id'=>$subject,'student_id'=>$id,'period'=>$period,'score'=>$entry['score'],'published'=>$publish?1:0,'updated_by'=>get_current_user_id(),'updated_at'=>current_time('mysql'));
  $ok=$entry['old']?$wpdb->update($table,$row,array('id'=>$entry['old']->id)):$wpdb->insert($table,$row);
  if($ok===false){$wpdb->query('ROLLBACK');return dgs_edu_error('write','ذخیره نمرات کامل نشد؛ دوباره تلاش کنید.');}
 }
 $wpdb->query('COMMIT');dgs_edu_audit($publish?'grades_publish':'grades_draft',$class->id);return $publish?'نمرات ذخیره و منتشر شدند.':'نمرات به صورت پیش‌نویس ذخیره شدند؛ برای نمایش به دانش‌آموز، مدیریت باید منتشر کند.';
}
add_action('admin_post_dgs_save_grades',function(){
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!is_user_logged_in())wp_die('درخواست مجاز نیست.',403);
 check_admin_referer('dgs_grades','dgs_grades_nonce');$r=dgs_grade_save(wp_unslash($_POST));$token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r),300);
 wp_safe_redirect(add_query_arg(array('dg_tab'=>'grades','grade_period'=>is_string($_POST['period']??null)?sanitize_text_field(wp_unslash($_POST['period'])):'','grade_pair'=>absint($_POST['class_id']??0).':'.absint($_POST['subject_id']??0),'dg_year'=>dgs_edu_year(is_string($_POST['year']??null)?wp_unslash($_POST['year']):''),'dg_notice'=>$token),dgs_page_url('portal')),303);exit;
});
function dgs_grade_context($year){
 $raw=isset($_GET['grade_period'])&&is_string($_GET['grade_period'])?wp_unslash($_GET['grade_period']):substr($year,0,4).'-07';return dgs_grade_period($raw,$year)?:substr($year,0,4).'-07';
}
function dgs_grade_pairs($year){
 global $wpdb;$t=dgs_edu_tables();$manager=current_user_can('dgs_manage_education');
 $sql="SELECT DISTINCT a.class_id,a.subject_id,c.title,c.grade,s.title AS subject FROM {$t['teaching']} a JOIN {$t['classes']} c ON c.id=a.class_id JOIN {$t['subjects']} s ON s.id=a.subject_id WHERE c.school_year=%s AND c.active=1 AND s.active=1";
 $args=array($year);if(!$manager){$sql.=' AND a.teacher_id=%d';$args[]=get_current_user_id();}return $wpdb->get_results($wpdb->prepare($sql.' ORDER BY c.grade,c.title,s.title',$args));
}
function dgs_edu_view_grades($year){return dgs_grade_panel($year);}
function dgs_grade_panel($year){
 if(function_exists('dgs_mg_panel')&&(current_user_can('dgs_manage_education')||dgs_edu_role(get_current_user_id(),'dgs_teacher')))return dgs_mg_panel($year);
 global $wpdb;$t=dgs_edu_tables();$table=dgs_grade_table();$manager=current_user_can('dgs_manage_education');$teacher=dgs_edu_role(get_current_user_id(),'dgs_teacher');$period=dgs_grade_context($year);
 $out='<section class="dge-box"><h3>نمرات و کارنامه ماهانه</h3><form method="get" action="'.esc_url(dgs_page_url('portal')).'" class="dge-filter"><input type="hidden" name="dg_tab" value="grades"><input type="hidden" name="dg_year" value="'.esc_attr($year).'"><label>ماه شمسی (مثلاً 1405-07)<input name="grade_period" dir="ltr" pattern="14[0-9]{2}-(0[1-9]|1[0-2])" value="'.esc_attr($period).'" required></label>';
 $pairs=array();$selected='';$pair=null;
 if($manager||$teacher){$pairs=dgs_grade_pairs($year);$options=array();foreach($pairs as $p)$options[$p->class_id.':'.$p->subject_id]=dgs_edu_grade_label($p->grade).' '.$p->title.' — '.$p->subject;
 $selected=isset($_GET['grade_pair'])&&is_string($_GET['grade_pair'])?$_GET['grade_pair']:(string)array_key_first($options);$out.=dgs_edu_select('grade_pair','کلاس و درس',$options,$selected,false);foreach($pairs as $p)if($selected===$p->class_id.':'.$p->subject_id)$pair=$p;
 }
 $out.='<button class="dg-btn dg-btn-outline">نمایش</button></form><p class="dg-muted">نمرات از ۲۰ هستند. فقط نمرات منتشرشده برای دانش‌آموز و مشاور نمایش داده می‌شوند.</p></section>';
 if(($manager||$teacher)&&$pair){
 $roster=$wpdb->get_results($wpdb->prepare("SELECT u.ID,u.display_name,g.score,g.published FROM {$t['enrollments']} e JOIN {$wpdb->users} u ON u.ID=e.student_id LEFT JOIN $table g ON g.student_id=e.student_id AND g.subject_id=%d AND g.period=%s AND g.class_id=e.class_id WHERE e.class_id=%d AND e.school_year=%s ORDER BY u.display_name",$pair->subject_id,$period,$pair->class_id,$year));
 $out.='<section class="dge-box"><h3>ثبت و ویرایش: '.esc_html($pair->title.' — '.$pair->subject).'</h3><form class="dge-form" method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="dgs_save_grades">'.wp_nonce_field('dgs_grades','dgs_grades_nonce',true,false);
 foreach(array('year'=>$year,'period'=>$period,'class_id'=>$pair->class_id,'subject_id'=>$pair->subject_id) as $key=>$value)$out.='<input type="hidden" name="'.esc_attr($key).'" value="'.esc_attr($value).'">';
 $out.=dgs_edu_table_start(array('دانش‌آموز','نمره از ۲۰','وضعیت'));foreach($roster as $r)$out.='<tr><td>'.esc_html($r->display_name).'</td><td><input aria-label="نمره '.esc_attr($r->display_name).'" type="number" min="0" max="20" step="0.01" name="scores['.(int)$r->ID.']" value="'.esc_attr($r->score??'').'" dir="ltr"></td><td>'.($r->score===null?'ثبت نشده':($r->published?'منتشرشده':'پیش‌نویس')).'</td></tr>';
 $out.='</tbody></table></div><p>خانه خالی نمره را حذف نمی‌کند. ذخیره مجدد بدون انتشار، نمرات را پیش‌نویس می‌کند.</p>';
 if($manager)$out.='<label><input type="checkbox" name="publish" value="1">تأیید و انتشار نمرات این فهرست</label>';
 $out.='<button class="dg-btn">ذخیره نمرات</button></form></section>';
 }elseif($manager||$teacher)$out.=dgs_empty('کلاس و درس تخصیص‌یافته انتخاب کنید.');
 return $out.(function_exists('dgs_mg_reader')?dgs_mg_reader($year):'').dgs_grade_report($year,$period);
}
function dgs_grade_report($year,$period){
 global $wpdb;$t=dgs_edu_tables();$table=dgs_grade_table();$uid=get_current_user_id();$manager=current_user_can('dgs_manage_education');$args=array(dgs_grade_previous($period),$period,$year);
 $where='';
 if(!$manager){$where=' AND g.published=1';
  if(dgs_edu_role($uid,'dgs_student')){$where.=' AND g.student_id=%d';$args[]=$uid;}
  elseif(dgs_edu_role($uid,'dgs_counselor')){$where.=" AND EXISTS (SELECT 1 FROM {$t['counseling']} a WHERE a.student_id=g.student_id AND a.school_year=c.school_year AND a.counselor_id=%d)";$args[]=$uid;}
  elseif(dgs_edu_role($uid,'dgs_teacher')){$where=" AND EXISTS (SELECT 1 FROM {$t['teaching']} a WHERE a.class_id=g.class_id AND a.subject_id=g.subject_id AND a.teacher_id=%d)";$args[]=$uid;}
  else return '';
 }
 $rows=$wpdb->get_results($wpdb->prepare("SELECT g.*,u.display_name,c.title AS class_title,s.title AS subject,p.score AS previous_score FROM $table g JOIN {$wpdb->users} u ON u.ID=g.student_id JOIN {$t['classes']} c ON c.id=g.class_id JOIN {$t['subjects']} s ON s.id=g.subject_id LEFT JOIN $table p ON p.student_id=g.student_id AND p.subject_id=g.subject_id AND p.period=%s AND p.published=1 WHERE g.period=%s AND c.school_year=%s $where ORDER BY u.display_name,s.title LIMIT 501",$args));
 $out='<section class="dge-box"><h3>کارنامه '.esc_html(dgs_fa_digits($period)).'</h3><p>مقایسه با ماه قبل: '.esc_html(dgs_fa_digits(dgs_grade_previous($period))).'؛ خط تیره یعنی نمره منتشرشده‌ای برای مقایسه وجود ندارد. میانگین، ساده و بدون ضریب است.</p>';
 if(!$rows)return $out.'<p>برای این ماه نمره قابل نمایشی وجود ندارد.</p></section>';
 if(count($rows)>500)$out.='<p>فقط ۵۰۰ ردیف نخست نمایش داده می‌شود.</p>';
 $out.=dgs_edu_table_start(array('دانش‌آموز','کلاس','درس','نمره','ماه قبل','تغییر','وضعیت'));$totals=array();
 foreach(array_slice($rows,0,500) as $r){$delta=$r->previous_score===null?'—':sprintf('%+.2f',(float)$r->score-(float)$r->previous_score);$out.='<tr><td>'.esc_html($r->display_name).'</td><td>'.esc_html($r->class_title).'</td><td>'.esc_html($r->subject).'</td><td>'.esc_html((function_exists('dgs_mg_fmt')?dgs_mg_fmt(dgs_mg_cents($r->score)):dgs_fa_digits($r->score))).'</td><td>'.esc_html($r->previous_score===null?'—':(function_exists('dgs_mg_fmt')?dgs_mg_fmt(dgs_mg_cents($r->previous_score)):dgs_fa_digits($r->previous_score))).'</td><td dir="ltr">'.esc_html($delta).'</td><td>'.($r->published?'منتشرشده':'پیش‌نویس').'</td></tr>';if($r->published){if(!isset($totals[$r->student_id]))$totals[$r->student_id]=array('name'=>$r->display_name,'sum'=>0,'count'=>0);$totals[$r->student_id]['sum']+=(float)$r->score;$totals[$r->student_id]['count']++;}}
 $out.='</tbody></table></div>';foreach($totals as $v)$out.='<p>'.esc_html($v['name']).': میانگین نمرات منتشرشدهٔ نمایش‌داده‌شده '.esc_html(dgs_fa_digits(number_format($v['sum']/$v['count'],2))).'</p>';
 return $out.'</section>';
}
