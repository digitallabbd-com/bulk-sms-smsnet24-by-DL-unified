<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class DuesReminderScheduler {
    public function register(): void {
        add_action(SettingsPage::CRON_DUES, [$this, 'run']);
    }

    public function run(): void {
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_dues_reminder']) || !class_exists('WooCommerce')) return;

        $days = max(1, (int) ($o['dues_days'] ?? 7));
        $tpl  = (string) ($o['dues_tpl'] ?? '');
        if (!$tpl) return;

        $cutoff = strtotime("-{$days} days");
        $orders = wc_get_orders([
            'status' => ['pending', 'on-hold'],
            'date_created' => '<' . date('Y-m-d H:i:s', $cutoff),
            'limit' => -1,
        ]);

        $sender = new Sender();
        foreach ($orders as $order) {
            $phone = Phone::normalizeBD((string) $order->get_billing_phone());
            if (!$phone) continue;

            $msg = strtr($tpl, [
                '{name}'         => $order->get_formatted_billing_full_name(),
                '{due_amount}'   => wc_price($order->get_total(), ['currency' => $order->get_currency()]),
                '{due_date}'     => $order->get_date_created()->date('Y-m-d'),
                '{days_overdue}' => $days,
            ]);

            $sender->queue($phone, $msg, ['event' => 'dues_reminder', 'order_id' => (int) $order->get_id()]);
        }
    }
}
