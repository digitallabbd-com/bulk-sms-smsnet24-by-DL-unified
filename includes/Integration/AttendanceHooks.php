<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class AttendanceHooks {
    public function register(): void {
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_attendance_notice'])) return;

        add_action('smsnet24u_absent_users', [$this, 'on_absent'], 10, 2);

        // Example proxies
        add_action('wpsp_after_attendance_saved', function($date, $absentees){ do_action('smsnet24u_absent_users', $date, $absentees); }, 10, 2);
        add_action('smgt_after_attendance_marked', function($date, $absentees){ do_action('smsnet24u_absent_users', $date, $absentees); }, 10, 2);
        add_action('educare_attendance_saved',     function($date, $absentees){ do_action('smsnet24u_absent_users', $date, $absentees); }, 10, 2);
    }

    public function on_absent($date, $user_ids): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['absent_tpl'] ?? '');
        if (!$tpl || empty($user_ids) || !is_array($user_ids)) return;

        $sender = new Sender();
        foreach ($user_ids as $uid) {
            $phone = Phone::normalizeBD((string) get_user_meta((int) $uid, 'billing_phone', true));
            if (!$phone) continue;

            $msg = strtr($tpl, [
                '{name}' => get_the_author_meta('display_name', (int) $uid),
                '{date}' => is_string($date) ? $date : current_time('Y-m-d'),
            ]);
            $sender->queue($phone, $msg, ['event' => 'attendance_absent', 'user_id' => (int) $uid, 'date' => $date]);
        }
    }
}
