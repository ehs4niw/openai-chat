<?php
if(!defined('ABSPATH'))exit;
function dgs_ta_tables(){global $wpdb;return ['days'=>$wpdb->prefix.'dgs_teacher_days','rows'=>$wpdb->prefix.'dgs_teacher_presence','audit'=>$wpdb->prefix.'dgs_teacher_presence_audit','expected'=>$wpdb->prefix.'dgs_teacher_expected'];}
add_action('init',function(){if(get_option('dgs_teacher_att_schema')==='1'&&get_option('dgs_teacher_selection_schema')==='1')return;global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=dgs_ta_tables();$cc=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t['days']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 school_year varchar(9) NOT NULL,
 school_day date NOT NULL,
 revision bigint(20) unsigned NOT NULL DEFAULT 1,
 PRIMARY KEY (id),
 UNIQUE KEY year_day (school_year,school_day)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['rows']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 school_year varchar(9) NOT NULL,
 school_day date NOT NULL,
 teacher_id bigint(20) unsigned NOT NULL,
 status varchar(12) NOT NULL,
 arrival varchar(5) NOT NULL DEFAULT '',
 departure varchar(5) NOT NULL DEFAULT '',
 note text NOT NULL,
 planned longtext NOT NULL,
 actor_id bigint(20) unsigned NOT NULL,
 updated_at datetime NOT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY teacher_day (teacher_id,school_day),
 KEY year_day (school_year,school_day)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['audit']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 presence_id bigint(20) unsigned NOT NULL,
 actor_id bigint(20) unsigned NOT NULL,
 before_data longtext NOT NULL,
 after_data longtext NOT NULL,
 created_at datetime NOT NULL,
 PRIMARY KEY (id),
 KEY presence_history (presence_id)
 ) ENGINE=InnoDB $cc;");
 dbDelta("CREATE TABLE {$t['expected']} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 school_year varchar(9) NOT NULL,
 school_day date NOT NULL,
 teacher_id bigint(20) unsigned NOT NULL,
 assignments longtext NOT NULL,
 actor_id bigint(20) unsigned NOT NULL,
 updated_at datetime NOT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY teacher_day (teacher_id,school_day),
 KEY year_day (school_year,school_day)
 ) ENGINE=InnoDB $cc;");foreach($t as $table)if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;update_option('dgs_teacher_att_schema','1',false);update_option('dgs_teacher_selection_schema','1',false);
},20);
function dgs_ta_labels(){return ['present'=>'حاضر','absent'=>'غایب','late'=>'تأخیر','leave'=>'مرخصی','cancelled'=>'لغو جلسه'];}
// Teaching assignments identify eligible teachers, not their attendance dates.
function dgs_ta_plan($year,$day=''){
 global $wpdb;$e=dgs_edu_tables();$out=[];$rows=$wpdb->get_results($wpdb->prepare("SELECT a.teacher_id,c.title AS class_title,c.grade,s.title AS subject FROM {$e['teaching']} a JOIN {$e['classes']} c ON c.id=a.class_id JOIN {$e['subjects']} s ON s.id=a.subject_id JOIN {$wpdb->users} u ON u.ID=a.teacher_id WHERE c.school_year=%s AND c.active=1 ORDER BY u.display_name,c.grade,c.title,s.title",$year));
 foreach($rows as $r)if(dgs_edu_role($r->teacher_id,'dgs_teacher'))$out[(int)$r->teacher_id][]=['class'=>dgs_edu_grade_label($r->grade).' '.$r->class_title,'subject'=>$r->subject];return $out;
}
function dgs_ta_expected($year,$day){global $wpdb;$t=dgs_ta_tables();$out=[];foreach($wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['expected']} WHERE school_year=%s AND school_day=%s",$year,$day)) as $r)$out[(int)$r->teacher_id]=$r;return $out;}
function dgs_ta_selected($year,$day){return array_values(array_unique(array_merge(array_keys(dgs_ta_expected($year,$day)),array_keys(dgs_ta_index($year,$day)))));}
function dgs_ta_assignment_text($slots){$text=[];foreach($slots as $slot){$line=trim(($slot['subject']??$slot['activity']??'').' — '.($slot['class']??''),' —');if($line!=='')$text[]=$line;}return implode('، ',array_unique($text));}
function dgs_ta_select($d){
 if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','انتخاب دبیران فقط توسط مدیر و معاون است.');$year=is_string($d['year']??null)?dgs_edu_year($d['year']):'';$day=is_string($d['day']??null)?dgs_att_parse_day($d['day'],$year):false;$revision=$d['revision']??null;$raw=$d['selected_ids']??[];
 if(!$day||!is_scalar($revision)||!ctype_digit((string)$revision)||!is_array($raw)||count($raw)>500||get_option('dgs_teacher_selection_schema')!=='1')return dgs_edu_error('input','تاریخ و انتخاب دبیران معتبر نیست.');
 $ids=[];foreach($raw as $id){if(!is_scalar($id)||!ctype_digit((string)$id)||(int)$id<1)return dgs_edu_error('teacher','شناسه دبیر معتبر نیست.');$ids[]=(int)$id;}$ids=array_values(array_unique($ids));$plan=dgs_ta_plan($year);$expected=dgs_ta_expected($year,$day);$saved=dgs_ta_index($year,$day);
 foreach($ids as $id)if(!dgs_edu_role($id,'dgs_teacher')||(!isset($plan[$id])&&!isset($expected[$id])&&!isset($saved[$id])))return dgs_edu_error('teacher','دبیر باید تخصیص کلاس و درس در همین سال داشته باشد.');
 if(array_diff(array_keys($saved),$ids))return dgs_edu_error('saved','برای این دبیر حضور ثبت شده است؛ برای حفظ سوابق، او را از فهرست این روز حذف نکنید.');
 global $wpdb;$t=dgs_ta_tables();if($wpdb->query('START TRANSACTION')===false)return dgs_edu_error('write','شروع ذخیره ممکن نشد.');try{
 $old=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['days']} WHERE school_year=%s AND school_day=%s FOR UPDATE",$year,$day));if((int)$revision!==($old?(int)$old->revision:0))throw new RuntimeException('فهرست یا حضور این روز هم‌زمان تغییر کرده؛ صفحه را تازه کنید.');
 $ok=$old?$wpdb->update($t['days'],['revision'=>(int)$revision+1],['id'=>$old->id,'revision'=>(int)$revision]):$wpdb->insert($t['days'],['school_year'=>$year,'school_day'=>$day,'revision'=>1]);if(!$ok)throw new RuntimeException('این روز هم‌زمان تغییر کرد؛ دوباره تلاش کنید.');
 foreach($expected as $id=>$row)if(!in_array($id,$ids,true)&&$wpdb->delete($t['expected'],['id'=>$row->id])===false)throw new RuntimeException('تغییر فهرست ذخیره نشد.');
 foreach($ids as $id){if(isset($expected[$id]))continue;$slots=isset($saved[$id])?(json_decode($saved[$id]->planned,true)?:[]):($plan[$id]??[]);if($wpdb->insert($t['expected'],['school_year'=>$year,'school_day'=>$day,'teacher_id'=>$id,'assignments'=>wp_json_encode($slots),'actor_id'=>get_current_user_id(),'updated_at'=>current_time('mysql')])===false)throw new RuntimeException('انتخاب دبیر ذخیره نشد.');}
 if(!$wpdb->insert($t['audit'],['presence_id'=>0,'actor_id'=>get_current_user_id(),'before_data'=>wp_json_encode(['year'=>$year,'day'=>$day,'selected'=>array_keys($expected)]),'after_data'=>wp_json_encode(['year'=>$year,'day'=>$day,'selected'=>$ids]),'created_at'=>current_time('mysql')]))throw new RuntimeException('سابقه انتخاب ذخیره نشد.');
 if($wpdb->query('COMMIT')===false)throw new RuntimeException('ذخیره نهایی انجام نشد.');return 'دبیران مورد انتظار این روز ذخیره شدند؛ هنوز وضعیت حضور برایشان ثبت نشده است.';
 }catch(Throwable $e){$wpdb->query('ROLLBACK');return dgs_edu_error('write',$e->getMessage());}
}
function dgs_ta_rows($year,$day){global $wpdb;$t=dgs_ta_tables();return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['rows']} WHERE school_year=%s AND school_day=%s",$year,$day),OBJECT_K);}
function dgs_ta_index($year,$day){$out=[];foreach(dgs_ta_rows($year,$day) as $r)$out[(int)$r->teacher_id]=$r;return $out;}
function dgs_ta_revision($year,$day){global $wpdb;$t=dgs_ta_tables();return (int)$wpdb->get_var($wpdb->prepare("SELECT revision FROM {$t['days']} WHERE school_year=%s AND school_day=%s",$year,$day));}
function dgs_ta_save($d){
 if(!current_user_can('dgs_manage_education'))return dgs_edu_error('access','ثبت حضور دبیران فقط توسط مدیر و معاون است.');$year=is_string($d['year']??null)?dgs_edu_year($d['year']):'';$day=is_string($d['day']??null)?dgs_att_parse_day($d['day'],$year):false;$rev=$d['revision']??null;
 if(!$day||$day>dgs_att_today()||!is_scalar($rev)||!ctype_digit((string)$rev)||!is_array($d['teachers']??null)||count($d['teachers'])>500||get_option('dgs_teacher_att_schema')!=='1')return dgs_edu_error('input','روز معتبر حداکثر امروز و فرم معتبر انتخاب کنید.');
 $plan=dgs_ta_plan($year);$expected=dgs_ta_expected($year,$day);$saved=dgs_ta_index($year,$day);$selected=dgs_ta_selected($year,$day);$clean=[];foreach($d['teachers'] as $id=>$row){if(!ctype_digit((string)$id)||!in_array((int)$id,$selected,true)||!dgs_edu_role((int)$id,'dgs_teacher')||!is_array($row))return dgs_edu_error('teacher','دبیر معتبر انتخاب کنید.');foreach($row as $v)if(!is_scalar($v))return dgs_edu_error('input','ورودی معتبر نیست.');$status=(string)($row['status']??'');if($status==='')continue;if(!isset(dgs_ta_labels()[$status]))return dgs_edu_error('status','وضعیت معتبر نیست.');$arrival=dgs_edu_digits((string)($row['arrival']??''));$departure=dgs_edu_digits((string)($row['departure']??''));if(($arrival!==''&&!dgs_school_clock($arrival))||($departure!==''&&!dgs_school_clock($departure))||($departure!==''&&($arrival===''||$departure<$arrival)))return dgs_edu_error('clock','ساعت ورود و خروج ۲۴ساعته و به ترتیب باشند.');if(in_array($status,['absent','leave','cancelled'],true)&&($arrival!==''||$departure!==''))return dgs_edu_error('clock','برای غیبت، مرخصی یا لغو جلسه ساعت ورود و خروج وارد نکنید.');$note=sanitize_textarea_field((string)($row['note']??''));if(isset($saved[$id])&&$saved[$id]->status===$status&&$saved[$id]->arrival===$arrival&&$saved[$id]->departure===$departure&&$saved[$id]->note===$note)continue;if(dgs_edu_length($note)>1000||(!empty($saved[$id])&&$note==='')||(in_array($status,['leave','cancelled'],true)&&$note===''))return dgs_edu_error('note','برای اصلاح، مرخصی یا لغو جلسه توضیح بنویسید (حداکثر ۱۰۰۰ نویسه).');$clean[(int)$id]=['school_year'=>$year,'school_day'=>$day,'teacher_id'=>(int)$id,'status'=>$status,'arrival'=>$arrival,'departure'=>$departure,'note'=>$note,'planned'=>isset($saved[$id])?$saved[$id]->planned:(isset($expected[$id])?$expected[$id]->assignments:wp_json_encode($plan[$id]??[])),'actor_id'=>get_current_user_id(),'updated_at'=>current_time('mysql')];}
 if(!$clean&&$saved)return 'تغییر جدیدی وجود ندارد؛ اطلاعات قبلی محفوظ است.';if(!$clean)return dgs_edu_error('empty','حداقل یک وضعیت انتخاب کنید؛ بدون ثبت به معنی حاضر نیست.');
 global $wpdb;$t=dgs_ta_tables();$wpdb->query('START TRANSACTION');try{$old=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['days']} WHERE school_year=%s AND school_day=%s FOR UPDATE",$year,$day));if((int)$rev!==($old?(int)$old->revision:0))throw new RuntimeException('اطلاعات هم‌زمان تغییر کرده؛ صفحه را تازه کنید.');if($old)$ok=$wpdb->update($t['days'],['revision'=>(int)$rev+1],['id'=>$old->id,'revision'=>(int)$rev]);else $ok=$wpdb->insert($t['days'],['school_year'=>$year,'school_day'=>$day,'revision'=>1]);if(!$ok)throw new RuntimeException('این روز هم‌زمان تغییر کرد؛ دوباره بارگذاری کنید.');
 foreach($clean as $id=>$row){$before=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['rows']} WHERE teacher_id=%d AND school_day=%s FOR UPDATE",$id,$day));if($before&&$before->school_year!==$year)throw new RuntimeException('سابقه این تاریخ متعلق به سال دیگری است.');$ok=$before?$wpdb->update($t['rows'],$row,['id'=>$before->id]):$wpdb->insert($t['rows'],$row);if($ok===false)throw new RuntimeException('ذخیره حضور انجام نشد.');$rid=$before?$before->id:$wpdb->insert_id;if(!$wpdb->insert($t['audit'],['presence_id'=>$rid,'actor_id'=>get_current_user_id(),'before_data'=>wp_json_encode($before),'after_data'=>wp_json_encode($row),'created_at'=>current_time('mysql')]))throw new RuntimeException('سابقه تغییر ذخیره نشد.');}
 if($wpdb->query('COMMIT')===false)throw new RuntimeException('ذخیره نهایی نشد.');return 'حضور دبیران ذخیره شد؛ سوابق تغییرات محفوظ است.';
 }catch(Throwable $e){$wpdb->query('ROLLBACK');return dgs_edu_error('write',$e->getMessage());}
}
add_action('admin_post_dgs_teacher_attendance',function(){if(!current_user_can('dgs_manage_education')||($_SERVER['REQUEST_METHOD']??'')!=='POST')wp_die('دسترسی ندارید.','',array('response'=>403));check_admin_referer('dgs_teacher_attendance');$d=wp_unslash($_POST);$r=($d['operation']??'save')==='select'?dgs_ta_select($d):dgs_ta_save($d);$token=wp_generate_password(24,false,false);set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,['error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r],300);$year=is_string($d['year']??null)?dgs_edu_year($d['year']):dgs_edu_current_year();wp_safe_redirect(dgs_school_url('teacher_attendance',$year,['ta_day'=>is_scalar($d['day']??null)?$d['day']:'','dg_notice'=>$token]),303);exit;});
function dgs_ta_form($year,$day,$operation='save'){return '<form method="post" class="dgx-form" action="'.esc_url(admin_url('admin-post.php')).'">'.wp_nonce_field('dgs_teacher_attendance','_wpnonce',true,false).'<input type="hidden" name="operation" value="'.esc_attr($operation).'"><input type="hidden" name="action" value="dgs_teacher_attendance"><input type="hidden" name="year" value="'.esc_attr($year).'"><input type="hidden" name="day" value="'.esc_attr(dgs_att_jdate($day)).'"><input type="hidden" name="revision" value="'.dgs_ta_revision($year,$day).'">';}
function dgs_ta_inputs($id,$row=null){$prefix='teachers['.$id.']';$h=dgs_edu_select($prefix.'[status]','وضعیت',dgs_ta_labels(),$row?$row->status:'',false);foreach(['arrival'=>'ورود واقعی','departure'=>'خروج واقعی'] as $key=>$label)$h.='<label>'.$label.'<input type="time" name="'.$prefix.'['.$key.']" value="'.esc_attr($row?$row->$key:'').'"></label>';$h.='<label>توضیح / دلیل اصلاح<input name="'.$prefix.'[note]" maxlength="1000" value="'.esc_attr($row?$row->note:'').'"></label>';return $h;}
function dgs_edu_view_teacher_attendance($year){
 if(!current_user_can('dgs_manage_education'))return '';$day=dgs_att_parse_day(dgs_att_get('ta_day',dgs_att_jdate(dgs_att_today())),$year)?:dgs_att_parse_day(dgs_att_default_day($year),$year);$start=(new DateTimeImmutable($day))->modify('-'.(((int)(new DateTimeImmutable($day))->format('w')+1)%7).' days');$plan=dgs_ta_plan($year);$expected=dgs_ta_expected($year,$day);$saved=dgs_ta_index($year,$day);$ids=dgs_ta_selected($year,$day);$candidates=array_unique(array_merge(array_keys($plan),$ids));$teacher=absint(dgs_att_get('ta_teacher'));
 $h='<div class="dsp-school dgx-teacher-att">'.dgs_extension_title('حضور و غیاب دبیران','teacher_attendance','تاریخ را انتخاب کنید، دبیران مورد انتظار را تیک بزنید و سپس وضعیت حضور را ثبت کنید. کلاس‌ها و درس‌ها برای شناسایی نمایش داده می‌شوند.').'<section class="dsp-card"><form class="dgx-filter" method="get"><input type="hidden" name="dg_tab" value="teacher_attendance"><input type="hidden" name="dg_year" value="'.esc_attr($year).'"><label>تاریخ شمسی<input name="ta_day" dir="ltr" value="'.esc_attr(dgs_att_jdate($day)).'"></label>'.dgs_edu_select('ta_teacher','گزارش یک دبیر',dgs_edu_user_options('dgs_teacher'),$teacher,false).'<button class="dg-btn">نمایش روز / گزارش</button></form><nav class="dgx-week">';for($i=0;$i<7;$i++){$date=$start->modify('+'.$i.' days')->format('Y-m-d');if(!dgs_att_parse_day(dgs_att_jdate($date),$year))continue;$n=count(dgs_ta_selected($year,$date));$h.='<a '.($date===$day?'aria-current="page"':'').' href="'.esc_url(dgs_school_url('teacher_attendance',$year,['ta_day'=>dgs_att_jdate($date),'ta_teacher'=>$teacher])).'">'.esc_html(dgs_fa_digits(dgs_att_jdate($date))).'<small>'.esc_html(dgs_fa_digits($n)).' دبیر انتخاب‌شده</small></a>';}$h.='</nav></section>';
 $h.='<section class="dsp-card"><h3>۱. انتخاب دبیران مورد انتظار این روز</h3><p>فهرست از تخصیص کلاس و درس همین سال گرفته می‌شود. انتخاب‌نشدن یک دبیر به معنی غیبت او نیست.</p>'.dgs_ta_form($year,$day,'select').'<label>جست‌وجوی نام دبیر، کلاس یا درس<input type="search" data-ta-search placeholder="مثلاً ریاضی یا نام دبیر"></label><div class="dgx-teacher-picker">';
 foreach($candidates as $id){$u=get_userdata($id);if(!$u)continue;$slots=$plan[$id]??(isset($expected[$id])?(json_decode($expected[$id]->assignments,true)?:[]):(json_decode($saved[$id]->planned??'[]',true)?:[]));$description=dgs_ta_assignment_text($slots);$locked=isset($saved[$id]);$h.='<label class="dgx-teacher-option" data-ta-candidate data-ta-text="'.esc_attr($u->display_name.' '.$description).'"><input type="checkbox" name="selected_ids[]" value="'.(int)$id.'" '.checked(in_array((int)$id,$ids,true),true,false).' '.($locked?'disabled':'').'><span><strong>'.esc_html($u->display_name).'</strong><small>'.esc_html($description?:'تخصیص قبلی این روز').'</small>'.($locked?'<small>وضعیت حضور ثبت شده؛ در فهرست حفظ می‌شود.</small>':'').'</span></label>';if($locked)$h.='<input type="hidden" name="selected_ids[]" value="'.(int)$id.'">';}
 $h.='</div>';if(!$candidates)$h.='<p>هنوز کلاسی و درسی به دبیران این سال تخصیص نیافته است.</p>';$h.='<button class="dg-btn">ذخیره فهرست دبیران این روز</button></form></section>';
 $h.='<section class="dsp-card"><h3>۲. ثبت حضور روز '.esc_html(dgs_fa_digits(dgs_att_jdate($day))).'</h3><p>انتخاب دبیر در مرحله بالا، وضعیت حاضر ایجاد نمی‌کند؛ وضعیت هر نفر را جداگانه مشخص کنید.</p>';
 if(!$ids)$h.='<p>ابتدا دبیران مورد انتظار این روز را در بخش بالا انتخاب و ذخیره کنید.</p>';
 else{$h.=dgs_ta_form($year,$day).'<div class="dgx-table">'.dgs_edu_table_start(['دبیر / کلاس‌ها و درس‌ها','وضعیت و ساعت‌ها']);foreach($ids as $id){$u=get_userdata($id);if(!$u)continue;$slots=isset($saved[$id])?(json_decode($saved[$id]->planned,true)?:[]):(isset($expected[$id])?(json_decode($expected[$id]->assignments,true)?:[]):($plan[$id]??[]));$h.='<tr><td>'.esc_html($u->display_name).'<small>'.esc_html(dgs_ta_assignment_text($slots)).'</small>';if(isset($saved[$id]))$h.='<small>ثبت‌کننده: '.esc_html(dgs_mark_actor($saved[$id]->actor_id)).' · '.esc_html($saved[$id]->updated_at).'</small>';$h.='</td><td><div class="dgx-input-row">'.dgs_ta_inputs($id,$saved[$id]??null).'</div></td></tr>';}$h.='</tbody></table></div></div><button class="dg-btn" '.($day>dgs_att_today()?'disabled':'').'>ذخیره وضعیت‌های انتخاب‌شده</button></form>';}
 $h.='</section>';
 if($teacher&&dgs_edu_role($teacher,'dgs_teacher')){global $wpdb;$t=dgs_ta_tables();$end=$start->modify('+6 days')->format('Y-m-d');$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['rows']} WHERE teacher_id=%d AND school_year=%s AND school_day BETWEEN %s AND %s ORDER BY school_day",$teacher,$year,$start->format('Y-m-d'),$end));$h.='<section class="dsp-card"><h3>گزارش هفتهٔ '.esc_html(dgs_mark_actor($teacher)).'</h3>'.dgs_edu_table_start(['روز','وضعیت','ورود / خروج','توضیح / تاریخچه']);foreach($rows as $r){$h.='<tr><td>'.esc_html(dgs_fa_digits(dgs_att_jdate($r->school_day))).'</td><td>'.esc_html(dgs_ta_labels()[$r->status]).'</td><td>'.esc_html($r->arrival.' / '.$r->departure).'</td><td>'.esc_html($r->note).'<details><summary>تاریخچه تغییرات</summary>';$audit=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['audit']} WHERE presence_id=%d ORDER BY id DESC LIMIT 50",$r->id));foreach($audit as $a){$before=json_decode($a->before_data,true);$after=json_decode($a->after_data,true);$h.='<p>'.esc_html(dgs_mark_actor($a->actor_id).' · '.$a->created_at).' · '.esc_html(($before['status']??'بدون ثبت').' ← '.($after['status']??'')).'<br>'.esc_html('ورود: '.($before['arrival']??'—').' ← '.($after['arrival']??'—').' | خروج: '.($before['departure']??'—').' ← '.($after['departure']??'—').' | توضیح: '.($before['note']??'—').' ← '.($after['note']??'—')).'</p>';}$h.='</details></td></tr>';}$h.='</tbody></table></div><button type="button" class="dg-btn dg-btn-outline" data-dgx-print>چاپ گزارش</button></section>';}
 return $h.'</div>';
}
