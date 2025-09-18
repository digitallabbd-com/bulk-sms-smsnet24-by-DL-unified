<?php
namespace BulkSMS\SMSNET24\Unified\Admin;

final class SettingsPage {
    public const OPT       = 'smsnet24_unified_settings';
    public const CRON_DUES = 'smsnet24u_check_overdue_dues';
    public const CRON_SEND = 'smsnet24u_send_async';

    public function register(): void {
        add_action('admin_init',    [$this, 'registerSettings']);
        add_action('admin_menu',    [$this, 'menu']);
        register_activation_hook(SMSNET24U_FILE, [$this, 'onActivate']);
        register_deactivation_hook(SMSNET24U_FILE, [$this, 'onDeactivate']);
    }

   public function onActivate(): void {
        // schedule daily duesif (!wp_next_scheduled(self::CRON_DUES)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', self::CRON_DUES);
        }
        // create logs table$this->createLogTable();

    private function createLogTable(): void {
        global $wpdb;
        $table   = $wpdb->prefix . 'smsnet24_logs';
        $charset = $wpdb->get_charset_collate();
        $sql = "
        CREATE TABLE {$table} (
          id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          time          DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
          mobile        VARCHAR(20)    NOT NULL,
          message       TEXT           NOT NULL,
          event         VARCHAR(50)    NOT NULL,
          status        TINYINT(1)     NOT NULL,
          response_code SMALLINT       NOT NULL,
          response_body LONGTEXT       NOT NULL,
          meta          LONGTEXT       NULL,
          PRIMARY KEY  (id),
          KEY mobile_idx (mobile)
        ) {$charset};
        ";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function onDeactivate(): void {
        $ts = wp_next_scheduled(self::CRON_DUES);
        if ($ts) {
            wp_unschedule_event($ts, self::CRON_DUES);
        }
    }

    public function registerSettings(): void {
        register_setting(self::OPT, self::OPT, [
            'type'              => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default'           => $this->defaults(),
        ]);
    }

    private function defaults(): array {
        return [
            'api_base'            => 'https://sms.apinet.club/sendSms',
            'user_id'             => '',
            'user_password'       => '',
            'route_id'            => '',
            'sms_type_id'         => '',
            'sms_category_name'   => '',
            'return_type'         => 'String',
            'campaignType'        => 'T',
            'campaignId'          => '',
            'refOrderNo'          => '',
            'debug'               => 0,
            'max_message_len'     => 480,

            // feature toggles & templates...
            'enable_wc'             => 1,
            'enable_ready_delivery' => 0,
            'ready_tpl'             => 'Hi {name}, order #{order_number} will be ready on {delivery_date}.',
            'enable_dues_reminder'  => 0,
            'dues_days'             => 7,
            'dues_tpl'              => 'Dear {name}, your payment of {due_amount} (due {due_date}) is {days_overdue} days overdue.',
            'enable_dokan'          => 0,
            'vendor_onboard_tpl'    => 'Welcome to {site_name}! Your store "{store_name}" is live.',
            'vendor_new_order_tpl'  => 'New order #{order_number}, items {items_count}, total {order_total}. Buyer: {customer_name}, {customer_phone}.',
            'vendor_status_tpl'     => 'Order #{order_number} is now {order_status}.',
            'enable_learndash'      => 0,
            'ld_enroll_tpl'         => 'Hi {user_name}, enrolled in {course_title}.',
            'ld_complete_tpl'       => 'Congrats {user_name}, completed {course_title}.',
            'enable_learnpress'     => 0,
            'lp_enroll_tpl'         => 'Hi {user_name}, enrolled in {course_title}.',
            'lp_complete_tpl'       => 'Congrats {user_name}, completed {course_title}.',
            'enable_tutor'          => 0,
            'tutor_enroll_tpl'      => 'Hi {user_name}, enrolled in {course_title}.',
            'tutor_complete_tpl'    => 'Congrats {user_name}, completed {course_title}.',
            'enable_memberpress'    => 0,
            'mp_txn_tpl'            => 'Hi {user_name}, membership {membership} is active. Thank you!',
            'enable_bookly'         => 0,
            'bookly_new_tpl'        => 'Hi {customer_name}, your booking on {service_date} for {service_name} is confirmed.',
            'enable_student_result' => 0,
            'result_tpl'            => 'Hi {student_name}, result for {exam_name}: {result_status}.',
            'enable_attendance_notice' => 0,
            'absent_tpl'               => 'Absent alert for {name} on {date}.',
        ];
    }

    public function sanitize($in): array {
        $out = $this->defaults();

        // API Base URL
        $url = trim((string) ($in['api_base'] ?? ''));
        $p   = wp_parse_url($url);
        if ($p && !empty($p['scheme']) && strtolower($p['scheme']) === 'https' && !empty($p['host'])) {
            $base = 'https://' . $p['host'];
            if (!empty($p['path'])) {
                $base .= untrailingslashit($p['path']);
            }
            $out['api_base'] = $base;
        }

        // Credentials
        $out['user_id']       = sanitize_text_field((string) ($in['user_id'] ?? ''));
        $out['user_password'] = sanitize_text_field((string) ($in['user_password'] ?? ''));

        // Optional HTTP API params
        $out['route_id']          = sanitize_text_field((string) ($in['route_id'] ?? ''));
        $out['sms_type_id']       = sanitize_text_field((string) ($in['sms_type_id'] ?? ''));
        $out['sms_category_name'] = sanitize_text_field((string) ($in['sms_category_name'] ?? ''));
        $out['return_type']       = sanitize_text_field((string) ($in['return_type'] ?? 'String'));
        $out['campaignType']      = in_array(($in['campaignType'] ?? 'T'), ['T','P'], true) ? $in['campaignType'] : 'T';
        $out['campaignId']        = sanitize_text_field((string) ($in['campaignId'] ?? ''));
        $out['refOrderNo']        = sanitize_text_field((string) ($in['refOrderNo'] ?? ''));

        // Debug & length
        $out['debug']           = !empty($in['debug']) ? 1 : 0;
        $out['max_message_len'] = min(1000, max(120, (int) ($in['max_message_len'] ?? 480)));

        // Feature toggles
        $flags = [
            'enable_wc','enable_ready_delivery','enable_dues_reminder',
            'enable_dokan','enable_learndash','enable_learnpress','enable_tutor',
            'enable_memberpress','enable_bookly','enable_student_result','enable_attendance_notice'
        ];
        foreach ($flags as $f) {
            $out[$f] = !empty($in[$f]) ? 1 : 0;
        }
        $out['dues_days'] = max(1, (int) ($in['dues_days'] ?? 7));

        // Templates
        $tpls = [
            'ready_tpl','dues_tpl',
            'vendor_onboard_tpl','vendor_new_order_tpl','vendor_status_tpl',
            'ld_enroll_tpl','ld_complete_tpl',
            'lp_enroll_tpl','lp_complete_tpl',
            'tutor_enroll_tpl','tutor_complete_tpl',
            'mp_txn_tpl','bookly_new_tpl','result_tpl','absent_tpl'
        ];
        foreach ($tpls as $k) {
            $out[$k] = wp_kses_post((string) ($in[$k] ?? $out[$k]));
        }

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
    }

    public function render(): void {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'bulk-sms-smsnet24-by-dl-unified'));
        }
        $o = get_option(self::OPT, $this->defaults());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('SMSNet24 – Settings', 'bulk-sms-smsnet24-by-dl-unified'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::OPT); ?>

                <h2><?php esc_html_e('Core HTTP API Configuration', 'bulk-sms-smsnet24-by-dl-unified'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('API Endpoint (POST URL)', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="url" name="<?php echo esc_attr(self::OPT); ?>[api_base]" value="<?php echo esc_attr($o['api_base']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('User ID (email)', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="email" name="<?php echo esc_attr(self::OPT); ?>[user_id]" value="<?php echo esc_attr($o['user_id']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('User Password', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPT); ?>[user_password]" value="<?php echo esc_attr($o['user_password']); ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Route ID', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPT); ?>[route_id]" value="<?php echo esc_attr($o['route_id']); ?>" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('SMS Type ID', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPT); ?>[sms_type_id]" value="<?php echo esc_attr($o['sms_type_id']); ?>" class="small-text">
                        <p class="description"><?php esc_html_e('1=Text,2=Flash,3/5=Unicode,6=Unicode Flash,7=Wap Push', 'bulk-sms-smsnet24-by-dl-unified'); ?></p></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('SMS Category Name', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPT); ?>[sms_category_name]" value="<?php echo esc_attr($o['sms_category_name']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Return Type', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPT); ?>[return_type]">
                                <option value="String" <?php selected($o['return_type'], 'String'); ?>>String</option>
                                <option value="JSON"   <?php selected($o['return_type'], 'JSON'); ?>>JSON</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Campaign Type', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPT); ?>[campaignType]">
                                <option value="T" <?php selected($o['campaignType'], 'T'); ?>>Transactional (T)</option>
                                <option value="P" <?php selected($o['campaignType'], 'P'); ?>>Promotional (P)</option>
                            </select>
                            <p class="description"><?php esc_html_e('If Promotional, enter Campaign ID below.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Campaign ID', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPT); ?>[campaignId]" value="<?php echo esc_attr($o['campaignId']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Reference Order No.', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="text" name="<?php echo esc_attr(self::OPT); ?>[refOrderNo]" value="<?php echo esc_attr($o['refOrderNo']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Debug Logging', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPT); ?>[debug]" value="1" <?php checked(!empty($o['debug'])); ?>> <?php esc_html_e('Enable (staging)', 'bulk-sms-smsnet24-by-dl-unified'); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Max Message Length', 'bulk-sms-smsnet24-by-dl-unified'); ?></th>
                        <td><input type="number" min="120" max="1000" name="<?php echo esc_attr(self::OPT); ?>[max_message_len]" value="<?php echo (int) $o['max_message_len']; ?>" class="small-text">
                        <p class="description"><?php esc_html_e('Truncate SMS to this many chars.', 'bulk-sms-smsnet24-by-dl-unified'); ?></p></td>
                    </tr>
                </table>

                <?php submit_button(__('Save Settings', 'bulk-sms-smsnet24-by-dl-unified')); ?>
            </form>
        </div>
        <?php
    }
}