<?php
namespace BulkSMS\SMSNET24\Unified\Integration;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class BooklyHooks {
    public function register(): void {
        if (!defined('BOOKLY_VERSION')) return;
        $o = get_option(SettingsPage::OPT, []);
        if (empty($o['enable_bookly'])) return;

        add_action('bookly_new_booking', [$this, 'on_new_booking'], 10, 2);
    }

    public function on_new_booking($booking, $customer): void {
        $o = get_option(SettingsPage::OPT, []);
        $tpl = (string) ($o['bookly_new_tpl'] ?? '');
        if (!$tpl) return;

        $phoneRaw = '';
        if (is_object($customer) && isset($customer->phone)) {
            $phoneRaw = (string) $customer->phone;
        } elseif (is_object($customer) && isset($customer->wp_user_id)) {
            $phoneRaw = (string) get_user_meta((int) $customer->wp_user_id, 'billing_phone', true);
        }

        $phone = Phone::normalizeBD($phoneRaw);
        if (!$phone) return;

        $vars = [
            '{customer_name}' => is_object($customer) && isset($customer->full_name) ? (string) $customer->full_name : __('Customer', 'bulk-sms-smsnet24-by-dl-unified'),
            '{service_name}'  => is_object($booking) && isset($booking->service_name) ? (string) $booking->service_name : __('Service', 'bulk-sms-smsnet24-by-dl-unified'),
            '{service_date}'  => is_object($booking) && isset($booking->appointment_datetime) ? (string) $booking->appointment_datetime : '',
        ];
        (new Sender())->queue($phone, strtr($tpl, $vars), ['event' => 'bookly_new']);
    }
}
