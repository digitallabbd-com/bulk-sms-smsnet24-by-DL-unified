<?php
namespace BulkSMS\SMSNET24\Unified\Admin;
 
use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;
use function add_action;
use function admin_url;
use function current_time;
use function esc_attr;
use function esc_html_e;
use function get_option;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function submit_button;
use function wp_die;
use function wp_nonce_field;
use function wp_redirect;
 
/**
* Attendance Notice admin page.
*/
final class AttendanceNoticePage {
    /**
     * Register menu and form handler.
     */
    public function register(): void {
        add_action('admin_menu', function () {
            add_submenu_page(
                'smsnet24u-settings',
                __('Attendance Notice', 'bulk-sms-smsnet24-by-dl-unified'),
                __('Attendance Notice', 'bulk-sms-smsnet24-by-dl-unified'),
                'manage_options',
                'smsnet24u-attendance',
                [$this, 'render']
            );
        });
        add_action('admin_post_smsnet24u_send_attendance', [$this, 'handle']);
    }
 
    /**
     * Render the Attendance Notice form.
     */
    public function render(): void {
        if (! current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        }
 
        $notice = sanitize_text_field($_GET['notice'] ?? '');
        $today  = current_time('Y-m-d');
        $o      = get_option(SettingsPage::OPT, []);
        $max    = (int) ($o['max_message_len'] ?? 480);
        ?>
        <div class="wrap">
          <h1><?php esc_html_e('Attendance Notice', 'bulk-sms-smsnet24-by-dl-unified'); ?></h1>
 
          <?php if ($notice === 'sent'): ?>
            <div class="notice notice-success">
              <p><?php esc_html_e('Notices sent and logged.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p>
            </div>
          <?php elseif ($notice === 'invalid'): ?>
            <div class="notice notice-error">
              <p><?php esc_html_e('Provide date, message, and at least one valid number.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p>
            </div>
          <?php endif; ?>
 
          <form method="post" action="<?php echo esc_attr(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('smsnet24u_attendance'); ?>
            <input type="hidden" name="action" value="smsnet24u_send_attendance" />
 
            <table class="form-table">
              <tr>
                <th><label for="date"><?php esc_html_e('Date', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th>
                <td><input type="date" id="date" name="date" value="<?php echo esc_attr($today); ?>" required></td>
              </tr>
              <tr>
                <th><label for="message"><?php esc_html_e('Message', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th>
                <td>
                  <textarea
                    id="message"
                    name="message"
                    rows="4"
                    class="large-text"
                    maxlength="<?php echo $max; ?>"
                    placeholder="<?php esc_attr_e('Absent alert for {name} on {date}.', 'bulk-sms-smsnet24-by-dl-unified'); ?>"
                    required
                  ></textarea>
                  <p class="description">
                    <?php esc_html_e('Use {name} and {date} placeholders.', 'bulk-sms-smsnet24-by-dl-unified'); ?>
                  </p>
                </td>
              </tr>
              <tr>
                <th><label for="numbers"><?php esc_html_e('Numbers (comma-separated)', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th>
                <td><input type="text" id="numbers" name="numbers" class="regular-text" placeholder="8801XXXXXXXXX,8801YYYYYYYYY" required></td>
              </tr>
            </table>
 
            <?php submit_button(__('Send Absence Notices', 'bulk-sms-smsnet24-by-dl-unified')); ?>
          </form>
        </div>
        <?php
    }
 
    /**
     * Handle form submission, send SMS immediately and log.
     */
    public function handle(): void {
        if (! current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        }
 
        check_admin_referer('smsnet24u_attendance');
 
        $date    = sanitize_text_field($_POST['date'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $numbers = sanitize_text_field($_POST['numbers'] ?? '');
 
        if (! $date || ! $message || ! $numbers) {
            wp_redirect(add_query_arg('notice', 'invalid', admin_url('admin.php?page=smsnet24u-attendance')));
            exit;
        }
 
        $sender = new Sender();
        $raw_numbers = array_filter(array_map('trim', explode(',', $numbers)));
        $base_tpl = $message;
 
        foreach ($raw_numbers as $raw) {
            $to = Phone::normalizeBD($raw);
            if (! $to) {
                continue;
            }
 
            // Replace placeholders
            $msg = strtr($base_tpl, [
                '{name}' => __('Student/Employee', 'bulk-sms-smsnet24-by-dl-unified'),
                '{date}' => $date,
            ]);
 
            $meta = [
                'event' => 'attendance_manual',
                'date'  => $date,
            ];
 
            // Schedule via WP-Cron (for background)
            $sender->queue($to, $msg, $meta);
 
            // Immediately send and log (for real-time feedback and logs)
            $sender->handle([
                'to'      => $to,
                'message' => $msg,
                'meta'    => $meta,
            ]);
        }
 
        wp_redirect(add_query_arg('notice', 'sent', admin_url('admin.php?page=smsnet24u-attendance')));
        exit;
    }
}
 