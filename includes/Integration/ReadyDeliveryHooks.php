<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class ReadyDeliveryHooks {
    public function register(): void {
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_ready_delivery']) || !class_exists('WooCommerce')) return;

        add_action('woocommerce_order_status_ready-delivery', [$this, 'on_ready_delivery'], 10, 1);
    }

    public function on_ready_delivery($order_id): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['ready_tpl'] ?? '');
        if (!$tpl) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        $phone = Phone::normalizeBD((string) $order->get_billing_phone());
        if (!$phone) return;

        $msg = strtr($tpl, [
            '{name}' => $order->get_formatted_billing_full_name(),
            '{order_number}' => $order->get_order_number(),
            '{delivery_date}' => date('Y-m-d'),
        ]);

        (new Sender())->queue($phone, $msg, ['event' => 'ready_delivery', 'order_id' => (int) $order_id]);
    }
}
