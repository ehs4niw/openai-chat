<?php
if (!defined('ABSPATH')) exit;

function dgs_rc_feedback_table() { global $wpdb; return $wpdb->prefix.'dgs_report_feedback'; }
add_action('init', function () {
    if (get_option('dgs_rc_schema') === '1') return;
    global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
    $table = dgs_rc_feedback_table(); $cc = $wpdb->get_charset_collate();
    dbDelta("CREATE TABLE $table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        student_id bigint(20) unsigned NOT NULL,
        school_year varchar(9) NOT NULL,
        period varchar(7) NOT NULL,
        author_id bigint(20) unsigned NOT NULL,
        note text NOT NULL,
        revision bigint(20) unsigned NOT NULL DEFAULT 1,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY student_year_period (student_id,school_year,period)
    ) ENGINE=InnoDB $cc;");
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table) update_option('dgs_rc_schema', '1', false);
}, 16);

function dgs_rc_allowed($student, $year) {
    if (!is_user_logged_in() || !dgs_edu_year($year) || !dgs_edu_role($student, 'dgs_student')) return false;
    if (current_user_can('dgs_manage_education')) return true;
    $uid = get_current_user_id();
    if (dgs_edu_role($uid, 'dgs_student')) return (int)$student === $uid;
    if (!dgs_edu_role($uid, 'dgs_counselor')) return false;
    global $wpdb; $e = dgs_edu_tables();
    return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$e['counseling']} WHERE student_id=%d AND school_year=%s AND counselor_id=%d", $student, $year, $uid));
}
function dgs_rc_can_feedback($student, $year) {
    return dgs_rc_allowed($student, $year) && (current_user_can('dgs_manage_education') || dgs_edu_role(get_current_user_id(), 'dgs_counselor'));
}
function dgs_rc_term($period) { $m = (int)substr($period, 5, 2); return $m >= 7 && $m <= 10 ? '1' : (($m >= 11 || $m <= 3) ? '2' : '3'); }
function dgs_rc_terms() { return array('1'=>'ترم اول', '2'=>'ترم دوم', '3'=>'تابستان'); }
function dgs_rc_mean($sum, $count) { return $count ? (int)round($sum/$count, 0, PHP_ROUND_HALF_UP) : null; }

// A report is visible only when every required course has a published, locked snapshot.
// This same predicate is used for averages, ranking, history and exports.
function dgs_rc_month($records, $expected, $states) {
    $class = 0; $scores = array(); $complete = (bool)$records; $updated = '';
    foreach ($records as $row) {
        if ($class && $class !== (int)$row->class_id) $complete = false;
        $class = (int)$row->class_id; $subject = (int)$row->subject_id;
        $scores[$subject] = $row; $expected[$subject] = true;
        if (!(int)$row->published || ($states[$class.':'.$subject.':'.$row->period] ?? '') === 'open') $complete = false;
        if ($row->updated_at > $updated) $updated = $row->updated_at;
    }
    foreach ($expected as $subject=>$unused) if (!isset($scores[$subject])) $complete = false;
    $sum = 0; foreach ($scores as $row) $sum += dgs_mg_cents($row->score);
    return array('complete'=>$complete, 'class_id'=>$class, 'rows'=>$complete ? $scores : array(), 'sum'=>$complete ? $sum : 0, 'count'=>$complete ? count($scores) : 0, 'average'=>$complete ? dgs_rc_mean($sum,count($scores)) : null, 'updated_at'=>$complete ? $updated : '');
}
function dgs_rc_catalog($year) {
    global $wpdb; $e = dgs_edu_tables(); $m = dgs_mg_tables();
    $classes = array(); foreach (dgs_edu_classes($year, true) as $class) $classes[(int)$class->id] = $class;
    $expected = array();
    foreach ($wpdb->get_results($wpdb->prepare("SELECT a.class_id,a.subject_id FROM {$e['teaching']} a JOIN {$e['classes']} c ON c.id=a.class_id JOIN {$e['subjects']} s ON s.id=a.subject_id WHERE c.school_year=%s AND s.active=1", $year)) as $a) $expected[(int)$a->class_id][(int)$a->subject_id] = true;
    $states = array(); foreach ($wpdb->get_results($wpdb->prepare("SELECT class_id,subject_id,period,status FROM {$m['states']} WHERE school_year=%s", $year)) as $s) $states[$s->class_id.':'.$s->subject_id.':'.$s->period] = $s->status;
    return array('classes'=>$classes, 'expected'=>$expected, 'states'=>$states);
}
function dgs_rc_bundle($student, $year) {
    if (!dgs_rc_allowed($student, $year)) return dgs_edu_error('access', 'اجازه مشاهده این کارنامه را ندارید.');
    global $wpdb; $e = dgs_edu_tables(); $g = dgs_grade_table(); $catalog = dgs_rc_catalog($year);
    $rows = $wpdb->get_results($wpdb->prepare("SELECT g.*,s.title AS subject FROM $g g JOIN {$e['classes']} c ON c.id=g.class_id JOIN {$e['subjects']} s ON s.id=g.subject_id WHERE g.student_id=%d AND c.school_year=%s ORDER BY g.period DESC,s.title,g.id", $student, $year));
    $groups = array(); foreach ($rows as $r) if (dgs_grade_period($r->period, $year)) $groups[$r->period][] = $r;
    $months = array(); foreach ($groups as $p=>$records) $months[$p] = dgs_rc_month($records, $catalog['expected'][(int)$records[0]->class_id] ?? array(), $catalog['states']);
    $current = (int)$wpdb->get_var($wpdb->prepare("SELECT class_id FROM {$e['enrollments']} WHERE student_id=%d AND school_year=%s", $student, $year));
    return array('student'=>get_userdata($student), 'year'=>$year, 'months'=>$months, 'catalog'=>$catalog, 'current_class'=>$current);
}
function dgs_rc_ranking($bundle, $period) {
    $month = $bundle['months'][$period] ?? null;
    if (!$month || !$month['complete']) return array('rank'=>null, 'count'=>0);
    $catalog = $bundle['catalog']; $class = $catalog['classes'][$month['class_id']] ?? null;
    if (!$class) return array('rank'=>null, 'count'=>0);
    global $wpdb; $e = dgs_edu_tables(); $g = dgs_grade_table();
    $rows = $wpdb->get_results($wpdb->prepare("SELECT g.* FROM $g g JOIN {$e['classes']} c ON c.id=g.class_id WHERE c.school_year=%s AND c.grade=%d AND g.period=%s ORDER BY g.student_id,g.id", $bundle['year'], $class->grade, $period));
    $groups = array(); foreach ($rows as $r) $groups[(int)$r->student_id][] = $r;
    $rank = 1; $count = 0;
    foreach ($groups as $records) {
        $peer = dgs_rc_month($records, $catalog['expected'][(int)$records[0]->class_id] ?? array(), $catalog['states']);
        if (!$peer['complete']) continue;
        $count++;
        // Compare unrounded rational means; equal averages receive equal competition ranks.
        if ($peer['sum']*$month['count'] > $month['sum']*$peer['count']) $rank++;
    }
    return array('rank'=>$rank, 'count'=>$count);
}
function dgs_rc_feedback($student, $year, $period) {
    global $wpdb; $t = dgs_rc_feedback_table();
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE student_id=%d AND school_year=%s AND period=%s", $student, $year, $period));
}
function dgs_rc_feedback_save($d) {
    foreach ($d as $value) if (!is_scalar($value)) return dgs_edu_error('input', 'درخواست معتبر نیست.');
    $student = absint($d['student_id'] ?? 0); $year = dgs_edu_year($d['year'] ?? '');
    $period = $year ? dgs_grade_period($d['period'] ?? '', $year) : ''; $note = sanitize_textarea_field($d['note'] ?? '');
    if (!$year || !$period || !dgs_rc_can_feedback($student, $year)) return dgs_edu_error('access', 'اجازه ثبت بازخورد این دانش‌آموز را ندارید.');
    if (!$note || dgs_edu_length($note) > 2000) return dgs_edu_error('note', 'بازخورد بین یک تا ۲۰۰۰ نویسه باشد.');
    return dgs_mg_locked(function () use ($student,$year,$period,$note,$d) {
        $bundle = dgs_rc_bundle($student,$year); $month = is_wp_error($bundle) ? null : ($bundle['months'][$period] ?? null);
        if (!$month || !$month['complete']) return dgs_edu_error('unpublished', 'بازخورد فقط برای کارنامه کامل و منتشرشده ثبت می‌شود.');
        global $wpdb; $old = dgs_rc_feedback($student,$year,$period);
        if ((string)($d['revision'] ?? '') !== (string)($old->revision ?? 0)) return dgs_edu_error('stale', 'بازخورد تغییر کرده؛ صفحه را تازه کنید.');
        $row = array('student_id'=>$student,'school_year'=>$year,'period'=>$period,'author_id'=>get_current_user_id(),'note'=>$note,'revision'=>(int)($old->revision ?? 0)+1,'updated_at'=>dgs_mg_utc());
        $t = dgs_rc_feedback_table(); $ok = $old ? $wpdb->update($t,$row,array('id'=>$old->id,'revision'=>$old->revision)) : $wpdb->insert($t,$row);
        if (!$ok) throw new RuntimeException('بازخورد ذخیره نشد؛ دوباره تلاش کنید.');
        dgs_mg_audit(array('year'=>$year,'class_id'=>$month['class_id'],'subject_id'=>0,'period'=>$period),'report_feedback',0,$old,$row,'بازخورد کارنامه دانش‌آموز '.$student);
        return 'بازخورد ذخیره شد و در کارنامه دانش‌آموز نمایش داده می‌شود.';
    });
}
add_action('admin_post_dgs_report_feedback', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_user_logged_in()) wp_die('دسترسی مجاز نیست.','',array('response'=>403));
    check_admin_referer('dgs_report_feedback','rc_nonce'); $d = wp_unslash($_POST); $r = dgs_rc_feedback_save($d);
    $token = wp_generate_password(24,false,false); set_transient('dgs_edu_flash_'.get_current_user_id().'_'.$token,array('error'=>is_wp_error($r),'text'=>is_wp_error($r)?$r->get_error_message():$r),300);
    $year = is_string($d['year'] ?? null) ? dgs_edu_year($d['year']) : dgs_edu_current_year();
    $period = is_string($d['period'] ?? null) ? dgs_grade_period($d['period'],$year) : '';
    $student = is_scalar($d['student_id'] ?? null) ? absint($d['student_id']) : 0;
    wp_safe_redirect(add_query_arg('dg_notice',$token,dgs_rc_url($year,$period,$student)),303); exit;
});
