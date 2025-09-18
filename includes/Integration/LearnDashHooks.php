<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class LearnDashHooks {
    public function register(): void {
        if (!defined('LEARNDASH_VERSION')) return;
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_learndash'])) return;

        add_action('ld_added_course_access', [$this, 'on_enroll'], 10, 2);
        add_action('ld_course_completed', [$this, 'on_complete'], 10, 1);
    }

    public function on_enroll(int $user_id, int $course_id): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['ld_enroll_tpl'] ?? '');
        if (!$tpl) return;

        $phone = Phone::normalizeBD((string) get_user_meta($user_id, 'billing_phone', true));
        if (!$phone) return;

        $msg = strtr($tpl, [
            '{user_name}' => get_the_author_meta('display_name', $user_id),
            '{course_title}' => get_the_title($course_id),
        ]);
        (new Sender())->queue($phone, $msg, ['event' => 'ld_enroll', 'user_id' => $user_id, 'course_id' => $course_id]);
    }

    public function on_complete(array $data): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['ld_complete_tpl'] ?? '');
        if (!$tpl) return;

        $user_id = isset($data['user']->ID) ? (int) $data['user']->ID : 0;
        $course_id = isset($data['course']->ID) ? (int) $data['course']->ID : 0;
        if (!$user_id || !$course_id) return;

        $phone = Phone::normalizeBD((string) get_user_meta($user_id, 'billing_phone', true));
        if (!$phone) return;

        $msg = strtr($tpl, [
            '{user_name}' => get_the_author_meta('display_name', $user_id),
            '{course_title}' => get_the_title($course_id),
        ]);
        (new Sender())->queue($phone, $msg, ['event' => 'ld_complete', 'user_id' => $user_id, 'course_id' => $course_id]);
    }
}
