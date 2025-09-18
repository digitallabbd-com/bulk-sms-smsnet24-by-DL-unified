<?php
namespace BulkSMS\SMSNET24\Unified\Admin;

use BulkSMS\SMSNET24\Unified\Services\Sender;
use BulkSMS\SMSNET24\Unified\Support\Phone;

final class AttendanceNoticePage {
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

    public function render(): void {
        if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        $notice = sanitize_text_field($_GET['notice'] ?? '');
        $today  = current_time('Y-m-d');
        $o = get_option(SettingsPage::OPT, []);
        $max = (int) ($o['max_message_len'] ?? 480);
        ?>
        <div class="wrap">
          <h1><?php esc_html_e('Attendance Notice', 'bulk-sms-smsnet24-by-dl-unified'); ?></h1>
          <?php if ($notice === 'sent'): ?>
            <div class="notice notice-success"><p><?php esc_html_e('Absence notices queued.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p></div>
          <?php elseif ($notice === 'invalid'): ?>
            <div class="notice notice-error"><p><?php esc_html_e('Provide date, message, and at least one valid number.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p></div>
          <?php endif; ?>

          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('smsnet24u_attendance'); ?>
            <input type="hidden" name="action" value="smsnet24u_send_attendance" />
            <table class="form-table">
              <tr><th><label for="date"><?php esc_html_e('Date', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th><td><input type="date" name="date" id="date" value="<?php echo esc_attr($today); ?>" required></td></tr>
              <tr><th><label for="message"><?php esc_html_e('Message', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th><td>
                  <textarea name="message" id="message" rows="4" class="large-text" maxlength="<?php echo (int) $max; ?>" placeholder="<?php esc_attr_e('Absent alert for {name} on {date}.', 'bulk-sms-smsnet24-by-dl-unified'); ?>" required></textarea>
                  <p class="description"><?php esc_html_e('Use {name} and {date} placeholders.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p>
              </td></tr>
              <tr><th><label for="numbers"><?php esc_html_e('Numbers (comma-separated)', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th><td>
                  <input type="text" name="numbers" id="numbers" class="regular-text" placeholder="8801XXXXXXXXX, 8801YYYYYYYYY" required />
                  <p class="description"><?php esc_html_e('Bangladeshi numbers only in 8801XXXXXXXXX format.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p>
              </td></tr>
            </table>
            <?php submit_button(__('Send Absence Notices', 'bulk-sms-smsnet24-by-dl-unified')); ?>
          </form>
        </div>
        <?php
    }

    public function handle(): void {
        if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        check_admin_referer('smsnet24u_attendance');

        $date    = sanitize_text_field($_POST['date'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $numbers = sanitize_text_field($_POST['numbers'] ?? '');

        $o = get_option(SettingsPage::OPT, []);
        $max = (int) ($o['max_message_len'] ?? 480);
        $message = mb_substr($message, 0, $max);

        $targets = [];
        foreach (array_filter(array_map('trim', explode(',', $numbers))) as $raw) {
            $n = Phone::normalizeBD($raw);
            if ($n) $targets[] = $n;
        }
        $targets = array_values(array_unique($targets));

        if (!$date || !$message || empty($targets)) {
            wp_redirect(add_query_arg('notice', 'invalid', admin_url('admin.php?page=smsnet24u-attendance')));
            exit;
        }

        $sender = new Sender();
        foreach ($targets as $to) {
            $msg = strtr($message, ['{name}' => __('Student/Employee', 'bulk-sms-smsnet24-by-dl-unified'), '{date}' => $date]);
            $sender->queue($to, $msg, ['event' => 'attendance_manual', 'date' => $date]);
        }

        wp_redirect(add_query_arg('notice', 'sent', admin_url('admin.php?page=smsnet24u-attendance')));
        exit;
    }
}
