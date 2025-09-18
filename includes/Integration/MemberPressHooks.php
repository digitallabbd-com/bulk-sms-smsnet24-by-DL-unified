<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class MemberPressHooks {
    public function register(): void {
        if (!defined('MEPR_VERSION')) return;
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_memberpress'])) return;

        add_action('mepr-event-transaction-completed', [$this, 'on_txn_completed'], 10, 1);
    }

    public function on_txn_completed($event): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['mp_txn_tpl'] ?? '');
        if (!$tpl) return;

        $txn = is_object($event) ? $event->get_data() : null;
        if (!is_object($txn) || empty($txn->user_id) || empty($txn->product_id)) return;

        $user_id = (int) $txn->user_id;
        $product = get_post((int) $txn->product_id);

        $phone = Phone::normalizeBD((string) get_user_meta($user_id, 'billing_phone', true));
        if (!$phone) return;

        $msg = strtr($tpl, [
            '{user_name}' => get_the_author_meta('display_name', $user_id),
            '{membership}' => $product ? $product->post_title : __('membership', 'bulk-sms-smsnet24-by-dl-unified'),
        ]);
        (new Sender())->queue($phone, $msg, ['event' => 'mp_txn', 'user_id' => $user_id, 'product_id' => (int) $txn->product_id]);
    }
}
