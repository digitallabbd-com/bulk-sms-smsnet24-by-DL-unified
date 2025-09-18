<?php
namespace BulkSMS\SMSNET24\Unified\Services;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Support\Logger;

final class SmsClient {
    private string $base;
    private string $key;
    private string $sender;
    private string $successKey;
    private bool $debug;

    public function __construct() {
        $o = get_option(SettingsPage::OPT, []);
        $this->base   = rtrim((string) ($o['api_base'] ?? ''), '/');
        $this->key    = (string) ($o['api_key'] ?? '');
        $this->sender = (string) ($o['sender'] ?? '');
        $this->successKey = (string) ($o['success_status'] ?? 'OK');
        $this->debug  = !empty($o['debug']);
    }

    public function send(string $to880, string $message, array $meta = []): array {
        if (!$this->base || !$this->key || !$this->sender) {
            return ['ok' => false, 'error' => 'missing_config'];
        }

        $url = $this->base . '/v1/sms/send';
        $body = [
            'to'      => $to880,
            'sender'  => $this->sender,
            'message' => $message,
        ] + $meta;

        $args = [
            'timeout' => 12,
            'redirection' => 3,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ];

        $resp = wp_safe_remote_post($url, $args);
        if (is_wp_error($resp)) {
            if ($this->debug) Logger::error('wp_error', ['err' => $resp->get_error_message()]);
            return ['ok' => false, 'error' => $resp->get_error_message()];
        }
        $code = (int) wp_remote_retrieve_response_code($resp);
        $data = json_decode(wp_remote_retrieve_body($resp), true) ?: [];

        if ($code >= 200 && $code < 300) {
            $ok = true;
            if ($this->successKey !== '') {
                $ok = (is_string($data) && str_contains($data, $this->successKey))
                  || (is_array($data) && (in_array($this->successKey, $data, true) || (isset($data['status']) && $data['status'] === $this->successKey)));
            }
            return ['ok' => $ok, 'data' => $data, 'code' => $code];
        }
        if ($this->debug) Logger::error('http_error', ['code' => $code, 'body' => $data]);
        return ['ok' => false, 'code' => $code, 'data' => $data];
    }
}
