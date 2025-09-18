<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class LearnPressHooks {
    public function register(): void {
        if (!defined('LEARNPRESS_VERSION')) return;
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_learnpress'])) return;

        add_action('learn_press_user_enrolled_course', [$this, 'on_enroll'], 10, 2);
        add_action('learn_press_user_finished_course', [$this, 'on_complete'], 10, 2);
    }

    public function on_enroll(int $course_id, int $user_id): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['lp_enroll_tpl'] ?? '');
        if (!$tpl) return;

        $phone = Phone::normalizeBD((string) get_user_meta($user_id, 'billing_phone', true));
        if (!$phone) return;

        $msg = strtr($tpl, [
            '{user_name}' => get_the_author_meta('display_name', $user_id),
            '{course_title}' => get_the_title($course_id),
        ]);
        (new Sender())->queue($phone, $msg, ['event' => 'lp_enroll', 'user_id' => $user_id, 'course_id' => $course_id]);
    }

    public function on_complete(int $course_id, int $user_id): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['lp_complete_tpl'] ?? '');
        if (!$tpl) return;

        $phone = Phone::normalizeBD((string) get_user_meta($user_id, 'billing_phone', true));
        if (!$phone) return;

        $msg = strtr($tpl, [
            '{user_name}' => get_the_author_meta('display_name', $user_id),
            '{course_title}' => get_the_title($course_id),
        ]);
        (new Sender())->queue($phone, $msg, ['event' => 'lp_complete', 'user_id' => $user_id, 'course_id' => $course_id]);
    }
}
