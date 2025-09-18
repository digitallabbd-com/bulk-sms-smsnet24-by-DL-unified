<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class StudentResultHooks {
    public function register(): void {
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_student_result'])) return;

        add_action('smsnet24u_result_published', [$this, 'on_result'], 10, 2);

        // Example proxies (map from school plugins to generic action)
        add_action('wpsp_after_exam_result_published', function($exam_id, $student_id) { do_action('smsnet24u_result_published', $exam_id, $student_id); }, 10, 2);
        add_action('smgt_after_publish_exam_result',  function($exam_id, $student_id) { do_action('smsnet24u_result_published', $exam_id, $student_id); }, 10, 2);
        add_action('educare_result_published',        function($exam_id, $student_id) { do_action('smsnet24u_result_published', $exam_id, $student_id); }, 10, 2);
    }

    public function on_result($exam_id, $student_id): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['result_tpl'] ?? '');
        if (!$tpl) return;

        $phone = Phone::normalizeBD((string) get_user_meta((int) $student_id, 'billing_phone', true));
        if (!$phone) return;

        $msg = strtr($tpl, [
            '{student_name}' => get_the_author_meta('display_name', (int) $student_id),
            '{exam_name}'    => get_the_title((int) $exam_id),
            '{result_status}'=> (string) get_post_meta((int) $exam_id, 'result_status', true),
        ]);
        (new Sender())->queue($phone, $msg, ['event' => 'student_result', 'student_id' => (int) $student_id, 'exam_id' => (int) $exam_id]);
    }
}
