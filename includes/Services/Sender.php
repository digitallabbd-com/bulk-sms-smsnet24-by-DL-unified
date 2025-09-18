<?php
namespace BulkSMS\SMSNET24\Unified\Services;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Support\Logger;

final class Sender {
    public function register(): void {
        add_action(SettingsPage::CRON_SEND, [$this, 'handle'], 10, 1);
    }

    public function queue(string $to880, string $message, array $meta = []): void {
        if (!preg_match('/^8801[3-9]\d{8}$/', $to880)) return;

        $o = get_option(SettingsPage::OPT, []);
        $maxLen = max(120, (int) ($o['max_message_len'] ?? 480));
        $message = mb_substr($message, 0, $maxLen);

        wp_schedule_single_event(time() + 1, SettingsPage::CRON_SEND, [[
            'to' => $to880, 'message' => $message, 'meta' => $meta
        ]]);
    }

    public function handle(array $payload): void {
        $to   = (string) ($payload['to'] ?? '');
        $msg  = (string) ($payload['message'] ?? '');
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

        if (!$to || !$msg) return;

        $client = new SmsClient();
        $res = $client->send($to, $msg, $meta);
        if (empty($res['ok'])) {
            Logger::error('send_failed', ['to' => $to, 'meta' => $meta, 'res' => $res]);
        }
    }
}
