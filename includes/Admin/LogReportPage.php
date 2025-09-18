<?php
namespace BulkSMS\SMSNET24\Unified\Admin;
 
use \wpdb;
 
final class LogReportPage {
    public function register(): void {
        add_action('admin_menu', function () {
            add_submenu_page(
                'smsnet24u-settings',
                __('SMS Log Report', 'bulk-sms-smsnet24-by-dl-unified'),
                __('SMS Log Report', 'bulk-sms-smsnet24-by-dl-unified'),
                'manage_options',
                'smsnet24u-log-report',
                [$this, 'render']
            );
        });
    }
 
    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        }
 
        global $wpdb;
        $table = $wpdb->prefix . 'smsnet24_logs';
        $mobile_filter = sanitize_text_field($_GET['mobile'] ?? '');
 
        // build query
        $where = [];
        if ($mobile_filter) {
            $like = '%' . $wpdb->esc_like($mobile_filter) . '%';
            $where[] = $wpdb->prepare("mobile LIKE %s", $like);
        }
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $rows = $wpdb->get_results(
            "SELECT * FROM {$table} {$where_sql} ORDER BY time DESC LIMIT 200",
            ARRAY_A
        );
        ?>
        <div class="wrap">
          <h1><?php esc_html_e('SMS Log Report', 'bulk-sms-smsnet24-by-dl-unified'); ?></h1>
 
          <form method="get" action="">
            <input type="hidden" name="page" value="smsnet24u-log-report"/>
            <p>
              <label><?php esc_html_e('Mobile filter:', 'bulk-sms-smsnet24-by-dl-unified'); ?>
                <input type="text" name="mobile" value="<?php echo esc_attr($mobile_filter); ?>" placeholder="88017xxxxxxx"/>
              </label>
              <?php submit_button(__('Filter'), 'secondary', '', false); ?>
            </p>
          </form>
 
          <table class="fixed widefat striped">
            <thead>
              <tr>
                <th><?php esc_html_e('Date/Time', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                <th><?php esc_html_e('Mobile', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                <th><?php esc_html_e('Event', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                <th><?php esc_html_e('Status', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                <th><?php esc_html_e('Response Code', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                <th><?php esc_html_e('Message', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr><td colspan="6"><?php esc_html_e('No log entries found.', 'bulk-sms-smsnet24-by-dl-unified'); ?></td></tr>
              <?php else: ?>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?php echo esc_html($r['time']); ?></td>
                    <td><?php echo esc_html($r['mobile']); ?></td>
                    <td><?php echo esc_html($r['event']); ?></td>
                    <td><?php echo $r['status'] ? esc_html__('Success','bulk-sms-smsnet24-by-dl-unified') : esc_html__('Fail','bulk-sms-smsnet24-by-dl-unified'); ?></td>
                    <td><?php echo esc_html($r['response_code']); ?></td>
                    <td style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo esc_html($r['message']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php
    }
}
 