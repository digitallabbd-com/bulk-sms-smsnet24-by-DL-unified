<?php
namespace BulkSMS\SMSNET24\Unified\Admin;
 
use BulkSMS\SMSNET24\Unified\Services\Sender;
use function current_user_can;
use function get_users;
use function get_user_meta;
use function sanitize_text_field;
use function wp_die;
use function wp_redirect;
use function add_action;
use function admin_url;
use function array_unique;
use function sanitize_textarea_field;
 
final class RoleNoticePage {
    public function register(): void {
        add_action('admin_menu', function () {
            add_submenu_page(
                'smsnet24u-settings',
                __('Role SMS Notice', 'bulk-sms-smsnet24-by-dl-unified'),
                __('Role SMS Notice', 'bulk-sms-smsnet24-by-dl-unified'),
                'manage_options',
                'smsnet24u-role',
                [$this, 'render']
            );
        });
        add_action('admin_post_smsnet24u_send_role', [$this, 'handle']);
    }
 
    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        }
        $roles = wp_roles()->roles;
        $notice = sanitize_text_field($_GET['notice'] ?? '');
        $o = get_option(SettingsPage::OPT, []);
        $max = (int) ($o['max_message_len'] ?? 480);
        ?>
        <div class="wrap">
          <h1><?php esc_html_e('Role SMS Notice', 'bulk-sms-smsnet24-by-dl-unified'); ?></h1>
          <?php if ($notice === 'sent'): ?>
            <div class="notice notice-success"><p><?php esc_html_e('Messages sent and logged.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p></div>
          <?php elseif ($notice === 'missing'): ?>
            <div class="notice notice-error"><p><?php esc_html_e('Role and message are required.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p></div>
          <?php endif; ?>
 
          <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('smsnet24u_role'); ?>
            <input type="hidden" name="action" value="smsnet24u_send_role" />
            <table class="form-table">
              <tr>
                <th><label for="role"><?php esc_html_e('Select role', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th>
                <td>
                  <select name="role" id="role" required>
                    <?php foreach ($roles as $key => $role): ?>
                      <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($role['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
              </tr>
              <tr>
                <th><label for="message"><?php esc_html_e('Message', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th>
                <td>
                  <textarea name="message" id="message" rows="4" class="large-text" maxlength="<?php echo $max; ?>" required></textarea>
                  <p class="description"><?php printf(esc_html__('Max %d chars.', 'bulk-sms-smsnet24-by-dl-unified'), $max); ?></p>
                </td>
              </tr>
              <tr>
                <th><label for="extra"><?php esc_html_e('Extra numbers', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></th>
                <td>
                  <input type="text" name="extra" id="extra" class="regular-text" placeholder="8801XXXXXXXXX,8801YYYYYYYYY">
                  <p class="description"><?php esc_html_e('Comma-separated BD numbers.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p>
                </td>
              </tr>
            </table>
            <?php submit_button(__('Send SMS', 'bulk-sms-smsnet24-by-dl-unified')); ?>
          </form>
        </div>
        <?php
    }
 
    public function handle(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        }
        check_admin_referer('smsnet24u_role');
 
        $role    = sanitize_text_field($_POST['role'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $extra   = sanitize_text_field($_POST['extra'] ?? '');
 
        if (!$role || !$message) {
            wp_redirect(add_query_arg('notice', 'missing', admin_url('admin.php?page=smsnet24u-role')));
            exit;
        }
 
        $sender = new Sender();
        $numbers = [];
 
        $users = get_users(['role'=>$role,'fields'=>['ID']]);
        foreach ($users as $u) {
            $phone = sanitize_text_field(get_user_meta($u->ID,'billing_phone',true));
            if ($pn = \BulkSMS\SMSNET24\Unified\Support\Phone::normalizeBD($phone)) {
                $numbers[] = $pn;
            }
        }
        if ($extra) {
            foreach (explode(',', $extra) as $raw) {
                if ($pn = \BulkSMS\SMSNET24\Unified\Support\Phone::normalizeBD(trim($raw))) {
                    $numbers[] = $pn;
                }
            }
        }
        $numbers = array_unique($numbers);
 
        foreach ($numbers as $to) {
            $meta = ['event'=>'role_notice','role'=>$role];
            // schedule
            $sender->queue($to, $message, $meta);
            // immediate send + log
            $sender->handle(['to'=>$to,'message'=>$message,'meta'=>$meta]);
        }
 
        wp_redirect(add_query_arg('notice','sent', admin_url('admin.php?page=smsnet24u-role')));
        exit;
    }
}