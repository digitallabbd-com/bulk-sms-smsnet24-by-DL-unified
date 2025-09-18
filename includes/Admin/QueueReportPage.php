<?php
namespace BulkSMS\SMSNET24\Unified\Admin;
 
final class QueueReportPage {
    public function register(): void {
        add_action('admin_menu', function () {
            add_submenu_page(
                'smsnet24u-settings',
                __('SMS Queue Report', 'bulk-sms-smsnet24-by-dl-unified'),
                __('SMS Queue Report', 'bulk-sms-smsnet24-by-dl-unified'),
                'manage_options',
                'smsnet24u-queue-report',
                [$this, 'render']
            );
        });
    }
 
    public function render(): void {
        echo '<div class="wrap"><h1>' . esc_html__('SMS Queue Report', 'bulk-sms-smsnet24-by-dl-unified') . '</h1>';
 
        if (class_exists('ActionScheduler')) {
            $actions = \ActionScheduler::store()->query_actions([
                'per_page' => 50,
                'status'   => 'pending',
                'hook'     => 'smsnet24u_send_async',
            ]);
            if ($actions) {
                echo '<table class="widefat"><thead><tr><th>ID</th><th>Scheduled</th><th>Args</th></tr></thead><tbody>';
                foreach ($actions as $id) {
                    $action = \ActionScheduler::store()->fetch_action($id);
                    echo '<tr><td>' . esc_html($id) . '</td><td>' . esc_html($action->get_schedule()->next()->format('Y-m-d H:i:s')) . '</td><td><pre>' . esc_html(print_r($action->get_args(), true)) . '</pre></td></tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>' . esc_html__('No pending SMS in queue.', 'bulk-sms-smsnet24-by-dl-unified') . '</p>';
            }
        } else {
            // Fallback: WP-Cron
            $crons = _get_cron_array();
            $found = false;
            foreach ($crons as $timestamp => $hooks) {
                if (isset($hooks['smsnet24u_send_async'])) {
                    if (!$found) {
                        echo '<table class="widefat"><thead><tr><th>Timestamp</th><th>Args</th></tr></thead><tbody>';
                        $found = true;
                    }
                    foreach ($hooks['smsnet24u_send_async'] as $sig => $data) {
                        echo '<tr><td>' . esc_html(date('Y-m-d H:i:s', $timestamp)) . '</td><td><pre>' . esc_html(print_r($data['args'], true)) . '</pre></td></tr>';
                    }
                }
            }
            if ($found) {
                echo '</tbody></table>';
            } else {
                echo '<p>' . esc_html__('No pending SMS in WP-Cron queue.', 'bulk-sms-smsnet24-by-dl-unified') . '</p>';
            }
        }
 
        echo '</div>';
    }
}