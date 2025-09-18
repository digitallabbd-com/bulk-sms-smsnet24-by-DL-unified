<?php
namespace BulkSMS\SMSNET24\Unified\Services;
 
use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Support\Logger;
use function add_action;
use function get_option;
use function wp_schedule_single_event;
use function current_time;
use function wp_json_encode;
use function wp_remote_post;
use function is_wp_error;
use function wp_remote_retrieve_response_code;
use function wp_remote_retrieve_body;
 
final class Sender {
    public function register(): void {
        add_action(SettingsPage::CRON_SEND, [$this, 'handle'], 10, 1);
    }
 
    public function queue(string $to, string $message, array $meta = []): void {
        $o      = get_option(SettingsPage::OPT, []);
        $maxLen = min(1000, max(120, (int) ($o['max_message_len'] ?? 480)));
        $message = mb_substr($message, 0, $maxLen);
        wp_schedule_single_event(time() + 1, SettingsPage::CRON_SEND, [[
            'to'      => $to,
            'message' => $message,
            'meta'    => $meta,
        ]]);
    }
 
    public function handle(array $payload): void {
        $to      = (string) ($payload['to']      ?? '');
        $message = (string) ($payload['message'] ?? '');
        $meta    = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
 
        if (!$to || !$message) {
            return;
        }
 
        // Build POST body from settings + payload
        $o = get_option(SettingsPage::OPT, []);
        $body = [
            'user_id'           => $o['user_id'] ?? '',
            'user_password'     => $o['user_password'] ?? '',
            'route_id'          => $o['route_id'] ?? '',
            'sms_type_id'       => $o['sms_type_id'] ?? '',
            'sms_sender'        => $o['sender'] ?? '',
            'sms_receiver'      => $to,
            'sms_text'          => $message,
            'sms_category_name' => $o['sms_category_name'] ?? '',
            'campaignType'      => $o['campaignType'] ?? 'T',
            'campaignId'        => $o['campaignId'] ?? '',
            'refOrderNo'        => $o['refOrderNo'] ?? '',
            'return_type'       => $o['return_type'] ?? 'String',
        ];
 
        // Remove keys with empty values (optional params)
        $body = array_filter($body, static function($v) {
            return $v !== '' && $v !== null;
        });
 
        // Send HTTP POST
        $endpoint = rtrim($o['api_base'] ?? '', '/');
        $resp = wp_remote_post($endpoint, [
            'timeout'     => 15,
            'headers'     => ['Accept' => 'application/json'],
            'body'        => $body,
        ]);
 
        // Interpret response
        $ok    = false;
        $code  = 0;
        $data  = null;
 
        if (!is_wp_error($resp)) {
            $code = (int) wp_remote_retrieve_response_code($resp);
            $body = wp_remote_retrieve_body($resp);
            $data = $body;
 
            // string response: first pipe-delimited value = returncode
            $parts = explode('|', trim($body));
            if ((int) $parts[0] === 1) {
                $ok = true;
            }
        } else {
            $data = $resp->get_error_message();
        }
 
        // Log to DB
        global $wpdb;
        $table = $wpdb->prefix . 'smsnet24_logs';
        $wpdb->insert(
            $table,
            [
                'time'          => current_time('mysql'),
                'mobile'        => $to,
                'message'       => $message,
                'event'         => $meta['event'] ?? '',
                'status'        => $ok ? 1 : 0,
                'response_code' => $code,
                'response_body' => wp_json_encode($data),
                'meta'          => wp_json_encode($meta),
            ],
            ['%s','%s','%s','%d','%d','%s','%s']
        );
 
        // Debug?
        if (!empty($o['debug'])) {
            Logger::error('sms_sent', [
                'to'      => $to,
                'ok'      => $ok,
                'code'    => $code,
                'payload' => $body,
                'response'=> $data,
            ]);
        }
    }
}
 