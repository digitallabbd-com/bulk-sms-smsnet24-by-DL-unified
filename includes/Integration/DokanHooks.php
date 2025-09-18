<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class DokanHooks {
    public function register(): void {
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_dokan']) || !function_exists('dokan')) return;

        add_action('dokan_new_seller_created', [$this, 'on_vendor_created'], 10, 1);
        add_action('woocommerce_checkout_order_created', [$this, 'on_parent_order_created'], 20, 1);
        add_action('woocommerce_order_status_changed', [$this, 'on_vendor_status_change'], 20, 4);
    }

    public function on_vendor_created(int $vendor_id): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['vendor_onboard_tpl'] ?? '');
        if (!$tpl) return;

        $phone = '';
        if (function_exists('dokan_get_store_info')) {
            $info = dokan_get_store_info($vendor_id);
            $phone = isset($info['phone']) ? (string) $info['phone'] : '';
        }
        if (!$phone) $phone = (string) get_user_meta($vendor_id, 'billing_phone', true);
        $to = Phone::normalizeBD($phone);
        if (!$to) return;

        $store = function_exists('dokan_get_store_info') ? dokan_get_store_info($vendor_id) : [];
        $msg = strtr($tpl, [
            '{store_name}' => isset($store['store_name']) ? (string) $store['store_name'] : '',
            '{site_name}'  => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        ]);
        (new Sender())->queue($to, $msg, ['event' => 'dokan_onboard', 'vendor_id' => (int) $vendor_id]);
    }

    public function on_parent_order_created(\WC_Order $parent): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['vendor_new_order_tpl'] ?? '');
        if (!$tpl) return;

        foreach ($parent->get_children() as $child_id) {
            $sub = wc_get_order($child_id);
            if (!$sub instanceof \WC_Order) continue;
            if (!function_exists('dokan_get_seller_id_by_order')) continue;

            $vid = (int) dokan_get_seller_id_by_order($sub->get_id());
            if ($vid <= 0) continue;

            $phone = '';
            if (function_exists('dokan_get_store_info')) {
                $info = dokan_get_store_info($vid);
                $phone = isset($info['phone']) ? (string) $info['phone'] : '';
            }
            if (!$phone) $phone = (string) get_user_meta($vid, 'billing_phone', true);
            $to = Phone::normalizeBD($phone);
            if (!$to) continue;

            $items_count = 0;
            foreach ($sub->get_items() as $it) $items_count += (int) $it->get_quantity();
            $vars = [
                '{order_number}'   => $sub->get_order_number(),
                '{items_count}'    => (string) $items_count,
                '{order_total}'    => wc_price($sub->get_total(), ['currency' => $sub->get_currency()]),
                '{customer_name}'  => $sub->get_formatted_billing_full_name(),
                '{customer_phone}' => (string) $sub->get_billing_phone(),
                '{site_name}'      => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
            ];
            (new Sender())->queue($to, strtr($tpl, $vars), ['event' => 'dokan_vendor_new', 'order_id' => (int) $sub->get_id(), 'vendor_id' => $vid]);
        }
    }

    public function on_vendor_status_change($order_id, $from, $to, $order): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['vendor_status_tpl'] ?? '');
        if (!$tpl || !function_exists('dokan_get_seller_id_by_order')) return;

        $vid = (int) dokan_get_seller_id_by_order($order_id);
        if ($vid <= 0) return;

        $phone = '';
        if (function_exists('dokan_get_store_info')) {
            $info = dokan_get_store_info($vid);
            $phone = isset($info['phone']) ? (string) $info['phone'] : '';
        }
        if (!$phone) $phone = (string) get_user_meta($vid, 'billing_phone', true);
        $toPhone = Phone::normalizeBD($phone);
        if (!$toPhone) return;

        $items_count = 0;
        foreach ($order->get_items() as $it) $items_count += (int) $it->get_quantity();

        $vars = [
            '{order_number}'   => $order->get_order_number(),
            '{items_count}'    => (string) $items_count,
            '{order_total}'    => wc_price($order->get_total(), ['currency' => $order->get_currency()]),
            '{customer_name}'  => $order->get_formatted_billing_full_name(),
            '{customer_phone}' => (string) $order->get_billing_phone(),
            '{site_name}'      => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
            '{order_status}'   => wc_get_order_status_name($to),
        ];
        (new Sender())->queue($toPhone, strtr($tpl, $vars), ['event' => 'dokan_vendor_status', 'order_id' => (int) $order_id, 'vendor_id' => $vid]);
    }
}
