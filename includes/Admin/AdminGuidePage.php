<?php
namespace BulkSMS\SMSNET24\Unified\Admin;
 
final class AdminGuidePage {
    public function register(): void {
        add_action('admin_menu', function () {
            add_submenu_page(
                'smsnet24u-settings',
                __('Admin Guide', 'bulk-sms-smsnet24-by-dl-unified'),
                __('Admin Guide', 'bulk-sms-smsnet24-by-dl-unified'),
                'manage_options',
                'smsnet24u-admin-guide',
                [$this, 'render']
            );
        });
    }
 
    public function render(): void {
        echo '<div class="wrap"><h1>' . esc_html__('Admin Guide', 'bulk-sms-smsnet24-by-dl-unified') . '</h1>';
        echo '<p>' . esc_html__('This guide explains how to configure and manage the plugin.', 'bulk-sms-smsnet24-by-dl-unified') . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__('Go to SMSNet24 Settings to set API Base URL, API Key, Sender ID, and enable features.', 'bulk-sms-smsnet24-by-dl-unified') . '</li>';
        echo '<li>' . esc_html__('Edit message templates for each feature.', 'bulk-sms-smsnet24-by-dl-unified') . '</li>';
        echo '<li>' . esc_html__('Use Debug Logging on staging to troubleshoot.', 'bulk-sms-smsnet24-by-dl-unified') . '</li>';
        echo '<li>' . esc_html__('Monitor SMS Queue Report to see pending and sent messages.', 'bulk-sms-smsnet24-by-dl-unified') . '</li>';
        echo '</ul></div>';
    }
}