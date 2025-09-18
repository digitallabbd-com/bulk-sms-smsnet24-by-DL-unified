<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class WooCommerceHooks {
    public function register(): void {
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_wc']) || !class_exists('WooCommerce')) return;

        add_action('woocommerce_order_status_changed', [$this, 'on_status_change'], 10, 4);
        add_action('woocommerce_payment_complete', [$this, 'on_payment_complete'], 10, 1);
    }

    public function on_status_change($order_id, $from, $to, $order): void {
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_wc'])) return;

        $phone = Phone::normalizeBD((string) $order->get_billing_phone());
        if (!$phone) return;

        $msg = sprintf(__('Your order #%1$s is now %2$s. Thank you!', 'bulk-sms-smsnet24-by-dl-unified'),
            $order->get_order_number(),
            wc_get_order_status_name($to)
        );
        (new Sender())->queue($phone, $msg, ['event' => 'wc_status', 'order_id' => (int) $order_id]);
    }

    public function on_payment_complete($order_id): void {
        $order = wc_get_order($order_id);
        if (!$order) return;

        $phone = Phone::normalizeBD((string) $order->get_billing_phone());
        if (!$phone) return;

        $msg = sprintf(__('Payment received for order #%s. We’re processing it now.', 'bulk-sms-smsnet24-by-dl-unified'), $order->get_order_number());
        (new Sender())->queue($phone, $msg, ['event' => 'wc_payment', 'order_id' => (int) $order_id]);
    }
}
