<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;
use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;

final class WPSchoolPressHooks {
    public function register(): void {
        add_action('wpsp_after_student_added', [$this, 'on_student_added'], 10, 1);
        add_action('wpsp_after_exam_result_published', [$this, 'on_result_published'], 10, 2);
        add_action('wpsp_after_attendance_saved', [$this, 'on_attendance_saved'], 10, 2);
    }

    public function on_student_added($student_id): void {
        $phone = Phone::normalizeBD(get_user_meta((int) $student_id, 'billing_phone', true));
        if (!$phone) return;

        $msg = sprintf(__('Welcome %s, your student profile has been created.', 'bulk-sms-smsnet24-by-dl-unified'),
            get_the_author_meta('display_name', (int) $student_id)
        );
        (new Sender())->queue($phone, $msg, ['event' => 'wpsp_student_added', 'student_id' => (int) $student_id]);
    }

    public function on_result_published($exam_id, $student_id): void {
        $opts = get_option(SettingsPage::OPT, []);
        if (empty($opts['enable_student_result'])) return;

        $tpl = $opts['student_result_tpl'] ?? '';
        if (!$tpl) return;

        $phone = Phone::normalizeBD(get_user_meta((int) $student_id, 'billing_phone', true));
        if (!$phone) return;

        $msg = strtr($tpl, [
            '{student_name}' => get_the_author_meta('display_name', (int) $student_id),
            '{exam_name}'    => get_the_title((int) $exam_id),
            '{result_status}'=> get_post_meta((int) $exam_id, 'result_status', true) ?: '',
        ]);
        (new Sender())->queue($phone, $msg, ['event' => 'wpsp_result_published', 'student_id' => (int) $student_id, 'exam_id' => (int) $exam_id]);
    }

    public function on_attendance_saved($date, $absentees): void {
        $opts = get_option(SettingsPage::OPT, []);
        if (empty($opts['enable_attendance_notice'])) return;

        $tpl = $opts['attendance_absent_tpl'] ?? '';
        if (!$tpl || empty($absentees) || !is_array($absentees)) return;

        $sender = new Sender();
        foreach ($absentees as $uid) {
            $phone = Phone::normalizeBD(get_user_meta((int) $uid, 'billing_phone', true));
            if (!$phone) continue;
            $msg = strtr($tpl, [
                '{name}' => get_the_author_meta('display_name', (int) $uid),
                '{date}' => is_string($date) ? $date : current_time('Y-m-d'),
            ]);
            $sender->queue($phone, $msg, ['event' => 'attendance_absent', 'user_id' => (int) $uid, 'date' => $date]);
        }
    }
}
