<?php
namespace BulkSMS\SMSNET24\Unified\Admin;
 
final class UserGuidePage {
    public function register(): void {
        add_action('admin_menu', function () {
            add_submenu_page(
                'smsnet24u-settings',
                __('User Guide', 'bulk-sms-smsnet24-by-dl-unified'),
                __('User Guide', 'bulk-sms-smsnet24-by-dl-unified'),
                'read',
                'smsnet24u-user-guide',
                [$this, 'render']
            );
        });
    }
 
    public function render(): void {
        echo '<div class="wrap"><h1>' . esc_html__('User Guide', 'bulk-sms-smsnet24-by-dl-unified') . '</h1>';
        echo '<p>' . esc_html__('This guide explains how to use the Bulk SMS – SMSNet24 Unified plugin as an operator.', 'bulk-sms-smsnet24-by-dl-unified') . '</p>';
        echo '<ol>';
        echo '<li>' . esc_html__('Role SMS Notice: Select a role, type your message, optionally add extra numbers, and click Send.', 'bulk-sms-smsnet24-by-dl-unified') . '</li>';
        echo '<li>' . esc_html__('Attendance Notice: Set the date, type your message with {name} and {date}, paste numbers, and click Send.', 'bulk-sms-smsnet24-by-dl-unified') . '</li>';
        echo '<li>' . esc_html__('Automatic SMS: Triggered by WooCommerce, Dokan, LMS, MemberPress, Bookly, and school events when enabled.', 'bulk-sms-smsnet24-by-dl-unified') . '</li>';
        echo '</ol></div>';
    }
}