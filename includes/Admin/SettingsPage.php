<?php
namespace BulkSMS\SMSNET24\Unified\Admin;

use BulkSMS\SMSNET24\Unified\Support\Logger;

final class SettingsPage {
    public const OPT = 'smsnet24_unified_settings';
    public const CRON_DUES = 'smsnet24u_check_overdue_dues';
    public const CRON_SEND = 'smsnet24u_send_async';

    public function register(): void {
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_menu', [$this, 'menu']);
        register_activation_hook(SMSNET24U_FILE, [$this, 'onActivate']);
        register_deactivation_hook(SMSNET24U_FILE, [$this, 'onDeactivate']);
    }

    public function onActivate(): void {
        if (!wp_next_scheduled(self::CRON_DUES)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_DUES);
        }
    }

    public function onDeactivate(): void {
        $ts = wp_next_scheduled(self::CRON_DUES);
        if ($ts) wp_unschedule_event($ts, self::CRON_DUES);
    }

    public function registerSettings(): void {
        register_setting(self::OPT, self::OPT, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => $this->defaults(),
        ]);
    }

    private function defaults(): array {
        return [
            'api_base' => '',
            'api_key'  => '',
            'sender'   => '',
            'debug'    => 0,
            'success_status' => 'OK',
            'max_message_len' => 480,

            'enable_wc' => 1,
            'enable_ready_delivery' => 0,
            'enable_dues_reminder' => 0,
            'dues_days' => 7,

            'enable_dokan' => 0,
            'vendor_onboard_tpl' => 'Welcome to {site_name}! Your store "{store_name}" is live.',
            'vendor_new_order_tpl' => 'New order #{order_number}, items {items_count}, total {order_total}. Buyer: {customer_name}, {customer_phone}.',
            'vendor_status_tpl' => 'Order #{order_number} is now {order_status}.',

            'enable_learndash' => 0,
            'ld_enroll_tpl' => 'Hi {user_name}, enrolled in {course_title}.',
            'ld_complete_tpl' => 'Congrats {user_name}, completed {course_title}.',

            'enable_learnpress' => 0,
            'lp_enroll_tpl' => 'Hi {user_name}, enrolled in {course_title}.',
            'lp_complete_tpl' => 'Congrats {user_name}, completed {course_title}.',

            'enable_tutor' => 0,
            'tutor_enroll_tpl' => 'Hi {user_name}, enrolled in {course_title}.',
            'tutor_complete_tpl' => 'Congrats {user_name}, completed {course_title}.',

            'enable_memberpress' => 0,
            'mp_txn_tpl' => 'Hi {user_name}, membership {membership} is active. Thank you!',

            'enable_bookly' => 0,
            'bookly_new_tpl' => 'Hi {customer_name}, your booking on {service_date} for {service_name} is confirmed.',

            'enable_student_result' => 0,
            'result_tpl' => 'Hi {student_name}, result for {exam_name}: {result_status}.',

            'enable_attendance_notice' => 0,
            'absent_tpl' => 'Absent alert for {name} on {date}.',

            'ready_tpl' => 'Hi {name}, order #{order_number} will be ready on {delivery_date}.',
        ];
    }

    public function sanitize($in): array {
        $out = $this->defaults();
        $url = trim((string) ($in['api_base'] ?? ''));
        $p = wp_parse_url($url);
        if ($p && !empty($p['scheme']) && strtolower($p['scheme']) === 'https' && !empty($p['host'])) {
            $base = 'https://' . $p['host'];
            if (!empty($p['port'])) $base .= ':' . (int) $p['port'];
            if (!empty($p['path'])) $base .= untrailingslashit($p['path']);
            $out['api_base'] = $base;
        }

        $out['api_key']  = sanitize_text_field((string) ($in['api_key'] ?? ''));
        $out['sender']   = sanitize_text_field((string) ($in['sender'] ?? ''));
        $out['debug']    = !empty($in['debug']) ? 1 : 0;
        $out['success_status'] = sanitize_text_field((string) ($in['success_status'] ?? 'OK'));
        $out['max_message_len'] = min(1000, max(120, (int) ($in['max_message_len'] ?? 480)));

        $flags = [
            'enable_wc','enable_ready_delivery','enable_dues_reminder',
            'enable_dokan','enable_learndash','enable_learnpress','enable_tutor',
            'enable_memberpress','enable_bookly','enable_student_result','enable_attendance_notice'
        ];
        foreach ($flags as $f) $out[$f] = !empty($in[$f]) ? 1 : 0;

        $out['dues_days'] = max(1, (int) ($in['dues_days'] ?? 7));

        $tpls = [
            'vendor_onboard_tpl','vendor_new_order_tpl','vendor_status_tpl',
            'ld_enroll_tpl','ld_complete_tpl',
            'lp_enroll_tpl','lp_complete_tpl',
            'tutor_enroll_tpl','tutor_complete_tpl',
            'mp_txn_tpl','bookly_new_tpl',
            'result_tpl','absent_tpl','ready_tpl'
        ];
        foreach ($tpls as $k) $out[$k] = wp_kses_post((string) ($in[$k] ?? $out[$k]));

        return $out;
    }

    public function menu(): void {
        add_menu_page(
            __('SMSNet24 Settings', 'bulk-sms-smsnet24-by-dl-unified'),
            __('SMSNet24', 'bulk-sms-smsnet24-by-dl-unified'),
            'manage_options',
            'smsnet24u-settings',
            [$this, 'render'],
            'dashicons-email-alt2',
            56
        );

        add_submenu_page('smsnet24u-settings', __('Role SMS Notice', 'bulk-sms-smsnet24-by-dl-unified'), __('Role SMS Notice', 'bulk-sms-smsnet24-by-dl-unified'), 'manage_options', 'smsnet24u-role', '__return_null');
        add_submenu_page('smsnet24u-settings', __('Attendance Notice', 'bulk-sms-smsnet24-by-dl-unified'), __('Attendance Notice', 'bulk-sms-smsnet24-by-dl-unified'), 'manage_options', 'smsnet24u-attendance', '__return_null');
    }

    public function render(): void {
        if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        $o = get_option(self::OPT, $this->defaults());
        ?>
        <div class="wrap">
          <h1><?php esc_html_e('SMSNet24 – Settings', 'bulk-sms-smsnet24-by-dl-unified'); ?></h1>
          <form method="post" action="options.php">
            <?php settings_fields(self::OPT); ?>
            <h2><?php esc_html_e('API configuration', 'bulk-sms-smsnet24-by-dl-unified'); ?></h2>
            <table class="form-table">
              <tr><th><?php esc_html_e('API Base URL (https)', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><input type="url" name="<?php echo esc_attr(self::OPT); ?>[api_base]" value="<?php echo esc_attr($o['api_base']); ?>" class="regular-text" required></td></tr>
              <tr><th><?php esc_html_e('API Key', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><input type="password" name="<?php echo esc_attr(self::OPT); ?>[api_key]" value="<?php echo esc_attr($o['api_key']); ?>" class="regular-text" required></td></tr>
              <tr><th><?php esc_html_e('Sender ID', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><input type="text" name="<?php echo esc_attr(self::OPT); ?>[sender]" value="<?php echo esc_attr($o['sender']); ?>" class="regular-text" required></td></tr>
              <tr><th><?php esc_html_e('Success status keyword', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><input type="text" name="<?php echo esc_attr(self::OPT); ?>[success_status]" value="<?php echo esc_attr($o['success_status']); ?>" class="regular-text"></td></tr>
              <tr><th><?php esc_html_e('Debug logging', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[debug]" value="1" <?php checked(!empty($o['debug'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></td></tr>
              <tr><th><?php esc_html_e('Max message length', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><input type="number" min="120" max="1000" name="<?php echo esc_attr(self::OPT); ?>[max_message_len]" value="<?php echo (int) $o['max_message_len']; ?>" class="small-text" /></td></tr>
            </table>

            <h2><?php esc_html_e('WooCommerce & operational', 'bulk-sms-smsnet24-by-dl-unified'); ?></h2>
            <table class="form-table">
              <tr><th><?php esc_html_e('Enable WooCommerce', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_wc]" value="1" <?php checked(!empty($o['enable_wc'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></td></tr>
              <tr><th><?php esc_html_e('Ready-for-delivery', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_ready_delivery]" value="1" <?php checked(!empty($o['enable_ready_delivery'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label><br><textarea name="<?php echo esc_attr(self::OPT); ?>[ready_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['ready_tpl']); ?></textarea></td></tr>
              <tr><th><?php esc_html_e('Dues reminders (daily)', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_dues_reminder]" value="1" <?php checked(!empty($o['enable_dues_reminder'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label> &nbsp; <?php esc_html_e('Days overdue:', 'bulk-sms-smsnet24-by-dl-unified'); ?> <input type="number" min="1" name="<?php echo esc_attr(self::OPT); ?>[dues_days]" class="small-text" value="<?php echo (int) $o['dues_days']; ?>" /><br><textarea name="<?php echo esc_attr(self::OPT); ?>[dues_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['dues_tpl']); ?></textarea></td></tr>
            </table>

            <h2><?php esc_html_e('Dokan', 'bulk-sms-smsnet24-by-dl-unified'); ?></h2>
            <table class="form-table">
              <tr><th><?php esc_html_e('Enable Dokan', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_dokan]" value="1" <?php checked(!empty($o['enable_dokan'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></td></tr>
              <tr><th><?php esc_html_e('Vendor onboarding template', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><textarea name="<?php echo esc_attr(self::OPT); ?>[vendor_onboard_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['vendor_onboard_tpl']); ?></textarea></td></tr>
              <tr><th><?php esc_html_e('Vendor new order template', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><textarea name="<?php echo esc_attr(self::OPT); ?>[vendor_new_order_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['vendor_new_order_tpl']); ?></textarea></td></tr>
              <tr><th><?php esc_html_e('Vendor status template', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><textarea name="<?php echo esc_attr(self::OPT); ?>[vendor_status_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['vendor_status_tpl']); ?></textarea></td></tr>
            </table>

            <h2><?php esc_html_e('LMS & membership', 'bulk-sms-smsnet24-by-dl-unified'); ?></h2>
            <table class="form-table">
              <tr><th><?php esc_html_e('LearnDash', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_learndash]" value="1" <?php checked(!empty($o['enable_learndash'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label><br><textarea name="<?php echo esc_attr(self::OPT); ?>[ld_enroll_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['ld_enroll_tpl']); ?></textarea><br><textarea name="<?php echo esc_attr(self::OPT); ?>[ld_complete_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['ld_complete_tpl']); ?></textarea></td></tr>

              <tr><th><?php esc_html_e('LearnPress', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_learnpress]" value="1" <?php checked(!empty($o['enable_learnpress'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label><br><textarea name="<?php echo esc_attr(self::OPT); ?>[lp_enroll_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['lp_enroll_tpl']); ?></textarea><br><textarea name="<?php echo esc_attr(self::OPT); ?>[lp_complete_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['lp_complete_tpl']); ?></textarea></td></tr>

              <tr><th><?php esc_html_e('Tutor LMS', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_tutor]" value="1" <?php checked(!empty($o['enable_tutor'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label><br><textarea name="<?php echo esc_attr(self::OPT); ?>[tutor_enroll_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['tutor_enroll_tpl']); ?></textarea><br><textarea name="<?php echo esc_attr(self::OPT); ?>[tutor_complete_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['tutor_complete_tpl']); ?></textarea></td></tr>

              <tr><th><?php esc_html_e('MemberPress', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_memberpress]" value="1" <?php checked(!empty($o['enable_memberpress'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label><br><textarea name="<?php echo esc_attr(self::OPT); ?>[mp_txn_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['mp_txn_tpl']); ?></textarea></td></tr>
            </table>

            <h2><?php esc_html_e('Booking & school', 'bulk-sms-smsnet24-by-dl-unified'); ?></h2>
            <table class="form-table">
              <tr><th><?php esc_html_e('Bookly', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_bookly]" value="1" <?php checked(!empty($o['enable_bookly'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label><br><textarea name="<?php echo esc_attr(self::OPT); ?>[bookly_new_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['bookly_new_tpl']); ?></textarea></td></tr>

              <tr><th><?php esc_html_e('Student result published', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_student_result]" value="1" <?php checked(!empty($o['enable_student_result'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label><br><textarea name="<?php echo esc_attr(self::OPT); ?>[result_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['result_tpl']); ?></textarea></td></tr>

              <tr><th><?php esc_html_e('Attendance absence', 'bulk-sms-smsnet24-by-dl-unified'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[enable_attendance_notice]" value="1" <?php checked(!empty($o['enable_attendance_notice'])); ?> /> <?php esc_html_e('Enable', 'bulk-sms-smsnet24-by-dl-unified'); ?></label><br><textarea name="<?php echo esc_attr(self::OPT); ?>[absent_tpl]" rows="2" class="large-text"><?php echo esc_textarea($o['absent_tpl']); ?></textarea></td></tr>
            </table>

            <?php submit_button(); ?>
          </form>
        </div>
        <?php
    }
}
